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
	 * @package MantisBT
	 * @copyright Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
	 * @copyright Copyright (C) 2002 - 2011  MantisBT Team - mantisbt-dev@lists.sourceforge.net
	 * @link http://www.mantisbt.org
	 */

?>
<?php if ( ( !bug_is_readonly( $f_bug_id ) ) &&
		( access_has_bug_level( config_get( 'add_bugnote_threshold' ), $f_bug_id ) ) ) { ?>
<?php # Bugnote Add Form BEGIN ?>
<a name="addbugnote"></a> <br />

<?php
	collapse_open( 'bugnote_add' );
?>
<form name="bugnoteadd" method="post" action="bugnote_add.php">
<?php echo form_security_field( 'bugnote_add' ) ?>
<input type="hidden" name="bug_id" value="<?php echo $f_bug_id ?>" />
<table class="width100" cellspacing="0">
<tr class="header">
	<td class="form-title" colspan="2">
<?php
	collapse_icon( 'bugnote_add' );
	echo lang_get( 'add_bugnote_title' ) ?>
	</td>
</tr>
<tr class="row-2">
	<td class="category" width="25%">
		Write a <?php echo lang_get( 'bugnote' ) ?><br>
<br>
<span style="font-weight:normal;">
OR<br>
<br>
To email someone Mantis does not notify, click [Open Email]. Mantis adds your email as a Note. A recipient Mantis user can click [Reply All] to add to this Issue.</span>
	</td>
	<td width="75%">
		<textarea name="bugnote_text" cols="80" rows="10"></textarea>
	</td>
</tr>
<?php if ( access_has_bug_level( config_get( 'private_bugnote_threshold' ), $f_bug_id ) ) { ?>
<tr class="row-1">
	<td class="category">
		<?php echo lang_get( 'view_status' ) ?>
	</td>
	<td>
<?php
		$t_default_bugnote_view_status = config_get( 'default_bugnote_view_status' );
		if ( access_has_bug_level( config_get( 'set_view_status_threshold' ), $f_bug_id ) ) {
?>
			<input type="checkbox" id="private" name="private" <?php check_checked( $t_default_bugnote_view_status, VS_PRIVATE ); ?> />
			<label for="private"><?php echo lang_get( 'private' ); ?></label>
<?php
		} else {
			echo get_enum_element( 'project_view_state', $t_default_bugnote_view_status );
		}
?>
	</td>
</tr>
<?php } ?>

<?php if ( config_get('time_tracking_enabled') ) { ?>
<?php	if ( access_has_bug_level( config_get( 'time_tracking_edit_threshold' ), $f_bug_id ) ) { ?>
<tr <?php echo helper_alternate_class() ?>>
	<td class="category">
		<?php echo lang_get( 'time_tracking' ) ?> (HH:MM)
	</td>
	<td>
		<?php if ( config_get( 'time_tracking_stopwatch' ) && config_get( 'use_javascript' ) ) { ?>
		<script language="javascript">
			var time_tracking_stopwatch_lang_start = "<?php echo lang_get( 'time_tracking_stopwatch_start' ) ?>";
			var time_tracking_stopwatch_lang_stop = "<?php echo lang_get( 'time_tracking_stopwatch_stop' ) ?>";
		</script>
		<?php
			html_javascript_link( 'time_tracking_stopwatch.js' );
		?>
		<input type="text" name="time_tracking" size="5" value="00:00" />
		<input type="button" name="time_tracking_ssbutton" value="<?php echo lang_get( 'time_tracking_stopwatch_start' ) ?>" onclick="time_tracking_swstartstop()" />
		<input type="button" name="time_tracking_reset" value="<?php echo lang_get( 'time_tracking_stopwatch_reset' ) ?>" onclick="time_tracking_swreset()" />
		<?php } else { ?>
		<input type="text" name="time_tracking" size="5" value="00:00" />
		<?php } ?>
	</td>
</tr>
<?php	} ?>
<?php } ?>

<?php event_signal( 'EVENT_BUGNOTE_ADD_FORM', array( $f_bug_id ) );
	$current_user = auth_get_current_user_id();
	// Build list of recipients
	$t_recipients = array();
	$t_reporter_id = bug_get_field( $f_bug_id, 'reporter_id' );
	if ( $t_reporter_id != $current_user && user_is_enabled($t_reporter_id) )
		$t_recipients[$t_reporter_id] = sprintf('%s<%s>', user_get_realname($t_reporter_id), user_get_email( $t_reporter_id ) );

	$t_bug_monitor_table = db_get_table( 'mantis_bug_monitor_table' );
	$query = "SELECT DISTINCT user_id
			  FROM $t_bug_monitor_table
			  WHERE bug_id=" . db_param();
	$result = db_query_bound( $query, Array( $f_bug_id ) );
	$count = db_num_rows( $result );
	for( $i = 0; $i < $count; $i++ ) {
		$t_user_id = db_result( $result, $i );
		if ( $t_user_id != $current_user && user_is_enabled($t_user_id) )
			$t_recipients[$t_user_id] = sprintf('%s<%s>', user_get_realname($t_user_id), user_get_email( $t_user_id ) );
	}

	$t_bugnote_table = db_get_table( 'mantis_bugnote_table' );
	$query = "SELECT DISTINCT reporter_id
			  FROM $t_bugnote_table
			  WHERE bug_id = " . db_param();
	$result = db_query_bound( $query, Array( $f_bug_id ) );
	$count = db_num_rows( $result );
	for( $i = 0; $i < $count; $i++ ) {
		$t_user_id = db_result( $result, $i );
		if ( $t_user_id != $current_user && user_is_enabled($t_user_id) )
			$t_recipients[$t_user_id] = sprintf('%s<%s>', str_replace(' ','%20',user_get_realname($t_user_id)), user_get_email( $t_user_id ) );
	}

	$recipients = implode(';',$t_recipients);
	$mantisEmail = "mantis@get-it-write.com";
	$mantisName = "GIW%20Issue%20Tracker";
	$EmailReportingMailboxes = config_get('plugin_EmailReporting_mailboxes');
	$parentProject = project_hierarchy_get_parent($g_project_override);
	$currentProjectFound = false;
	foreach($EmailReportingMailboxes AS $t_key => $t_array){
		if (isset($t_array['project_id']) && $t_array['project_id'] == $g_project_override){
			if (isset($t_array['erp_username'])){
				$mantisEmail = $t_array['erp_username'];
				$mantisName = project_get_name($g_project_override);
				if (substr($mantisName, -6) != 'Mantis')
					$mantisName .= ' Mantis';
				$currentProjectFound = true;
			}
		}elseif (!$currentProjectFound && isset($t_array['project_id']) && $t_array['project_id'] == $parentProject){
			if (isset($t_array['erp_username'])){
				$mantisEmail = $t_array['erp_username'];
				$mantisName = project_get_name($parentProject);
				if (substr($mantisName, -6) != 'Mantis')
					$mantisName .= ' Mantis';
			}
		}
	}
 ?>
<tr class="footer">
	<td class="center" colspan="2">
		<input type="submit" class="button" value="<?php echo lang_get( 'add_bugnote_button' ) ?>" />
		<input type="button" class="button" value="Open Email" onclick="window.location='mailto:<?php echo $recipients."?cc=$mantisName <$mantisEmail>&subject=".rawurlencode($tpl_project_name).'%20'.$f_bug_id.':%20'.rawurlencode($tpl_bug->summary); ?>';" />
	</td>
</tr>
</table>
</form>
<?php
	collapse_closed( 'bugnote_add' );
?>
<table class="width100" cellspacing="0">
<tr>
	<td class="form-title">
	<?php	collapse_icon( 'bugnote_add' );
		echo lang_get( 'add_bugnote_title' ) ?>
	</td>
</tr>
</table>
<?php
	collapse_end( 'bugnote_add' );
?>

<?php # Bugnote Add Form END ?>
<?php
}
