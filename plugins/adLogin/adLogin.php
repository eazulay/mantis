<?php
class adLoginPlugin extends MantisPlugin {
 
	function register() {
		$this->name        = 'adLogin';
		$this->description = 'Automated login within AD';
		$this->version     = '1.01';
		$this->requires    = array('MantisCore'       => '1.2.0',);
		$this->author      = 'Cas Nuy';
		$this->contact     = 'Cas-at-nuy.info';
		$this->url         = 'http://www.nuy.info';
	}
 
	function init() { 
		plugin_event_hook( 'EVENT_CORE_READY', 'login_ad' );
	}

 	function login_ad() {
		if ( isset($_SERVER['AUTH_USER'])) {
			$temp = explode('\\', $_SERVER['AUTH_USER']); 
			if ($temp[1] == "") {
				$name = $temp[0];
			} else {
				$name = $temp[1];
			}
			$ok=auth_attempt_script_login( $name );
		}
	}
}