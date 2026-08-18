#!/usr/bin/php -q
<?php
# One-off migration for the HelpNotes plugin's "To Do" feature.
#
# Scans bugnote text for the legacy "**TO DO**" (or "## TO DO", etc.) convention, tolerating one
# or more leading meta lines - each either "*Update of ~N:*" (the same-issue "Copy Note" flow) or
# "*Superseded by ~N.*" (this note has itself been replaced by a newer one) - followed by a blank
# line before the marker. Any OTHER leading meta line (e.g. "*Copied to #N.*") disqualifies the
# note even if "TO DO" appears later. A note whose skipped meta lines include a "*Superseded by*"
# is flagged has_archived=1 instead of the normal editable state, since it's a stale/replaced
# checklist, not the active one. Notes already flagged has_todo=1 are skipped entirely, so
# re-running this script is a no-op for them - safe to run repeatedly as more notes get authored
# using the old convention. For each remaining match: flags the note has_todo=1 (and has_archived=1
# if superseded), and runs the same HelpNotesPlugin::convert_done_markers() conversion the live
# "flag as To Do" checkbox uses, turning "- text **DONE**" bullet lines into "- ✓ text" (skipping
# "**NOT DONE**" negations).
#
# Usage:
#   php scripts/todo_migrate_legacy_notes.php                 Preview only, no writes.
#   php scripts/todo_migrate_legacy_notes.php --apply --user=eyal   Apply for real, logged in as "eyal".

global $g_bypass_headers;
$g_bypass_headers = 1;

require_once( dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'core.php' );
require_once( 'bugnote_api.php' );
require_once( dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'HelpNotes' . DIRECTORY_SEPARATOR . 'HelpNotes.php' );

if( php_sapi_name() != 'cli' ) {
	echo "todo_migrate_legacy_notes.php is not allowed to run through the webserver.\n";
	exit( 1 );
}

/** @var string[] $argv CLI arguments, provided by PHP when run via the command line. */
$t_apply = in_array( '--apply', $argv );
$t_username = null;
foreach ( $argv as $t_arg ) {
	if ( strpos( $t_arg, '--user=' ) === 0 ) {
		$t_username = substr( $t_arg, 7 );
	}
}

if ( $t_apply ) {
	if ( is_blank( $t_username ) || user_get_id_by_name( $t_username ) === false ) {
		echo "Pass --user=<username> (a valid Mantis account) to apply changes.\n";
		exit( 1 );
	}
	if ( !auth_attempt_script_login( $t_username ) ) {
		echo "Unable to login as '$t_username'.\n";
		exit( 1 );
	}
}

$t_bugnote_table = db_get_table( 'mantis_bugnote_table' );
$t_bugnote_text_table = db_get_table( 'mantis_bugnote_text_table' );
# Explicit basename: this script runs outside event_signal() dispatch, so plugin_table()'s default
# plugin_get_current() would resolve to nothing (see HelpNotes.php's set_bugnote_todo_flag() for
# the same issue hit via bugnote_add.php).
$t_todo_table = plugin_table( 'bugnote_todo', 'HelpNotes' );

// Excludes notes already flagged has_todo=1, so re-running the script is a no-op for them
// (idempotent - a second run leaves already-flagged notes alone).
$t_query = "SELECT bn.id AS bugnote_id, bn.bug_id, bnt.note
	FROM $t_bugnote_table bn
	JOIN $t_bugnote_text_table bnt ON bn.bugnote_text_id = bnt.id
	LEFT JOIN $t_todo_table td ON td.bugnote_id = bn.id
	WHERE td.has_todo IS NULL OR td.has_todo = 0";
$t_result = db_query_bound( $t_query, array() );

$t_todo_marker_regex = '/^\s*(?:#{1,6}\s*|\*\*)\s*TO[\s-]?DO\b/i';
$t_update_of_regex = '/^\*Update of ~\d+:?\*$/';
$t_superseded_by_regex = '/^\*Superseded by ~\d+\.?\*$/';

/**
 * Returns array('marker_line' => ..., 'is_archived' => bool) if $p_text's TO DO marker is
 * recognized, or null if not. The marker may be on the literal first line, or preceded by one or
 * more leading meta lines - each either "*Update of ~N:*" or "*Superseded by ~N.*" - followed by
 * exactly one blank line. Any OTHER leading meta line (e.g. "*Copied to #N.*") disqualifies the
 * note even if "TO DO" appears later. is_archived is true if any of the skipped meta lines was a
 * "*Superseded by*" - that note is no longer the active/current checklist.
 */
