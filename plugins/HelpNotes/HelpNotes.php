<?php
class HelpNotesPlugin extends MantisPlugin {
	function register() {
		$this->name = 'Help Notes';								# Proper name of plugin
		$this->description = 'Mark notes containing useful help';# Short description of the plugin
		$this->page = '';										# Default plugin page
		$this->version = '2.0';									# Plugin version string
		$this->requires = array(								# Plugin dependencies, array of basename => version pairs
			'MantisCore' => '1.2.0',							# Should always depend on an appropriate version of MantisBT
			);
		$this->author = 'Eyal Azulay';							# Author/team name
		$this->contact = 'eyal.mantis@tiyal.com';				# Author/team e-mail address
		$this->url = 'https://www.get-it-write.com/';			# Support webpage
	}

	function events() {
		return array(
			'EVENT_HELPNOTES_POPULATE' => EVENT_TYPE_CHAIN,
			'EVENT_HELPNOTES_HASHELP' => EVENT_TYPE_CHAIN,
			'EVENT_HELPNOTES_UPDATE_HASHELP' => EVENT_TYPE_OUTPUT,
			'EVENT_HELPNOTES_UPDATE_HASTODO' => EVENT_TYPE_OUTPUT,
			'EVENT_HELPNOTES_TOGGLE_TODO_ITEM' => EVENT_TYPE_OUTPUT,
		);
	}

	function hooks() {
		return array(
			'EVENT_BUGNOTE_EDIT_FORM' => 'update_bugnote_form_help',
			'EVENT_BUGNOTE_EDIT' => 'update_bugnote_help',
			'EVENT_BUGNOTE_ADD_FORM' => 'create_bugnote_form_help',
			'EVENT_BUGNOTE_ADD' => 'update_bugnote_help',
			'EVENT_VIEW_BUGNOTES_START' => 'view_bugnotes_help',
			'EVENT_HELPNOTES_POPULATE' => 'populate_helpnotes',
			'EVENT_HELPNOTES_UPDATE_HASHELP' => 'update_bugnote_hashelp',
			'EVENT_HELPNOTES_UPDATE_HASTODO' => 'update_bugnote_hastodo',
			'EVENT_HELPNOTES_TOGGLE_TODO_ITEM' => 'toggle_bugnote_todo_item',
			'EVENT_DISPLAY_FORMATTED' => 'format_help_string',
			'EVENT_MENU_FILTER' => 'help_notes_menu',
		);
	}
	
	function create_bugnote_form_help($p_event, $p_bug_id) {
		global $update_issue_table_columns;
		if (access_has_global_level(DEVELOPER)){
			$has_help = 0;
			$has_todo = 0;
			echo '<tr ' . helper_alternate_class() . '><td class="category" colspan="', is_page_name('bug_update_page.php') ? '2' : '1', '">Contains Help</td>
<td colspan="' . ($update_issue_table_columns - 2) . '"><input type="hidden" name="has_help" value="0"> <input type="checkbox" id="has_help" name="has_help" value="1"'.($has_help==1?' checked':'').'> <label for="has_help">Help</label></td></tr>';
			// this.form.elements['bugnote_text'] rather than a fixed id/container, since this same
			// hook (and this checkbox) is also rendered on bug_change_status_page.php and
			// bug_update_advanced_page.php, which don't share bugnote_add_inc.php's form structure.
			$t_todo_onchange = 'var t=this.form.elements[\'bugnote_text\']; if(t){if(this.checked){if(!t.value.trim()){t.value=\'**TO DO**\';}}else{if(t.value.trim()==\'**TO DO**\'){t.value=\'\';}}}';
			echo '<tr ' . helper_alternate_class() . '><td class="category" colspan="', is_page_name('bug_update_page.php') ? '2' : '1', '">Contains To Do</td>
<td colspan="' . ($update_issue_table_columns - 2) . '"><input type="hidden" name="has_todo" value="0"> <input type="checkbox" id="has_todo" name="has_todo" value="1"'.($has_todo==1?' checked':'').' onchange="' . $t_todo_onchange . '"> <label for="has_todo">To Do</label></td></tr>';
		}
	}

