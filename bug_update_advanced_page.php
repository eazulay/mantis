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

$g_allow_browser_cache = 1;
require_once( 'core.php' );
require_once( 'ajax_api.php' );
require_once( 'bug_api.php' );
require_once( 'custom_field_api.php' );
require_once( 'date_api.php' );
require_once( 'last_visited_api.php' );
require_once( 'projax_api.php' );

$f_bug_id = gpc_get_int( 'bug_id' );

$tpl_bug = bug_get( $f_bug_id, true );

if ( $tpl_bug->project_id != helper_get_current_project() ) {
	# in case the current project is not the same project of the bug we are viewing...
	# ... override the current project. This to avoid problems with categories and handlers lists etc.
	$g_project_override = $tpl_bug->project_id;
	$tpl_changed_project = true;
} else {
	$tpl_changed_project = false;
}

if ( bug_is_readonly( $f_bug_id ) ) {
	error_parameters( $f_bug_id );
	trigger_error( ERROR_BUG_READ_ONLY_ACTION_DENIED, ERROR );
}

access_ensure_bug_level( config_get( 'update_bug_threshold' ), $f_bug_id );

html_page_top( bug_format_summary( $f_bug_id, SUMMARY_CAPTION ) );

print_recently_visited();

$t_fields = config_get( 'bug_update_page_fields' );
$t_fields = columns_filter_disabled( $t_fields );

$tpl_bug_id = $f_bug_id;

$t_action_button_position = config_get( 'action_button_position' );

$tpl_top_buttons_enabled = $t_action_button_position == POSITION_TOP || $t_action_button_position == POSITION_BOTH;
$tpl_bottom_buttons_enabled = $t_action_button_position == POSITION_BOTTOM || $t_action_button_position == POSITION_BOTH;

$tpl_show_id = in_array( 'id', $t_fields );
$tpl_show_project = in_array( 'project', $t_fields );
$tpl_show_category = in_array( 'category_id', $t_fields );
$tpl_show_view_state = in_array( 'view_state', $t_fields );
$tpl_view_state = $tpl_show_view_state ? string_display_line( get_enum_element( 'view_state', $tpl_bug->view_state ) ) : '';
$tpl_show_date_submitted = in_array( 'date_submitted', $t_fields );
$tpl_date_submitted = $tpl_show_date_submitted ? date( config_get( 'normal_date_format' ), $tpl_bug->date_submitted ) : '';
$tpl_show_last_updated = in_array( 'last_updated', $t_fields );
$tpl_last_updated = $tpl_show_last_updated ? date( config_get( 'normal_date_format' ), $tpl_bug->last_updated ) : '';
$tpl_show_reporter = in_array( 'reporter', $t_fields );
$tpl_show_handler = in_array( 'handler', $t_fields );
$tpl_show_priority = in_array( 'priority', $t_fields );
$tpl_show_severity = in_array( 'severity', $t_fields );
$tpl_show_reproducibility = in_array( 'reproducibility', $t_fields );
$tpl_show_status = in_array( 'status', $t_fields );
$tpl_show_resolution = in_array( 'resolution', $t_fields );
$tpl_show_projection = in_array( 'projection', $t_fields ) && config_get( 'enable_projection' ) == ON;
$tpl_show_eta = in_array( 'eta', $t_fields ) && config_get( 'enable_eta' ) == ON;
$t_show_profiles = config_get( 'enable_profiles' ) == ON;
$tpl_show_platform = $t_show_profiles && in_array( 'platform', $t_fields );
$tpl_show_os = $t_show_profiles && in_array( 'os', $t_fields );
$tpl_show_os_version = $t_show_profiles && in_array( 'os_version', $t_fields );
$tpl_show_versions = version_should_show_product_version( $tpl_bug->project_id );
$tpl_show_product_version = $tpl_show_versions && in_array( 'product_version', $t_fields );
$tpl_show_product_build = $tpl_show_versions && in_array( 'product_build', $t_fields ) && ( config_get( 'enable_product_build' ) == ON );
$tpl_product_build_attribute = $tpl_show_product_build ? string_attribute( $tpl_bug->build ) : '';
$tpl_show_target_version = $tpl_show_versions && in_array( 'target_version', $t_fields ) && access_has_bug_level( config_get( 'roadmap_update_threshold' ), $tpl_bug_id );
$tpl_show_fixed_in_version = $tpl_show_versions && in_array( 'fixed_in_version', $t_fields );
$tpl_show_due_date = in_array( 'due_date', $t_fields ) && access_has_bug_level( config_get( 'due_date_view_threshold' ), $tpl_bug_id );
$tpl_show_summary = in_array( 'summary', $t_fields );
$tpl_summary_attribute = $tpl_show_summary ? string_attribute( $tpl_bug->summary ) : '';
$tpl_show_description = in_array( 'description', $t_fields );
$tpl_description_textarea = $tpl_show_description ? string_textarea( $tpl_bug->description ) : '';
$tpl_show_additional_information = in_array( 'additional_info', $t_fields );
$tpl_additional_information_textarea = $tpl_show_additional_information ? string_textarea( $tpl_bug->additional_information ) : '';
$tpl_show_steps_to_reproduce = in_array( 'steps_to_reproduce', $t_fields );
$tpl_steps_to_reproduce_textarea = $tpl_show_steps_to_reproduce ? string_textarea( $tpl_bug->steps_to_reproduce ) : '';
$tpl_handler_name = string_display_line( user_get_name( $tpl_bug->handler_id ) );

