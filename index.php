<?php
if (file_exists("include/host.php"))
{                                   		// i'm running on the server, where WHUFU has its own include directory
	include_once("include/host.php");
	define('INCPATH0', "include/");
}
else 																		// i'm running locally, using the shared include directory
{
	include_once("../include/host.php");
	define('INCPATH0', "../include/");
}
include_once(INCPATH0 . "host.php");
include_once(INCPATH0 . "template.inc");
include_once(INCPATH0 . "class.Properties.php");
include_once(INCPATH0 . "class.ViewBase.php");
include_once(INCPATH0 . "class.DBBase.php");

include_once("class.Things.php");
include_once("class.Pages.php");
include_once("class.Geo.php");				// after Pages

include_once(INCPATH0 . "jfdbg.php");
$noDbg = NODBG_DFLT;

date_default_timezone_set('America/Los_Angeles');		// now required by PHP

// ---------------- Properties Class, to add useful functions -------------

class WhuProps extends Properties
{
	function __construct($props, $over = array())		// do overrides in one call
	{
		parent::__construct(array_merge($props, $over));
	}


	static function parseKeys($str)					// just return names
	{
		$ret = array();
		$vals = explode(',', $str);
		for ($i = 0; $i < sizeof($vals); $i++) 
		{
			$val = explode('=', trim($vals[$i]));
			$ret[] = trim($val[0]);
		}
		sort($ret);
		return $ret;
	}
	static function parseParms($str)				// return key/value pairs
	{
		$ret = array();
		$vals = explode(',', $str);
// dumpVar($vals, "explode(', ', $str)");
		for ($i = 0; $i < sizeof($vals); $i++) 
		{
			$val = explode('=', trim($vals[$i]));
			if (sizeof($val) < 2)
				continue;
			$ret[$val[0]] = trim($val[1]);
		}
		return $ret;
	}
	// collects the so-frequent date(fmt, strtotime(str)) in one place
	function dateFromString($fmt, $str)		{ 		return date($fmt, strtotime($str)); 		}
	
}

// ---------------- Template Class, for nothing just yet -------------

class WhuTemplate extends VwTemplate
{
}

// ---------------- Start Code ---------------------------------------------

session_start();			// always make this the first thing!

$defaults = array(
	'page' => 'home', 
	'type' => 'home', 
	'key'	 =>	'', 
);

$props = new WhuProps($defaults);		// default settings
$props->set($_REQUEST);							// absorb all web parms
$props->dump('props');

$curpage = $props->get('page');
$curtype = $props->get('type');

switch ("$curpage$curtype") 
{
	case 'homehome':		$page = new HomeHome($props);			break;		
	case 'homesearch':	$page = new HomeSearch($props);			break;
	case 'homelook':		$page = new HomeLook($props);				break;
	case 'homeread':		$page = new HomeRead($props);				break;
	case 'homeorient':	$page = new HomeOrient($props);			break;
	case 'homebrowse':	$page = new HomeBrowse($props);			break;
	
	case 'homeabout':		$page = new HomeAbout($props);			break;	
	
	case 'picid':				$page = new OnePic($props);			break;	
	case 'spotid':			$page = new OneSpot($props);			break;	
	case 'daydate':			$page = new OneDay($props);			break;	
	
	case 'logid':				$page = new OneTripLog($props);			break;	
	case 'tripshome':		$page = new AllTrips($props);			break;	
	
	default: 
		dumpVar("$curpage$curtype", "Unknown page/type:");
		echo "No Page Handler: <b>$curpage$curtype</b>";
		exit;
}

$templates = array("main" => 'container.ihtml', "the_page" => $page->file);
$page->startPage($templates, true);
$page->setStyle();
$page->showPage();
$page->endPage();
?>