	function update_bugnote_form_help($p_event, $p_bug_id, $p_bugnote_id) {
		if (access_has_global_level(DEVELOPER)){
			$has_help = 0;
			if ($p_bugnote_id > 0) {
				$t_help_table = plugin_table( 'bugnote_help' );
				$t_query = "SELECT has_help FROM $t_help_table WHERE bugnote_id=" . db_param();
				$t_result = db_query_bound( $t_query, array( $p_bugnote_id ) );
				if (db_num_rows($t_result) > 0) {
					$t_row = db_fetch_array($t_result);
					$has_help = $t_row['has_help'];
				}
			}
			$has_todo = 0;
			if ($p_bugnote_id > 0) {
				$t_todo_table = plugin_table( 'bugnote_todo' );
				$t_query = "SELECT has_todo FROM $t_todo_table WHERE bugnote_id=" . db_param();
				$t_result = db_query_bound( $t_query, array( $p_bugnote_id ) );
				if (db_num_rows($t_result) > 0) {
					$t_row = db_fetch_array($t_result);
					$has_todo = $t_row['has_todo'];
				}
			}
			echo '<tr ' . helper_alternate_class() . '><td class="center" colspan="2"><b>Contains Help</b><br />
<input type="hidden" name="has_help" value="0">
<input type="checkbox" id="has_help" name="has_help" value="1"'.($has_help==1?' checked':'').'>
</td></tr>';
			echo '<tr ' . helper_alternate_class() . '><td class="center" colspan="2"><b>Contains To Do</b><br />
<input type="hidden" name="has_todo" value="0">
<input type="checkbox" id="has_todo" name="has_todo" value="1"'.($has_todo==1?' checked':'').'>
</td></tr>';
		}
	}

	function update_bugnote_help($p_event, $p_bug_id, $p_bugnote_id) {
		if (access_has_global_level(DEVELOPER)){
			$t_help_table = plugin_table('bugnote_help');
			$has_help = gpc_get_bool('has_help', false);
			$t_query = "REPLACE INTO $t_help_table (bugnote_id, has_help) values(" . db_param() . "," . db_param() . ")";
			db_query_bound($t_query, array($p_bugnote_id, $has_help));

			$has_todo = gpc_get_bool('has_todo', false);
			self::set_bugnote_todo_flag($p_bugnote_id, $has_todo);
		}
	}

	function populate_helpnotes($p_event, $p_bug_id, $p_bugnotes) {
		$t_notes_str = '';
		$t_notes_arr = array();
		for ($i=0; $i < count($p_bugnotes); $i++) {
			$t_bugnote = $p_bugnotes[$i];
			$t_bugnote->has_help = 0;
			$t_bugnote->has_todo = 0;
			$t_notes_arr[$t_bugnote->id] = $i;
			$t_notes_str .= ','.$t_bugnote->id;
		}
		if (!empty($t_notes_str)){
			$t_notes_str = substr($t_notes_str, 1);
			$t_help_table = plugin_table('bugnote_help');
			$t_query = "SELECT bugnote_id, has_help FROM $t_help_table WHERE bugnote_id IN ($t_notes_str)";
			$t_result = db_query_bound( $t_query, array() );
			while ( $t_row = db_fetch_array( $t_result ) ) {
				$p_bugnotes[$t_notes_arr[$t_row['bugnote_id']]]->has_help = $t_row['has_help'];
			}
			$t_todo_table = plugin_table('bugnote_todo');
			$t_query = "SELECT bugnote_id, has_todo FROM $t_todo_table WHERE bugnote_id IN ($t_notes_str)";
			$t_result = db_query_bound( $t_query, array() );
			while ( $t_row = db_fetch_array( $t_result ) ) {
				$p_bugnotes[$t_notes_arr[$t_row['bugnote_id']]]->has_todo = $t_row['has_todo'];
			}
		}
		return $p_bugnotes;
	}

	function update_bugnote_hashelp($p_event, $p_bugnote_id, $has_help) {
		if (access_has_global_level(DEVELOPER)){
			$t_help_table = plugin_table('bugnote_help');
			$t_query = "REPLACE INTO $t_help_table (bugnote_id, has_help) values(" . db_param() . "," . db_param() . ")";
			db_query_bound($t_query, array($p_bugnote_id, $has_help));
		}
	}

