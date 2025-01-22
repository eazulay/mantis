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
 * This include file prints out the list of bugnotes attached to the bug
 * $f_bug_id must be set and be set to the bug id
 *
 * @package MantisBT
 * @copyright Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
 * @copyright Copyright (C) 2002 - 2011  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
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
foreach($t_bugnotes as $t_bugnote) {
	$t_bugnote_users[] = $t_bugnote->reporter_id;
}
user_cache_array_rows( $t_bugnote_users );

$num_notes = count( $t_bugnotes );
?>

<?php # Bugnotes BEGIN
?>
<a name="bugnotes" id="bugnotes"></a><br />

<?php
    collapse_open( 'bugnotes' );
?>

<table class="width100" cellspacing="0">
<tr class="header">
	<td class="form-title" colspan="2" style="position:relative;">
<?php
    collapse_icon( 'bugnotes' );
    echo lang_get( 'bug_notes_title' );
    if (access_has_global_level(DEVELOPER)){ ?>
    &nbsp; &nbsp;
    <form id="bugnotes_move" method="post" action="bugnotes_move.php?bug_id=<?php echo $f_bug_id; ?>" style="display:none; position:absolute; top:3px;">
    <?php echo form_security_field( 'bugnotes_move' ); ?>
        <label for="move_to_bug_id">Move Selected Notes to Issue #</label> <input type="text" id="move_to_bug_id" name="move_to_bug_id" size="9">
        &nbsp;
        <label for="record_transfer">Record transfer on original Issue</label> <input type="checkbox" id="record_transfer" name="record_transfer" value="1" checked>
        &nbsp;
        <input type="submit" class="button" value="Move Notes" />
        <button onclick="navigateDelete(); return false;">Delete Selected Notes</button>
    </form>
<?php } ?>
	</td>
