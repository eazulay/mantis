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
 * This include file prints out the bug bugnote_stats
 * $f_bug_id must already be defined
 *
 * @package MantisBT
 * @copyright Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
 * @copyright Copyright (C) 2002 - 2011  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 */

/**
 * Requires bugnote API
 */
require_once( 'bugnote_api.php' );

if ( !config_get('time_tracking_enabled') )
	return;
?>

<a name="bugnotestats" id="bugnotestats" /><br />

<?php
	collapse_open( 'bugnotestats' );

	$t_today = date( "d:m:Y" );
	$t_date_submitted = isset( $t_bug ) ? date( "d:m:Y", $t_bug->date_submitted ) : $t_today;
	
	$t_bugnote_stats_from_def = isset( $_SESSION['bugnote_stats_from_def'] ) ? $_SESSION['bugnote_stats_from_def'] : $t_date_submitted;
	$t_bugnote_stats_from_def_ar = explode ( ":", $t_bugnote_stats_from_def );
	$t_bugnote_stats_from_def_d = $t_bugnote_stats_from_def_ar[0];
	$t_bugnote_stats_from_def_m = $t_bugnote_stats_from_def_ar[1];
	$t_bugnote_stats_from_def_y = $t_bugnote_stats_from_def_ar[2];

	$t_bugnote_stats_from_d = gpc_get_int('start_day', $t_bugnote_stats_from_def_d);
	$t_bugnote_stats_from_m = gpc_get_int('start_month', $t_bugnote_stats_from_def_m);
	$t_bugnote_stats_from_y = gpc_get_int('start_year', $t_bugnote_stats_from_def_y);

	$t_bugnote_stats_to_def = isset( $_SESSION['bugnote_stats_to_def'] ) ? $_SESSION['bugnote_stats_to_def'] : $t_today;
	$t_bugnote_stats_to_def_ar = explode ( ":", $t_bugnote_stats_to_def );
	$t_bugnote_stats_to_def_d = $t_bugnote_stats_to_def_ar[0];
	$t_bugnote_stats_to_def_m = $t_bugnote_stats_to_def_ar[1];
	$t_bugnote_stats_to_def_y = $t_bugnote_stats_to_def_ar[2];

	$t_bugnote_stats_to_d = gpc_get_int('end_day', $t_bugnote_stats_to_def_d);
	$t_bugnote_stats_to_m = gpc_get_int('end_month', $t_bugnote_stats_to_def_m);
	$t_bugnote_stats_to_y = gpc_get_int('end_year', $t_bugnote_stats_to_def_y);
	
	$f_bugnote_cost = gpc_get_int( 'bugnote_cost', isset( $_SESSION['bugnote_cost'] ) ? $_SESSION['bugnote_cost'] : '' );
	$f_project_id = helper_get_current_project();

	$t_cost_col = ( ON == config_get( 'time_tracking_with_billing' ) );
	
	$f_get_bug_stats_button = gpc_isset('get_bug_stats_button');

	 // Added by Eyal Azulay
	$t_category = gpc_get_string( 'show_category', '' );
	$f_export_bug_stats_button = gpc_isset( 'export_bug_stats_button' );

	# Time tracking date range input form
	# CSRF protection not required here - form does not result in modifications

	if ($f_export_bug_stats_button):
		require_once( 'csv_api.php' );
		$t_nl = csv_get_newline();
		$t_sep = csv_get_separator();
		$t_filename = "time_report_{$t_bugnote_stats_from_y}-{$t_bugnote_stats_from_m}-{$t_bugnote_stats_from_d}_{$t_bugnote_stats_to_y}-{$t_bugnote_stats_to_m}-{$t_bugnote_stats_to_d}_".csv_get_default_filename();

		header( 'Pragma: public' );
		header( 'Content-Type: text/plain; name=' . urlencode( file_clean_name( $t_filename ) ) );
		header( 'Content-Transfer-Encoding: BASE64;' );
		header( 'Content-Disposition: attachment; filename="' . urlencode( file_clean_name( $t_filename ) ) . '"' );
	else:
