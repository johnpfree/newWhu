<?php
include_once("host.php");
include_once(INCPATH . "template.inc");
include_once(INCPATH . "class.Properties.php");
include_once(INCPATH . "class.ViewBase.php");
include_once(INCPATH . "class.DBBase.php");

include_once("class.Things.php");
include_once("class.Pages.php");
include_once("class.Geo.php");				// after Pages

include_once(INCPATH . "jfdbg.php");
$noDbg = NODBG_DFLT;

date_default_timezone_set('America/Los_Angeles');		// now required by PHP

// ---------------- Properties Class, to add useful functions -------------

class WhuProps extends Properties
{
	function __construct($props, $over = array())		// do overrides in one call
	{
		parent::__construct(array_merge($props, $over));
	}
	
	static function verboseDate($str)				{	return date("l F j, Y", strtotime($str));	}
	
	function pagetypekey($page, $type = NULL, $key = NULL, $id = NULL)
	{
		$this->set('page', $page);
		if ($type == NULL) return;
		
		$this->set('type', $type);
		if ($key == NULL) return;
		
		$this->set('key', $key);
		if ($id == NULL) return;

		$this->set('id', $id);
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
class StyleProps extends WhuProps 
{
	var $palette = 'UNSET';
	function __construct($props, $over = array())		// little hack, overload the $over array to pass the palette name
	{
		$this->palette = $over;
		// dumpVar($over, "Set palette $over");
		parent::__construct($props);
	}

	function pageBackColor() { return $this->getDefault("bbackcolor", "#fff"); }
	function pageLineColor() { return $this->getDefault("bodycolor" , "#000"); }
	function contBackColor() { return $this->getDefault("backcolor" , $this->pageBackColor()); }
	function contLineColor() { return $this->getDefault("linecolor" , $this->pageLineColor()); }
	function allFontColor()  { return $this->getDefault("fontcolor" , $this->contLineColor()); }
	function boldFontColor() { return $this->getDefault("boldcolor" , $this->allFontColor()); }
	function linkColor()     { return $this->getDefault("linkcolor" , $this->allFontColor()); }
	function linkHover()     { return $this->getDefault("linkhover" , $this->allFontColor()); }
}

// ---------------- Template Class, for nothing just yet -------------

class WhuTemplate extends VwTemplate
{}

// ---------------- Start Code ---------------------------------------------

// session_start();			// always make this the first thing!

$defaults = array(
	'page' => 'home', 
	'type' => 'home', 
	'key'	 =>	'', 
);

$props = new WhuProps($defaults);		// default settings
$props->set($_REQUEST);							// absorb all web parms
$props->dump('props');

// grab form requests and package them for the factory below
if ($props->isProp('do_text_search'))	{				// text search
	$props->pagetypekey('results', 'text', $props->get('search_text'));
}
else if ($props->isProp('comment_form')) {		// comment form
	$formkeys = array(
		'choose_purpose' => 'Purpose', 
		'f_name'		 		=> 'Name', 
		'f_email' 			=> 'Email', 
		'f_Topic' 			=> 'Topic', 
		'f_comment' 		=> 'Comment', 
		'f_url' 				=> 'Url', 
	 );	
	$props->pagetypekey($props->get('fpage'), $props->get('ftype'), $props->get('fkey'), $props->get('fid'));	
}
else if ($props->isProp('search_near_spot')) {
}
else if ($props->isProp('search_places')) {	
	$props->pagetypekey('map', 'near', $props->get('search_radius'));	
}
else if ($props->isProp('search_types')) {	
	$props->pagetypekey('map', 'near', $props->get('search_types'));	
}

$curpage = $props->get('page');
$curtype = $props->get('type');

switch ("$curpage$curtype") 
{
	case 'homehome':		$page = new HomeHome($props);			break;		
	case 'homelook':		$page = new HomeLook($props);				break;
	case 'homeread':		$page = new HomeRead($props);				break;
	case 'homeorient':	$page = new HomeOrient($props);			break;
	case 'homebrowse':	$page = new HomeBrowse($props);			break;	
	
	case 'spotid':			$page = new OneSpot($props);			break;	
	case 'daydate':			$page = new OneDay($props);			break;	

	case 'picsid':			$page = new TripPictures($props);		break;	
	case 'picsdate':		$page = new DateGallery($props);		break;	
	case 'picscat':			$page = new CatGallery($props);		break;	
	case 'picid':
		$props->set('id', $props->get('key'));			// Wordpress likes to send pic/id/i#. NOT pic/xxx/c#/i#, so add the id parm
	case 'piccat':	
	case 'picdate':			$page = new OnePic($props);					break;	
	
	case 'logid':				$page = new OneTripLog($props);		break;	
	case 'mapid':				$page = new OneMap($props);			break;	
	case 'mapspot':			$page = new SpotMap($props);			break;	
	case 'mapnear':			$page = new NearMap($props);			break;	
	case 'mapplace':		$page = new PlaceMap($props);			break;	
	
	case 'txtsid':			$page = new TripStories($props);			break;	
	case 'txtwpid':			$page = new TripStory($props);				break;
	case 'txtdate':			$page = new TripStoryByDate($props);	break;
	
	case 'tripshome':		$page = new AllTrips($props);				break;	
	case 'spotshome':		$page = new SpotsHome($props);			break;
	case 'spotstype':		$page = new SpotsTypes($props);			break;
	case 'spotskey':		$page = new SpotsKeywords($props);	break;
	case 'spotsplace':	$page = new SpotsPlaces($props);		break;
	
	case 'searchhome':	$page = new Search($props);				break;
	case 'resultstext':	$page = new SearchResults($props);	break;

	case 'abouthome':		$page = new About($props);				break;	
	case 'contacthome':	$page = new ContactForm($props);	break;	
	
	default: 
		dumpVar("$curpage$curtype", "Unknown page/type:");
		echo "No Page Handler: <b>$curpage$curtype</b>";
		exit;
}

$templates = array("main" => 'container.ihtml', "the_content" => $page->file);
$page->startPage($templates);
$page->setStyle($curpage);
$savepage = $page;
$page->key = $props->get('key');		// just for convenience, everyone needs it
$page->showPage();
if (!is_object($page))
{
	$page = $savepage;
	dumpVar("NOTICE that \$page got f---d up by calling Wordpress.");
}
$page->setCaption();
$page->endPage();
?>