$tpl_can_change_view_state = $tpl_show_view_state && access_has_project_level( config_get( 'change_view_status_threshold' ) );

if ( $tpl_show_product_version ) {
	$tpl_product_version_released_mask = VERSION_RELEASED;

	if ( access_has_project_level( config_get( 'report_issues_for_unreleased_versions_threshold' ) ) ) {
		$tpl_product_version_released_mask = VERSION_ALL;
	}
}

$tpl_formatted_bug_id = $tpl_show_id ? bug_format_id( $f_bug_id ) : '';
$tpl_project_name = $tpl_show_project ? string_display_line( project_get_name( $tpl_bug->project_id ) ) : '';

$category_is_bug = $tpl_bug->category_id == 17;
$update_issue_table_columns = $category_is_bug ? 12 : 10;

echo '<br />';
echo '<form name="update_bug_form" method="post" action="bug_update.php">';
echo form_security_field( 'bug_update' );
echo '<table class="width100" cellspacing="0">';
echo '<tr class="header">';
echo '<td class="form-title" colspan="6">';
echo '<input type="hidden" name="bug_id" value="', $tpl_bug_id, '" />';
echo '<input type="hidden" name="update_mode" value="1" />';
echo lang_get( 'updating_bug_advanced_title' );
echo '</td><td class="right" colspan="' . ($update_issue_table_columns - 6) . '">';
print_bracket_link( string_get_bug_view_url( $tpl_bug_id ), lang_get( 'back_to_bug_link' ) );
echo '</td></tr>';

# Submit Button
if ( $tpl_top_buttons_enabled ) {
        echo '<tr><td class="center" colspan="' . $update_issue_table_columns . '">';
        echo '<input ', helper_get_tab_index(), ' type="submit" class="button" value="', lang_get( 'update_information_button' ), '" />';
        echo '</td></tr>';
}

