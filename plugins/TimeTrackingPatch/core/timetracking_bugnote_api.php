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
 * BugNote API
 * @package CoreAPI
 * @subpackage BugnoteAPI
 * @copyright Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
 * @copyright Copyright (C) 2002 - 2010  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 */

/**
 * requires current_user_api
 */
require_once( 'current_user_api.php' );
/**
 * requires email_api
 */
require_once( 'email_api.php' );
/**
 * requires history_api
 */
require_once( 'history_api.php' );
/**
 * requires bug_api
 */
require_once( 'bug_api.php' );

/**
 * Returns an array of bugnote stats
 * @param int $p_bug_id bug id
 * @param string $p_from Starting date (yyyy-mm-dd) inclusive, if blank, then ignored.
 * @param string $p_to Ending date (yyyy-mm-dd) inclusive, if blank, then ignored.
 * @return array array of bugnote stats
 * @access public
 */
function timetracking_bugnote_stats_get_events_array( $p_bug_id, $p_from, $p_to ) {
	$c_bug_id = db_prepare_int( $p_bug_id );
	
	//Modified to fix the date range issue on 03-12-10
	$c_to = strtotime( $p_to." 23:59:59", SECONDS_PER_DAY - 1); // @23:59:59
	$c_from = strtotime( $p_from." 00:00:00");
    //Modified 
	$t_user_table = db_get_table( 'mantis_user_table' );
	$t_bugnote_table = db_get_table( 'mantis_bugnote_table' );

	if( !is_blank( $c_from ) ) {
		$t_from_where = " AND bn.date_submitted >= $c_from ";
	} else {
		$t_from_where = '';
	}

	if( !is_blank( $c_to ) ) {
		$t_to_where = " AND bn.date_submitted <= $c_to ";
	} else {
		$t_to_where = '';
	}

	$t_results = array();

	$query = "SELECT username, SUM(time_tracking) AS sum_time_tracking
				FROM $t_user_table u, $t_bugnote_table bn
				WHERE u.id = bn.reporter_id AND
				bn.bug_id = '$c_bug_id'
				$t_from_where $t_to_where
			GROUP BY u.id, u.username";

	$result = db_query( $query );

	while( $row = db_fetch_array( $result ) ) {
		$t_results[] = $row;
	}

	return $t_results;
}

/**
 * Returns an array of bugnote stats
 * @param int $p_project_id project id
 * @param string $p_from Starting date (yyyy-mm-dd) inclusive, if blank, then ignored.
 * @param string $p_to Ending date (yyyy-mm-dd) inclusive, if blank, then ignored.
 * @param int $p_cost cost
 * @return array array of bugnote stats
 * @access public
 */
function timetracking_bugnote_stats_get_project_array( $p_project_id, $p_from, $p_to, $p_cost, $p_bugnote_price) {
	$c_project_id = db_prepare_int( $p_project_id );
    //Modified on 02-12-10
	$c_to = strtotime( $p_to." 23:59:59", SECONDS_PER_DAY - 1); // @23:59:59
	$c_from = strtotime( $p_from." 00:00:00");
    //Modified	
	if ( $c_to === false || $c_from === false ) {
		error_parameters( array( $p_form, $p_to ) );
		trigger_error( ERROR_GENERIC, ERROR );
	}

	$c_cost = db_prepare_double( $p_cost );

	$t_bug_table = db_get_table( 'mantis_bug_table' );
	$t_user_table = db_get_table( 'mantis_user_table' );
	$t_bugnote_table = db_get_table( 'mantis_bugnote_table' );
	$t_project_user_list_table = db_get_table( 'mantis_project_user_list_table' );

	if( !is_blank( $c_from ) ) {
		$t_from_where = " AND bn.date_submitted >= $c_from";
	} else {
		$t_from_where = '';
	}

	if( !is_blank( $c_to ) ) {
		$t_to_where = " AND bn.date_submitted <= $c_to";
	} else {
		$t_to_where = '';
	}
	
    if( $p_bugnote_price && ALL_PROJECTS != $c_project_id ) {
		$t_user_price_where = " AND pu.project_id = '$c_project_id'";
	} else {
		$t_user_price_where = '';
	}

	if( ALL_PROJECTS != $c_project_id ) {
		$t_project_where = " AND b.project_id = '$c_project_id' AND bn.bug_id = b.id ";
	} else {
		$t_project_where = '';
	}

	$t_results = array();

	$query = "SELECT username, realname, user_price, summary, bn.bug_id, SUM(time_tracking) AS sum_time_tracking
			FROM $t_user_table u LEFT JOIN $t_project_user_list_table pu ON (pu.user_id = u.id $t_user_price_where), $t_bugnote_table bn, $t_bug_table b 
			WHERE u.id = bn.reporter_id AND bn.time_tracking != 0 AND bn.bug_id = b.id   
			$t_project_where $t_from_where $t_to_where 
			GROUP BY bn.bug_id, u.id, u.username, b.summary
			ORDER BY bn.bug_id";
	
	$result = db_query( $query );

	$t_cost_min = $c_cost / 60;

	while( $row = db_fetch_array( $result ) ) {
		$t_total_cost = $t_cost_min * $row['sum_time_tracking'];
		$row['cost'] = $t_total_cost;
		$t_results[] = $row;
	}

	return $t_results;
}