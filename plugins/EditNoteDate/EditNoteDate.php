<?php
class EditNoteDatePlugin extends MantisPlugin {
	function register() {
		$this->name = 'EditNoteDate';							# Proper name of plugin
		$this->description = 'Enables editing note submit date';# Short description of the plugin
		$this->page = '';										# Default plugin page
		$this->version = '1.0';									# Plugin version string
		$this->requires = array(								# Plugin dependencies, array of basename => version pairs
			'MantisCore' => '1.2.0',							# Should always depend on an appropriate version of MantisBT
			);
		$this->author = 'Eyal Azulay';							# Author/team name
		$this->contact = 'eyal@get-it-write.com';				# Author/team e-mail address
		$this->url = 'http://www.get-it-write.com/';			# Support webpage
	}

	function hooks() {
		return array(
			'EVENT_BUGNOTE_EDIT_FORM' => 'update_bug_form',
			'EVENT_BUGNOTE_EDIT' => 'update_bug',
			'EVENT_BUGNOTE_ADD_FORM' => 'create_bug_form',
			'EVENT_BUGNOTE_ADD' => 'update_bug');
	}

	function create_bug_form($p_event, $p_bug_id) {
		$this->update_bug_form($p_event, $p_bug_id, 0);
	}

	function update_bug_form($p_event, $p_bug_id, $p_bugnote_id) {
		if (access_has_global_level(DEVELOPER)){
			if ($p_bugnote_id > 0) {
				$t_query = "SELECT date_submitted as date_submitted FROM mantis_bugnote_table WHERE id=" . db_param();
				$t_result = db_query_bound( $t_query, array($p_bugnote_id));
				if (db_num_rows($t_result) < 1)
					echo '<tr ', helper_alternate_class(), '><td colspan="2">EditNoteDatePlugin Error: bugnote id not found</td></tr>';
				else {
					$t_row = db_fetch_array($t_result);
					echo '<tr ', helper_alternate_class(), '><td colspan="2">Submit Date: <input type=text id="date_submitted" name="date_submitted" value="', Date ( 'Y-m-d H:i:s', $t_row['date_submitted'] ),'">';

					date_print_calendar('trigger_note');
					date_finish_calendar( 'date_submitted', 'trigger_note');
					echo '</td></tr>';
				}
			} else {
				echo '<tr ', helper_alternate_class(), '><td class="category" colspan="', is_page_name('bug_update_page.php') ? '2' : '1', '">Submit Date</td><td colspan="8"><input type=text id="date_submitted" name="date_submitted" value="">';

				date_print_calendar('trigger_note');
				date_finish_calendar( 'date_submitted', 'trigger_note');
				echo '</td></tr>';
			}
		}
	}

	function update_bug($p_event, $p_bug_id, $p_bugnote_id) {
		if (access_has_global_level(DEVELOPER) && gpc_get_string('date_submitted',null)!=null){
			$date_submitted = gpc_get_string('date_submitted');
			if ($date_submitted) {
				$p_user_id = auth_get_current_user_id();
				$t_prefs = user_pref_get( $p_user_id );
				$p_tz = new DateTimeZone($t_prefs->timezone);
				$tz_time = new DateTime("now", $p_tz);
				$tz_offset = $tz_time->getOffset();
/*				$tz_offset -= 3600; // Adjust server timezone offset (when server was in NL)
				$date = getdate();
				$month = $date[mon];
				$day = $date[mday];
				$wday = $date[wday];
				if ($month == 3 && $day-$wday >=25 || $month > 3 && $month < 10 || $month == 10 && $day-$wday < 25)
					$tz_offset -= 3600;*/
				$t_query = 'UPDATE mantis_bugnote_table SET date_submitted = unix_timestamp(' . db_param() . ') - '.$tz_offset.' WHERE id=' . db_param();
				db_query_bound($t_query, array($date_submitted, $p_bugnote_id));
			}
		}
	}
}
?>
