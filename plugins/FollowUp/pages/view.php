<?php
	auth_ensure_user_authenticated( );
	$t_css_url = config_get( 'css_include_file' );
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <link rel="stylesheet" type="text/css" href="<?php echo string_sanitize_url( helper_mantis_url( $t_css_url ), true ); ?>" />
    <script type="text/javascript"><!--
		if (document.layers){ document.write("<style>td{padding:0px;}<\/style>"); }
    // --></script>
   	<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
	<meta http-equiv="Pragma" content="no-cache" />
	<meta http-equiv="Cache-Control" content="no-cache" />
	<meta http-equiv="Pragma-directive" content="no-cache" />
	<meta http-equiv="Cache-Directive" content="no-cache" />
	<meta name="robots" content="noindex,follow" />
	<link rel="shortcut icon" href="/images/favicon.ico" type="image/x-icon" />
    <link rel="search" type="application/opensearchdescription+xml" title="MantisBT: Text Search" href="browser_search_plugin.php?type=text" />	<link rel="search" type="application/opensearchdescription+xml" title="MantisBT: Issue Id" href="browser_search_plugin.php?type=id" />
    <title>Send Follow Up Email - Get-It-Write Tracking System</title>
<script type="text/javascript" src="javascript/min/common.js"></script>
<script type="text/javascript">var loading_lang = "Loading...";</script><script type="text/javascript" src="javascript/min/ajax.js"></script>
<script type="text/javascript">
function selectElement(element) {
    if (window.getSelection) {
        var sel = window.getSelection();
        sel.removeAllRanges();
        var range = document.createRange();
        range.selectNodeContents(element);
        sel.addRange(range);
    } else if (document.selection) {
        var textRange = document.body.createTextRange();
        textRange.moveToElementText(element);
        textRange.select();
    }
}
</script>
<?php
	$t_page_number = 1;
	$t_per_page = -1;
	$t_bug_count = null;
	$t_page_count = null;

	# Get bug rows according to the current filter
	$t_issues = filter_get_bug_rows( $t_page_number, $t_per_page, $t_page_count, $t_bug_count );
	if( $t_issues === false )
		$t_issues = array();
	$t_columns = array_keys( getClassProperties('BugData', 'protected') );
	$t_user_id = auth_get_current_user_id();
	$t_bugnote_order = current_user_get_pref( 'bugnote_order' );

	html_page_top2();
	print_recently_visited();

	$t_filter = current_user_get_bug_filter();
	# NOTE: this check might be better placed in current_user_get_bug_filter()
	if ( $t_filter === false ) {
		$t_filter = filter_get_default();
	}
	list( $t_sort, ) = explode( ',', $t_filter['sort'] );
	list( $t_dir, ) = explode( ',', $t_filter['dir'] );

	$t_checkboxes_exist = false;

	$t_icon_path = config_get( 'icon_path' );

	# Improve performance by caching category data in one pass
	if ( helper_get_current_project() > 0 ) {
		category_get_all_rows( helper_get_current_project() );
	} else {
		$t_categories = array();
		foreach ($rows as $t_row) {
			$t_categories[] = $t_row->category_id;
		}
		category_cache_array_rows( array_unique( $t_categories ) );
	}
	$t_columns = helper_get_columns_to_view( COLUMNS_TARGET_VIEW_PAGE );

	$col_count = count( $t_columns );

	$t_filter_position = config_get( 'filter_position' );
	if ( ( $t_filter_position & FILTER_POSITION_TOP ) == FILTER_POSITION_TOP ) {
		filter_draw_selection_area( $f_page_number );
    }
    # Follow Up Start
    ?>
<br>
<div class="width100" ondblclick='selectElement(this)'>
<?php
	foreach( $t_issues as $t_issue ){
        $issue_id = $t_issue->id;
        $t_info_required_from_def = custom_field_get_definition( 2 );
        echo string_get_bug_view_link( $issue_id ).' ';
        print_custom_field_value( $t_info_required_from_def, 2, $issue_id );
        echo "<br>\n";
    }
echo "</div>";
html_page_bottom();
?>