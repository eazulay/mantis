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
	 /**
	  * MantisBT Core API's
	  */

	$g_allow_browser_cache = 1;
	require_once( 'core.php' );

	require_once( 'bug_api.php' );
	require_once( 'custom_field_api.php' );

	require_once( 'relationship_api.php' );

	define ( 'BUG_VIEW_INC_ALLOW', true );

	$f_bug_id = gpc_get_int( 'id' );
	$t_bug = bug_get( $f_bug_id );

	$tpl_file = __FILE__;
	$tpl_mantis_dir = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;
	$tpl_show_page_header = false;
	$tpl_force_readonly = true;
	$tpl_fields_config_option = 'bug_change_status_page_fields';

	if( $t_bug->project_id != helper_get_current_project() ) {
		# in case the current project is not the same project of the bug we are viewing...
		# ... override the current project. This to avoid problems with categories and handlers lists etc.
		$g_project_override = $t_bug->project_id;
	}

	$f_new_status = gpc_get_int( 'new_status' );
	$f_reopen_flag = gpc_get_int( 'reopen_flag', OFF );

	$t_current_user_id = auth_get_current_user_id();

	if ( !( ( access_has_bug_level( access_get_status_threshold( $f_new_status, bug_get_field( $f_bug_id, 'project_id' ) ), $f_bug_id ) ) ||
				( ( bug_get_field( $f_bug_id, 'reporter_id' ) == $t_current_user_id ) &&
						( ( ON == config_get( 'allow_reporter_reopen' ) ) ||
								( ON == config_get( 'allow_reporter_close' ) ) ) ) ||
				( ( ON == $f_reopen_flag ) && ( access_has_bug_level( config_get( 'reopen_bug_threshold' ), $f_bug_id ) ) )
			) ) {
		access_denied();
	}
	# get new issue handler if set, otherwise default to original handler
	$f_handler_id = gpc_get_int( 'handler_id', bug_get_field( $f_bug_id, 'handler_id' ) );

	if ( config_get( 'bug_assigned_status' ) == $f_new_status ) {
		$t_bug_sponsored = sponsorship_get_amount( sponsorship_get_all_ids( $f_bug_id ) ) > 0;
		if ( $t_bug_sponsored ) {
			if ( !access_has_bug_level( config_get( 'assign_sponsored_bugs_threshold' ), $f_bug_id ) ) {
				trigger_error( ERROR_SPONSORSHIP_ASSIGNER_ACCESS_LEVEL_TOO_LOW, ERROR );
			}
		}

		if ( $f_handler_id != NO_USER ) {
            if ( !access_has_bug_level( config_get( 'handle_bug_threshold' ), $f_bug_id, $f_handler_id ) ) {
				trigger_error( ERROR_HANDLER_ACCESS_TOO_LOW, ERROR );
			}

			if ( $t_bug_sponsored ) {
				if ( !access_has_bug_level( config_get( 'handle_sponsored_bugs_threshold' ), $f_bug_id, $f_handler_id ) ) {
					trigger_error( ERROR_SPONSORSHIP_HANDLER_ACCESS_LEVEL_TOO_LOW, ERROR );
				}
			}
		}
	}

	$t_status_label = str_replace( " ", "_", MantisEnum::getLabel( config_get( 'status_enum_string' ), $f_new_status ) );
	$t_resolved = config_get( 'bug_resolved_status_threshold' );
	$t_closed = config_get( 'bug_closed_status_threshold' );
	$t_bug = bug_get( $f_bug_id );

	html_page_top( bug_format_summary( $f_bug_id, SUMMARY_CAPTION ) );

	print_recently_visited();
?>

<br />
<div align="center">
<form method="post" action="bug_update.php" id="status_update_form">
<?php echo form_security_field( 'bug_update' ) ?>
<table class="width75" cellspacing="0">

<!-- Title -->
<tr class="header">
	<td class="form-title" colspan="2">
		<input type="hidden" name="bug_id" value="<?php echo $f_bug_id ?>" />
		<input type="hidden" name="status" value="<?php echo $f_new_status ?>" />
		<?php echo lang_get( $t_status_label . '_bug_title' ) ?>
	</td>
</tr>

<?php
	if ( $t_resolved <= $f_new_status ) {
		if ( relationship_can_resolve_bug( $f_bug_id ) == false ) {
			echo "<tr><td colspan=\"2\">" . lang_get( 'relationship_warning_blocking_bugs_not_resolved_2' ) . "</td></tr>";
		}
	}
?>

<?php
$t_current_resolution = $t_bug->resolution;
$t_bug_is_open = in_array( $t_current_resolution, array( config_get( 'default_bug_resolution' ), config_get( 'bug_reopen_resolution' ) ) );
if ( ( $t_resolved <= $f_new_status ) && ( ( $t_closed > $f_new_status ) || ( $t_bug_is_open ) ) ) { ?>
<!-- Resolution -->
<tr <?php echo helper_alternate_class() ?>>
	<td class="category">
		<?php echo lang_get( 'resolution' ) ?>
	</td>
	<td>
		<select name="resolution">
			<?php
                $t_resolution = $f_new_status == HOLD ? SUSPENDED : ($t_bug_is_open ? config_get( 'bug_resolution_fixed_threshold' ) : $t_current_resolution);
                print_enum_string_option_list( "resolution", $t_resolution );
            ?>
		</select>
	</td>
</tr>
<?php } ?>

