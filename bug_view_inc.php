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
	 * This include file prints out the bug information
	 * $f_bug_id MUST be specified before the file is included
	 *
	 * @package MantisBT
	 * @copyright Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
	 * @copyright Copyright (C) 2002 - 2011  MantisBT Team - mantisbt-dev@lists.sourceforge.net
	 * @link http://www.mantisbt.org
	 */

	if ( !defined( 'BUG_VIEW_INC_ALLOW' ) ) {
		access_denied();
	}

	 /**
	  * MantisBT Core API's
	  */
	require_once('core.php');
	require_once('bug_api.php');
	require_once('custom_field_api.php');
	require_once('file_api.php');
	require_once('date_api.php');
	require_once('relationship_api.php');
	require_once('last_visited_api.php');
	require_once('tag_api.php');
	require_once('bugnote_api.php');
	require_once('project_hierarchy_api.php');
	require_once('history_api.php');
	$f_bug_id = gpc_get_int( 'id' );

	bug_ensure_exists( $f_bug_id );

	$tpl_bug = bug_get( $f_bug_id, true );

	# In case the current project is not the same project of the bug we are
	# viewing, override the current project. This ensures all config_get and other
	# per-project function calls use the project ID of this bug.
	$g_project_override = $tpl_bug->project_id;

	access_ensure_bug_level( VIEWER, $f_bug_id );

	$f_history = gpc_get_bool( 'history', config_get( 'history_default_visible' ) );

	$t_fields = config_get( $tpl_fields_config_option );
	$t_fields = (array)columns_filter_disabled( $t_fields );

	compress_enable();

	if ( $tpl_show_page_header ) {
		html_page_top( bug_format_summary( $f_bug_id, SUMMARY_CAPTION ) );
		print_recently_visited();
	}

	$t_action_button_position = config_get( 'action_button_position' );

	$t_bugslist = gpc_get_cookie( config_get( 'bug_list_cookie' ), false );

	$tpl_show_versions = version_should_show_product_version( $tpl_bug->project_id );
	$tpl_show_product_version = $tpl_show_versions && in_array( 'product_version', $t_fields );
	$tpl_show_fixed_in_version = $tpl_show_versions && in_array( 'fixed_in_version', $t_fields );
	$tpl_show_product_build = $tpl_show_versions && in_array( 'product_build', $t_fields )
		&& ( config_get( 'enable_product_build' ) == ON );
	$tpl_product_build = $tpl_show_product_build ? string_display_line( $tpl_bug->build ) : '';
	$tpl_show_target_version = $tpl_show_versions && in_array( 'target_version', $t_fields )
		&& access_has_bug_level( config_get( 'roadmap_view_threshold' ), $f_bug_id );

	$tpl_product_version_string  = '';
	$tpl_target_version_string   = '';
	$tpl_fixed_in_version_string = '';

	if ( $tpl_show_product_version || $tpl_show_fixed_in_version || $tpl_show_target_version ) {
		$t_version_rows = version_get_all_rows( $tpl_bug->project_id );

		if ( $tpl_show_product_version ) {
			$tpl_product_version_string  = prepare_version_string( $tpl_bug->project_id, version_get_id( $tpl_bug->version, $tpl_bug->project_id ), $t_version_rows );
		}

		if ( $tpl_show_target_version ) {
			$tpl_target_version_string   = prepare_version_string( $tpl_bug->project_id, version_get_id( $tpl_bug->target_version, $tpl_bug->project_id) , $t_version_rows );
		}

		if ( $tpl_show_fixed_in_version ) {
			$tpl_fixed_in_version_string = prepare_version_string( $tpl_bug->project_id, version_get_id( $tpl_bug->fixed_in_version, $tpl_bug->project_id ), $t_version_rows );
		}
	}

	$tpl_product_version_string = string_display_line( $tpl_product_version_string );
	$tpl_target_version_string = string_display_line( $tpl_target_version_string );
	$tpl_fixed_in_version_string = string_display_line( $tpl_fixed_in_version_string );

	$tpl_bug_id = $f_bug_id;
	$tpl_form_title = lang_get( 'bug_view_title' );
	$tpl_wiki_link = config_get_global( 'wiki_enable' ) == ON ? 'wiki.php?id=' . $f_bug_id : '';

	if ( access_has_bug_level( config_get( 'view_history_threshold' ), $f_bug_id ) )
		$tpl_history_link = "view.php?id=$f_bug_id&history=1#history";
	else
		$tpl_history_link = '';

	$tpl_show_reminder_link = !current_user_is_anonymous() && !bug_is_readonly( $f_bug_id ) &&
		  access_has_bug_level( config_get( 'bug_reminder_threshold' ), $f_bug_id );
	$tpl_bug_reminder_link = 'bug_reminder_page.php?bug_id=' . $f_bug_id;

	$tpl_print_link = 'print_bug_page.php?bug_id=' . $f_bug_id;

	$tpl_top_buttons_enabled = !$tpl_force_readonly && ( $t_action_button_position == POSITION_TOP || $t_action_button_position == POSITION_BOTH );
	$tpl_bottom_buttons_enabled = !$tpl_force_readonly && ( $t_action_button_position == POSITION_BOTTOM || $t_action_button_position == POSITION_BOTH );

	$tpl_show_project = in_array( 'project', $t_fields );
	$tpl_project_name = $tpl_show_project ? string_display_line( project_get_name( $tpl_bug->project_id ) ): '';
	$tpl_is_root_project = project_hierarchy_is_toplevel($tpl_bug->project_id);

	$tpl_show_id = in_array( 'id', $t_fields );
	$tpl_formatted_bug_id = $tpl_show_id ? string_display_line( bug_format_id( $f_bug_id ) ) : '';

	$tpl_show_date_submitted = in_array( 'date_submitted', $t_fields );
	$tpl_date_submitted = $tpl_show_date_submitted ? date( config_get( 'normal_date_format' ), $tpl_bug->date_submitted ) : '';

	$tpl_show_last_updated = in_array( 'last_updated', $t_fields );
	$tpl_last_updated = $tpl_show_last_updated ? date( config_get( 'normal_date_format' ), $tpl_bug->last_updated ) : '';

	$tpl_show_tags = in_array( 'tags', $t_fields ) && access_has_global_level( config_get( 'tag_view_threshold' ) );

	$tpl_bug_overdue = bug_is_overdue( $f_bug_id );

	$tpl_show_view_state = in_array( 'view_state', $t_fields );
	$tpl_bug_view_state_enum = $tpl_show_view_state ? string_display_line( get_enum_element( 'view_state', $tpl_bug->view_state ) ) : '';

	$tpl_show_due_date = in_array( 'due_date', $t_fields ) && access_has_bug_level( config_get( 'due_date_view_threshold' ), $f_bug_id );

	if ( $tpl_show_due_date ) {
		if ( !date_is_null( $tpl_bug->due_date ) ) {
			$tpl_bug_due_date = date( config_get( 'normal_date_format' ), $tpl_bug->due_date );
		} else {
			$tpl_bug_due_date = '';
		}
	}

	$tpl_show_reporter = in_array( 'reporter', $t_fields );
	$tpl_show_handler = in_array( 'handler', $t_fields ) && access_has_bug_level( config_get( 'view_handler_threshold' ), $f_bug_id );
	$tpl_show_additional_information = !is_blank( $tpl_bug->additional_information ) && in_array( 'additional_info', $t_fields );
	$tpl_show_steps_to_reproduce = !is_blank( $tpl_bug->steps_to_reproduce ) && in_array( 'steps_to_reproduce', $t_fields );
	$tpl_show_monitor_box = !$tpl_force_readonly;
	$tpl_show_relationships_box = !$tpl_force_readonly;
	$tpl_show_upload_form = !$tpl_force_readonly && !bug_is_readonly( $f_bug_id );
	$tpl_show_history = $f_history;
	$tpl_show_profiles = config_get( 'enable_profiles' );
	$tpl_show_platform = $tpl_show_profiles && in_array( 'platform', $t_fields );
	$tpl_platform = $tpl_show_platform ? string_display_line( $tpl_bug->platform ) : '';
	$tpl_show_os = $tpl_show_profiles && in_array( 'os', $t_fields );
	$tpl_os = $tpl_show_os ? string_display_line( $tpl_bug->os ) : '';
	$tpl_show_os_version = $tpl_show_profiles && in_array( 'os_version', $t_fields );
	$tpl_os_version = $tpl_show_os_version ? string_display_line( $tpl_bug->os_build ) : '';
	$tpl_show_projection = in_array( 'projection', $t_fields );
	$tpl_projection = $tpl_show_projection ? string_display_line( get_enum_element( 'projection', $tpl_bug->projection ) ) : '';
	$tpl_show_eta = in_array( 'eta', $t_fields );
	$tpl_eta = $tpl_show_eta ? string_display_line( get_enum_element( 'eta', $tpl_bug->eta ) ) : '';
	$tpl_show_attachments = in_array( 'attachments', $t_fields );
	$tpl_can_attach_tag = $tpl_show_tags && !$tpl_force_readonly && access_has_bug_level( config_get( 'tag_attach_threshold' ), $f_bug_id );
	$tpl_show_category = in_array( 'category_id', $t_fields );
	$tpl_category = $tpl_show_category ? string_display_line( category_get_name( $tpl_bug->category_id ) ) : ''; // Changed from category_full_name($tpl_bug->category_id)
	$tpl_show_priority = in_array( 'priority', $t_fields );
	$tpl_priority = $tpl_show_priority ? string_display_line( get_enum_element( 'priority', $tpl_bug->priority ) ) : '';
	$tpl_show_severity = in_array( 'severity', $t_fields );
	$tpl_severity = $tpl_show_severity ? string_display_line( get_enum_element( 'severity', $tpl_bug->severity ) ) : '';
	$tpl_show_reproducibility = in_array( 'reproducibility', $t_fields );
	$tpl_reproducibility = $tpl_show_reproducibility ? string_display_line( get_enum_element( 'reproducibility', $tpl_bug->reproducibility ) ): '';
	$tpl_show_status = in_array( 'status', $t_fields );
	$tpl_status = $tpl_show_status ? string_display_line( get_enum_element( 'status', $tpl_bug->status ) ) : '';
	$tpl_show_resolution = in_array( 'resolution', $t_fields );
	$tpl_resolution = $tpl_show_resolution ? string_display_line( get_enum_element( 'resolution', $tpl_bug->resolution ) ) : '';
	$tpl_show_summary = in_array( 'summary', $t_fields );
	$tpl_show_description = in_array( 'description', $t_fields );

	$tpl_summary = $tpl_show_summary ? bug_format_summary( $f_bug_id, SUMMARY_FIELD ) : '';
	$tpl_description = $tpl_show_description ? string_display_links( $tpl_bug->description ) : '';
	$tpl_steps_to_reproduce = $tpl_show_steps_to_reproduce ? string_display_links( $tpl_bug->steps_to_reproduce ) : '';
	$tpl_additional_information = $tpl_show_additional_information ? string_display_links( $tpl_bug->additional_information ) : '';
	$t_feedback = config_get( 'bug_feedback_status' );

	$t_all_bugnotes = bugnote_get_all_bugnotes($f_bug_id);
	$t_bugnote_count = count( $t_all_bugnotes );

	$submitter = history_get_first_reporter( $f_bug_id );

	$tpl_links = event_signal( 'EVENT_MENU_ISSUE', $f_bug_id );

	#
	# Start of Template
	#

	# Fixed section
	echo '<div id="fixed_scroll" class="hidden-first">';
	echo '<table cellspacing="0">';
	$t_type_def = custom_field_get_definition( 1 );
	$tpl_show_type = custom_field_has_read_access( 1, $f_bug_id );
	echo '<tr>';
	# Project
	echo '<td class="small-caption">', $tpl_project_name;
	# Summary
	echo ' ',$tpl_summary, '</td>';
	# Category
	echo '<td class="small-caption center">', $tpl_category, '</td>';
	# Type (Custom field)
	echo '<td class="small-caption center">';
	if ( $tpl_show_type) # has read access
		print_custom_field_value( $t_type_def, 1, $f_bug_id );
	echo '</td>';
	# Handler
	echo '<td class="small-caption center">';
	print_user_with_subject( $tpl_bug->handler_id, $tpl_bug_id );
	echo '</td>';
	# Status
	if ( $tpl_show_status ) {
		echo '<td class="small-caption center" bgcolor="', get_status_color( $tpl_bug->status ), '">', $tpl_status, '</td>';
	}
	echo '</tr>';
	echo '</table>';
	echo '</div>';
	html_javascript_link( 'addLoadEvent.js' );
	html_javascript_link( 'form_warnings.js' );
    echo "<script type='text/javascript'>
	var fixedTable = document.getElementById('fixed_scroll');
	window.onscroll = scroll;
	function scroll(){
		if (window.pageYOffset > 200){
			if (fixedTable.classList.contains('hidden-first'))
				fixedTable.classList.remove('hidden-first');
			if (fixedTable.classList.contains('transparent'))
				fixedTable.classList.remove('transparent');
			if (!fixedTable.classList.contains('opaque'))
				fixedTable.classList.add('opaque');
		}else{
			if (fixedTable.classList.contains('opaque'))
				fixedTable.classList.remove('opaque');
			if (!fixedTable.classList.contains('transparent') && !fixedTable.classList.contains('hidden-first'))
				fixedTable.classList.add('transparent');
		}
    }

	addLoadEvent(adjustFixedWidth);
	window.onresize = adjustFixedWidth;
	function adjustFixedWidth(){
		fixedTable.style.width = (window.innerWidth - 85) + 'px';
	}
	addLoadEvent(setWarningOnNavigate);

	document.addEventListener('DOMContentLoaded', function(){
		function adjustScroll(){
			const headerHeight = document.querySelector('#fixed_scroll').offsetHeight;
			const targetElement = document.querySelector(location.hash);
			if (targetElement){
				const elementTop = targetElement.getBoundingClientRect().top + window.pageYOffset;
				window.scrollTo({
					top: elementTop - headerHeight,
					behavior: 'smooth'
				});
			}
		}
		// Adjust scroll when page loads with hash
		if (window.location.hash)
			setTimeout(function(){
				adjustScroll();
			}, 50);
		// Adjust scroll when hash changes (clicking an internal link)
		window.addEventListener('hashchange', adjustScroll);
	});

	function submitChangeStatus(formCount){ /* There are two versions of this form (Issue Metadata section open and closed) */
		var noteAddDiv = document.getElementById('bugnote_add_open');
		var textArea = noteAddDiv.querySelector('textarea');
		var statusText =  document.getElementsByName('change_status_text')[formCount];
		var statusChangeForm = statusText.parentElement;
		statusText.value = textArea.value;
		statusChangeForm.submit();
		return false;
	}

	function replyToNote(noteID) {
		var noteAddDiv = document.getElementById('bugnote_add_open');
		var textArea = noteAddDiv.querySelector('textarea');
		if (textArea.value == '')
			textArea.value = 'Re ~'+noteID+': ';
		";
	if ($tpl_bug->status == $t_feedback){
		echo "var newStatus =  document.getElementsByName('new_status');
		if (newStatus){
			newStatus = newStatus[0];
			newStatus.value = '".ACKNOWLEDGED."';
			submitChangeStatus(0);
		}
	";
	}else{
		echo "noteAddDiv.scrollIntoView();
		setTimeout(function(){
			textArea.focus();
		}, 500);
	";
	}
	echo "}

	function copyNote(noteID) {
		var noteRow = document.getElementById('c'+noteID);
		var copyOptions = noteRow.querySelector('.copy-options');
		copyOptions.classList.toggle('hidden');
	}

	function copyNoteOverride(e, noteID, fromBugID) {
		var noteRow = document.getElementById('c'+noteID);
		var copyTo = noteRow.querySelector('[name=\"bug_id\"]').value;
		if (copyTo == fromBugID){
			e.preventDefault();
			var noteAddDiv = document.getElementById('bugnote_add_open');
			var textArea = noteAddDiv.querySelector('textarea');
			var noteText = noteRow.querySelector('input[name=\"bugnote_text\"]').value;
			if (noteText.substring(0, 12) == 'Duplicate of'){
				noteText = 'Update/Extension'+noteText.substring(9);
				textArea.value = noteText;
				noteAddDiv.scrollIntoView();
			}
		}
	}

    addLoadEvent(colourifyLinks);
    function colourifyLinks() {
        var notesDiv = document.getElementById('bugnotes_open');
        var links = notesDiv.querySelectorAll('a');
        var indexOccurs = [];
        var usefulIndex = [];
        var usefulCount = 0;
		//var startTime = performance.now();
        links.forEach(link => {
            if (link.href.includes('/view.php')) {
                if (typeof indexOccurs[link.href] === 'undefined') {
                    indexOccurs[link.href] = 1;
                }else{
                    indexOccurs[link.href]++;
					if (indexOccurs[link.href] == 2)
						usefulIndex[link.href] = usefulCount++;
				}
            }
        });
		//var endTime = performance.now();
		//console.log('Loop took '+(endTime - startTime)+' ms. usefulCount = '+usefulCount);
		//startTime = performance.now();
        var uniqueColours = generateUniqueColours(usefulCount);
		//endTime = performance.now();
		//console.log('generateUniqueColours took '+(endTime - startTime)+' ms.');
		//startTime = performance.now();
        links.forEach(link => {
            if (typeof usefulIndex[link.href] !== 'undefined'){
                link.style.backgroundColor = uniqueColours[usefulIndex[link.href]].bg;
                link.style.color = uniqueColours[usefulIndex[link.href]].fg;
            }
        });
		//endTime = performance.now();
		//console.log('Assigning colours to links took '+(endTime - startTime)+' ms.');
    }

    function generateUniqueColours(colourCount) {
        var colourArray = [];
        if (colourCount > 0) {
            var r = 0, g = 0, b = 0;
            var colourEntries = Math.ceil(Math.cbrt(colourCount));
            var colourDistance = 255;
            if (colourEntries > 1)
                colourDistance = Math.floor(255 / (colourEntries - 1));
            for(var ri = 0; ri < colourEntries; ri++) {
                for(var gi = 0; gi < colourEntries; gi++) {
                    for(var bi = 0; bi < colourEntries; bi++) {
                        r = ri * colourDistance;
                        g = gi * colourDistance;
                        b = bi * colourDistance;
                        if (colourIsDark(r, g, b))
                            colourArray.push({bg: 'RGB(' + r + ',' + g + ',' + b + ')', fg: '#fff'});
                        else
                            colourArray.push({bg: 'RGB(' + r + ',' + g + ',' + b + ')', fg: '#000'});
                    }
                }
            }
        }
        return colourArray;
    }

    function colourIsDark(r, g, b) {
		return (r / 255.0 * 0.299 + g / 255.0 * 0.587 + b / 255.0 * 0.114) < 0.5;
        /* return ((r < 128 ? 0 : 1) +
                (g < 128 ? 0 : 1) +
                (b < 128 ? 0 : 1)) < 2;*/
    }
	</script>";

	# Normal page

	echo '<br />';

	collapse_open('issue_details');

	echo '<table class="width100" cellspacing="0">';
	echo '<tr class="header">';

	# Form Title
	echo '<td class="form-title" colspan="', $t_bugslist ? '2' : '3', '">';

	collapse_icon( 'issue_details' );

	echo $tpl_form_title;

	echo ' &nbsp; <span class="small">';

	# Jump to Bugnotes
	print_bracket_link( "#bugnotes", lang_get( 'jump_to_bugnotes' ) );

	if ( !is_blank( $tpl_wiki_link ) ) {
		print_bracket_link( $tpl_wiki_link, lang_get( 'wiki' ) );
	}

	foreach ( $tpl_links as $t_plugin => $t_hooks ) {
		foreach( $t_hooks as $t_hook ) {
			if ( is_array( $t_hook ) ) {
				foreach( $t_hook as $t_label => $t_href ) {
					if ( is_numeric( $t_label ) ) {
						print_bracket_link_prepared( $t_href );
					} else {
						print_bracket_link( $t_href, $t_label );
					}
				}
			} else {
				print_bracket_link_prepared( $t_hook );
			}
		}
	}

	echo '</span> &nbsp; ';

	echo '</td>';

	# prev/next links
	if ( $t_bugslist ) {
		echo '<td class="center" width="50%"><span class="small">';

		$t_bugslist = explode( ',', $t_bugslist );
		$t_index = array_search( $f_bug_id, $t_bugslist );
		if ( false !== $t_index ) {
			if ( isset( $t_bugslist[$t_index-1] ) ) {
				print_bracket_link( 'view.php?id='.$t_bugslist[$t_index-1], '&lt;&lt;' );
			}

			if ( isset( $t_bugslist[$t_index+1] ) ) {
				print_bracket_link( 'view.php?id='.$t_bugslist[$t_index+1], '&gt;&gt;' );
			}
		}
		echo '</span></td>';
	}


	# Links
	echo '<td class="right" width="18%">';

	if ( !is_blank( $tpl_history_link ) ) {
		# History
		echo '<span class="small">';
		print_bracket_link( $tpl_history_link, lang_get( 'bug_history' ) );
		echo '</span>';
	}

	# Print Bug
	echo '<span class="small">';
	print_bracket_link( $tpl_print_link, lang_get( 'print' ) );
	echo '</span>';
	echo '</td>';
	echo '</tr>';

	if ( $tpl_top_buttons_enabled ) {
		echo '<tr><td colspan="3" class="center">';
		html_buttons_view_bug_page( $tpl_bug_id );
		echo '</td></tr>';
	}

	helper_alternate_class(); // Make sure Summary starts on the brigher background colour
	# Summary
	if ( $tpl_show_summary ) {
		echo '<tr ', helper_alternate_class(), '>';
		echo '<td class="category" width="17%">', lang_get( 'summary' ), '</td>';
		echo '<td colspan="3"><b>', $tpl_summary, '</b></td>';
		echo '</tr>';
	}

	#
	# Bug Details Event Signal
	#
	event_signal( 'EVENT_VIEW_BUG_DETAILS', array( $tpl_bug_id ) );

	#
	# Bug Details (screen wide fields)
	#

	# Description
	if ( $tpl_show_description ) {
		echo '<tr ', helper_alternate_class(), '>';
		echo '<td class="category">', lang_get( 'description' ), '</td>';
		echo '<td colspan="3">', $tpl_description, '</td>';
		echo '</tr>';
	}

	# Steps to Reproduce
	if ( $tpl_show_steps_to_reproduce ) {
		echo '<tr ', helper_alternate_class(), '>';
		echo '<td class="category">', lang_get( 'steps_to_reproduce' ), '</td>';
		echo '<td colspan="3">', $tpl_steps_to_reproduce, '</td>';
		echo '</tr>';
	}

	# Additional Information
	if ( $tpl_show_additional_information ) {
		echo '<tr ', helper_alternate_class(), '>';
		echo '<td class="category">', lang_get( 'additional_information' ), '</td>';
		echo '<td colspan="3">', $tpl_additional_information, '</td>';
		echo '</tr>';
	}

	# Attachments
	if ( $tpl_show_attachments ) {
		echo '<tr ', helper_alternate_class(), '>';
		echo '<td class="category">';
		$attachmentsExpanded = collapse_display('attachments');
		echo '<a href="" onclick="if (ToggleDiv(\'attachments\')) this.firstChild.src=\'images/minus.png\';
								else this.firstChild.src=\'images/plus.png\';
								return false;"><img src="images/' . ($attachmentsExpanded ? 'minus.png' : 'plus.png') . '" alt="-" border="0" width="14" height="14" /></a> ';
		echo lang_get( 'attached_files' ), '</td>';
		echo '<td colspan="3">';
		echo '<div id="attachments_open" class="' . ($attachmentsExpanded ? '' : 'hidden') . '">';
		# File upload
		if ( $tpl_show_upload_form && file_allow_bug_upload( $f_bug_id ) ) {
			$t_max_file_size = (int)min( ini_get_number( 'upload_max_filesize' ), ini_get_number( 'post_max_size' ), config_get( 'max_file_size' ) );
			echo '<form method="post" enctype="multipart/form-data" action="bug_file_add.php">';
			echo form_security_field( 'bug_file_add' );
			echo '<input type="hidden" name="bug_id" value="', $f_bug_id, '" />';
			echo '<input type="hidden" name="max_file_size" value="', $t_max_file_size, '" />';
			echo 'Add attachment: ';
			echo '<input name="file" type="file" size="40" />';
			echo '<input type="submit" class="button" value="', lang_get( "upload_file_button" ), '"/> ';
			echo '<span class="small">(', lang_get( 'max_file_size' ), ': ', number_format( $t_max_file_size/1000 ), 'k)</span>';
			echo '</form><br>';
		}
		$t_attachments_count = print_bug_attachments_list( $tpl_bug_id );
		echo '</div>';
		echo '<div id="attachments_closed" class="' . ($attachmentsExpanded ? 'hidden' : '') . '">';
		if ($t_attachments_count == 0)
			echo 'There are no attachments to this issue.';
		else if ($t_attachments_count == 1)
			echo 'There is one attachment. Expand this section to display it.';
		else
			echo 'There are ' . $t_attachments_count . ' attachments. Expand this section to display them.';
		echo "<br><br>";
			# File upload
		if ( $tpl_show_upload_form && file_allow_bug_upload( $f_bug_id ) ) {
			$t_max_file_size = (int)min( ini_get_number( 'upload_max_filesize' ), ini_get_number( 'post_max_size' ), config_get( 'max_file_size' ) );
			echo '<form method="post" enctype="multipart/form-data" action="bug_file_add.php">';
			echo form_security_field( 'bug_file_add' );
			echo '<input type="hidden" name="bug_id" value="', $f_bug_id, '" />';
			echo '<input type="hidden" name="max_file_size" value="', $t_max_file_size, '" />';
			echo 'Add attachment: ';
			echo '<input name="file" type="file" size="40" />';
			echo '<input type="submit" class="button" value="', lang_get( "upload_file_button" ), '"/> ';
			echo '<span class="small">(', lang_get( 'max_file_size' ), ': ', number_format( $t_max_file_size/1000 ), 'k)</span>';
			echo '</form>';
		}
		echo '</div>';
		echo '</td></tr>';
	}

	if ( $tpl_bottom_buttons_enabled ) {
		echo '<tr class="footer"><td colspan="4" class="center">';
		html_buttons_view_bug_page( $tpl_bug_id );
		echo '</td></tr>';
	}

	echo '</table>';

	collapse_closed( 'issue_details' );

	echo '<table class="width100" cellspacing="0">';
	echo '<tr class="header">';

	# Form Title
	echo '<td class="form-title" colspan="', $t_bugslist ? '2' : '3', '">';

	collapse_icon( 'issue_details' );

	echo $tpl_form_title;

	echo ' &nbsp; <span class="small">';

	# Jump to Bugnotes
	print_bracket_link( "#bugnotes", lang_get( 'jump_to_bugnotes' ) );

	if ( !is_blank( $tpl_wiki_link ) ) {
		print_bracket_link( $tpl_wiki_link, lang_get( 'wiki' ) );
	}

	foreach ( $tpl_links as $t_plugin => $t_hooks ) {
		foreach( $t_hooks as $t_hook ) {
			if ( is_array( $t_hook ) ) {
				foreach( $t_hook as $t_label => $t_href ) {
					if ( is_numeric( $t_label ) ) {
						print_bracket_link_prepared( $t_href );
					} else {
						print_bracket_link( $t_href, $t_label );
					}
				}
			} else {
				print_bracket_link_prepared( $t_hook );
			}
		}
	}
	echo '</span> &nbsp; ';

	# UPDATE button
	html_button_bug_update( $f_bug_id );

	echo '</td>';

	# prev/next links
	if ( $t_bugslist ) {
		echo '<td class="center" width="50%"><span class="small">';

		if ( false !== $t_index ) {
			if ( isset( $t_bugslist[$t_index-1] ) ) {
				print_bracket_link( 'view.php?id='.$t_bugslist[$t_index-1], '&lt;&lt;' );
			}

			if ( isset( $t_bugslist[$t_index+1] ) ) {
				print_bracket_link( 'view.php?id='.$t_bugslist[$t_index+1], '&gt;&gt;' );
			}
		}
		echo '</span></td>';
	}

	# Links
	echo '<td class="right" width="18%">';

	# History
	if ( !is_blank( $tpl_history_link ) ) {
		echo '<span class="small">';
		print_bracket_link( $tpl_history_link, lang_get( 'bug_history' ) );
		echo '</span>';
	}

	# Print Bug
	echo '<span class="small">';
	print_bracket_link( $tpl_print_link, lang_get( 'print' ) );
	echo '</span>';
	echo '</td>';
	echo '</tr>';

	if ( $tpl_show_summary || $tpl_show_status ) {
		echo '<tr ', helper_alternate_class(), '>';
	# Summary
		if ( $tpl_show_summary ){
			echo '<td class="category" width="17%"><b>', lang_get( 'summary' ), '</b></td>';
			echo '<td colspan="3"><b>', $tpl_summary, '</b></td>';
		}
		echo '</tr>';
	}

/*	echo '<tr class="footer">';
	echo '<td class="center" colspan="4">';
	html_buttons_view_bug_page( $tpl_bug_id );
	echo '</td>';
	echo '</tr>';*/

	echo '</table>';

	collapse_end( 'issue_details' );

	# File upload box
/*	if ( $tpl_show_upload_form ) { // Recoded this form above as part of the main section
		include( $tpl_mantis_dir . 'bug_file_upload_inc.php' );
	}*/

	echo '<br>';

	collapse_open('issue_metadata');

	echo '<table class="width100" cellspacing="0">';

	echo '<tr class="header">';
	echo '<td class="form-title" colspan="' . (access_has_global_level(DEVELOPER) ? '12' : '10') . '">';

	collapse_icon( 'issue_metadata' );

	echo ' Issue Metadata</td>';

	echo '</tr>';

	# Labels
	echo '<tr class="bottom-border">';
	echo '<th colspan="2">ID</th><th colspan="2">Time</th><th colspan="2">People</th>';
	if (access_has_global_level(DEVELOPER))
		echo '<th colspan="2">Environment</th>';
	echo '<th colspan="2">Class</th><th colspan="2">Progress</th>';
	echo '</tr>';

	if ( $tpl_show_id || $tpl_show_date_submitted || $tpl_show_reporter || $tpl_show_category || $tpl_show_status ) {
		echo '<tr class="bug-primary row-2">';
		# Bug ID
		echo '<td class="category" width="7%">', $tpl_show_id ? lang_get( 'issue_id' ) : '', '</td>';
		echo '<td class="center" width="10%">', $tpl_formatted_bug_id, '</td>';
		# Date Submitted
		echo '<td class="category" width="9%">', $tpl_show_date_submitted ? lang_get( 'date_submitted' ) : '', '</td>';
		echo '<td class="center" width="12%">', $tpl_date_submitted, '</td>';
		# Reporter
		echo '<td class="category" width="8%">', $tpl_show_reporter ? lang_get( 'reporter' ) : '', '</td>';
		echo '<td class="center" width="17%">';
		if ( $submitter != '' && $submitter != user_get_name( $tpl_bug->reporter_id ) )
			echo $submitter . ' for ';
		print_user_with_subject( $tpl_bug->reporter_id, $tpl_bug_id );
		echo '</td>';
		# Profile
		if (access_has_global_level(DEVELOPER)) {
			echo '<td class="category disabled" width="15%">', lang_get( 'profile' ), '</td>';
			echo '<td />';
		}
		# Category
		echo '<td class="category" width="8%">', $tpl_show_category ? lang_get( 'category' ) : '', '</td>';
		echo '<td class="center" width="10%">', $tpl_category, '</td>';
		# Status
		echo '<td class="category" width="8%" rowspan="2">', $tpl_show_status ? lang_get( 'status' ) : '', '</td>';
		if ( $tpl_show_status ) {
			echo '<td class="center" bgcolor="', get_status_color( $tpl_bug->status ), '" width="10%" rowspan="2">', $tpl_status, '</td>';
		}
		echo '</tr>';
	}

	if ( $tpl_show_project || $tpl_show_last_updated || $tpl_show_handler || $tpl_show_platform ) {
		echo '<tr class="bug-primary row-1">';
		# Project
		echo '<td class="category">', $tpl_show_project ? lang_get( 'email_project' ) : '', '</td>';
		echo '<td class="center';
		if ($tpl_is_root_project)
			echo ' error';
		echo '">', $tpl_project_name, '</td>';
		# Date Updated
		echo '<td class="category">', $tpl_show_last_updated ? lang_get( 'last_update' ) : '','</td>';
		echo '<td class="center">', $tpl_last_updated, '</td>';
		# Handler
		echo '<td class="category">', $tpl_show_handler ? lang_get( 'assigned_to' ) : '', '</td>';
		echo '<td class="center">';
		print_user_with_subject( $tpl_bug->handler_id, $tpl_bug_id );
		echo '';
		# Platform
		if (access_has_global_level(DEVELOPER)) {
			echo '<td class="category">', $tpl_show_platform ? lang_get( 'platform' ) : '', '</td>';
			echo '<td class="center">',$tpl_show_platform ? $tpl_platform : '', '</td>';
		}
		# Type (Custom field)
		$t_type_def = custom_field_get_definition( 1 );
		$tpl_show_type = custom_field_has_read_access( 1, $f_bug_id );
		echo '<td class="category">', $tpl_show_type ? string_display( lang_get_defaulted( $t_type_def['name'] ) ) : '', '</td>';
		echo '<td class="center">';
		if ( $tpl_show_type) # has read access
			print_custom_field_value( $t_type_def, 1, $f_bug_id );
		echo '</td>';
		echo '</tr>';
	}

	if ( $tpl_show_view_state || $tpl_show_due_date || $tpl_show_handler || $tpl_show_os || $tpl_show_severity || $tpl_show_priority ) {
		echo '<tr class="bug-primary row-2">';
		# View Status (Visibility)
		echo '<td class="category">', $tpl_show_view_state ? lang_get( 'view_status' ) : '', '</td>';
		echo '<td class="center">', $tpl_bug_view_state_enum, '</td>';
		# Due Date
		echo '<td class="category">', $tpl_show_due_date ? lang_get( 'due_date' ) : '', '</td>';
		if ( $tpl_show_due_date ) {
			if ( $tpl_bug_overdue ) {
				echo '<td class="overdue">', $tpl_bug_due_date, '</td>';
			} else {
				echo '<td class="center">', $tpl_bug_due_date, '</td>';
			}
		} else
			echo '<td />';
		echo '';
		# Notes By
		echo '<td class="category">', 'Notes By', '</td>';
		echo '<td class="center">';
		include( $tpl_mantis_dir . 'bugnote_userlist_inc.php' );
		echo '</td>';
		# Operating System
		if (access_has_global_level(DEVELOPER)) {
			echo '<td class="category">', $tpl_show_os ? lang_get( 'os' ) : '', '</td>';
			echo '<td class="center">', $tpl_os, '</td>';
		}
		# Severity
		echo '<td class="category">', $tpl_show_severity ? lang_get( 'severity' ) : '', '</td>';
		echo '<td class="center">', $tpl_severity, '</td>';
		# Priority
		echo '<td class="category">', $tpl_show_priority ? lang_get( 'priority' ) : '', '</td>';
		echo '<td class="center">', $tpl_priority, '</td>';
		echo '</tr>';
	}

	if ( $tpl_show_eta || $tpl_show_os_version || $tpl_show_reproducibility || $tpl_show_resolution ) {
		echo '<tr class="bug-primary row-1';
		if ( !($tpl_show_product_version || $tpl_show_product_build || $tpl_show_target_version || $tpl_show_fixed_in_version || $tpl_show_projection) )
			echo ' bottom-border';
		echo '">';
		# No. of Notes
		echo '<td class="category">', lang_get( 'notes' ), '</td>';
		echo '<td class="center">', $t_bugnote_count == 0 ? lang_get( 'none' ) : $t_bugnote_count, '</td>';
		# ETA
		echo '<td class="category">', $tpl_show_eta ? lang_get( 'eta' ) : '', '</td>';
		echo '<td class="center">', $tpl_eta, '</td>';
		# User list monitoring the bug
		echo '<td class="category">', $tpl_show_monitor_box ? lang_get( 'users_monitoring_bug' ) : '', '</td>';
		echo '<td class="center">';
		if ( $tpl_show_monitor_box )
			include( $tpl_mantis_dir . 'bug_monitor_list_view_inc.php' );
		echo '</td>';
		# OS Version
		if (access_has_global_level(DEVELOPER)) {
			echo '<td class="category">', $tpl_show_os_version ? lang_get( 'os_version' ) : '', '</td>';
			echo '<td class="center">', $tpl_os_version, '</td>';
		}
		# Reproducibility
		echo '<td class="category">', $tpl_show_reproducibility ? lang_get( 'reproducibility' ) : '', '</td>';
		echo '<td class="center">', $tpl_reproducibility, '</td>';
		# Resolution
		echo '<td class="category">', $tpl_show_resolution ? lang_get( 'resolution' ) : '', '</td>';
		echo '<td class="center">', $tpl_resolution, '</td>';
		echo '</tr>';
	}

	if ( $tpl_show_product_version || $tpl_show_product_build || $tpl_show_target_version || $tpl_show_fixed_in_version || $tpl_show_projection ) {
		echo '<tr class="bug-primary row-2 bottom-border">';
		# Product Version
		echo '<td class="category">', $tpl_show_product_version ? lang_get( 'product_version' ) : '', '</td>';
		echo '<td class="center">', $tpl_product_version_string, '</td>';
		# Product Build
		echo '<td class="category">', $tpl_show_product_build ? lang_get( 'product_build' ) : '', '</td>';
		echo '<td class="center">', $tpl_product_build, '</td>';
		# Target Version
		echo '<td class="category">', $tpl_show_target_version ? lang_get( 'target_version' ) : '', '</td>';
		echo '<td class="center">', $tpl_target_version_string, '</td>';
		# Fixed in Version
		echo '<td class="category">', $tpl_show_fixed_in_version ? lang_get( 'fixed_in_version' ) : '', '</td>';
		echo '<td class="center">', $tpl_fixed_in_version_string, '</td>';
		# Projection
		echo '<td class="category">', $tpl_show_projection ? lang_get( 'projection' ) : '', '</td>';
		echo '<td class="center">', $tpl_projection, '</td>';
		echo '</tr>';
	}

	# Custom Fields
	$t_custom_fields_found = false;
	$t_related_custom_field_ids = custom_field_get_linked_ids( $tpl_bug->project_id );

	foreach( $t_related_custom_field_ids as $t_id ) {
		if( $t_id == 1 ||	// Type
			$t_id == 2 && $tpl_bug->status != $t_feedback || // Info Required
			$t_id >=3 && $t_id <=8 ) // Authorization related
			continue;
		if( !custom_field_has_read_access( $t_id, $f_bug_id ) )
			continue;
		# has read access

		$t_custom_fields_found = true;
		$t_def = custom_field_get_definition( $t_id );

		echo '<tr ', helper_alternate_class(), '>';
		echo '<td class="category" colspan="2">', string_display( lang_get_defaulted( $t_def['name'] ) ), '</td>';
		echo '<td colspan="8">';
		print_custom_field_value( $t_def, $t_id, $f_bug_id );
		echo '</td></tr>';
	}

	# Approval
	echo '<tr ', helper_alternate_class(), '>';
	echo '<td class="category" colspan="2">', string_display( 'Approval' ), '</td>';
	echo '<td colspan="' . (access_has_global_level(DEVELOPER) ? '10' : '8') . '">';
	$t_def = custom_field_get_definition( 3 ); $auth_status = custom_field_get_value( $t_def['id'], $f_bug_id );
	if ( $auth_status != null && $auth_status != '' ){
		print_custom_field_value( $t_def, $t_def['id'], $f_bug_id );
		echo ' By ';
		$t_def = custom_field_get_definition( 6 ); print_custom_field_value( $t_def, $t_def['id'], $f_bug_id );
		echo ' On ';
		$t_def = custom_field_get_definition( 7 ); print_custom_field_value( $t_def, $t_def['id'], $f_bug_id );
		echo '; Recorded By ';
		$t_def = custom_field_get_definition( 4 ); print_custom_field_value( $t_def, $t_def['id'], $f_bug_id );
		echo ' On ';
		$t_def = custom_field_get_definition( 5 ); print_custom_field_value( $t_def, $t_def['id'], $f_bug_id );
		$t_def = custom_field_get_definition( 8 ); $note_id = custom_field_get_value( $t_def['id'], $f_bug_id );
		if ($note_id > 0){
			echo '; Note ';
			echo '<a href="'.string_get_bugnote_view_url($f_bug_id, $note_id).'">'.$note_id.'</a>';
		}
	}
	echo '</td></tr>';

	# Tags
	if ( $tpl_show_tags ) {
		echo '<tr ', helper_alternate_class(), '>';
		echo '<td class="category" colspan="2">', lang_get( 'tags' ), '</td>';
		echo '<td colspan="' . (access_has_global_level(DEVELOPER) ? '10' : '8') . '">';
		tag_display_attached( $tpl_bug_id );
		echo '</td></tr>';
	}

	# Add tags Form
	if ( $tpl_can_attach_tag ) {
		echo '<tr ', helper_alternate_class(), '>';
		echo '<td class="category" colspan="2">', lang_get( 'tag_attach_long' ), '</td>';
		echo '<td colspan="' . (access_has_global_level(DEVELOPER) ? '10' : '8') . '">';
		print_tag_attach_form( $tpl_bug_id );
		echo '</td></tr>';
	}

	echo '<tr class="footer">';
	echo '<td class="center" colspan="' . (access_has_global_level(DEVELOPER) ? '12' : '10') . '">';
	html_buttons_view_bug_page( $tpl_bug_id, true );
/*
	if ( $tpl_show_reminder_link ) {
		print_bracket_link( $tpl_bug_reminder_link, lang_get( 'bug_reminder' ) );
	}*/
	echo '</td>';
	echo '</tr>';

	echo '</table>';

	collapse_closed( 'issue_metadata' );

	echo '<table class="width100" cellspacing="0">';
	echo '<tr class="header">';
	echo '<td class="form-title">';

	collapse_icon( 'issue_metadata' );

	echo ' Issue Metadata</td>';

	# Status
	if ($tpl_show_status) {
		echo '<td class="category" width="8%">', $tpl_show_status ? lang_get('status') : '', '</td>';
		echo '<td class="center" bgcolor="', get_status_color($tpl_bug->status), '" width="10%">', $tpl_status, '</td>';
    }

    # Change status button/dropdown
    if (! bug_is_readonly($tpl_bug->id) || config_get('allow_reporter_close')){
        echo '<td class="center" width="280">';
        html_button_bug_change_status($tpl_bug->id);
        echo '</td>';
    }

	echo '</tr>';
	echo '</table>';

	collapse_end( 'issue_metadata' );

	# User list sponsoring the bug
	include( $tpl_mantis_dir . 'bug_sponsorship_list_view_inc.php' );

	# Bug Relationships
	if ( $tpl_show_relationships_box ) {
		relationship_view_box ( $tpl_bug->id );
	}

	# Time tracking statistics
	if (config_get('time_tracking_enabled') && access_has_bug_level(config_get('time_tracking_view_threshold'), $f_bug_id)){
		include($tpl_mantis_dir . 'bugnote_stats_inc.php');
	}

	# Bugnotes and "Add Note" box
	if ( 'ASC' == current_user_get_pref( 'bugnote_order' ) ) {
		include( $tpl_mantis_dir . 'bugnote_view_inc.php' );

		if ( !$tpl_force_readonly ) {
			include( $tpl_mantis_dir . 'bugnote_add_inc.php' );
		}
	} else {
		if ( !$tpl_force_readonly ) {
			include( $tpl_mantis_dir . 'bugnote_add_inc.php' );
		}

		include( $tpl_mantis_dir . 'bugnote_view_inc.php' );
	}

	# Allow plugins to display stuff after notes
	event_signal( 'EVENT_VIEW_BUG_EXTRA', array( $f_bug_id ) );

	# History
	if ($tpl_show_history){
		include($tpl_mantis_dir . 'history_inc.php');
	}

	html_page_bottom();

	last_visited_issue( $tpl_bug_id );
