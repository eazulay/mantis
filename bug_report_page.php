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
	 * This file POSTs data to report_bug.php
	 *
	 * @package MantisBT
	 * @copyright Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
	 * @copyright Copyright (C) 2002 - 2011  MantisBT Team - mantisbt-dev@lists.sourceforge.net
	 * @link http://www.mantisbt.org
	 */

	 $g_allow_browser_cache = 1;

	 /**
	  * MantisBT Core API's
	  */
	require_once( 'core.php' );

	require_once( 'file_api.php' );
	require_once( 'custom_field_api.php' );
	require_once( 'last_visited_api.php' );
	require_once( 'projax_api.php' );
	require_once( 'collapse_api.php' );

	$f_master_bug_id = gpc_get_int( 'm_id', 0 );

	# this page is invalid for the 'All Project' selection except if this is a clone
	if ( (ALL_PROJECTS == helper_get_current_project() || count(helper_get_current_project_trace()) < 2) && ( 0 == $f_master_bug_id ) ) {
		print_header_redirect( 'login_select_proj_page.php?ref=bug_report_page.php' );
	}

	access_ensure_project_level( config_get( 'report_bug_threshold' ) );

	if ( $f_master_bug_id > 0 ) {
		# master bug exists...
		bug_ensure_exists( $f_master_bug_id );

		# master bug is not read-only...
		if ( bug_is_readonly( $f_master_bug_id ) ) {
			error_parameters( $f_master_bug_id );
			trigger_error( ERROR_BUG_READ_ONLY_ACTION_DENIED, ERROR );
		}

		$t_bug = bug_get( $f_master_bug_id, true );

		# the user can at least update the master bug (needed to add the relationship)...
		access_ensure_bug_level( config_get( 'update_bug_threshold', null, $t_bug->project_id ), $f_master_bug_id );

		#@@@ (thraxisp) Note that the master bug is cloned into the same project as the master, independent of
		#       what the current project is set to.
		if( $t_bug->project_id != helper_get_current_project() ) {
            # in case the current project is not the same project of the bug we are viewing...
            # ... override the current project. This to avoid problems with categories and handlers lists etc.
            $g_project_override = $t_bug->project_id;
            $t_changed_project = true;
        } else {
            $t_changed_project = false;
        }

		$f_build				= $t_bug->build;
		$f_profile_id			= 0;
		$f_platform				= $t_bug->platform;
		$f_os					= $t_bug->os;
		$f_os_build				= $t_bug->os_build;
		$f_product_version		= $t_bug->version;
		$f_target_version		= $t_bug->target_version;
		$f_handler_id			= $t_bug->handler_id;

		$f_category_id			= $t_bug->category_id;
		$f_reproducibility		= $t_bug->reproducibility;
		$f_severity				= $t_bug->severity;
		$f_priority				= $t_bug->priority;
		$f_summary				= $t_bug->summary;
		$f_description			= $t_bug->description;
		$f_steps_to_reproduce	= $t_bug->steps_to_reproduce;
		$f_additional_info		= $t_bug->additional_information;
		$f_view_state			= $t_bug->view_state;
		$f_due_date				= $t_bug->due_date;

		$t_project_id			= $t_bug->project_id;
	} else {
		$f_build				= gpc_get_string( 'build', '' );
		$f_platform				= gpc_get_string( 'platform', '' );
		$f_os					= gpc_get_string( 'os', '' );
		$f_os_build				= gpc_get_string( 'os_build', '' );
		$f_product_version		= gpc_get_string( 'product_version', '' );
		$f_target_version		= gpc_get_string( 'target_version', '' );
		$f_profile_id			= gpc_get_int( 'profile_id', profile_get_default( auth_get_current_user_id() ) );
		$f_handler_id			= gpc_get_int( 'handler_id', 0 );

		$f_category_id			= gpc_get_int( 'category_id', 0 );
		$f_reproducibility		= gpc_get_int( 'reproducibility', config_get( 'default_bug_reproducibility' ) );
		$f_severity				= gpc_get_int( 'severity', config_get( 'default_bug_severity' ) );
		$f_priority				= gpc_get_int( 'priority', config_get( 'default_bug_priority' ) );
		$f_summary				= gpc_get_string( 'summary', '' );
		$f_description			= gpc_get_string( 'description', '' );
		$f_steps_to_reproduce	= trim( gpc_get_string( 'steps_to_reproduce', config_get( 'default_bug_steps_to_reproduce' ) ) );
		$f_additional_info		= gpc_get_string( 'additional_info', config_get ( 'default_bug_additional_info' ) );
		$f_view_state			= gpc_get_int( 'view_state', config_get( 'default_bug_view_status' ) );
		$f_due_date				= gpc_get_string( 'due_date', '');

		if ( $f_due_date == '' ) {
			$f_due_date = date_get_null();
		}

		$t_project_id			= helper_get_current_project();

		$t_changed_project		= false;
	}

	$f_report_stay			= gpc_get_bool( 'report_stay', false );

	$t_fields = config_get( 'bug_report_page_fields' );
	$t_fields = columns_filter_disabled( $t_fields );

	$t_has_develper_access_level = access_has_project_level( DEVELOPER );

	$tpl_show_category = in_array( 'category_id', $t_fields );
	$tpl_show_reproducibility = in_array( 'reproducibility', $t_fields );
	$tpl_show_severity = in_array( 'severity', $t_fields );
	$tpl_show_priority = in_array( 'priority', $t_fields ) && $t_has_develper_access_level;
	$tpl_show_steps_to_reproduce = in_array( 'steps_to_reproduce', $t_fields );
	$tpl_show_handler = in_array( 'handler', $t_fields ) && $t_has_develper_access_level;
	$tpl_show_profiles = config_get( 'enable_profiles' );
	$tpl_show_platform = $tpl_show_profiles && in_array( 'platform', $t_fields );
	$tpl_show_os = $tpl_show_profiles && in_array( 'os', $t_fields );
	$tpl_show_os_version = $tpl_show_profiles && in_array( 'os_version', $t_fields );

	$tpl_show_versions = version_should_show_product_version( $t_project_id );
	$tpl_show_product_version = $tpl_show_versions && in_array( 'product_version', $t_fields );
	$tpl_show_product_build = $tpl_show_versions && in_array( 'product_build', $t_fields ) && config_get( 'enable_product_build' ) == ON;
	$tpl_show_target_version = $tpl_show_versions && in_array( 'target_version', $t_fields ) && access_has_project_level( config_get( 'roadmap_update_threshold' ) );
	$tpl_show_additional_info = in_array( 'additional_info', $t_fields );
	$tpl_show_due_date = in_array( 'due_date', $t_fields ) && $t_has_develper_access_level && access_has_project_level( config_get( 'due_date_update_threshold' ), helper_get_current_project(), auth_get_current_user_id() );
	$tpl_show_attachments = in_array( 'attachments', $t_fields ) && file_allow_bug_upload();
	$tpl_show_view_state = in_array( 'view_state', $t_fields ) && $t_has_develper_access_level && access_has_project_level( config_get( 'set_view_status_threshold' ) );

	# don't index bug report page
	html_robots_noindex();

	html_page_top( lang_get( 'report_bug_link' ) );

	print_recently_visited();
