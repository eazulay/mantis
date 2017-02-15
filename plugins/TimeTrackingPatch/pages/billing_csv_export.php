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
	 * @copyright Copyright (C) 2002 - 2010  MantisBT Team - mantisbt-dev@lists.sourceforge.net
	 * @link http://www.mantisbt.org
	 */
	 /**
	  * MantisBT Core API's
	  */
	require_once( 'core.php' );

	require_once( 'filter_api.php' );
	require_once( 'csv_api.php' );
	require_once( 'columns_api.php' );

	auth_ensure_user_authenticated();

	helper_begin_long_process();

	$t_page_number = 1;
	$t_per_page = -1;
	$t_bug_count = null;
	$t_page_count = null;

	$t_nl = csv_get_newline();
 	$t_sep = csv_get_separator();

	# Get bug rows according to the current filter
	
	$t_filename = date("Y-m-d_H-i",time())."_".csv_get_default_filename();
	
	$f_project_id = helper_get_current_project();
	$t_from = gpc_get_string('d_from');
	$t_to = gpc_get_string('d_to');
	$f_bugnote_cost = gpc_get_int('bugnote_cost');
	$f_bugnote_price = gpc_get_string('bugnote_price');
	$t_bugnote_stats = bugnote_stats_get_project_array( $f_project_id, $t_from, $t_to, $f_bugnote_cost, $f_bugnote_price );
	$t_reporting_period = plugin_lang_get('reporting_period') . column_billing_get_date($t_from) . " thru " . column_billing_get_date($t_to);
	$t_sum_amt = 0;
    $t_billing_export = array();
	$t_i = 0;
	
	foreach ( $t_bugnote_stats as $t_item ) {
		if ($t_item['username']!="administrator"){
		    $t_billing_export[$t_i]['username'] = $t_item['realname'];
		    $t_billing_export[$t_i]['summary'] = $t_item['summary'];
		    $t_billing_export[$t_i]['bug_id'] = $t_item['bug_id'];
		    $t_billing_export[$t_i]['sum_time_tracking'] = db_minutes_to_hhmm($t_item['sum_time_tracking']);
		    if($f_bugnote_price){
			    if($f_bugnote_cost){
				    $t_billing_export[$t_i]['user_price'] = number_format($f_bugnote_cost, 2);
				    $t_billing_export[$t_i]['total_amt'] = number_format($f_bugnote_cost * $t_item['sum_time_tracking']/60, 2);
			    }else{
				    $t_billing_export[$t_i]['user_price'] = number_format($t_item['user_price'], 2);
				    $t_billing_export[$t_i]['total_amt'] = number_format(($t_item['sum_time_tracking'] * $t_item['user_price'])/60, 2);
			    }
		    }else{
			    if($f_bugnote_cost){
				    $t_billing_export[$t_i]['total_amt'] = number_format(($f_bugnote_cost * $t_item['sum_time_tracking'])/60, 2);
			    }
		    }
		    $t_i++;
	    }
	}

	# Send headers to browser to activate mime loading

	# Make sure that IE can download the attachments under https.
	header( 'Pragma: public' );

	header( 'Content-Type: text/plain; name=' . urlencode( file_clean_name( $t_filename ) ) );
	header( 'Content-Transfer-Encoding: BASE64;' );

	# Added Quotes (") around file name.
	header( 'Content-Disposition: attachment; filename="' . urlencode( file_clean_name( $t_filename ) ) . '"' );

	# Get columns to be exported
	# export the titles
	$t_first_column = true;
	ob_start();
	$t_titles = array();
	echo $t_reporting_period;
	echo $t_nl;
	if (sizeof($t_billing_export)>0){
	    foreach ( $t_billing_export[0] as $k=>$t_column ) {
		    if ( !$t_first_column ) {
			    echo $t_sep;
		    } else {
			    $t_first_column = false;
		    }
    		echo column_billing_get_title($k) ;
	    }
	    echo $t_nl;

	    $t_header = ob_get_clean();

	    # Fixed for a problem in Excel where it prompts error message "SYLK: File Format Is Not Valid"
	    # See Microsoft Knowledge Base Article - 323626
	    # http://support.microsoft.com/default.aspx?scid=kb;en-us;323626&Product=xlw
	    $t_first_three_chars = utf8_substr( $t_header, 0, 3 );
	    if ( strcmp( $t_first_three_chars, 'ID' . $t_sep ) == 0 ) {
		    $t_header = str_replace( 'ID' . $t_sep, 'Id' . $t_sep, $t_header );
	    }
	    # end of fix

	    echo $t_header;

	    # export the rows
	    foreach ( $t_billing_export as $t_row ) {
		    $t_first_column = true;
		    foreach ($t_row as $k=>$v){
    		    if ( !$t_first_column ) {
	    		    echo $t_sep;
		        } else {
			        $t_first_column = false;
		        }
		        ob_start();
		        echo column_billing_get_field($k, $v);
	        }
	        echo $t_nl;
	    }
	}else{
	    echo plugin_lang_get("no_record_csv");
    }
	