	function update_bugnote_hastodo($p_event, $p_bugnote_id, $has_todo) {
		if (access_has_global_level(DEVELOPER)){
			self::set_bugnote_todo_flag($p_bugnote_id, $has_todo);
		}
	}

	/**
	 * Shared by the form-submit save path, the inline AJAX toggle, and the same-issue "Copy Note"
	 * flag transfer below: writes the has_todo flag, and on a 0 -> 1 transition, converts legacy
	 * "**DONE**"-marked bullet lines to the new "✓" marker.
	 */
	static function set_bugnote_todo_flag($p_bugnote_id, $p_has_todo) {
		// Explicit basename: plugin_table() defaults to plugin_get_current(), which is only set
		// while event_signal() is actively dispatching a hook. transfer_todo_on_supersede() below
		// calls into this method directly from bugnote_add.php, outside that dispatch, so the
		// default would resolve to an empty basename (e.g. "..._plugin__bugnote_todo_table").
		$t_todo_table = plugin_table('bugnote_todo', 'HelpNotes');
		$t_was_todo = false;
		$t_query = "SELECT has_todo FROM $t_todo_table WHERE bugnote_id=" . db_param();
		$t_result = db_query_bound($t_query, array($p_bugnote_id));
		if (db_num_rows($t_result) > 0) {
			$t_row = db_fetch_array($t_result);
			$t_was_todo = (bool)$t_row['has_todo'];
		}
		$t_query = "REPLACE INTO $t_todo_table (bugnote_id, has_todo) values(" . db_param() . "," . db_param() . ")";
		db_query_bound($t_query, array($p_bugnote_id, $p_has_todo));

		if ($p_has_todo && !$t_was_todo) {
			$t_old_text = bugnote_get_text($p_bugnote_id);
			$t_new_text = self::convert_done_markers($t_old_text);
			if ($t_new_text !== $t_old_text) {
				bugnote_set_text($p_bugnote_id, $t_new_text);
			}
		}
	}

	/**
	 * Called from bugnote_add.php's same-issue "Copy Note" handling (the "*Update of ~N:*" /
	 * "*Superseded by ~..." convention): if the note being superseded was flagged To Do, move the
	 * flag onto its replacement instead of leaving both notes flagged - the superseded copy is
	 * stale and flagging only the new one makes it less likely to be edited by mistake.
	 */
	static function transfer_todo_on_supersede($p_source_bugnote_id, $p_new_bugnote_id) {
		$t_todo_table = plugin_table('bugnote_todo', 'HelpNotes');
		$t_query = "SELECT has_todo FROM $t_todo_table WHERE bugnote_id=" . db_param();
		$t_result = db_query_bound($t_query, array($p_source_bugnote_id));
		$t_was_todo = false;
		if (db_num_rows($t_result) > 0) {
			$t_row = db_fetch_array($t_result);
			$t_was_todo = (bool)$t_row['has_todo'];
		}
		if (!$t_was_todo) {
			return;
		}
		self::set_bugnote_todo_flag($p_source_bugnote_id, false);
		self::set_bugnote_todo_flag($p_new_bugnote_id, true);
	}

	/**
	 * Matches a bullet line, indented or not - indented dash-lines (e.g. "  - text") still render
	 * as their own <li> client-side (wrapped in a nested <div><ul>, not a nested <li>), so they
	 * must count as checkable items here too or the client's and server's item ordinals diverge.
	 * Group 1: leading whitespace (preserved as-is). Group 2: existing "✓ " marker, if any. Group 3: text.
	 */
	const TODO_BULLET_REGEX = '/^([ \t]*)- (✓ )?([^\n]*)$/m';

	function toggle_bugnote_todo_item($p_event, $p_bugnote_id, $p_index, $p_checked) {
		if (access_has_global_level(DEVELOPER)){
			$t_old_text = bugnote_get_text($p_bugnote_id);
			$t_count = -1;
			$t_index = $p_index;
			$t_checked = $p_checked;
			$t_new_text = preg_replace_callback(self::TODO_BULLET_REGEX, function($m) use (&$t_count, $t_index, $t_checked) {
				$t_count++;
				if ($t_count != $t_index) {
					return $m[0];
				}
				return $m[1] . '- ' . ($t_checked ? '✓ ' : '') . $m[3];
			}, $t_old_text);
			if ($t_new_text !== $t_old_text) {
				bugnote_set_text($p_bugnote_id, $t_new_text);
			}
		}
	}