?>
<br />
<div>
<form name="report_bug_form" method="post" <?php if ( $tpl_show_attachments ) { echo 'enctype="multipart/form-data"'; } ?> action="bug_report.php">
<?php echo form_security_field( 'bug_report' ) ?>
<table class="width90" cellspacing="0">
<thead>
	<tr class="header">
		<td class="form-title" colspan="2">
			<input type="hidden" name="m_id" value="<?php echo $f_master_bug_id ?>" />
			<input type="hidden" name="project_id" value="<?php echo $t_project_id ?>" />
			<?php echo lang_get( 'enter_report_details_title' ) ?>
		</td>
	</tr>
<?php
	event_signal( 'EVENT_REPORT_BUG_FORM_TOP', array( $t_project_id ) );
?>
	<tr class="subheader">
		<th colspan="2">Metadata</th>
	</tr>
</thead>
<tbody>
<?php
	// Custom Fields
	$t_related_custom_field_ids = custom_field_get_linked_ids( $t_project_id );
	foreach( $t_related_custom_field_ids as $t_id ) {
		$t_def = custom_field_get_definition( $t_id );
		// Include fields configured for Raising Issues "display_report"
		if( ( $t_def['display_report'] || $t_def['require_report']) && custom_field_has_write_access_to_project( $t_id, $t_project_id ) ) { ?>
	<tr>
		<td class="category">
			<?php if($t_def['require_report']) {?><?php } echo string_display( lang_get_defaulted( $t_def['name'] ) ) ?> <span class="required">*</span>
		</td>
		<td>
			<?php print_custom_field_input( $t_def, ( $f_master_bug_id === 0 ) ? null : $f_master_bug_id ) ?>
		</td>
	</tr>
<?php
		}
	}

	if ( $tpl_show_category ) { ?>
	<tr>
		<td class="category" width="20%">
			<?php print_documentation_link( 'category' ); echo config_get( 'allow_no_category' ) ? '' : ' <span class="required">*</span>'; ?>
		</td>
		<td width="80%">
			<?php if ( $t_changed_project ) {
				echo "[" . project_get_field( $t_bug->project_id, 'name' ) . "] ";
			} ?>
			<select <?php echo helper_get_tab_index() ?> name="category_id">
				<?php
					$excluded_categories = null;
					if (!$t_has_develper_access_level)
						$excluded_categories = array(18, 20); // exclude Improvement and Quoted Project
					print_category_option_list( $f_category_id, null, $excluded_categories);
				?>
			</select>
		</td>
	</tr>
<?php }

	if ( $tpl_show_reproducibility ) { ?>
	<tr id="reproducibility">
		<td class="category">
			<?php print_documentation_link( 'reproducibility' ) ?><span class="required">*</span>
		</td>
		<td>
			<select <?php echo helper_get_tab_index() ?> name="reproducibility">
				<?php print_enum_string_option_list( 'reproducibility', $f_reproducibility ) ?>
			</select>
		</td>
	</tr>
<?php }

	if ( $tpl_show_severity ) { ?>
	<tr>
		<td class="category">
			<?php print_documentation_link( 'severity' ) ?>
		</td>
		<td>
			<select <?php echo helper_get_tab_index() ?> name="severity">
				<?php print_enum_string_option_list( 'severity', $f_severity ) ?>
			</select>
		</td>
	</tr>
<?php }
	
	if ( $tpl_show_platform || $tpl_show_os || $tpl_show_os_version ) { ?>
	<tr id="profile">
		<td class="category">
			<?php echo lang_get( 'select_profile' ) ?>
		</td>
		<td>
		<?php if (count(profile_get_all_for_user( auth_get_current_user_id() )) > 0) { ?>
			<select <?php echo helper_get_tab_index() ?> name="profile_id">
				<?php print_profile_option_list( auth_get_current_user_id(), $f_profile_id ) ?>
			</select>
		<?php } ?>
			<div id="profile_fields" style="display:inline-block;">
			<?php if( ON == config_get( 'use_javascript' ) ) { ?>
				<strong style="display:inline-block; vertical-align:top;"><?php echo lang_get( 'or_fill_in' ); ?></strong>
				<?php collapse_open( 'profile' ); collapse_icon('profile'); ?>
				<table class="width100" cellspacing="0" style="margin:10px 3px 0;">
			<?php } else { ?>
				<strong><?php echo lang_get( 'or_fill_in' ); ?></strong>
			<?php } ?>
					<tr>
						<td class="category">
							<?php echo lang_get( 'platform' ) ?>
						</td>
						<td>
							<?php if ( config_get( 'allow_freetext_in_profile_fields' ) == OFF ) { ?>
							<select name="platform">
								<option value=""></option>
								<?php print_platform_option_list( $f_platform ); ?>
							</select>
							<?php
								} else {
									projax_autocomplete( 'platform_get_with_prefix', 'platform', array( 'value' => $f_platform, 'size' => '29', 'maxlength' => '32', 'tabindex' => helper_get_tab_index_value() ) );
								}
							?>
						</td>
					</tr>
					<tr>
						<td class="category">
							<?php echo lang_get( 'os' ) ?>
						</td>
						<td>
							<?php if ( config_get( 'allow_freetext_in_profile_fields' ) == OFF ) { ?>
							<select name="os">
								<option value=""></option>
								<?php print_os_option_list( $f_os ); ?>
							</select>
							<?php
								} else {
									projax_autocomplete( 'os_get_with_prefix', 'os', array( 'value' => $f_os, 'size' => '29', 'maxlength' => '32', 'tabindex' => helper_get_tab_index_value() ) );
								}
							?>
						</td>
					</tr>
					<tr>
						<td class="category">
							<?php echo lang_get( 'os_version' ) ?>
						</td>
						<td>
							<?php
								if ( config_get( 'allow_freetext_in_profile_fields' ) == OFF ) {
							?>
							<select name="os_build">
								<option value=""></option>
									<?php print_os_build_option_list( $f_os_build ); ?>
								</select>
							<?php
								} else {
									projax_autocomplete( 'os_build_get_with_prefix', 'os_build', array( 'value' => $f_os_build, 'size' => '29', 'maxlength' => '32', 'tabindex' => helper_get_tab_index_value() ) );
								}
							?>
						</td>
					</tr>
				<?php if( ON == config_get( 'use_javascript' ) ) { ?>
				</table>
				<?php collapse_closed( 'profile' ); collapse_icon('profile'); ?>
				<?php collapse_end( 'profile' ); ?>
			<?php } ?>
			</div>
		</td>
	</tr>
<?php }
	if ( $tpl_show_product_version ) {
		$t_product_version_released_mask = VERSION_RELEASED;

		if (access_has_project_level( config_get( 'report_issues_for_unreleased_versions_threshold' ) ) ) {
			$t_product_version_released_mask = VERSION_ALL;
		} ?>
	<tr>
		<td class="category">
			<?php echo lang_get( 'product_version' ) ?>
		</td>
		<td>
			<select <?php echo helper_get_tab_index() ?> name="product_version">
				<?php print_version_option_list( $f_product_version, $t_project_id, $t_product_version_released_mask ) ?>
			</select>
		</td>
	</tr>
<?php }
	if ( $tpl_show_product_build ) { ?>
	<tr>
		<td class="category">
			<?php echo lang_get( 'product_build' ) ?>
		</td>
		<td>
			<input <?php echo helper_get_tab_index() ?> type="text" name="build" size="32" maxlength="32" value="<?php echo string_attribute( $f_build ) ?>" />
		</td>
	</tr>
<?php }
	if ( $tpl_show_priority ) { ?>
<tr>
	<td class="category">
		<?php print_documentation_link( 'priority' ) ?>
	</td>
	<td>
		<select <?php echo helper_get_tab_index() ?> name="priority">
			<?php print_enum_string_option_list( 'priority', $f_priority ) ?>
		</select>
	</td>
</tr>
<?php }
if ( $tpl_show_due_date ) {
	$t_date_to_display = '';

	if ( !date_is_null( $f_due_date ) ) {
		$t_date_to_display = date( config_get( 'calendar_date_format' ), $f_due_date );
	} ?>
<tr>
	<td class="category">
		<?php print_documentation_link( 'due_date' ) ?>
	</td>
	<td>
	<?php
		print "<input ".helper_get_tab_index()." type=\"text\" id=\"due_date\" name=\"due_date\" size=\"24\" maxlength=\"16\" value=\"".$t_date_to_display."\" />";
		date_print_calendar();
	?>
	</td>
</tr>
<?php }
if ( $tpl_show_handler ) { ?>
<tr>
	<td class="category">
		<?php echo lang_get( 'assign_to' ) ?>
	</td>
	<td>
		<select <?php echo helper_get_tab_index() ?> name="handler_id">
			<option value="0" selected="selected"></option>
			<?php print_assign_to_option_list( $f_handler_id ) ?>
		</select>
	</td>
</tr>
<?php }
// Target Version (if permissions allow)
if ( $tpl_show_target_version ) { ?>
<tr>
	<td class="category">
		<?php echo lang_get( 'target_version' ) ?>
	</td>
	<td>
		<select <?php echo helper_get_tab_index() ?> name="target_version">
			<?php print_version_option_list() ?>
		</select>
	</td>
</tr>
<?php }
if ( $tpl_show_view_state ) { ?>
<tr>
	<td class="category">
		<?php echo lang_get( 'view_status' ) ?>
	</td>
	<td>
		<label><input <?php echo helper_get_tab_index() ?> type="radio" name="view_state" value="<?php echo VS_PUBLIC ?>" <?php check_checked( $f_view_state, VS_PUBLIC ) ?> /> <?php echo lang_get( 'public' ) ?></label>
		<label><input <?php echo helper_get_tab_index() ?> type="radio" name="view_state" value="<?php echo VS_PRIVATE ?>" <?php check_checked( $f_view_state, VS_PRIVATE ) ?> /> <?php echo lang_get( 'private' ) ?></label>
	</td>
</tr>
<?php }
//Relationship (in case of cloned bug creation...)
if( $f_master_bug_id > 0 ) { ?>
<tr>
	<td class="category">
		<?php echo lang_get( 'relationship_with_parent' ) ?>
	</td>
	<td>
		<?php relationship_list_box( /* none */ -2, "rel_type", false, true ) ?>
		<?php echo '<b>' . lang_get( 'bug' ) . ' ' . bug_format_id( $f_master_bug_id ) . '</b>' ?>
	</td>
</tr>
<?php }
	event_signal( 'EVENT_REPORT_BUG_FORM', array( $t_project_id ) ) ?>