<?php
if ( ( $t_resolved <= $f_new_status ) && ( $t_closed > $f_new_status ) ) { ?>
<!-- Duplicate ID -->
<tr <?php echo helper_alternate_class() ?>>
	<td class="category">
		<?php echo lang_get( 'duplicate_id' ) ?>
	</td>
	<td>
		<input type="text" name="duplicate_id" maxlength="10" />
	</td>
</tr>
<?php } ?>

<?php
if ( access_has_bug_level( config_get( 'update_bug_assign_threshold', config_get( 'update_bug_threshold' ) ), $f_bug_id ) ) {
	$t_suggested_handler_id = $t_bug->handler_id;

	if ( $t_suggested_handler_id == NO_USER && access_has_bug_level( config_get( 'handle_bug_threshold' ), $f_bug_id ) ) {
		$t_suggested_handler_id = $t_current_user_id;
	}
?>
<!-- Assigned To -->
<tr <?php echo helper_alternate_class() ?>>
	<td class="category">
		<?php echo lang_get( 'assigned_to' ) ?>
	</td>
	<td>
		<select name="handler_id">
			<option value="0"></option>
			<?php print_assign_to_option_list( $t_suggested_handler_id, $t_bug->project_id ) ?>
		</select>
	</td>
</tr>
<?php } ?>

<!-- Custom Fields -->
<?php
/** @todo thraxisp - I undid part of the change for #5068 for #5527
 * We really need to say what fields are shown in which statuses. For now,
 * this page will show required custom fields in update mode, or
 * display or required fields on resolve or close
 */
$t_custom_status_label = "update"; # Don't show custom fields by default
if ( $f_new_status == $t_resolved )
	$t_custom_status_label = "resolved";
if ( $t_closed == $f_new_status )
	$t_custom_status_label = "closed";

$t_related_custom_field_ids = custom_field_get_linked_ids( bug_get_field( $f_bug_id, 'project_id' ) );

foreach( $t_related_custom_field_ids as $t_id ) {
	$t_def = custom_field_get_definition( $t_id );
	$t_display = $t_def['display_' . $t_custom_status_label];
	$t_require = $t_def['require_' . $t_custom_status_label];

	if ( !$t_require ) {
		if ( $f_new_status != FEEDBACK && $f_new_status != APPROVAL && $f_new_status != CONFIRMED && $f_new_status != HOLD && $f_new_status != $t_resolved && $f_new_status != $t_closed )
			continue;
		if ( ( $f_new_status == FEEDBACK || $f_new_status == APPROVAL ) && $t_id > 2 ) // Info Required => show only first two custom fields (Type and Info Required From)
			continue;
		if ( ($f_new_status == CONFIRMED || $f_new_status == HOLD) && ($t_id < 3 || $t_id >= 8 ) ) // Approve/Hold => show only fields related to Approval
			continue;
		if ( ( $f_new_status == $t_resolved || $f_new_status == $t_closed ) && !$t_display )
			continue;
	}
	if ( custom_field_has_write_access( $t_id, $f_bug_id ) ) {
?>
<tr <?php echo helper_alternate_class() ?>>
	<td class="category">
		<?php if ( $t_require ) {?><span class="required">*</span><?php } echo lang_get_defaulted( $t_def['name'] ) ?>
	</td>
	<td>
		<?php
			$default_value = null;

			switch ($t_id) {
				case 3: if ( $f_new_status == CONFIRMED )
							$default_value = "Approved";
						elseif ( $f_new_status == HOLD )
							$default_value = custom_field_get_value( $t_id, $f_bug_id );
						$auth_status = $default_value;
						break;
				case 4:
				case 6: if ( custom_field_get_value( 3, $f_bug_id ) != $auth_status )
							$default_value = user_get_realname( auth_get_current_user_id() );
						else
							$default_value = custom_field_get_value( $t_id, $f_bug_id );
						break;
				case 5:
				case 7: if ( custom_field_get_value( 3, $f_bug_id ) != $auth_status )
							$default_value = db_now();
						else
							$default_value = custom_field_get_value( $t_id, $f_bug_id );
						break;
			}
			print_custom_field_input( $t_def, $f_bug_id, $default_value );
		?>
	</td>
</tr>
<?php
	} #  custom_field_has_write_access( $t_id, $f_bug_id ) )
	else if ( custom_field_has_read_access( $t_id, $f_bug_id ) ) {
?>
	<tr <?php echo helper_alternate_class() ?>>
		<td class="category">
			<?php echo lang_get_defaulted( $t_def['name'] ) ?>
		</td>
		<td>
			<?php print_custom_field_value( $t_def, $t_id, $f_bug_id );			?>
		</td>
	</tr>
<?php
	} # custom_field_has_read_access( $t_id, $f_bug_id ) )
} # foreach( $t_related_custom_field_ids as $t_id )
?>