function todo_migrate_find_marker_line( $p_text ) {
	global $t_todo_marker_regex, $t_update_of_regex, $t_superseded_by_regex;
	$t_lines = explode( "\n", str_replace( "\r\n", "\n", $p_text ) );
	$i = 0;
	$t_skipped_meta = false;
	$t_is_archived = false;
	while ( $i < count( $t_lines ) ) {
		$t_trimmed = trim( $t_lines[$i] );
		if ( preg_match( $t_update_of_regex, $t_trimmed ) ) {
			$i++;
			$t_skipped_meta = true;
		} else if ( preg_match( $t_superseded_by_regex, $t_trimmed ) ) {
			$i++;
			$t_skipped_meta = true;
			$t_is_archived = true;
		} else {
			break;
		}
	}
	if ( $t_skipped_meta ) {
		if ( $i < count( $t_lines ) && trim( $t_lines[$i] ) === '' ) {
			$i++;
		} else {
			return null;
		}
	}
	if ( !isset( $t_lines[$i] ) || !preg_match( $t_todo_marker_regex, $t_lines[$i] ) ) {
		return null;
	}
	return array( 'marker_line' => trim( $t_lines[$i] ), 'is_archived' => $t_is_archived );
}

$t_candidates = array();
while ( $t_row = db_fetch_array( $t_result ) ) {
	$t_text = $t_row['note'];
	$t_match = todo_migrate_find_marker_line( $t_text );
	if ( $t_match === null ) {
		continue;
	}
	$t_candidates[] = array(
		'bugnote_id' => $t_row['bugnote_id'],
		'bug_id' => $t_row['bug_id'],
		'marker_line' => $t_match['marker_line'],
		'is_archived' => $t_match['is_archived'],
		'old_text' => $t_text,
		'new_text' => HelpNotesPlugin::convert_done_markers( $t_text ),
	);
}

function todo_migrate_print_line_diff( $p_old, $p_new ) {
	$t_old_lines = explode( "\n", str_replace( "\r\n", "\n", $p_old ) );
	$t_new_lines = explode( "\n", str_replace( "\r\n", "\n", $p_new ) );
	$t_count = max( count( $t_old_lines ), count( $t_new_lines ) );
	for ( $i = 0; $i < $t_count; $i++ ) {
		$t_old_line = isset( $t_old_lines[$i] ) ? $t_old_lines[$i] : '';
		$t_new_line = isset( $t_new_lines[$i] ) ? $t_new_lines[$i] : '';
		if ( $t_old_line !== $t_new_line ) {
			echo "    - $t_old_line\n";
			echo "    + $t_new_line\n";
		}
	}
}

echo count( $t_candidates ) . " candidate note(s) found (matches a legacy TO DO marker, allowing leading \"*Update of ~N:*\" / \"*Superseded by ~N.*\" lines).\n\n";

$t_conversion_count = 0;
$t_archived_count = 0;
foreach ( $t_candidates as $t_candidate ) {
	echo "----- bugnote #{$t_candidate['bugnote_id']} (issue #{$t_candidate['bug_id']})" . ( $t_candidate['is_archived'] ? " [Archived]" : "" ) . " -----\n";
	echo "Marker line: {$t_candidate['marker_line']}\n";
	if ( $t_candidate['is_archived'] ) {
		$t_archived_count++;
	}
	if ( $t_candidate['old_text'] !== $t_candidate['new_text'] ) {
		$t_conversion_count++;
		echo "  DONE marker conversion:\n";
		todo_migrate_print_line_diff( $t_candidate['old_text'], $t_candidate['new_text'] );
	} else {
		echo "  (no **DONE** bullet lines to convert)\n";
	}
	echo "\n";
}

echo "Summary: " . count( $t_candidates ) . " note(s) would be flagged has_todo=1 ($t_archived_count of them as Archived, superseded), $t_conversion_count of them have DONE markers to convert.\n\n";

if ( !$t_apply ) {
	echo "Dry run only - no changes written. Re-run with --apply --user=<username> to write the above.\n";
	exit( 0 );
}

echo "Applying...\n";
foreach ( $t_candidates as $t_candidate ) {
	$t_query = "REPLACE INTO $t_todo_table (bugnote_id, has_todo, has_archived) values(" . db_param() . "," . db_param() . "," . db_param() . ")";
	db_query_bound( $t_query, array( $t_candidate['bugnote_id'], 1, $t_candidate['is_archived'] ? 1 : 0 ) );

	if ( $t_candidate['old_text'] !== $t_candidate['new_text'] ) {
		bugnote_set_text( $t_candidate['bugnote_id'], $t_candidate['new_text'] );
	}
}
echo "Done - flagged " . count( $t_candidates ) . " note(s) as To Do ($t_archived_count as Archived), converted $t_conversion_count.\n";

exit( 0 );
