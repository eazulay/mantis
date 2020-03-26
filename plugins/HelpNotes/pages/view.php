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

	auth_ensure_user_authenticated( );
	$t_css_url = config_get( 'css_include_file' );

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<link rel="stylesheet" type="text/css" href="<?php echo string_sanitize_url( helper_mantis_url( $t_css_url ), true ); ?>" />
	<script type="text/javascript"><!--
		if (document.layers){ document.write("<style>td{padding:0px;}<\/style>"); }
	// --></script>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
	<meta http-equiv="Pragma" content="no-cache" />
	<meta http-equiv="Cache-Control" content="no-cache" />
	<meta http-equiv="Pragma-directive" content="no-cache" />
	<meta http-equiv="Cache-Directive" content="no-cache" />
	<meta name="robots" content="noindex,follow" />
	<link rel="shortcut icon" href="/mantis/images/favicon.ico" type="image/x-icon" />
	<link rel="search" type="application/opensearchdescription+xml" title="MantisBT: Text Search" href="/mantis/browser_search_plugin.php?type=text" />	<link rel="search" type="application/opensearchdescription+xml" title="MantisBT: Issue Id" href="https://get-it-write.com/mantis/browser_search_plugin.php?type=id" />
	<title>View Help Notes - Get-It-Write Tracking System</title>
<script type="text/javascript" src="/mantis/javascript/min/common.js"></script>
<script type="text/javascript">var loading_lang = "Loading...";</script><script type="text/javascript" src="/mantis/javascript/min/ajax.js"></script>
<script type="text/javascript">
function selectElement(element) {
    if (window.getSelection) {
        var sel = window.getSelection();
        sel.removeAllRanges();
        var range = document.createRange();
        range.selectNodeContents(element);
        sel.addRange(range);
    } else if (document.selection) {
        var textRange = document.body.createTextRange();
        textRange.moveToElementText(element);
        textRange.select();
    }
}
</script>
<?php
	$t_page_number = 1;
	$t_per_page = -1;
	$t_bug_count = null;
	$t_page_count = null;

	# Get bug rows according to the current filter
	$t_issues = filter_get_bug_rows( $t_page_number, $t_per_page, $t_page_count, $t_bug_count );
	if( $t_issues === false )
		$t_issues = array();
	$t_columns = array_keys( getClassProperties('BugData', 'protected') );
	$t_user_id = auth_get_current_user_id();
	$t_bugnote_order = current_user_get_pref( 'bugnote_order' );

	html_page_top2();
	print_recently_visited();

	$t_filter = current_user_get_bug_filter();
	# NOTE: this check might be better placed in current_user_get_bug_filter()
	if ( $t_filter === false ) {
		$t_filter = filter_get_default();
	}
	list( $t_sort, ) = explode( ',', $t_filter['sort'] );
	list( $t_dir, ) = explode( ',', $t_filter['dir'] );

	$t_checkboxes_exist = false;

	$t_icon_path = config_get( 'icon_path' );

	# Improve performance by caching category data in one pass
	if ( helper_get_current_project() > 0 ) {
		category_get_all_rows( helper_get_current_project() );
	} else {
		$t_categories = array();
		foreach ($rows as $t_row) {
			$t_categories[] = $t_row->category_id;
		}
		category_cache_array_rows( array_unique( $t_categories ) );
	}
	$t_columns = helper_get_columns_to_view( COLUMNS_TARGET_VIEW_PAGE );

	$col_count = count( $t_columns );

	$t_filter_position = config_get( 'filter_position' );
	if ( ( $t_filter_position & FILTER_POSITION_TOP ) == FILTER_POSITION_TOP ) {
		filter_draw_selection_area( $f_page_number );
	}

	# Help Notes start
	$date_format = config_get('normal_date_format');
	$date_format_break_pos = strpos($date_format, '\a\t');
	if ($date_format_break_pos !== FALSE){
		$date_format = substr($date_format, 0, $date_format_break_pos) . '\<\b\r\>' . substr($date_format, $date_format_break_pos);
	}
	?>
<br>
<table id="buglist" class="width100" cellspacing="1">
	<tr>
		<td class="form-title" colspan="6"><span class="floatleft">View Help Notes</span></td>
	</tr>
	<tr class='row-category'>
		<th class="form-title">Issue</th>
		<th class="form-title">Note</th>
		<th class="form-title" style="min-width:15%;">Issue Summary</th>
		<th class="form-title">Created</th>
		<th class="form-title">Edited</th>
		<th class="form-title">Help</th>
	</tr>
<?php
	foreach( $t_issues as $t_issue ) {
		$issue_id = $t_issue->id;
		$summary = $t_issue->summary;
		$t_bugnotes = bugnote_get_all_visible_bugnotes( $issue_id, $t_bugnote_order, 0, $t_user_id );
		$t_bugnotes = event_signal( 'EVENT_HELPNOTES_POPULATE', array( $f_bug_id, $t_bugnotes ) );
		foreach( $t_bugnotes as $t_bugnote ) {
			if ($t_bugnote->has_help){
				echo "	<tr valign='top'>
		<td>".string_get_bug_view_link($issue_id, $t_user_id)."</td>
		<td>".string_get_bugnote_view_link($issue_id, $t_bugnote->id, $t_user_id)."</td>
		<td>".string_display_links($summary)."</td>
		<td style='white-space: nowrap'>".date($date_format, $t_bugnote->date_submitted)."</td>
		<td style='white-space: nowrap'>".date($date_format, $t_bugnote->last_modified)."</td>
		<td ondblclick='selectElement(this)'>";
				$note_content = $t_bugnote->note;
				if (preg_match_all('/\{\{(.+)\}\}/sU', $note_content, $matches))
					$note_content =  implode("</p><p>", $matches[1]);
				$note_content = string_display_links($note_content);
				$note_content = str_replace("</p><p>", "</p>\r\n<p>", $note_content);
				$note_content = str_replace("<br />\r\n-", "</p>\r\n<p style='margin:0;'>-", $note_content);
				$note_content = str_replace("<br />\r\n<br />\r\n", "</p>\r\n<p>", $note_content);
				echo '<p style="margin-top:0;">'.$note_content."</p></td>
	</tr>\n";
			}
		}
	}
	echo "</table>";

	if ( ( $t_filter_position & FILTER_POSITION_BOTTOM ) == FILTER_POSITION_BOTTOM ) {
		filter_draw_selection_area( $f_page_number );
	}
	html_page_bottom();