event_signal( 'EVENT_UPDATE_BUG_FORM_TOP', array( $tpl_bug_id, true ) );

	# Labels
	echo '<tr>';
	echo '<th colspan="2">ID</th><th colspan="2">Time</th><th colspan="2">People</th>';
	if ($category_is_bug)
		echo '<th colspan="2">Environment</th>';
	echo '<th colspan="2">Class</th><th colspan="2">Progress</th>';
	echo '</tr>';

	if ( $tpl_show_id || $tpl_show_date_submitted || $tpl_show_reporter || $tpl_show_category || $tpl_show_status ) {
		echo '<tr class="bug-primary row-2">';
		# Bug ID
		echo '<td class="category" width="6%">', $tpl_show_id ? lang_get( 'issue_id' ) : '', '</td>';
		echo '<td class="center" width="8%">', $tpl_formatted_bug_id, '</td>';
		# Date Submitted
		echo '<td class="category" width="8%">', $tpl_show_date_submitted ? lang_get( 'date_submitted' ) : '', '</td>';
		echo '<td class="center" width="12%">', $tpl_date_submitted, '</td>';
		# Reporter
		echo '<td class="category" width="7%">', $tpl_show_reporter ? lang_get( 'reporter' ) : '', '</td>';
		echo '<td class="center" width="12%">';
		if ( $tpl_show_reporter ) {
			if ( ON == config_get( 'use_javascript' ) ) {
				$t_username = prepare_user_name( $tpl_bug->reporter_id );
				echo ajax_click_to_edit( $t_username, 'reporter_id', 'entrypoint=issue_reporter_combobox&issue_id=' . $tpl_bug_id );
			} else {
				echo '<select ', helper_get_tab_index(), ' name="reporter_id">';
				print_reporter_option_list( $tpl_bug->reporter_id, $tpl_bug->project_id );
				echo '</select>';
			}
		}
		echo '</td>';
		# Profile
		if ($category_is_bug) {
			echo '<td class="category disabled" width="12%">', lang_get( 'profile' ), '</td>';
			echo '<td width="6%"></td>';
		}
		# Category
		echo '<td class="category" width="7%">', $tpl_show_category ? lang_get( 'category' ) : '', '</td>';
		echo '<td class="center" width="8%">';
		if ( $tpl_show_category ) {
			echo '<select ', helper_get_tab_index(), ' name="category_id">';
			print_category_option_list( $tpl_bug->category_id, $tpl_bug->project_id );
			echo '</select>';
		}
		echo '</td>';
		# Status
		if ( $tpl_show_status ) {
			echo '<td class="category" width="6%" rowspan="2">', $tpl_show_status ? lang_get( 'status' ) : '', '</td>';
			echo '<td class="center" bgcolor="', get_status_color( $tpl_bug->status ), '" width="8%" rowspan="2">';
			print_status_option_list( 'status', $tpl_bug->status,
								( $tpl_bug->reporter_id == auth_get_current_user_id() &&
										( ON == config_get( 'allow_reporter_close' ) ) ), $tpl_bug->project_id );
			echo '</td>';
		}
		echo '</tr>';
	}

	if ( $tpl_show_project || $tpl_show_last_updated || $tpl_show_handler || $tpl_show_platform ) {
		echo '<tr class="bug-primary row-1">';
		# Project
		echo '<td class="category">', $tpl_show_project ? lang_get( 'email_project' ) : '', '</td>';
		echo '<td class="center">', $tpl_project_name, '</td>';
		# Date Updated
		echo '<td class="category">', $tpl_show_last_updated ? lang_get( 'last_update' ) : '','</td>';
		echo '<td class="center">', $tpl_last_updated, '</td>';
		# Handler
		echo '<td class="category">', $tpl_show_handler ? lang_get( 'assigned_to' ) : '', '</td>';
		echo '<td class="center">';
		if ( access_has_project_level( config_get( 'update_bug_assign_threshold', config_get( 'update_bug_threshold' ) ) ) ) {
			echo '<select ', helper_get_tab_index(), ' name="handler_id">';
			echo '<option value="0"></option>';
			print_assign_to_option_list( $tpl_bug->handler_id, $tpl_bug->project_id );
			echo '</select>';
		} else {
			echo $tpl_handler_name;
		}
		echo '</td>';
		# Platform (now Device/Profile)
		if ($category_is_bug) {
			echo '<td class="category">', $tpl_show_platform ? lang_get( 'platform' ) : '', '</td>';
			echo '<td class="center">';
			if ( $tpl_show_platform ) {
				if ( config_get( 'allow_freetext_in_profile_fields' ) == OFF ) {
					echo '<select name="platform"><option value=""></option>';
					print_platform_option_list( $tpl_bug->platform );
					echo '</select>';
				} else {
					projax_autocomplete( 'platform_get_with_prefix', 'platform', array( 'value' => $tpl_bug->platform, 'size' => '16', 'maxlength' => '32', 'tabindex' => helper_get_tab_index_value() ) );
				}
			}
			echo '</td>';
		}
		# Type (Custom field)
		$t_type_def = custom_field_get_definition( 1 ); // Type
		$tpl_show_type = ( $t_type_def['display_update'] || $t_type_def['require_update'] ) && custom_field_has_write_access( 1, $tpl_bug_id );
		echo '<td class="category">', $t_type_def['require_update'] ? '<span class="required">*</span>' : '', $tpl_show_type ? string_display( lang_get_defaulted( $t_type_def['name'] ) ) : '', '</td>';
		echo '<td class="center">';
		if ( $tpl_show_type) # has read access
			//print_custom_field_value( $t_type_def, 1, $f_bug_id );
			print_custom_field_input( $t_type_def, $tpl_bug_id );
		echo '</td>';
		echo '</tr>';
	}

	if ( $tpl_show_view_state || $tpl_show_due_date || $tpl_show_handler || $tpl_show_os || $tpl_show_severity || $tpl_show_priority ) {
		echo '<tr class="bug-primary row-2">';
		# View Status (Visibility)
		echo '<td class="category">', $tpl_show_view_state ? lang_get( 'view_status' ) : '', '</td>';
		echo '<td class="center">';
		if ( $tpl_can_change_view_state ) {
			echo '<select ', helper_get_tab_index(), ' name="view_state">';
			print_enum_string_option_list( 'view_state', $tpl_bug->view_state);
			echo '</select>';
		} else if ( $tpl_show_view_state )
			echo $tpl_view_state;
		echo '</td>';
		# Due Date
		echo '<td class="category">', $tpl_show_due_date ? lang_get( 'due_date' ) : '', '</td>';
		if ( $tpl_show_due_date ) {
			if ( bug_is_overdue( $tpl_bug_id ) )
				echo '<td class="overdue center">';
			else
				echo '<td class="center">';
			if ( access_has_bug_level( config_get( 'due_date_update_threshold' ), $tpl_bug_id ) ) {
				$t_date_to_display = '';
				if ( !date_is_null( $tpl_bug->due_date ) )
					$t_date_to_display = date( config_get( 'calendar_date_format' ), $tpl_bug->due_date );
				echo '<input ', helper_get_tab_index(), ' type="text" id="due_date" name="due_date" size="13" maxlength="16" value="', $t_date_to_display, '">';
				date_print_calendar();
				date_finish_calendar( 'due_date', 'trigger');
			} else {
				if ( !date_is_null( $tpl_bug->due_date ) )
					echo date( config_get( 'short_date_format' ), $tpl_bug->due_date );
			}
		} else
			echo '<td />';
		echo '';
		# Notes By
		echo '<td class="category">', 'Notes By', '</td>';
		echo '<td class="center">';
		include( $tpl_mantis_dir . 'bugnote_userlist_inc.php' );
		echo '</td>';
		# Operating System (now OS and Version)
		if ($category_is_bug) {
			echo '<td class="category">', $tpl_show_os ? lang_get( 'os' ) : '', '</td>';
			echo '<td class="center">';
			if ( $tpl_show_os ) {
				if ( config_get( 'allow_freetext_in_profile_fields' ) == OFF ) {
					echo '<select name="os"><option value=""></option>';
					print_os_option_list( $tpl_bug->os );
					echo '</select>';
				} else
					projax_autocomplete( 'os_get_with_prefix', 'os', array( 'value' => $tpl_bug->os, 'size' => '16', 'maxlength' => '32', 'tabindex' => helper_get_tab_index_value() ) );
			}
			echo '</td>';
		}
		# Severity
		echo '<td class="category">', $tpl_show_severity ? lang_get( 'severity' ) : '', '</td>';
		echo '<td class="center">';
		if ( $tpl_show_severity ) {
			echo '<select ', helper_get_tab_index(), ' name="severity">';
			print_enum_string_option_list( 'severity', $tpl_bug->severity );
			echo '</select>';
		}
		echo '</td>';
		# Priority
		echo '<td class="category">', $tpl_show_priority ? lang_get( 'priority' ) : '', '</td>';
		echo '<td class="center">';
		if ( $tpl_show_priority ) {
			echo '<select ', helper_get_tab_index(), ' name="priority">';
			print_enum_string_option_list( 'priority', $tpl_bug->priority );
			echo '</select>';
		}
		echo '</td>';
		echo '</tr>';
	}

	if ( $tpl_show_eta || $tpl_show_os_version || $tpl_show_reproducibility || $tpl_show_resolution) {
		echo '<tr class="bug-primary row-1">';
		# No. of Notes
		echo '<td class="category">', lang_get( 'notes' ), '</td>';
		echo '<td class="center">', $t_bugnote_count == 0 ? lang_get( 'none' ) : $t_bugnote_count, '</td>';
		# ETA
		echo '<td class="category">', $tpl_show_eta ? lang_get( 'eta' ) : '', '</td>';
		echo '<td class="center">';
		if ( $tpl_show_eta ) {
			echo '<select ', helper_get_tab_index(), ' name="eta">';
			print_enum_string_option_list( 'eta', $tpl_bug->eta );
			echo '</select>';
		}
		echo '</td>';
		# User list monitoring the bug
		echo '<td class="category disabled">', lang_get( 'users_monitoring_bug' ), '</td>';
		echo '<td class="center">';
//		include( $tpl_mantis_dir . 'bug_monitor_list_view_inc.php' );
		echo '</td>';
		# OS Version (now Browser)
		if ($category_is_bug) {
			echo '<td class="category">', $tpl_show_os_version ? lang_get( 'os_version' ) : '', '</td>';
			echo '<td class="center">';
			if ( $tpl_show_os_version ) {
				if ( config_get( 'allow_freetext_in_profile_fields' ) == OFF ) {
					echo '<select name="os_build"><option value=""></option>';
					print_os_build_option_list( $tpl_bug->os_build );
					echo '</select>';
				} else {
					projax_autocomplete( 'os_build_get_with_prefix', 'os_build', array( 'value' => $tpl_bug->os_build, 'size' => '16', 'maxlength' => '16', 'tabindex' => helper_get_tab_index_value() ) );
				}
			}
			echo '</td>';
		}
		# Reproducibility
		echo '<td class="category">', $tpl_show_reproducibility ? lang_get( 'reproducibility' ) : '', '</td>';
		echo '<td class="center">';
		if ( $tpl_show_reproducibility ) {
			echo '<select ', helper_get_tab_index(), ' name="reproducibility">';
			print_enum_string_option_list( 'reproducibility', $tpl_bug->reproducibility );
			echo '</select>';
		}
		echo '</td>';
		# Resolution
		echo '<td class="category">', $tpl_show_resolution ? lang_get( 'resolution' ) : '', '</td>';
		echo '<td class="center">';
		if ( $tpl_show_resolution ) {
			# Resolution
			echo '<select ', helper_get_tab_index(), ' name="resolution">';
			print_enum_string_option_list( "resolution", $tpl_bug->resolution );
			echo '</select>';
		}
		echo '</td>';
		echo '</tr>';
	}

	if ( $tpl_show_product_version || $tpl_show_product_build || $tpl_show_target_version || $tpl_show_fixed_in_version || $tpl_show_projection ) {
		echo '<tr class="bug-primary row-2">';
		# Product Version
		echo '<td class="category">', $tpl_show_product_version ? lang_get( 'product_version' ) : '', '</td>';
		echo '<td class="center">';
		if ( $tpl_show_product_version ) {
			echo '<select ', helper_get_tab_index(), ' name="version">';
			print_version_option_list( $tpl_bug->version, $tpl_bug->project_id, $tpl_product_version_released_mask );
			echo '</select>';
		}
		echo '</td>';
		# Product Build
		echo '<td class="category">', $tpl_show_product_build ? lang_get( 'product_build' ) : '', '</td>';
		echo '<td class="center">';
		if ( $tpl_show_product_build )
			echo '<input type="text" name="build" size="16" maxlength="32" value="', $tpl_product_build_attribute, '" />';
		echo '</td>';
		# Target Version
		echo '<td class="category">', $tpl_show_target_version ? lang_get( 'target_version' ) : '', '</td>';
		echo '<td class="center">';
		if ( $tpl_show_target_version ) {
			echo '<select ', helper_get_tab_index(), ' name="target_version">';
			print_version_option_list( $tpl_bug->target_version, $tpl_bug->project_id, VERSION_ALL );
			echo '</select>';
		}
		echo '</td>';
		# Fixed in Version
		echo '<td class="category">', $tpl_show_fixed_in_version ? lang_get( 'fixed_in_version' ) : '', '</td>';
		echo '<td class="center">';
		if ( $tpl_show_fixed_in_version ) {
			echo '<select ', helper_get_tab_index(), ' name="fixed_in_version">';
			print_version_option_list( $tpl_bug->fixed_in_version, $tpl_bug->project_id, VERSION_ALL );
			echo '</select>';
		}
		echo '</td>';
		# Projection
		echo '<td class="category">', $tpl_show_projection ? lang_get( 'projection' ) : '', '</td>';
		echo '<td class="center">';
		if ( $tpl_show_projection ) {
			echo '<select name="projection">';
			print_enum_string_option_list( 'projection', $tpl_bug->projection );
			echo '</select>';
		}
		echo '</td>';
		echo '</tr>';
	}