</tbody>
<thead>
	<tr class="subheader">
		<th colspan="2"><?php print_documentation_link( 'description' ) ?></th>
	</tr>
</thead>
<tbody>
<?php /* 2025-01-10 replaced Description with a hidden field (added to Summary above), populated by alternative fields #2719 ?>
	<tr>
		<td class="category">
			<?php print_documentation_link( 'description' ) ?><span class="required">*</span>
		</td>
		<td>
			<textarea <?php echo helper_get_tab_index() ?> name="description" cols="80" rows="10"><?php echo string_textarea( $f_description ) ?></textarea>
		</td>
	</tr>
<?php */ ?>
	<tr id="aim">
		<td class="category">
			Aim <span class="required">*</span>
		</td>
		<td>
			<textarea <?php echo helper_get_tab_index() ?> name="aim" cols="80" rows="2"></textarea>
		</td>
	</tr>
	<tr id="question" style="display:none;" class="first-visible">
		<td class="category">
			Question <span class="required">*</span>
		</td>
		<td>
			<textarea <?php echo helper_get_tab_index() ?> name="question" cols="80" rows="2" placeholder="Who...? What...? When...? Where...? Why...? How...?"></textarea>
		</td>
	</tr>
	<tr id="motivation" style="display:none;">
		<td class="category">
			Motivation <span class="required">*</span>
		</td>
		<td>
			<textarea <?php echo helper_get_tab_index() ?> name="motivation" cols="80" rows="2" placeholder="Because/in order to…"></textarea>
		</td>
	</tr>
	<tr id="expectation" style="display:none;">
		<td class="category">
			Expectation <span class="required">*</span>
		</td>
		<td>
			<textarea <?php echo helper_get_tab_index() ?> name="expectation" cols="80" rows="2" placeholder="I expected…"></textarea>
		</td>
	</tr>
	<tr id="outcome" style="display:none;">
		<td class="category">
			Outcome <span class="required">*</span>
		</td>
		<td>
			<textarea <?php echo helper_get_tab_index() ?> name="outcome" cols="80" rows="2"></textarea>
		</td>
	</tr>
	<tr id="error_message" style="display:none;">
		<td class="category">
			Error Message
		</td>
		<td>
			<textarea <?php echo helper_get_tab_index() ?> name="error_message" cols="80" rows="2" placeholder="Copy or describe the message."></textarea>
		</td>
	</tr>
	<tr id="other_approaches" style="display:none;">
		<td class="category">
			Other Approaches
		</td>
		<td>
			<textarea <?php echo helper_get_tab_index() ?> name="other_approaches" cols="80" rows="2" placeholder="To achieve my Aim, I also tried…"></textarea>
		</td>
	</tr>
	<tr id="last_success" style="display:none;">
		<td class="category">
			Last Successful Use
		</td>
		<td>
			<textarea <?php echo helper_get_tab_index() ?> name="last_success" cols="80" rows="2" placeholder="E.g., Never, Date, Yesterday, Last week, Last month..."></textarea>
		</td>
	</tr>
	<tr id="recent_changes" style="display:none;">
		<td class="category">
			Recent System Changes
		</td>
		<td>
			<textarea <?php echo helper_get_tab_index() ?> name="recent_changes" cols="80" rows="" placeholder="E.g., System updates, new programs, settings changes, errors elsewhere…"></textarea>
		</td>
	</tr>
	<tr>
		<td class="category">
			<?php print_documentation_link( 'summary' ) ?> (Issue Title)<span class="required">*</span>
		</td>
		<td>
			<input <?php echo helper_get_tab_index() ?> type="text" name="summary" maxlength="128" value="<?php echo string_attribute( $f_summary ) ?>" class="fullwidth" />
			<input type="hidden" name="description" value="" />
		</td>
	</tr>