</tr>
<?php # no bugnotes
	if ( 0 == $num_notes ) { ?>
<tr>
	<td class="center" colspan="2">
		<?php echo lang_get( 'no_bugnotes_msg' ) ?>
	</td>
</tr>
<?php
    }

	event_signal( 'EVENT_VIEW_BUGNOTES_START', array( $f_bug_id, $t_bugnotes ) );

	if (class_exists('HelpNotesPlugin'))
		$t_bugnotes = event_signal( 'EVENT_HELPNOTES_POPULATE', array( $f_bug_id, $t_bugnotes ) );

	$t_normal_date_format = config_get( 'normal_date_format' );
	$t_total_time = 0;

	for ( $i=0; $i < $num_notes; $i++ ) {
		$t_bugnote = $t_bugnotes[$i];

		if ( $t_bugnote->date_submitted != $t_bugnote->last_modified )
			$t_bugnote_modified = true;
		else
			$t_bugnote_modified = false;

		$t_bugnote_id_formatted = bugnote_format_id( $t_bugnote->id );

		if ( 0 != $t_bugnote->time_tracking ) {
			$t_time_tracking_hhmm = db_minutes_to_hhmm( $t_bugnote->time_tracking );
			$t_bugnote->note_type = TIME_TRACKING;
			$t_total_time += $t_bugnote->time_tracking;
		} else {
			$t_time_tracking_hhmm = '';
		}

		if ( VS_PRIVATE == $t_bugnote->view_state ) {
			$t_bugnote_css		= 'bugnote-private';
			$t_bugnote_note_css	= 'bugnote-note-private';
		} else {
			$t_bugnote_css		= 'bugnote-public';
			$t_bugnote_note_css	= 'bugnote-note-public';
		}
		$t_bugnote_row_css = '';
		if (class_exists('HelpNotesPlugin')){
			if ($t_bugnote->has_help)
				$t_bugnote_row_css = ' bugnote-hashelp';
		}
?>
<tr class="bugnote<?php echo $t_bugnote_row_css ?>" id="c<?php echo $t_bugnote->id ?>">
    <td class="<?php echo $t_bugnote_css ?>">
		<?php if ( ON  == config_get("show_avatar") ) print_avatar( $t_bugnote->reporter_id ); ?>
        <?php # Note Selection checkbox
        if (access_has_global_level(DEVELOPER)) {
            echo '<div style="float:right;"><label for="note_selected_'.$t_bugnote->id.'" style="font-weight:normal;">Select</label> <input type="checkbox" id="note_selected_'.$t_bugnote->id.'" name="note_selected['.$t_bugnote->id.']" value="1" form="bugnotes_move" onclick="note_selected(this.checked, '.$t_bugnote->id.')"></div>';
        }
        ?>
		<span style="font-weight:bold;"><a href="<?php echo string_get_bugnote_view_url($t_bugnote->bug_id, $t_bugnote->id); ?>" title="<?php echo lang_get('bugnote_link_title'); ?>"><?php echo $t_bugnote_id_formatted; ?></a></span>
        <br />
		<?php # Has Help
		if (class_exists('HelpNotesPlugin') && access_has_global_level(DEVELOPER)) {
			echo '<div style="float:right; clear:right;"><label for="has_help_'.$t_bugnote->id.'" style="font-weight:normal;">Help</label> <input type="checkbox" id="has_help_'.$t_bugnote->id.'" name="has_help['.$t_bugnote->id.']" value="1"'.($t_bugnote->has_help ? ' checked' : '').' onchange="hasHelpChanged(this);"></div>';
		}
		echo print_user( $t_bugnote->reporter_id );
		?>
		<span class="small"><?php
			if ( user_exists( $t_bugnote->reporter_id ) ) {
				$t_access_level = access_get_project_level( null, (int)$t_bugnote->reporter_id );
				echo '(', get_enum_element( 'access_levels', $t_access_level ), ')';
			}
		?></span>
        <?php if ( VS_PRIVATE == $t_bugnote->view_state ) { ?>
        <span class="small">[ <?php echo lang_get( 'private' ) ?> ]</span>
        <?php } ?>
        <br />
		<span class="small"><?php echo date( $t_normal_date_format, $t_bugnote->date_submitted ); ?></span><br />
		<?php
		if ( $t_bugnote_modified ) {
			echo '<span class="small">' . lang_get( 'edited_on') . lang_get( 'word_separator' ) . date( $t_normal_date_format, $t_bugnote->last_modified ) . '</span><br />';
		}

		# bug must be open to be editable
		if ( !bug_is_readonly( $f_bug_id ) ) {
			echo '<div style="margin-top:5px; padding-left:0;">';
			$t_can_edit_note = false;
			$t_can_delete_note = false;

			# admins and the bugnote creator can edit/delete this bugnote
			if ( ( access_has_bug_level( config_get( 'manage_project_threshold' ), $f_bug_id ) ) ||
				( ( $t_bugnote->reporter_id == $t_user_id ) && ( ON == config_get( 'bugnote_allow_user_edit_delete' ) ) ) ) {
				$t_can_edit_note = true;
				$t_can_delete_note = true;
			}

			# users above update_bugnote_threshold should be able to edit this bugnote
			if ( $t_can_edit_note || access_has_bug_level( config_get( 'update_bugnote_threshold' ), $f_bug_id ) ) {
				print_button( 'bugnote_edit_page.php?bugnote_id='.$t_bugnote->id, lang_get( 'bugnote_edit_link' ) );
			}

			# users above delete_bugnote_threshold should be able to delete this bugnote
			if ( $t_can_delete_note || access_has_bug_level( config_get( 'delete_bugnote_threshold' ), $f_bug_id ) ) {
				echo " ";
				print_button( 'bugnote_delete.php?bugnote_id=' . $t_bugnote->id, lang_get( 'delete_link' ) );
			}

			# users with access to both update and change view status (or the bugnote author) can change public/private status
			if ( access_has_bug_level( config_get( 'private_bugnote_threshold' ), $f_bug_id ) ) {
				if ( VS_PRIVATE == $t_bugnote->view_state ) {
					echo " ";
					print_button( 'bugnote_set_view_state.php?private=0&bugnote_id=' . $t_bugnote->id, lang_get( 'make_public' ) );
				} else {
					echo " ";
					print_button( 'bugnote_set_view_state.php?private=1&bugnote_id=' . $t_bugnote->id, lang_get( 'make_private' ) );
				}
			}

			echo ' <input type="button" class="button-small" onclick="replyToNote('.$t_bugnote->id.');" value="Reply" />';
			echo ' <input type="button" class="button-small" onclick="copyNote('.$t_bugnote->id.');" value="Copy" />
		<div class="copy-options hidden">
			<form method="post" action="bugnote_add.php">
				<input type="hidden" name="bugnote_text" value="Duplicate of ~'.$t_bugnote->id.":\n". htmlspecialchars($t_bugnote->note, ENT_QUOTES, 'UTF-8').'" />
				<input type="hidden" name="date_submitted" value="' . date('Y-m-d H:i:s', $t_bugnote->date_submitted) . '" />
				<input type="hidden" name="source_bugnote_id" value="'.$t_bugnote->id.'" />
				<input type="hidden" name="return_to_bug_id" value="'.$f_bug_id.'" />
				<Label>To: <input type="number" name="bug_id" min="1" value="'.$f_bug_id.'" /></Label>
				<input type="submit" class="button-small" onclick="copyNoteOverride(event,'.$t_bugnote->id.','.$f_bug_id.');" value="Apply" />
			</form>
		</div>
	</div>';
		}
		?>
	</td>
	<td class="<?php echo $t_bugnote_note_css ?>">
		<?php
			switch ( $t_bugnote->note_type ) {
				case REMINDER:
					echo '<em>' . lang_get( 'reminder_sent_to' ) . lang_get( 'word_separator' );
					$t_note_attr = utf8_substr( $t_bugnote->note_attr, 1, utf8_strlen( $t_bugnote->note_attr ) - 2 );
					$t_to = array();
					foreach ( explode( '|', $t_note_attr ) as $t_recipient ) {
						$t_to[] = prepare_user_name( $t_recipient );
					}
					echo implode( ', ', $t_to ) . '</em><br /><br />';
					break;
				case TIME_TRACKING:
					if ( access_has_bug_level( config_get( 'time_tracking_view_threshold' ), $f_bug_id ) ) {
						echo '<b>', lang_get( 'time_tracking_time_spent' ) . ' ' . $t_time_tracking_hhmm, '</b><br /><br />';
					}
					break;
			}

			echo string_display_links( $t_bugnote->note );
		?>
	</td>
</tr>
<?php event_signal( 'EVENT_VIEW_BUGNOTE', array( $f_bug_id, $t_bugnote->id, VS_PRIVATE == $t_bugnote->view_state ) );
		if ( $i < $num_notes - 1 ){ ?>
<tr class="spacer">
	<td colspan="2"></td>
</tr>
<?php
		}
	} # end for loop

	if ( $t_total_time > 0 && access_has_bug_level( config_get( 'time_tracking_view_threshold' ), $f_bug_id ) ) {
		echo '<tr class="footer"><td colspan="2">', sprintf ( lang_get( 'total_time_for_issue' ), db_minutes_to_hhmm( $t_total_time ) ), '</td></tr>';
	}

	event_signal( 'EVENT_VIEW_BUGNOTES_END', $f_bug_id );