	/**
	 * Detect legacy "**DONE**"-style markers (excluding "**NOT DONE**") on bullet lines (indented
	 * or not) and insert the new "- ✓ " marker. A bare "**DONE**"/"**Done**" marker (nothing else
	 * inside the bold span) is stripped, since it carries no information beyond the completion
	 * flag itself - anything else inside the bold span (an annotation, e.g. "**DONE by Eyal...**")
	 * is an existing rule and is preserved untouched, never stripped. Used both when a note is
	 * flagged To Do (0 -> 1 transition) and by the one-off legacy migration script, so the two
	 * paths can't drift.
	 */
	static function convert_done_markers($p_text) {
		$t_lines = explode("\n", str_replace("\r\n", "\n", $p_text));
		$t_changed = false;
		foreach ($t_lines as &$t_line) {
			if (!preg_match('/^[ \t]*- (?!✓ )/', $t_line)) {
				continue;
			}
			if (!preg_match_all('/\*\*([^*]*)\*\*/', $t_line, $m)) {
				continue;
			}
			foreach ($m[0] as $i => $t_full_match) {
				$t_inner = $m[1][$i];
				if (!preg_match('/\bDONE\b/i', $t_inner) || preg_match('/\bNOT\s+DONE\b/i', $t_inner)) {
					continue;
				}
				if (preg_match('/^\s*DONE\s*$/i', $t_inner)) {
					$t_line = str_replace($t_full_match, '', $t_line);
					$t_line = rtrim($t_line);
				}
				$t_line = preg_replace('/^([ \t]*)- /', '$1- ✓ ', $t_line, 1);
				$t_changed = true;
				break;
			}
		}
		unset($t_line);
		return $t_changed ? implode("\n", $t_lines) : $p_text;
	}
	
