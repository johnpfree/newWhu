<?php
define("HOST", "jfmac");

$eold = error_reporting(E_ALL);	//E_ERROR);
// $str = sprintf("old->new %X->%X strict=%X all=%X error=%X", $eold, $enew, E_STRICT, E_ALL, E_ERROR);
// dumpVar($str, "xxxx");

define("NODBG_DFLT", 0);

define('INCPATH', "/Users/jf/Sites/include/");
define('JQ_INCPATH', '../include/jquery.js');
//define('JQ_INCPATH', 'https://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js');
//https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.6/jquery-ui.min.js

define('iPhotoPATH', "/Users/jf/Sites/cloudyhands.com/pix/iPhoto/");
define('iPhotoURL', "../cloudyhands.com/pix/iPhoto/");

define('PICPATH', 'http://jfmac.local/~jf/cloudyhands.com/pix/');
// 150718 - for some reason relative paths re fucked up, so I am fixing abs path
// define('PICPATH', "/Users/jf/Sites/cloudyhands.com/pix/");

define('REL_PICPATH', "../cloudyhands.com/");
define('REL_PICPATH_WF', "../cloudyhands.com/");
define('RAWPICPATH', 'galleries');		// 'pc091020'

define('WF_DB', 'whufu');

// localhost
define('GOOAPIKEY', 'ABQIAAAAlIXDdyC97EmuJeZHm9CxgBS_SpYYKkgqa-wX1jPJw-rXk3e4OhRlQoodb26v8cEDzVCEIuzs4UIdyQ');

class DBHost extends mysqli
{
	var $dsn = array(
	    'phptype'  => 'mysql',
	    'username' => 'jf',
	    'password' => 'trailview',
	    'hostspec' => '127.0.0.1',
	    // 'hostspec' => 'localhost',
	    'database' => 'NULL'
	);
}

define("WP_DATABASE", 'wptest');
define("WP_PATH", '../wptest/');			// used NOW to pull in new posts
?>
