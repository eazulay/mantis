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
 * @package CoreAPI
 * @subpackage ProjectAPI
 * @copyright Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
 * @copyright Copyright (C) 2002 - 2010  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 */




# ===================================
# Data Modification
# ===================================
# update user development price with the specified access level to a project
function timetracking_project_change_dev_price( $p_project_id, $p_price, $p_user_id, $p_access_level ) {
	$t_project_user_list_table = db_get_table( 'mantis_project_user_list_table' );

	$c_project_id = db_prepare_int( $p_project_id );
	$c_price = db_prepare_string( $p_price );
	$c_access_level = db_prepare_int( $p_access_level );

	if( DEFAULT_ACCESS_LEVEL == $p_access_level ) {

		# Default access level for this user
		$c_access_level = db_prepare_int( user_get_access_level( $p_user_id ) );
	}

	$query = "UPDATE
				  $t_project_user_list_table
				  SET  user_price = " . db_param() ." WHERE user_id = " . db_param() . " AND project_id = " . db_param() ;

	db_query_bound( $query, Array( $c_price, $p_user_id, $c_project_id  ) );

	# db_query errors on failure so:
	return true;
}
