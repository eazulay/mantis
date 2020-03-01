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
	 * This is the first page a user sees when they login to the bugtracker
	 * News is displayed which can notify users of any important changes
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

	require_once( 'current_user_api.php' );
	require_once( 'news_api.php' );
	require_once( 'date_api.php' );
	require_once( 'print_api.php' );
	require_once( 'rss_api.php' );

	access_ensure_project_level( VIEWER );

	$f_offset = gpc_get_int( 'offset', 0 );

	$t_project_id = helper_get_current_project();

	$t_project_path = helper_get_current_project_trace();
	$t_enable_raise = (count($t_project_path) >= 2);

/*	$t_rss_enabled = config_get( 'rss_enabled' );

	if ( OFF != $t_rss_enabled && news_is_enabled() ) {
		$t_rss_link = rss_get_news_feed_url( $t_project_id );
		html_set_rss_link( $t_rss_link );
	}
*/
	html_page_top( lang_get( 'main_link' ) );

	if ( !current_user_is_anonymous() ) {
		$t_current_user_id = auth_get_current_user_id();
		$t_hide_status = config_get( 'bug_resolved_status_threshold' );

		echo '<div class="main-page-content">
	<h4>I want to:</h4>
	<ul>
		<li>';
		if ($t_enable_raise)
			print_link('bug_report_page.php', 'Raise a New Issue');
		else
			echo 'Raise a New Issue';
		echo '</li>
		<li>Add a Note to: ';
		jump_issue_form();
		echo '<form id="filters_form)closed" method="post" name="filters_closed" action="view_all_set.php?f=3&type=1">
		or find by Keyword: <input type="text" size="16" name="', FILTER_PROPERTY_FREE_TEXT, '" />
		<input type="submit" name="switch_to_query_button" class="button-small" value="Search"></form></li>
		<li>View Issues:
			<ul><li>';
		print_link('view_all_set.php?type=1&show_status='.FEEDBACK.'&sort_0=priority&dir_0=DESC', 'Info Required');
		echo '</li>
		<li>';
		print_link('view_all_set.php?type=1&show_status='.RESOLVED.'sort_0=priority&dir_0=DESC', 'Resolved');
		echo '</li>
		<li>';
		print_link('view_all_set.php?type=1&hide_status='.$t_hide_status.'&sort_0=status&dir_0=ASC&sort_1=priority&dir_1=DESC', 'All Open');
		echo '</li>
		<li>';
		print_link('view_all_set.php?type=1&sort_0=status&dir_0=ASC&sort_1=priority&dir_1=DESC', 'All');
		echo '</li>
			</li>
		</ul>
	</ul>
</div>';
		echo '<div class="quick-summary-right">';
		echo lang_get( 'last_visit' ) . ': ';
		echo date( config_get( 'normal_date_format' ), current_user_get_field( 'last_visit' ) );
		echo '</div>';

/*		if (access_has_project_level( DEVELOPER )){
			echo '<div class="quick-summary-left">';
			echo lang_get( 'open_and_assigned_to_me' ) . ': ';
			print_link( "view_all_set.php?type=1&handler_id=$t_current_user_id&hide_status=$t_hide_status", current_user_get_assigned_open_bug_count(), false, 'subtle' );
			echo '</div>';
		}
		echo '<div class="quick-summary-right">';
		echo lang_get( 'open_and_reported_to_me' ) . ': ';
		print_link( "view_all_set.php?type=1&reporter_id=$t_current_user_id&hide_status=$t_hide_status", current_user_get_reported_open_bug_count(), false, 'subtle' );
		echo '</div>';
*/
	}

	echo '<br /><br />';

	if ( news_is_enabled() ) {
		echo '<br />';

		$t_news_rows = news_get_limited_rows( $f_offset, $t_project_id );
		$t_news_count = count( $t_news_rows );

		# Loop through results
		for ( $i = 0; $i < $t_news_count; $i++ ) {
			$t_row = $t_news_rows[$i];

			# only show VS_PRIVATE posts to configured threshold and above
			if ( ( VS_PRIVATE == $t_row[ 'view_state' ] ) &&
				 !access_has_project_level( config_get( 'private_news_threshold' ) ) ) {
				continue;
			}

			print_news_entry_from_row( $t_row );
			echo '<br />';
		}  # end for loop

		echo '<div align="center">';

		print_bracket_link( 'news_list_page.php', lang_get( 'archives' ) );
		$t_news_view_limit = config_get( 'news_view_limit' );
		$f_offset_next = $f_offset + $t_news_view_limit;
		$f_offset_prev = $f_offset - $t_news_view_limit;

		if ( $f_offset_prev >= 0) {
			print_bracket_link( 'main_page.php?offset=' . $f_offset_prev, lang_get( 'newer_news_link' ) );
		}

		if ( $t_news_count == $t_news_view_limit ) {
			print_bracket_link( 'main_page.php?offset=' . $f_offset_next, lang_get( 'older_news_link' ) );
		}
/*
		if ( OFF != $t_rss_enabled ) {
			print_bracket_link( $t_rss_link, lang_get( 'rss' ) );
		}
*/
		echo '</div>';
	}

	html_page_bottom();
