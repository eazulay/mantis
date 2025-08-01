<?php
# MantisBT - a php based bugtracking system

# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.

	/**
	 * Insert the bugnote into the database then redirect to the bug page
	 *
	 * @package MantisBT
	 * @copyright Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
	 * @copyright Copyright (C) 2002 - 2011  MantisBT Team - mantisbt-dev@lists.sourceforge.net
	 * @link http://www.mantisbt.org
	 */
	 /**
	  * MantisBT Core API's
	  */
	require_once( 'core.php' );

	require_once( 'bug_api.php' );
	require_once( 'bugnote_api.php' );

	form_security_validate( 'bugnote_add' );

	$f_bug_id			= gpc_get_int( 'bug_id' );
	$f_return_to_bug_id = gpc_get_int( 'return_to_bug_id', $f_bug_id );
	$f_source_bugnote_id= gpc_get_int( 'source_bugnote_id', 0 );
	$f_private			= gpc_get_bool( 'private' );
	$f_time_tracking	= gpc_get_string( 'time_tracking', '0:00' );
	$f_bugnote_text		= trim( gpc_get_string( 'bugnote_text', '' ) );

	$t_bug = bug_get( $f_bug_id, true );
	if( $t_bug->project_id != helper_get_current_project() ) {
		# in case the current project is not the same project of the bug we are viewing...
		# ... override the current project. This to avoid problems with categories and handlers lists etc.
		$g_project_override = $t_bug->project_id;
	}

	if ( bug_is_readonly( $f_bug_id ) ) {
		error_parameters( $f_bug_id );
		trigger_error( ERROR_BUG_READ_ONLY_ACTION_DENIED, ERROR );
	}

	access_ensure_bug_level( config_get( 'add_bugnote_threshold' ), $f_bug_id );

	// We always set the note time to BUGNOTE, and the API will overwrite it with TIME_TRACKING
	// if $f_time_tracking is not 0 and the time tracking feature is enabled.
	$t_bugnote_id = bugnote_add( $f_bug_id, $f_bugnote_text, $f_time_tracking, $f_private, BUGNOTE );
    if ( !$t_bugnote_id ) {
        error_parameters( lang_get( 'bugnote' ) );
        trigger_error( ERROR_EMPTY_FIELD, ERROR );
    }

	# Plugin integration
	event_signal( 'EVENT_BUGNOTE_ADD', array( $f_bug_id, $t_bugnote_id ) );

	form_security_purge( 'bugnote_add' );

	if ( $f_source_bugnote_id > 0 ) {
		$source_note_text = bugnote_get_text( $f_source_bugnote_id );
		$first_note_line = strtok( $source_note_text, "\r\n" );
		$first_line_len = strlen( $first_note_line );
		$note_starts_with_meta = $first_line_len > 2 && $first_note_line[0] == '*' && $first_note_line[1] != '*' && $first_note_line[$first_line_len - 1] == '*';
		bugnote_set_text( $f_source_bugnote_id, "*Duplicated to #" . $f_bug_id . ".*\n" . ($note_starts_with_meta ? "" : "\n") . $source_note_text );
		$t_url = string_get_bug_view_url( $f_return_to_bug_id, auth_get_current_user_id() );
		print_successful_redirect( $t_url . "#c" . $f_source_bugnote_id );
	} else {
		print_successful_redirect_to_bug( $f_bug_id );
	}