event_signal( 'EVENT_UPDATE_BUG_FORM', array( $tpl_bug_id, true ) );

echo '<tr class="padded-spacer"><td colspan="' . $update_issue_table_columns . '" style="padding:2px;" /></tr>';

# Summary
if ( $tpl_show_summary ) {
	echo '<tr class="row-2">';
	echo '<td class="category" colspan="2">', lang_get( 'summary' ), '</td>';
	echo '<td colspan="' . ($update_issue_table_columns - 2) . '">', '<input ', helper_get_tab_index(), ' type="text" name="summary" size="105" maxlength="128" value="', $tpl_summary_attribute, '" />';
	echo '</td></tr>';
}

# Description
if ( $tpl_show_description ) {
	echo '<tr ', helper_alternate_class(), '>';
	echo '<td class="category" colspan="2">', lang_get( 'description' ), '</td>';
	echo '<td colspan="' . ($update_issue_table_columns - 2) . '">';
	echo '<textarea ', helper_get_tab_index(), ' cols="80" rows="10" name="description">', $tpl_description_textarea, '</textarea>';
	echo '</td></tr>';
}

# Steps to Reproduce
if ( $tpl_show_steps_to_reproduce ) {
	echo '<tr ', helper_alternate_class(), '>';
	echo '<td class="category" colspan="2">', lang_get( 'steps_to_reproduce' ), '</td>';
	echo '<td colspan="' . ($update_issue_table_columns - 2) . '">';
	echo '<textarea ', helper_get_tab_index(), ' cols="80" rows="10" name="steps_to_reproduce">', $tpl_steps_to_reproduce_textarea, '</textarea>';
	echo '</td></tr>';
}