<?php
if ( ( $f_new_status >= $t_resolved ) ) {
	$t_show_product_version = ( ON == config_get( 'show_product_version' ) )
		|| ( ( AUTO == config_get( 'show_product_version' ) )
					&& ( count( version_get_all_rows( $t_bug->project_id ) ) > 0 ) );
	if ( $t_show_product_version ) {
?>
<!-- Fixed in Version -->
<tr <?php echo helper_alternate_class() ?>>
	<td class="category">
		<?php echo lang_get( 'fixed_in_version' ) ?>
	</td>
	<td>
		<select name="fixed_in_version">
			<?php print_version_option_list( bug_get_field( $f_bug_id, 'fixed_in_version' ),
							bug_get_field( $f_bug_id, 'project_id' ), VERSION_ALL ) ?>
		</select>
	</td>
</tr>
<?php }
	} ?>

<?php
if ( ( $f_new_status >= $t_resolved ) && ( $t_closed > $f_new_status ) ) { ?>
<!-- Close Immediately (if enabled) -->
<?php if ( ( ON == config_get( 'allow_close_immediately' ) )
				&& ( access_has_bug_level( access_get_status_threshold( $t_closed, bug_get_field( $f_bug_id, 'project_id' ) ), $f_bug_id ) ) ) { ?>
<tr <?php echo helper_alternate_class() ?>>
	<td class="category">
		<?php echo lang_get( 'close_immediately' ) ?>
	</td>
	<td>
		<input type="checkbox" name="close_now" />
	</td>
</tr>
<?php } ?>
<?php } ?>

<?php
	if ( ON == $f_reopen_flag ) {
		# bug was re-opened
		printf("	<input type=\"hidden\" name=\"resolution\" value=\"%s\" />\n",  config_get( 'bug_reopen_resolution' ) );
	}
?>

<!-- Bugnote -->
<tr <?php echo helper_alternate_class() ?>>
	<td class="category">
		<?php echo lang_get( 'add_bugnote_title' ) ?>
	</td>
	<td class="center">
		<textarea name="bugnote_text" cols="80" rows="10"><?php echo gpc_get_string('change_status_text'); ?></textarea>
	</td>
</tr>
<?php if ( access_has_bug_level( config_get( 'private_bugnote_threshold' ), $f_bug_id ) ) { ?>
<tr <?php echo helper_alternate_class() ?>>
	<td class="category">
		<?php echo lang_get( 'view_status' ) ?>
	</td>
	<td>
<?php
		$t_default_bugnote_view_status = config_get( 'default_bugnote_view_status' );
		if ( access_has_bug_level( config_get( 'set_view_status_threshold' ), $f_bug_id ) ) {
?>
			<input type="checkbox" name="private" <?php check_checked( $t_default_bugnote_view_status, VS_PRIVATE ); ?> />
<?php
			echo '<label for="private">' . lang_get( 'private' ) . '</label>';
		} else {
			echo get_enum_element( 'project_view_state', $t_default_bugnote_view_status );
		}
?>
	</td>
</tr>
<?php } ?>

<?php if ( config_get('time_tracking_enabled') ) { ?>
<?php if ( access_has_bug_level( config_get( 'private_bugnote_threshold' ), $f_bug_id ) ) { ?>
<?php if ( access_has_bug_level( config_get( 'time_tracking_edit_threshold' ), $f_bug_id ) ) { ?>
<tr <?php echo helper_alternate_class() ?>>
	<td class="category">
		<?php echo lang_get( 'time_tracking' ) ?> (HH:MM)
	</td>
	<td>
		<input type="text" name="time_tracking" size="5" value="0:00" />
	</td>
</tr>
<?php } ?>
<?php } ?>
<?php } ?>

<?php event_signal( 'EVENT_BUGNOTE_ADD_FORM', array( $f_bug_id ) ); ?>

<!-- Submit Button -->
<tr class="footer">
	<td class="center" colspan="2">
		<input type="submit" class="button" value="<?php echo lang_get( $t_status_label . '_bug_button' ) ?>" />
	</td>
</tr>


</table>
</form>
</div>

<?php
echo '<br />';

if ( $t_resolved == $f_new_status ): ?>
<script lang="javascript">
<!--
    const statusUpdateForm = document.getElementById("status_update_form");
    statusUpdateForm.addEventListener("submit", function(event){
        const fldResolution = statusUpdateForm.elements["resolution"];
        const resolution = fldResolution.value;
        console.log(resolution);
        if ( resolution == '10' || resolution == '30' ){ /* open or reopen */
            const tdRes = fldResolution.parentNode;
            if (tdRes.lastChild.nodeName != 'SPAN'){
                tdRes.classList.add("error");
                const span = document.createElement("span");
                span.innerHTML = " Invalid Resolution value";
                tdRes.appendChild(span);
            }
            event.preventDefault();
        }
    });
-->
</script>
<?php
endif;

include( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'bug_view_inc.php' );