?>
<form method="post" action="<?php echo form_action_self() ?>">
	<input type="hidden" name="id" value="<?php echo isset( $f_bug_id ) ? $f_bug_id : 0 ?>" />
	<table border="0" class="width100" cellspacing="0">
		<tr>
			<td class="form-title" colspan="4">
				<?php
					collapse_icon( 'bugnotestats' );
					echo lang_get( 'time_tracking' )
				?>
			</td>
		</tr>
		<tr class="row-2">
			<td class="category" width="25%">
				<?php
					$t_filter = array();
					$t_filter['do_filter_by_date'] = 'on';
					$t_filter['start_day'] = $t_bugnote_stats_from_d;
					$t_filter['start_month'] = $t_bugnote_stats_from_m;
					$t_filter['start_year'] = $t_bugnote_stats_from_y;
					$t_filter['end_day'] = $t_bugnote_stats_to_d;
					$t_filter['end_month'] = $t_bugnote_stats_to_m;
					$t_filter['end_year'] = $t_bugnote_stats_to_y;
					print_filter_do_filter_by_date(true);
				?>
			</td>
		</tr>
		<tr class="row-2">
			<td>
				<?php // Eyal Azulay added this table row
					echo lang_get("category");
					global $t_select_modifier, $t_filter;
					$t_select_modifier = "Category";
					$t_filter[FILTER_PROPERTY_CATEGORY] = $t_category;
					print_filter_show_category();
				?>
			</td>
		</tr>
<?php
	if ( $t_cost_col ) {
?>
		<tr class="row-1">
			<td>
				<?php echo lang_get( 'time_tracking_cost_per_hour' ) ?>:
				<input type="text" name="bugnote_cost" value="<?php echo $f_bugnote_cost ?>" />
			</td>
		</tr>
<?php
	}
?>
		<tr>
			<td class="center" colspan="2">
				<input type="submit" class="button" name="get_bug_stats_button"
					value="<?php echo lang_get( 'time_tracking_get_info_button' ) ?>" />
				<input type="submit" class="button" name="export_bug_stats_button"
					value="<?php echo lang_get( 'time_tracking_export_csv_button' ) ?>" />
			</td>
		</tr>
	</table>
</form>

