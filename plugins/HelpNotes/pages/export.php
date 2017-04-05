<?php
# MantisBT - a php based bugtracking system
# Copyright (C) 2002 - 2011  MantisBT Team - mantisbt-dev@lists.sourceforge.net
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

require_once( 'core.php' );
require_once( 'csv_api.php' );

auth_ensure_user_authenticated( );
helper_begin_long_process( );

$t_page_number = 1;
$t_per_page = -1;
$t_bug_count = null;
$t_page_count = null;

# Get bug rows according to the current filter
$t_issues = filter_get_bug_rows( $t_page_number, $t_per_page, $t_page_count, $t_bug_count );
if( $t_issues === false ) {
	$t_issues = array();
}
$t_columns = array_keys( getClassProperties('BugData', 'protected') );

$t_user_id = auth_get_current_user_id();
$t_bugnote_order = current_user_get_pref( 'bugnote_order' );

$t_filename = "help_notes.csv";

# Send headers to browser to activate mime loading
# Make sure that IE can download the attachments under https.
header( 'Pragma: public' );
header( 'Content-Type: text/csv; name=' . $t_filename );
header( 'Content-Disposition: attachment; filename="' . $t_filename . '"' );

$file = fopen("php://output","w");

fputcsv($file, array('Issue','Note','Issue Summary','Created','Edited','Note Help'));

# export the rows
foreach( $t_issues as $t_issue ) {
	$issue_id = $t_issue->id;
	$summary = $t_issue->summary;
	$t_bugnotes = bugnote_get_all_visible_bugnotes( $issue_id, $t_bugnote_order, 0, $t_user_id );
	$t_bugnotes = event_signal( 'EVENT_HELPNOTES_POPULATE', array( $f_bug_id, $t_bugnotes ) );
	foreach( $t_bugnotes as $t_bugnote ) {
		if ($t_bugnote->has_help){
			$line = array();
			$line[] = $issue_id;
			$line[] = $t_bugnote->id;
			$line[] = $summary;
			$line[] = csv_format_date_submitted($t_bugnote->date_submitted);
			$line[] = csv_format_date_submitted($t_bugnote->last_modified);
			if (preg_match_all('/\{\{(.+)\}\}/sU', $t_bugnote->note, $matches))
				$line[] = implode("\n", $matches[1]);
			else
				$line[] = $t_bugnote->note;
			fputcsv($file, $line);
		}
	}
}
fclose($file);
