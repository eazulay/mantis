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
 * This include file prints out the list of users monitoring the current
 * bug.	$f_bug_id must be set and be set to the bug id
 *
 * @package MantisBT
 * @copyright Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
 * @copyright Copyright (C) 2002 - 2011  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 */

if ( access_has_bug_level( config_get( 'show_monitor_list_threshold' ), $f_bug_id ) ) {
	
	$t_users = bug_get_monitors( $f_bug_id );
	$num_users = sizeof ( $t_users );

	if ( 0 == $num_users ) {
		//echo lang_get( 'no_users_monitoring_bug' );
	} else {
		$t_can_delete_others = access_has_bug_level( config_get( 'monitor_delete_others_bug_threshold' ), $f_bug_id );
		$print_i = 0;
		for ( $i = 0; $i < $num_users; $i++ ) {
			if ($t_users[$i] == $tpl_bug->reporter_id || $t_users[$i] == $tpl_bug->handler_id || in_array($t_users[$i], $t_bugnote_users))
				continue;
			echo ($print_i > 0) ? ', ' : '';
			echo print_user( $t_users[$i] );
			if ( $t_can_delete_others ) {
				echo ' [<a class="small" href="' . helper_mantis_url( 'bug_monitor_delete.php' ) . '?bug_id=' . $f_bug_id . '&user_id=' . $t_users[$i] . form_security_param( 'bug_monitor_delete' ) . '">' . lang_get( 'delete_link' ) . '</a>]';
			}
			$print_i++;
		}
		if ($print_i > 0)
			echo '<br />';
	}

	if ( access_has_bug_level( config_get( 'monitor_add_others_bug_threshold' ), $f_bug_id ) ) {
		//echo lang_get( 'username' );
?>
	<form method="get" action="bug_monitor_add.php">
	<?php echo form_security_field( 'bug_monitor_add' ) ?>
		<input type="hidden" name="bug_id" value="<?php echo (integer)$f_bug_id; ?>" />
		<input type="submit" class="button-small" value="<?php echo lang_get( 'add_user_to_monitor' ); ?>:" />
		<input type="text" class="small" name="username" style="width:26%;" placeholder="user" />
	</form>
<?php }
} ?>