</tbody>
<thead>
	<tr class="subheader">
		<th colspan="2">Location <span class="required">*</span></th>
	</tr>
</thead>
<tbody>
<?php /* 2025-01-10 removed Steps to Reproduce Bug #2719
	if ( $tpl_show_steps_to_reproduce ) { ?>
		<tr id="steps_to_reproduce">
			<td class="category">
				<?php print_documentation_link( 'steps_to_reproduce' ) ?>
			</td>
			<td>
				<textarea <?php echo helper_get_tab_index() ?> name="steps_to_reproduce" cols="80" rows="10"><?php echo string_textarea( $f_steps_to_reproduce ) ?></textarea>
			</td>
		</tr>
<?php } */ ?>
	<tr>
		<td class="category" colspan="2">
			<em id="location_mandatory_message">Fill in at least one Location field; more if that might help.</em>
			<input type="hidden" name="steps_to_reproduce" value="" />
		</td>
	</tr>
	<tr id="url">
		<td class="category">
			URL
		</td>
		<td>
			<input <?php echo helper_get_tab_index() ?> type="url" name="url" class="fullwidth" />
		</td>
	</tr>
	<tr id="path">
		<td class="category">
			Path
		</td>
		<td>
			<input <?php echo helper_get_tab_index() ?> type="text" name="path" class="fullwidth" placeholder="E.g., Menu > View Issues" />
		</td>
	</tr>
	<tr id="page_field">
		<td class="category">
			Page, Field
		</td>
		<td>
			<input <?php echo helper_get_tab_index() ?> type="text" name="page_field" class="fullwidth" placeholder="E.g., View Issues; click [Reset Filter] and Select 'All Open'" />
		</td>
	</tr>
	<tr id="process">
		<td class="category">
			Process
		</td>
		<td>
			<textarea <?php echo helper_get_tab_index() ?>
				name="process" cols="80" rows="4" placeholder="Steps taken or desired (for a bug, say at which step you encountered the problem)."
			><?php echo string_textarea( $f_steps_to_reproduce ) ?></textarea>
		</td>
	</tr>
	<tr id="multiple">
		<td class="category">
			Multiple/Systemwide
		</td>
		<td>
			<input <?php echo helper_get_tab_index() ?> type="text" name="multiple" class="fullwidth" />
		</td>
	</tr>