# Additional Information
if ( $tpl_show_additional_information ) {
	echo '<tr ', helper_alternate_class(), '>';
	echo '<td class="category" colspan="2">', lang_get( 'additional_information' ), '</td>';
	echo '<td colspan="' . ($update_issue_table_columns - 2) . '">';
	echo '<textarea ', helper_get_tab_index(), ' cols="80" rows="10" name="additional_information">', $tpl_additional_information_textarea, '</textarea>';
	echo '</td></tr>';
}

# Custom Fields
$t_related_custom_field_ids = custom_field_get_linked_ids( $tpl_bug->project_id );

foreach ( $t_related_custom_field_ids as $t_id ) {
	if ($t_id != 1) {
		$t_def = custom_field_get_definition( $t_id );
		if ( ( $t_def['display_update'] || $t_def['require_update'] ) && custom_field_has_write_access( $t_id, $tpl_bug_id ) ) {
			echo '<tr ', helper_alternate_class(), '>';
			echo '<td class="category" colspan="2">';
			if ( $t_def['require_update'] ) {
				echo '<span class="required">*</span>';
			}

			echo string_display( lang_get_defaulted( $t_def['name'] ) );
			echo '</td><td colspan="' . ($update_issue_table_columns - 2) . '">';
			print_custom_field_input( $t_def, $tpl_bug_id );
			echo '</td></tr>';
		}
	}
} # foreach( $t_related_custom_field_ids as $t_id )

