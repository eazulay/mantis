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

# In general the value OFF means the feature is disabled and ON means the
# feature is enabled.  Any other cases will have an explanation.

# Look in http://www.mantisbt.org/docs/ or config_defaults_inc.php for more
# detailed comments.

if ( file_exists( dirname( __FILE__ ).DIRECTORY_SEPARATOR.'config_db_inc.php' ) )
	require_once( dirname( __FILE__ ).DIRECTORY_SEPARATOR.'config_db_inc.php' );

# --- Anonymous Access / Signup ---
$g_allow_signup				= OFF;
$g_allow_anonymous_login	= OFF;
$g_anonymous_account		= '';

# --- Email Configuration ---
$g_email_receive_own	= OFF;

/**
 * email separator and padding
 * Default is 28
 */
$g_email_padding_length	= 14;
$g_email_separator1		= str_pad('', 34, '=');
$g_email_separator2		= str_pad('', 114, '—');

# --- Attachments / File Uploads ---
$g_allow_file_upload	= ON;
$g_file_upload_method	= DISK; # or DATABASE
$g_max_file_size		= 5000000;	# in bytes
$g_preview_attachments_inline_max_size = 256 * 1024;
$g_allowed_files		= '';		# extensions comma separated, e.g. 'php,html,java,exe,pl'
$g_disallowed_files		= 'php,php3,phtml,html,class,java,exe,pl,erp,dat';	# extensions comma separated
$g_allow_delete_own_attachments = ON;
$g_preview_max_height	= 500;
$g_preview_max_width    = '100%';
$g_allow_file_cache     = ON;

# --- Branding ---
$g_window_title			= 'Get-It-Write Tracking System';
$g_logo_image			= 'images/mantis_logo_small.gif';
$g_favicon_image		= 'images/favicon.ico';

# --- Real names ---
$g_show_realname = OFF;
$g_show_user_realname_threshold = VIEWER;	# Set to access level (e.g. VIEWER, REPORTER, DEVELOPER, MANAGER, NOBODY, etc)

# --- Statuses ---
$g_status_enum_string = '10:new,20:feedback,30:acknowledged,35:approval,40:confirmed,50:assigned,55:progress,70:test,80:resolved,75:recurring,65:fail,85:hold,90:closed';
$g_resolution_enum_string = '10:open,20:fixed,25:query replied,28:info not supplied,30:reopened,40:unable to duplicate,50:not fixable,60:duplicate,70:not a bug,80:suspended,90:wont fix';
$g_status_colors['new'] = '#FFE6E5';
$g_status_colors['feedback'] = '#F4E1F9'; // Info Required
$g_status_colors['acknowledged'] = '#FCEBF3'; // Info Returned
$g_status_colors['approval'] = '#BAD7D9'; // Approval Required
$g_status_colors['confirmed'] = '#E1F9E5'; // Approved
$g_status_colors['assigned'] = '#FDF5D3';
$g_status_colors['resolved'] = '#ECF9CF';
$g_status_colors['closed'] = '#F5F5EF';
$g_status_colors['test'] = '#FDFEE5';
$g_status_colors['fail'] = '#FED4D3';
$g_status_colors['progress'] = '#E1E9F9';
$g_status_colors['recurring'] = '#E1F2F9';
$g_status_colors['hold'] = '#E7E3E7';
$g_status_enum_workflow[NEW_]= '20:feedback,40:confirmed,35:approval,50:assigned';
$g_status_enum_workflow[FEEDBACK] = '30:acknowledged,35:approval,40:confirmed,50:assigned,55:progress,75:recurring,85:hold';
$g_status_enum_workflow[ACKNOWLEDGED] = '20:feedback,35:approval,40:confirmed,50:assigned,55:progress,65:fail,80:resolved,75:recurring,70:test,85:hold,90:closed';
$g_status_enum_workflow[CONFIRMED] = '20:feedback,35:approval,50:assigned,55:progress,80:resolved,75:recurring,70:test,85:hold';
$g_status_enum_workflow[APPROVAL] = '20:feedback,40:confirmed,85:hold,90:closed';
$g_status_enum_workflow[ASSIGNED] = '20:feedback,35:approval,40:confirmed,55:progress,70:test,80:resolved,75:recurring,85:hold';
$g_status_enum_workflow[PROGRESS] = '20:feedback,50:assigned,70:test,80:resolved,75:recurring';
$g_status_enum_workflow[TEST] = '20:feedback,50:assigned,55:progress,80:resolved,75:recurring,85:hold';
$g_status_enum_workflow[RESOLVED] = '20:feedback,50:assigned,55:progress,70:test,75:recurring,65:fail,85:hold,90:closed';
$g_status_enum_workflow[RECURRING] = '20:feedback,50:assigned,55:progress,85:hold,70:test,80:resolved,65:fail';
$g_status_enum_workflow[FAIL] = '20:feedback,50:assigned,55:progress,80:resolved,75:recurring,85:hold,90:closed';
$g_status_enum_workflow[HOLD] = '10:new,20:feedback,30:acknowledged,35:approval,40:confirmed,50:assigned,55:progress,70:test,80:resolved,75:recurring,65:fail,90:closed';
$g_status_enum_workflow[CLOSED] = '20:feedback,50:assigned,55:progress,80:resolved,75:recurring,65:fail';