</tbody>
<thead>
	<tr class="subheader">
		<th colspan="2">Supporting Info</th>
	</tr>
</thead>
<tbody>
<?php
	if ( $tpl_show_additional_info ) { ?>
	<tr>
		<td class="category">
			<?php print_documentation_link( 'additional_information' ) ?>
		</td>
		<td>
			<textarea <?php echo helper_get_tab_index() ?> name="additional_info" cols="80" rows="2"><?php echo string_textarea( $f_additional_info ) ?></textarea>
		</td>
	</tr>
<?php }
	if ( $tpl_show_attachments ) { // File Upload (if enabled)
	$t_max_file_size = (int)min( ini_get_number( 'upload_max_filesize' ), ini_get_number( 'post_max_size' ), config_get( 'max_file_size' ) ); ?>
	<tr><td colspan="2"></td></tr>
	<tr>
		<td class="category">
			<?php echo lang_get( 'upload_file' ) ?>
			<?php echo '<span class="small">(' . lang_get( 'max_file_size' ) . ': ' . number_format( $t_max_file_size/1000 ) . 'k)</span>'?>
		</td>
		<td>
			<input type="hidden" name="max_file_size" value="<?php echo $t_max_file_size ?>" />
			<input <?php echo helper_get_tab_index() ?> name="file" type="file" size="60" />
		</td>
	</tr>
<?php } ?>
	<tr>
		<td class="left" colspan="2" style="padding: 12px; font-style: italic;">
			<span class="required"> * <?php echo lang_get( 'required' ) ?></span>
		</td>
	</tr>