# Bugnote Text Box
echo '<tr ', helper_alternate_class(), '>';
echo '<td class="category" colspan="2">', lang_get( 'add_bugnote_title' ), '</td>';
echo '<td colspan="' . ($update_issue_table_columns - 2) . '"><textarea ', helper_get_tab_index(), ' name="bugnote_text" cols="80" rows="10"></textarea></td></tr>';

# Bugnote Private Checkbox (if permitted)
if ( access_has_bug_level( config_get( 'private_bugnote_threshold' ), $tpl_bug_id ) ) {
	echo '<tr ', helper_alternate_class(), '>';
	echo '<td class="category" colspan="2">', lang_get( 'private' ), '</td>';
	echo '<td colspan="' . ($update_issue_table_columns - 2) . '">';

	$t_default_bugnote_view_status = config_get( 'default_bugnote_view_status' );
	if ( access_has_bug_level( config_get( 'set_view_status_threshold' ), $tpl_bug_id ) ) {
		echo '<input ', helper_get_tab_index(), ' type="checkbox" name="private" ', check_checked( config_get( 'default_bugnote_view_status' ), VS_PRIVATE ), ' />';
		echo lang_get( 'private' );
	} else {
		echo get_enum_element( 'view_state', $t_default_bugnote_view_status );
	}

	echo '</td></tr>';
}

# Time Tracking (if permitted)
if ( config_get('time_tracking_enabled') ) {
	if ( access_has_bug_level( config_get( 'time_tracking_edit_threshold' ), $tpl_bug_id ) ) {
		echo '<tr ', helper_alternate_class(), '>';
		echo '<td class="category" colspan="2">', lang_get( 'time_tracking' ), ' (HH:MM)</td>';
		echo '<td colspan="' . ($update_issue_table_columns - 2) . '"><input type="text" name="time_tracking" size="5" value="0:00" /></td></tr>';
	}
}

event_signal( 'EVENT_BUGNOTE_ADD_FORM', array( $tpl_bug_id ) );

# Submit Button
if ( $tpl_bottom_buttons_enabled ) {
       echo '<tr class="footer"><td class="center" colspan="' . $update_issue_table_columns . '">';
       echo '<input ', helper_get_tab_index(), ' type="submit" class="button" value="', lang_get( 'update_information_button' ), '" />';
       echo '</td></tr>';
}

echo '</table></form>';

include( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'bugnote_view_inc.php' );
html_page_bottom();

last_visited_issue( $tpl_bug_id );
