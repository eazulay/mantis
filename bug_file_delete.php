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
	 * Delete a file from a bug and then view the bug
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

	require_once( 'file_api.php' );

	form_security_validate( 'bug_file_delete' );

	$f_file_id = gpc_get_int( 'file_id' );

	$file_id_array = array();

	// check if $f_file_id is an array
	if( is_array( $f_file_id ) ) {
		$file_id_array = $f_file_id;
	} else {
		$file_id_array[] = $f_file_id;
	}

	$first_file_id = $file_id_array[0];

	$t_bug_id = file_get_field( $first_file_id, 'bug_id' );

	$t_bug = bug_get( $t_bug_id, true );
	if( $t_bug->project_id != helper_get_current_project() ) {
		# in case the current project is not the same project of the bug we are viewing...
		# ... override the current project. This to avoid problems with categories and handlers lists etc.
		$g_project_override = $t_bug->project_id;
	}

	access_ensure_bug_level( config_get( 'update_bug_threshold' ), $t_bug_id );

	$filenames = array();
	for ( $i = 0; $i < count( $file_id_array ); $i++ ) {
		$filenames[] = file_get_field( $file_id_array[$i], 'filename' );
	}

	helper_ensure_confirmed(
		lang_get( ( count( $file_id_array ) == 1 ? 'delete_attachment_sure_msg' : 'delete_attachments_sure_msg' ) ) . '<br />' . implode( '<br />', $filenames ),
		lang_get( 'delete_attachment_button' ) );

	for ( $i = 0; $i < count( $file_id_array ); $i++ ) {
		file_delete( $file_id_array[$i], 'bug' );
	}

	form_security_purge( 'bug_file_delete' );

	print_header_redirect_view( $t_bug_id );