</tbody>
<tfoot>
	<tr class="footer">
		<td class="center" colspan="2">
		<?php if ( $t_has_develper_access_level ) { ?>
			<label>
				<input <?php echo helper_get_tab_index() ?> type="checkbox" id="report_stay" name="report_stay" <?php check_checked( $f_report_stay ) ?> />
				<?php print_documentation_link( 'report_stay' ); // echo lang_get( 'check_report_more_bugs' ) ?>
			</label>
		<?php } ?>
			<input <?php echo helper_get_tab_index() ?> type="submit" class="button" value="<?php echo lang_get( 'submit_report_button' ) ?>" />
		</td>
	</tr>
</tfoot>
</table>
</form>
</div>

<!-- Autofocus, show/hide bug-specific fields -->
<?php if ( ON == config_get( 'use_javascript' ) ) { ?>
<script type="text/javascript" language="JavaScript">
<!--
	if (window.document.report_bug_form.custom_field_1)
		window.document.report_bug_form.custom_field_1.focus();
	else
		window.document.report_bug_form.category_id.focus();

    const reproducibility_row = document.querySelector('#reproducibility');
	const profile_row = document.querySelector('#profile');
	const profile_fields_row = document.querySelector('#profile_fields');
    const aim_row = document.querySelector('#aim');
    const question_row = document.querySelector('#question');
    const motivation_row = document.querySelector('#motivation');
    const expectation_row = document.querySelector('#expectation');
    const outcome_row = document.querySelector('#outcome');
    const error_message_row = document.querySelector('#error_message');
    const other_approaches_row = document.querySelector('#other_approaches');
    const last_success_row = document.querySelector('#last_success');
    const recent_changes_row = document.querySelector('#recent_changes');
	const location_mandatory_message = document.querySelector('#location_mandatory_message');
	let isBug = false;
	let isQuery = false;
    window.document.report_bug_form.category_id.addEventListener('change', e => {
		const categoryID = e.currentTarget.value;
		if (categoryID == '0')
			return;
        isBug = e.currentTarget.value === '17';
		isQuery = e.currentTarget.value === '19';
		if (!isBug && window.document.report_bug_form.reproducibility.value != '100') {
			window.document.report_bug_form.reproducibility.value = '100'; // (not a bug)
		}
		reproducibility_row.style.display = isBug ? 'table-row' : 'none';
		profile_row.style.display = isBug ? 'table-row' : 'none';
		profile_fields_row.style.display = isBug ? 'inline-block' : 'none';
		aim_row.style.display = isQuery ? 'none' : 'table-row';
		aim_row.querySelector('textarea').placeholder = isBug ? 'I wanted to…' : 'I want to do/see…';
		question_row.style.display = isQuery ? 'table-row' : 'none';
		motivation_row.style.display = isBug ? 'none' : 'table-row';
		expectation_row.style.display = isBug ? 'table-row' : 'none';
		outcome_row.style.display = isQuery ? 'none' : 'table-row';
		outcome_row.querySelector('textarea').placeholder = isBug ? 'Instead, this happened…' : 'I want these results… (request a preferred format if relevant)';
		error_message_row.style.display = isBug ? 'table-row' : 'none';
		last_success_row.style.display = isBug ? 'table-row' : 'none';
		recent_changes_row.style.display = isBug ? 'table-row' : 'none';
    });
	// Ensure browser [back] button, which remembers field values (e.g. Category) also shows/hides related fields.
	window.document.report_bug_form.category_id.dispatchEvent(new Event('change', { bubbles: true }));
	// Calculate Description value
	const description_fld = window.document.report_bug_form.description;
	window.document.report_bug_form.addEventListener('submit', function(e) {
		var preventSubmit = false;
		preventSubmit = validateMandatory(window.document.report_bug_form.custom_field_1) || preventSubmit; // Type
		preventSubmit = validateMandatory(window.document.report_bug_form.category_id, '0') || preventSubmit;
		preventSubmit = validateMandatory(window.document.report_bug_form.summary) || preventSubmit;
		description_fld.value = "";
		if (isBug) {
			description_fld.value = "**Aim:** " + window.document.report_bug_form.aim.value.trim() + "\n" +
			"**process:** " + window.document.report_bug_form.process.value.trim() + "\n" +
			"**Expectation:** " + window.document.report_bug_form.expectation.value.trim() + "\n" +
			"**Outcome:** " + window.document.report_bug_form.outcome.value.trim();
			if (window.document.report_bug_form.error_message.value.trim())
				description_fld.value += "\n**Error Message:** " + window.document.report_bug_form.error_message.value.trim();
			if (window.document.report_bug_form.other_approaches.value.trim())
				description_fld.value += "\n**Other Approaches:** " + window.document.report_bug_form.other_approaches.value.trim();
			if (window.document.report_bug_form.last_success.value.trim())
				description_fld.value += "\n**Last Successful Use:** " + window.document.report_bug_form.last_success.value.trim();
			if (window.document.report_bug_form.recent_changes.value.trim())
				description_fld.value += "\n**Recent Changes:** " + window.document.report_bug_form.recent_changes.value.trim();
			preventSubmit = validateMandatory(window.document.report_bug_form.aim) || preventSubmit;
			preventSubmit = validateMandatory(window.document.report_bug_form.reproducibility, '100') || preventSubmit;
			preventSubmit = validateMandatory(window.document.report_bug_form.steps) || preventSubmit;
			preventSubmit = validateMandatory(window.document.report_bug_form.expectation) || preventSubmit;
			preventSubmit = validateMandatory(window.document.report_bug_form.outcome) || preventSubmit;
		} else if (isQuery) {
			description_fld.value = window.document.report_bug_form.question.value.trim() + "\n" +			
				"**Motivation:** " + window.document.report_bug_form.motivation.value.trim();
			preventSubmit = validateMandatory(window.document.report_bug_form.question) || preventSubmit;
			preventSubmit = validateMandatory(window.document.report_bug_form.motivation) || preventSubmit;
		} else { // Quoted Project, Change Request, Improvement, Admin
			description_fld.value = "**Aim:** " + window.document.report_bug_form.aim.value.trim() + "\n" +
			"**Motivation:** " + window.document.report_bug_form.motivation.value.trim() + "\n" +
			"**Outcome:** " + window.document.report_bug_form.outcome.value.trim();
			preventSubmit = validateMandatory(window.document.report_bug_form.aim) || preventSubmit;
			preventSubmit = validateMandatory(window.document.report_bug_form.motivation) || preventSubmit;
			preventSubmit = validateMandatory(window.document.report_bug_form.outcome) || preventSubmit;
		}
		const steps_to_reproduce_fld = window.document.report_bug_form.steps_to_reproduce;
		steps_to_reproduce_fld.value = "";
		if (window.document.report_bug_form.steps_to_reproduce.value.trim())
			steps_to_reproduce_fld.value = "\n**URL:** " + window.document.report_bug_form.url.value.trim();
		if (window.document.report_bug_form.path.value.trim())
			steps_to_reproduce_fld.value += "\n**Path:** " + window.document.report_bug_form.path.value.trim();
		if (window.document.report_bug_form.page_field.value.trim())
			steps_to_reproduce_fld.value += "\n**Page, Field:** " + window.document.report_bug_form.page_field.value.trim();
		if (window.document.report_bug_form.process.value.trim())
			steps_to_reproduce_fld.value += "\n**Process:** " + window.document.report_bug_form.process.value.trim();
		if (window.document.report_bug_form.multiple.value.trim())
			steps_to_reproduce_fld.value += "\n**Multiple/Systemwide:** " + window.document.report_bug_form.multiple.value.trim();
		if (steps_to_reproduce_fld.value)
			steps_to_reproduce_fld.value = steps_to_reproduce_fld.value.substr(1); // Remove leading newline

		var atLeastOneLocation = (
			window.document.report_bug_form.url.value.trim() ||
			window.document.report_bug_form.path.value.trim() ||
			window.document.report_bug_form.page_field.value.trim() ||
			window.document.report_bug_form.process.value.trim() ||
			window.document.report_bug_form.multiple.value.trim()
		);
		if (!atLeastOneLocation){
			location_mandatory_message.classList.add('error');
			preventSubmit = true;
		} else {
			location_mandatory_message.classList.remove('error');
		}

		if (preventSubmit)
			e.preventDefault();
	});
	function validateMandatory(inputElement, emptyValue='') {
		if (inputElement) {
			if (inputElement.value.trim() == emptyValue) {
				inputElement.classList.add('error');
				return true; // Prevent Submit
			} else {
				inputElement.classList.remove('error');
				return false;
			}
		}
		return false;
	}
-->
</script>
<?php }
if ( $tpl_show_due_date ) {
	date_finish_calendar( 'due_date', 'trigger' );
}

html_page_bottom();