	/* Format help strings and add support for Markdown code */
	function format_help_string($p_event, $str, $multi_line=false) {
		// Handle inline images - this might run after URLs have been converted to links
		// So we need to handle both clean URLs and HTML links
		
		// First handle standard markdown: ![alt](url) or ![alt](file:123)
		$str = preg_replace_callback('/!\[([^\]]*)\]\(([^)]+?)(?:\s+&quot;(.*?)&quot;)?\)/',
			array($this, 'process_inline_image'), $str);
		
		// Then handle shorthand syntax: !(url) or !(file:123) - no alt text needed, optional title
		$str = preg_replace_callback('/!\(([^)]+?)(?:\s+&quot;(.*?)&quot;)?\)/',
			array($this, 'process_shorthand_image'), $str);
		
		$str = preg_replace('/\*\*\*([^ ](?:.*[^ ])?)\*\*\*/mU', '<strong><em>$1</em></strong>', $str);
		$str = preg_replace('/\*\*([^ ](?:.*[^ ])?)\*\*/mU', '<strong>$1</strong>', $str);
		$str = preg_replace('/\*([^ ](?:.*[^ ])?)\*/mU', '<em>$1</em>', $str);
		if ($multi_line) {
			$eol = '(?:<br \/>\n|\n|$)'; // End of lines include <br /> tags, which are added by Mantis Formatting plugin before this function is called
			$str = str_replace("\r\n", "\n", $str);
			$depth = 0;
			do {
				$depth++;
				$strPrev = $str;
				// Handle indented blocks (begin with two spaces)
				$str = preg_replace('/^&#160;&#160;([^\n]+)/m', "<div$depth>\n$1\n</div$depth>", $str);
				$str = preg_replace('/<\/div' . $depth . '>\n<div' . $depth . '>\n?/', '', $str);
				$depth++;
				// Handle numbered blocks (indent them the same as indented blocks)
				$str = preg_replace('/^(\d+\.\d*\.?) ([^\n]+)/m', "<div$depth>\n<span>$1</span> $2\n</div$depth>", $str);
				$str = preg_replace('/<\/div' . $depth . '>\n<div' . $depth . '>\n?/', '', $str);
				// Handle lists
				$str = preg_replace('/^- ([^\n]+)\n?/m', "<li>$1</li>", $str);
				$str = preg_replace('/^<li>(.*)<\/li>\n?/m', "<ul><li>$1</li></ul>\n", $str);
				$str = preg_replace('/<\/ul>\n<div' . $depth . '>/', "</ul><div$depth>", $str);
				$depth--;
				$str = preg_replace('/<\/ul>\n<div' . $depth . '>/', "</ul><div$depth>", $str);
				$depth++;
				// Handle blockquotes
				$str = preg_replace('/^&gt; (.+)$/m', "<blockquote>$1</blockquote>", $str);
				$str = preg_replace('/(<\/blockquote>\n<blockquote>)/', '', $str);
				// Handle hrs
				$str = preg_replace('/\n?-{3,}'.$eol.'/', "<hr />", $str);
				// Handle Headers
				$str = preg_replace('/^###### (.+)\n?/m', "\n<h6>$1</h6>\n", $str);
				$str = preg_replace('/^##### (.+)\n?/m', "\n<h5>$1</h5>\n", $str);
				$str = preg_replace('/^#### (.+)\n?/m', "\n<h4>$1</h4>\n", $str);
				$str = preg_replace('/^### (.+)\n?/m', "\n<h3>$1</h3>\n", $str);
				$str = preg_replace('/^## (.+)\n?/m', "\n<h2>$1</h2>\n", $str);
				$str = preg_replace('/^# (.+)\n?/m', "\n<h1>$1</h1>\n", $str);
				$str = preg_replace('/\R<h/mU', '<h', $str);
				// Handle Tables
				$str = preg_replace_callback(
					'/(?:^|\n)(?:\|([^\r\n]+)\|'.$eol.')+/', // Match lines starting and ending with `|`
					function ($matches) {
						$table = str_replace("<br />", "", $matches[0]); // Extract the table block
						$lines = explode("\n", trim($table)); // Split into individual lines
						$html = "<table>\n";
						$alignment = []; // Array to store column alignments

						foreach ($lines as $line) {
							// Remove leading/trailing pipes and split by `|`
							$cells = array_map('trim', explode('|', trim($line, '|')));

							// Detect if the line is a divider line (contains only dashes or colons)
							$isDividerLine = true;
							foreach ($cells as $cell) {
								if (!preg_match('/^\s*:?[-]{3,}:?\s*$/', $cell)) { // Match cells with 3 or more dashes, optionally prefixed/suffixed by colons
									$isDividerLine = false;
									break;
								}
							}
							if ($isDividerLine) {
								// Parse alignment based on the divider line
								foreach ($cells as $cell) {
									if (preg_match('/^\s*:[-]{3,}\s*$/', $cell)) {
										$alignment[] = 'left'; // Left-aligned
									} elseif (preg_match('/^\s*[-]{3,}:\s*$/', $cell)) {
										$alignment[] = 'right'; // Right-aligned
									} elseif (preg_match('/^\s*:[-]{3,}:\s*$/', $cell)) {
										$alignment[] = 'center'; // Center-aligned
									} else {
										$alignment[] = null; // Default (no alignment)
									}
								}
							} elseif (empty($alignment)) {
								// Header row (before detecting a divider line)
								$html .= "<tr><th>" . implode("</th><th>", $cells) . "</th></tr>\n";
							} else {
								// Body rows (after detecting a divider line)
								$html .= "<tr>";
								foreach ($cells as $i => $cell) {
									$cell = str_replace("&lt;br&gt;","<br>", $cell);
									if (isset($alignment[$i])) {
										$html .= "<td class=\"{$alignment[$i]}\">$cell</td>";
									} else {
										$html .= "<td>$cell</td>";
									}
								}
								$html .= "</tr>\n";
							}
						}

						$html .= "</table>";
						return $html;
					},
					$str
				);
			} while ($str != $strPrev);
			for ($i = $depth; $i > 0; $i--) {
				$str = preg_replace('/<div' . $i . '>(.+)<\/div' . $i . '>/sU', '<div>$1</div>', $str);
			}
			$str = preg_replace('/<div>\R/', '<div>', $str);
			$str = preg_replace('/\R<\/div>\R\R?/', '</div>', $str);
			$str = preg_replace('/\{\{(.+)\}\}/sU', '<b>$1</b>', $str);
		}
		return $str;
	}
	