?>
</table>

<?php
	collapse_closed( 'bugnotes' );
?>

<table class="width100" cellspacing="1">
<tr class="header">
	<td class="form-title" colspan="2">
		<?php collapse_icon( 'bugnotes' ); ?>
		<?php echo lang_get( 'bug_notes_title' ) ?>
	</td>
</tr>
</table>
<?php
	collapse_end( 'bugnotes' );

	if ( ON == config_get('use_javascript') && (class_exists('HelpNotesPlugin')) ): ?>
<script type="text/javascript">
	function hasHelpChanged(cb) {
		var queryString = 'entrypoint=bugnote_update_hashelp&note_id=' + cb.id.substr(9) + '&has_help=' + (cb.checked ? '1' : '0');
		AjaxSave(queryString, update_row_class, [cb.id.substr(9), cb.checked ? '1' : '0']);
	}

	function update_row_class(args) {
		var bugnoteId = args[0];
		var hasHelp = args[1];
		rowEl = document.getElementById('c'+bugnoteId);
		if (hasHelp == 1)
			rowEl.classList.add('bugnote-hashelp');
		else
			rowEl.classList.remove('bugnote-hashelp');
    }

    var selectedNotesCount = 0;
    var selectedNotes = [];
    function note_selected(checked, noteID){
        if (checked){
            selectedNotes.push(noteID);
            console.log(selectedNotes.join(','));
            selectedNotesCount++;
            if (selectedNotesCount == 1){
                var form = document.getElementById('bugnotes_move');
                form.style.display = "inline-block";
            }
        }else{
            var index = selectedNotes.indexOf(noteID);
            selectedNotes.splice(index, 1);
            console.log(selectedNotes.join(','));
            selectedNotesCount--;
            if (selectedNotesCount == 0){
                var form = document.getElementById('bugnotes_move');
                form.style.display = "none";
            }
        }
    }
    function navigateDelete(){
        window.location.href = "bugnote_delete.php?bugnote_id=" + selectedNotes.join(',');
    }
</script>
<?php endif;