<?php
	endif;
	if ($f_get_bug_stats_button || $f_export_bug_stats_button){
		$_SESSION['bugnote_stats_from_def'] = $t_bugnote_stats_from_d.':'.$t_bugnote_stats_from_m.':'.$t_bugnote_stats_from_y;
		$_SESSION['bugnote_stats_to_def'] = $t_bugnote_stats_to_d.':'.$t_bugnote_stats_to_m.':'.$t_bugnote_stats_to_y;
		$_SESSION['bugnote_cost'] = $f_bugnote_cost;
	
		# Retrieve time tracking information
		$t_from = "$t_bugnote_stats_from_y-$t_bugnote_stats_from_m-$t_bugnote_stats_from_d";
		$t_to = "$t_bugnote_stats_to_y-$t_bugnote_stats_to_m-$t_bugnote_stats_to_d";
		$t_bugnote_stats = bugnote_stats_get_project_array( $f_project_id, $t_from, $t_to, $f_bugnote_cost, $t_category, !$f_export_bug_stats_button );

		# Sort the array by bug_id, user/real name
		if ( ON == config_get( 'show_realname' ) )
			$t_name_field = 'realname';
		else
			$t_name_field = 'username';
		
		/* SQL already has correct Order By
		$t_sort_bug = $t_sort_name = array();
		foreach ( $t_bugnote_stats as $t_key => $t_item ) {
			$t_sort_bug[$t_key] = $t_item['bug_id'];
			$t_sort_name[$t_key] = $t_item[$t_name_field];
		}
		array_multisort( $t_sort_bug, SORT_NUMERIC, $t_sort_name, $t_bugnote_stats );
		unset( $t_sort_bug, $t_sort_name ); */

		if ( is_blank( $f_bugnote_cost ) || ( (double)$f_bugnote_cost == 0 ) ) {
			$t_cost_col = false;
		}

		$t_prev_id = -1;

		if ($f_export_bug_stats_button){
			ob_start();
			echo "Period: {$t_bugnote_stats_from_d}/{$t_bugnote_stats_from_m}/{$t_bugnote_stats_from_y} - {$t_bugnote_stats_to_d}/{$t_bugnote_stats_to_m}/{$t_bugnote_stats_to_y}".$t_nl;
			echo "Project{$t_sep}Username";
		}else{
?>
<br />
<table border="0" class="width100" cellspacing="0">
	<tr class="row-category-history">
		<td class="small-caption">
			<?php echo lang_get( $t_name_field ) ?>
		</td>
		<td class="small-caption">
			<?php echo lang_get( 'time_tracking' ) ?>
		</td>
<?php		if ( $t_cost_col) { ?>
		<td class="small-caption">
			<?php echo lang_get( 'time_tracking_cost' ) ?>
		</td>
<?php	 	} ?>
	</tr>
<?php
		}
		$t_sum_in_minutes = 0;
		$t_user_summary = array();

		# Initialize the user summary array
		foreach ( $t_bugnote_stats as $t_item ) {
			$t_user_summary[$t_item[$t_name_field]] = 0;
		}

		# Calculate the totals
		foreach ( $t_bugnote_stats as $t_item ) {
			$t_sum_in_minutes += $t_item['sum_time_tracking'];
			$t_user_summary[$t_item[$t_name_field]] += $t_item['sum_time_tracking'];

			$t_item['sum_time_tracking'] = db_minutes_to_hhmm( $t_item['sum_time_tracking'] );
			if ( $t_item['bug_id'] != $t_prev_id) {
				if ($f_export_bug_stats_button){
					echo csv_escape_string( $t_item['project_name'] ) . $t_sep;
					echo csv_escape_string( $t_item['bug_id'] ) . $t_sep;
					echo csv_escape_string( $t_item['summary'] ) . $t_sep;
					echo csv_escape_string( $t_item['sum_time_tracking'] ) . $t_nl;
				}else{
					$t_link = string_get_bug_view_link( $t_item['bug_id'] );
					echo '<tr class="row-category-history"><td colspan="3">' .
						$t_item['project_name'] . " " . $t_link . ": " . string_display( $t_item['summary'] ) . "</td></tr>";
				}
				$t_prev_id = $t_item['bug_id'];
			}
			if ($f_get_bug_stats_button){
?>
	<tr <?php echo helper_alternate_class() ?>>
		<td class="small-caption">
			<?php echo $t_item[$t_name_field] ?>
		</td>
		<td class="small-caption">
			<?php echo $t_item['sum_time_tracking'] ?>
		</td>
<?php			if ($t_cost_col) { ?>
		<td>
			<?php echo string_attribute( number_format( $t_item['cost'], 2 ) ); ?>
		</td>
<?php			} ?>
	</tr>

<?php		}
		} # end for loop
		if ($f_export_bug_stats_button):
			$t_header = ob_get_clean();
/*			$t_first_three_chars = utf8_substr( $t_header, 0, 3 );
			if ( strcmp( $t_first_three_chars, 'ID' . $t_sep ) == 0 ) {
				$t_header = str_replace( 'ID' . $t_sep, 'Id' . $t_sep, $t_header );
			}*/
			echo $t_header;
		else:
?>
	<tr <?php echo helper_alternate_class() ?>>
		<td class="small-caption">
			<?php echo lang_get( 'total_time' ); ?>
		</td>
		<td class="small-caption">
			<?php echo db_minutes_to_hhmm( $t_sum_in_minutes ); ?>
		</td>
<?php		if ( $t_cost_col ) { ?>
		<td>
			<?php echo string_attribute( number_format( $t_sum_in_minutes * $f_bugnote_cost / 60, 2 ) ); ?>
		</td>
<?php 		} ?>
	</tr>
</table>

<br />
<br />

<table border="0" class="width100" cellspacing="0">
	<tr class="row-category-history">
		<td class="small-caption">
			<?php echo lang_get( $t_name_field ) ?>
		</td>
		<td class="small-caption">
			<?php echo lang_get( 'time_tracking' ) ?>
		</td>
<?php		if ( $t_cost_col ) { ?>
		<td class="small-caption">
			<?php echo lang_get( 'time_tracking_cost' ) ?>
		</td>
<?php		} ?>
	</tr>

<?php		foreach ( $t_user_summary as $t_username => $t_total_time ) {
?>
	<tr <?php echo helper_alternate_class() ?>>
		<td class="small-caption">
			<?php echo $t_username; ?>
		</td>
		<td class="small-caption">
			<?php echo db_minutes_to_hhmm($t_total_time); ?>
		</td>
<?php 			if ( $t_cost_col ) { ?>
		<td>
			<?php echo string_attribute( number_format( $t_total_time * $f_bugnote_cost / 60, 2 ) ); ?>
		</td>
<?php 			} ?>
	</tr>
<?php 		} ?>

	<tr <?php echo helper_alternate_class() ?>>
		<td class="small-caption">
			<?php echo lang_get( 'total_time' ); ?>
		</td>
		<td class="small-caption">
			<?php echo db_minutes_to_hhmm( $t_sum_in_minutes ); ?>
		</td>
<?php		if ( $t_cost_col ) { ?>
		<td>
			<?php echo string_attribute( number_format( $t_sum_in_minutes * $f_bugnote_cost / 60, 2 ) ); ?>
		</td>
<?php		} ?>
	</tr>
</table>

<?php
		endif;
	} # end if
	if ($f_get_bug_stats_button):
		collapse_closed( 'bugnotestats' );
?>

<table class="width100" cellspacing="0">
	<tr>
		<td class="form-title" colspan="4">
			<?php
				collapse_icon( 'bugnotestats' );
				echo lang_get( 'time_tracking' );
			?>
		</td>
	</tr>
</table>

<?php
		collapse_end( 'bugnotestats' );
	endif;