	/**
	 * Process inline image markdown syntax following MantisBT patterns
	 */
	private function process_inline_image($matches) {
		$alt_text = $matches[1];
		$url_or_file = trim($matches[2]);
		$title = isset($matches[3]) ? $matches[3] : '';
		
		// Check if it's a MantisBT attachment reference (file:123)
		if (preg_match('/^file:(\d+)$/', $url_or_file, $file_matches)) {
			return $this->process_attachment_image($file_matches[1], $alt_text, $title);
		} else {
			// Standard web URL
			return $this->process_web_image($url_or_file, $alt_text, $title);
		}
	}
	
	/**
	 * Process shorthand image syntax: !(url) or !(file:123) or !(url "title")
	 * This is a simplified version without alt text for convenience
	 */
	private function process_shorthand_image($matches) {
		$url_or_file = trim($matches[1]);
		$title = isset($matches[2]) ? $matches[2] : '';
		
		// Only process if it looks like a URL or file reference to avoid false positives
		if (preg_match('/^file:\d+$/', $url_or_file) || 
			preg_match('/^https?:\/\//', $url_or_file) ||
			preg_match('/<a[^>]*href=["\']https?:\/\//', $url_or_file)) {
			
			// Call the main image processor with empty alt text but include title
			return $this->process_inline_image(array('', '', $url_or_file, $title));
		}
		
		// If it doesn't look like URL/file reference, return unchanged to avoid false positives
		return $matches[0];
	}
	
	/**
	 * Process MantisBT attachment image using the same methodology as print_api.php
	 */
	private function process_attachment_image($file_id, $alt_text, $title) {
		$file_id = (int)$file_id;
		
		try {
			// Get bug_id and user_id using MantisBT's API (same as file_api.php:265)
			$bug_id = file_get_field($file_id, 'bug_id', 'bug');
			if ($bug_id === false) {
				return '[Attachment not found: file:' . $file_id . ']';
			}
			
			$user_id = file_get_field($file_id, 'user_id', 'bug');
			
			// Use MantisBT's permission check (same as file_api.php:265)
			if (!file_can_view_bug_attachments($bug_id, (int)$user_id)) {
				return '[Access denied: file:' . $file_id . ']';
			}
			
			// Check download permissions (same as file_api.php:282)
			if (!file_can_download_bug_attachments($bug_id, (int)$user_id)) {
				return '[Download not allowed: file:' . $file_id . ']';
			}
			
			// Get file info for validation
			$filename = file_get_field($file_id, 'filename', 'bug');
			$filesize = file_get_field($file_id, 'filesize', 'bug');
			
			// Check file exists (same logic as file_api.php:293)
			$diskfile = file_get_field($file_id, 'diskfile', 'bug');
			$project_id = bug_get_field($bug_id, 'project_id');
			$diskfile_path = file_normalize_attachment_path($diskfile, $project_id);
			$file_exists = config_get('file_upload_method') != DISK || file_exists($diskfile_path);
			
			if (!$file_exists) {
				return '[File missing: file:' . $file_id . ']';
			}
			
			// Use MantisBT's image detection logic (same as file_api.php:299-306)
			$display_name = file_get_display_name($filename);
			$file_ext = strtolower(file_get_extension($display_name));
			$preview_image_ext = config_get('preview_image_extensions');
			
			// Check if it's a supported image type
			if (!in_array($file_ext, $preview_image_ext, true) || $filesize == 0) {
				return '[Not a supported image: file:' . $file_id . ']';
			}
			
			// Build image URL (same as print_api.php:1774 and file_api.php:286)
			$download_url = "file_download.php?file_id={$file_id}&type=bug";
			$image_url = $download_url . '&amp;show_inline=1' . form_security_param('file_show_inline');
			
			// Use filename for alt text if none provided
			if (empty($alt_text)) {
				$alt_text = $display_name;
			}
			
			// Title comes from markdown syntax only, not from database
			return $this->build_img_tag($image_url, $alt_text, $title);
			
		} catch (Exception $e) {
			return '[Error loading attachment: file:' . $file_id . ']';
		}
	}
	
