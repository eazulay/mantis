<?php
# MantisBT - a php based bugtracking system
# Copyright (C) 2002 - 2010  MantisBT Team - mantisbt-dev@lists.sourceforge.net
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

require_once( config_get( 'class_path' ) . 'MantisPlugin.class.php' );

class TimeTrackingPatchPlugin extends MantisPlugin  {
	
	
    /**
	 *  A method that populates the plugin information and minimum requirements.
	 */
	function register( ) {
		$this->name = lang_get('plugin_patch_title');
		$this->description = lang_get('plugin_patch_description');
		$this->page = "billing_page"; # Default plugin page
        $this->version = "1.0"; # Plugin version string
        $this->requires = array(
			'MantisCore' => '1.2.0',
		);
        $this->author = "Asit Katiyar"; # Author/team name
        $this->contact = ""; # Author/team e-mail address
        $this->url = "http://www.vivantech.com"; # Support webpage
        
	}
	
    /**
	 * Default plugin configuration.
	 */
	function config() {
		return array(
			'time_tracking_with_pricing' => ON,
		);
	}
	
    function init() {
		//timetracking_autoload();
		spl_autoload_register( array( 'TimeTrackingPatchPlugin', 'autoload' ) );
		
    	$t_pages_path = config_get_global('plugin_path' ). plugin_get_current() . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR;
		$t_path = config_get_global('plugin_path' ). plugin_get_current() . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR;
        
		set_include_path($t_pages_path . PATH_SEPARATOR . get_include_path());
		set_include_path($t_path . PATH_SEPARATOR . get_include_path());
		$this->check_db_fields();
		
	}
    public static function autoload( $className ) {
		
	}
    function timetrackingpatch_autoload() {
    }
    
    function billing_menu( ) {
		return array( '<a href="' . plugin_page( 'billing_page' ) . '">' . plugin_lang_get( 'billing_link' ) . '</a>', );
	}
    function check_db_fields(){
        require_once ('timetracking_db_api.php');
    	
            /*Check for the default user price*/
    	    if (!db_field_exists("default_price", db_get_table("mantis_user_table")))
            {
                $result = db_query("ALTER table ".db_get_table( 'mantis_user_table' )." add default_price DECIMAL(7,2) NOT NULL DEFAULT 0.00");
            }else if( db_get_field_type('default_price', db_get_table("mantis_user_table")) == "int" )
            {
           	    $result = db_query("ALTER table ".db_get_table( 'mantis_user_table' )." MODIFY COLUMN default_price DECIMAL(7,2) NOT NULL DEFAULT 0.00");
            }
        
            /*Check for the user price per hour per project*/
            if (!db_field_exists("user_price", db_get_table("mantis_project_user_list_table")))
            {
                $result = db_query("ALTER table ".db_get_table('mantis_project_user_list_table')." add user_price DECIMAL(7,2) NOT NULL DEFAULT 0");
            }else if ( db_get_field_type('user_price', db_get_table("mantis_project_user_list_table")) == "int" )
            {
           	    $result = db_query("ALTER table ".db_get_table( 'mantis_project_user_list_table' )." MODIFY COLUMN user_price DECIMAL(7,2) NOT NULL DEFAULT 0.00");
            } 
        
            /*Check for the user price per hour per project*/
            if (!db_field_exists("user_price", db_get_table("mantis_project_table")))
            {
                $result = db_query("ALTER table ".db_get_table('mantis_project_table')." add user_price DECIMAL(7,2) NOT NULL DEFAULT 0");
            }else if (db_get_field_type('user_price', db_get_table("mantis_project_table")) == "int" )
            {
           	    $result = db_query("ALTER table ".db_get_table( 'mantis_project_table' )." MODIFY COLUMN user_price DECIMAL(7,2) NOT NULL DEFAULT 0.00");
            }
       
    }
	
}


