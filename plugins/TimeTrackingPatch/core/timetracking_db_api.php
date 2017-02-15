<?php
/**
 * Retrieve the type of given table field
 * @param string $p_field_name a valid field name, string $p_table_name a valid database table name
 * @return string datatype of the field
 */

/**
 * requires adodb library
 */
 require_once( 'adodb' . DIRECTORY_SEPARATOR . 'adodb.inc.php' );

 function db_get_field_type($p_field_name, $p_table_name){
    global $g_db;
	$columns = $g_db->MetaColumns( $p_table_name );
	return is_array( $columns )? $columns[strtoupper($p_field_name)]->type : '';
}