	/**
	 * Process standard web image URL
	 */
	private function process_web_image($url, $alt_text, $title) {
		// Check if URL has already been converted to HTML link by MantisCoreFormatting
		if (preg_match('/<a[^>]*href=["\']([^"\']+)["\'][^>]*>/', $url, $matches)) {
			// Extract the actual URL from the HTML link
			$actual_url = $matches[1];
		} else {
			$actual_url = $url;
		}
		
		// Basic URL validation
		if (!filter_var($actual_url, FILTER_VALIDATE_URL)) {
			return '[Invalid URL: ' . htmlspecialchars($url) . ']';
		}
		
		// Security: Only allow http/https URLs
		$parsed = parse_url($actual_url);
		if (!in_array($parsed['scheme'], array('http', 'https'))) {
			return '[Invalid URL scheme: ' . htmlspecialchars($actual_url) . ']';
		}
		
		return $this->build_img_tag($actual_url, $alt_text, $title);
	}
	
	/**
	 * Build HTML img tag using MantisBT's styling methodology (print_api.php:1756-1776)
	 */
	private function build_img_tag($src, $alt_text, $title) {
		// Use same styling logic as print_api.php:1756-1771
		$preview_style = 'border: 0;';
		
		$max_width = config_get('preview_max_width');
		if (isset($max_width)) {
			$preview_style .= ' max-width:' . $max_width;
			if (is_numeric($max_width)) {
				$preview_style .= 'px';
			}
			$preview_style .= ';';
		}
		
		$max_height = config_get('preview_max_height');
		if (isset($max_height)) {
			$preview_style .= ' max-height:' . $max_height;
			if (is_numeric($max_height)) {
				$preview_style .= 'px';
			}
			$preview_style .= ';';
		}
		
		$style_attr = 'style="' . $preview_style . '"';
		$title_attr = $title ? ' title="' . string_attribute($title) . '"' : '';
		
		return '<img alt="' . string_attribute($alt_text) . '" ' . $style_attr . ' src="' . string_attribute($src) . '"' . $title_attr . ' />';
	}
	
	function help_notes_menu() {
		return array(
			'<a href="' . plugin_page( 'view' ) . '">Help Notes</a>',
			'<a href="' . plugin_page( 'export' ) . '">Help CSV</a>',
			'<a href="' . plugin_page( 'todo_view' ) . '">To Do Notes</a>',
			'<a href="' . plugin_page( 'todo_export' ) . '">To Do CSV</a>'
		);
	}

	/**
	 * Plugin schema.
	 */
	function schema() {
		return array(
			array( 'CreateTableSQL', array( plugin_table( 'bugnote_help' ), "
				bugnote_id		I		NOTNULL UNSIGNED PRIMARY,
				has_help		L		NOTNULL DEFAULT '0'
				" ) ),
			array( 'CreateTableSQL', array( plugin_table( 'bugnote_todo' ), "
				bugnote_id		I		NOTNULL UNSIGNED PRIMARY,
				has_todo		L		NOTNULL DEFAULT '0'
				" ) ),
		);
	}
}

function xmlhttprequest_bugnote_update_hashelp() {
	$f_bugnote_id = gpc_get_int( 'note_id' );
	$f_has_help = gpc_get_int( 'has_help' );

	event_signal( 'EVENT_HELPNOTES_UPDATE_HASHELP', array( $f_bugnote_id, $f_has_help ) );
}

function xmlhttprequest_bugnote_update_hastodo() {
	$f_bugnote_id = gpc_get_int( 'note_id' );
	$f_has_todo = gpc_get_int( 'has_todo' );

	event_signal( 'EVENT_HELPNOTES_UPDATE_HASTODO', array( $f_bugnote_id, $f_has_todo ) );
}

function xmlhttprequest_bugnote_toggle_todo_item() {
	$f_bugnote_id = gpc_get_int( 'note_id' );
	$f_index = gpc_get_int( 'index' );
	$f_checked = gpc_get_int( 'checked' );

	event_signal( 'EVENT_HELPNOTES_TOGGLE_TODO_ITEM', array( $f_bugnote_id, $f_index, $f_checked ) );
}
?>