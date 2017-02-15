<?php
/**
 * This include file prints out a unique list of users who have added bugnotes to the bug
 * $f_bug_id must be set and be set to the bug id
 *
 * @package MantisBT
 * @copyright Copyright (C) 2012  Eyal Azulay - eyal@get-it-write.com
 * @link http://www.get-it-write.com
 */

/**
 * Requires bugnote API
 */
	require_once( 'current_user_api.php' );

	# grab the user id currently logged in
	$t_user_id = auth_get_current_user_id();

	#precache access levels
	if ( isset( $g_project_override ) ) {
		access_cache_matrix_project( $g_project_override );
	} else {
		access_cache_matrix_project( helper_get_current_project() );
	}

	# get the bugnote data
	$t_bugnote_order = current_user_get_pref( 'bugnote_order' );
	$t_bugnotes = bugnote_get_all_visible_bugnotes( $f_bug_id, $t_bugnote_order, 0, $t_user_id );

	#precache users
	$t_bugnote_users = array();
	foreach($t_bugnotes as $t_bugnote)
		$t_bugnote_users[] = $t_bugnote->reporter_id;
	
	#make the array unique
	$t_bugnote_users = array_unique($t_bugnote_users);

	$num_notes = count( $t_bugnotes );

	$i = 0;
	foreach($t_bugnote_users as $t_bugnote_user) {
		if (++$i > 1)
			echo ', ';
		echo print_user( $t_bugnote_user );
	} # end for loop
?>