$g_bug_acknowledged_status = ACKNOWLEDGED;

$g_severity_enum_string = '10:unspecified,20:nice_to_have,50:important,60:critical,70:crash,80:block';
//$g_severity_enum_string = '10:feature,20:trivial,30:text,40:tweak,50:minor,60:major,70:crash,80:block';


# --- Others ---
$g_default_home_page = 'main_page.php';	# Set to name of page to go to after login

$g_reauthentication_expiry = 20*60;

/**
 * Enable or disable usage of the ETA field.
 * @global int $g_enable_eta
 */
$g_enable_eta = ON;

/**
 * Enable or disable usage of the Projection field.
 * @global int $g_enable_projection
 */
$g_enable_projection = OFF;

/**
 * Enable or disable usage of the Product Build field.
 * @global int $g_enable_product_build
 */
$g_enable_product_build = ON;
/**

 * threshold to update due date submitted
 * @global int $g_due_date_update_threshold
 */
$g_due_date_update_threshold = REPORTER;

/**
 * threshold to see due date
 * @global int $g_due_date_view_threshold
 */
$g_due_date_view_threshold = REPORTER;

/**
 * Access level needed to add other users to the list of users monitoring
 * a bug.
 * Look in the constant_inc.php file if you want to set a different value.
 * @global int $g_monitor_add_others_bug_threshold
 */
$g_monitor_add_others_bug_threshold = UPDATER;

/**
 * Threshold needed to be able to use stored queries
 * @global int $g_stored_query_use_threshold
 */
$g_stored_query_use_threshold = VIEWER;

/*****************
 * Time tracking *
 *****************/

/**
 * Turn on Time Tracking accounting
 * @global int $g_time_tracking_enabled
 */
$g_time_tracking_enabled = ON;

/**
 * A billing sums
 * @global int $g_time_tracking_with_billing
 */
$g_time_tracking_with_billing = ON;

/**
 * Stop watch to build time tracking field
 * @global int $g_time_tracking_stopwatch
 */
$g_time_tracking_stopwatch = ON;

/**
 * access level required to view time tracking information
 * @global int $g_time_tracking_view_threshold
 */
$g_time_tracking_view_threshold = DEVELOPER;

/**
 * access level required to add/edit time tracking information
 * @global int $g_time_tracking_edit_threshold
 */
$g_time_tracking_edit_threshold = DEVELOPER;

/**
 * access level required to run reports
 * @global int $g_time_tracking_reporting_threshold
 */
$g_time_tracking_reporting_threshold = MANAGER;

/**
 * allow time tracking to be recorded without a bugnote
 * @global int $g_time_tracking_without_note
 */
$g_time_tracking_without_note = ON;

/* Date Format */
$g_short_date_format = 'd/m/Y';
$g_normal_date_format = 'j M Y \a\t H:i';
$g_complete_date_format = 'd-m-y H:i T';
$g_default_timezone = 'Europe/Paris';

/**
 * These are the valid html tags for multi-line fields (e.g. description)
 * do NOT include href or img tags here
 * do NOT include tags that have parameters (eg. <font face="arial">)
 * @global string $g_html_valid_tags
 */
$g_html_valid_tags		= 'p, li, ul, ol, br, pre, i, b, u, em, strong';

/* Default settings */
$g_default_bug_severity = UNSPECIFIED;
$g_default_bugnote_order = 'DESC';
$g_default_bug_reproducibility = REPRODUCIBILITY_NOTAPPLICABLE;
$g_filter_custom_fields_per_row = 6;
$g_default_bug_priority = NONE;
$g_bug_readonly_status_threshold = CLOSED;
$g_update_readonly_bug_threshold = UPDATER;
$g_private_bugnote_threshold = MANAGER;
$g_reminder_receive_threshold = VIEWER;

/* Enable/disable features */
$g_show_project_menu_bar = ON;
$g_form_security_validation = OFF;

$g_css_include_file = 'css/default.css?v=20201202b';
