<?php
define("HOST", "jfmac");		// name for your server - any string

define("NODBG_DFLT", 0);		// show debug message

define('INCPATH', "/Users/.../");	// path for include files

define('iPhotoPATH', "/Users/.../");	// path to photos
define('PICPATH', 'http://.../');			// url to photos


define('GOOAPIKEY', '...');						// google api key

class DBHost extends mysqli
{
	var $dsn = array(										// mysql DSN info, WF_DB or WP_DATABASE is plugged into the 'database' key by the app
	    'phptype'  => 'mysql',
	    'username' => '...',
	    'password' => '...',
	    'hostspec' => '...',
	    'database' => 'NULL'
	);
}

define('WF_DB', '...');								// whufu database name
define("WP_DATABASE", '...');					// Wordpress database
define("WP_PATH", '.../');						// path to Wordpress
?>
