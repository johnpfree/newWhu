<?php

class DbWpNewBlog extends DbBase
{
	var $tablepref = '';
	function __construct()
	{
		$data = new DbWpData();
		$this->tablepref 	= $data->tablepref();
    $this->dsn = array_merge($this->dsn, $data->dsn());
		// dumpVar($this->dsn, "this->dsn");
		parent::__construct();
	}	
}

// ---------------------------------------------------------------------------------------  
class WhuUrl
{
	var $classes = '';
	var $params = '';
	function __construct($p, $t, $k, $msg, $ex = array())
	{
		$this->page = $p;
		$this->type = $t;
		$this->key  = $k;
		$this->extras = $ex;
	}
	function url()
	{
		$curpage = $props->get('page');
		$curtype = $props->get('type');
		$curkey  = $props->get('key');
		$curmsg  = $props->get('msg');

		if ("$this->page$this->type" == 'picsid')			
		{
			$trip= new WhuDbTrip($props, $this->key);				// straight to the flic collection for new trips
			if (($flik = $trip->flickToken()) != '') {
				return "https://www.flickr.com/photos/142792707@N04/collections/$flik/";	
			}
		}
		else if ("$this->page$this->type" == 'picsdate' && substr($curkey, 0, 4) == '2018')		//flickr day pics?
		{
			if (substr($curkey, 0, 4) == '2018') {
				return (new Flickr)->makeDateUrl($curkey);	
			}
			$flik = new Flickr();
			if ($flik->hasAlbum($curkey)) 
			{
				$url = $flik->makeDateUrl($curkey);	
			}
			else
			{
				$url = $flik->makeFirstPicUrl($curkey);
			}
			$img = "<img src='./resources/icons/social-36-flickr.png' width='26' height='20' title='Pictures'>";
			return sprintf("<a> %s href='%s'>%s</a>", $this->classes, $url, $mg);
			/*
			old:
			<td><a {PIC_CLASS} href="?page=pics&type=date&key={DAY_DATE}&extra={DAY_PICS}">{PICS_MSG}</a></td>
			flik first of the day
			http://jfmac.local/~jf/dev/flickr.php?act=getfordayraw&s=2018-09-05&e=2018-09-06
			flik album
		return sprintf("https://www.flickr.com/photos/%s/sets/%s/", $this->userid, $id);
			*/
		}
		return sprintf("<a> %s href='?page=%s&type=%s&key=%s%s'>%s</a>", $this->classes, $curpage, $curtype, $curkey, $this->params, $this->msg);
		// <a {PIC_CLASS} href="?page=pics&type=date&key={DAY_DATE}&extra={DAY_PICS}">{PICS_MSG}</a>
	}
	function addClass($str)	{	if ($this->classes != '') $this->classes .= ' '; $this->classes .= $str;	}
	function addParam($k,$v)	{	$this->params .= "&$k=$v";	}
}


/* 
Orphans that don't fit into the class structure. Mostly because I don't need to instantiate a WhuThing to use them

-- getGeocode()       uses Google location services to get the GPS of a place

-- getAllSpotKeys()   returns an array of all spot keywords. It does need the database, which I hack up in the call.

-- class Flickr				July 2018 - handle my new love affair with Flickr

-- class AjaxCode			my cute ajax code for the Search page - cleaner, faster, better!
*/
function getGeocode($name)
{
	$geocode_pending = true;
	$delay = 1;
	$res = array('stat' => 'none', 'name' => $name);

	$request_url = sprintf("http://maps.google.com/maps/api/geocode/json?address=%s&sensor=false", urlencode($name));
	$raw = @file_get_contents($request_url);
// dumpVar($raw, "file_get_contents($request_url)");  // exit;

	$json_data=json_decode($raw, true);
	if ($json_data['status'] == "OK")
	{
		$jres = $json_data['results'][0]['geometry'];
// dumpVar($jres['location'], "res");

		$res['lat'] = $jres['location']['lat'];
		$res['lon'] = $jres['location']['lng'];
		$res['stat'] = "yes";
	}
	return $res;
}
// ---------------------------------------------------------------------------------------  
function getWeatherInfo($doit = FALSE, $lat = 0, $lon = 0)
{
	dumpVar(boolStr($doit), "doit?");
  if ($doit) {			// NO Weather!
		return array('W_CITY' => "OFF"
						, 'WEATHER_MARK' => 0
						, 'W_LAT' => '' 
						, 'W_W'	 	=> ''
						, 'W_REL' => ''
						, 'W_WDIR' => ''
						, 'W_FEEL' => ''
						, 'W_HIST' => '');
  }
  $json_string = file_get_contents("http://api.wunderground.com/api/1d24ab90ef8f01c8/geolookup/conditions/almanac/forecast/q/$lat,$lon.json");
	// $php = json_decode(json_encode(json_decode($json_string)), true);
	$php = json_decode($json_string, true);
	
	$info = array('W_CITY' => $php['location']['city'], 'W_STATE' => $php['location']['state']
		, 'WEATHER_MARK' => 1
					, 'W_LAT' => $php['location']['lat'], 'W_LON' => $php['location']['lon']
					, 'W_W' => $php['current_observation']['weather'], 'W_TEMP' => $php['current_observation']['temperature_string']
					, 'W_REL' => $php['current_observation']['relative_humidity'], 'W_WIND' => $php['current_observation']['wind_string']
					, 'W_WDIR' => $php['current_observation']['wind_dir'], 'W_MPH' => $php['current_observation']['wind_mph']
					, 'W_FEEL' => $php['current_observation']['feelslike_f'], 'W_URL' => $php['current_observation']['forecast_url']
					, 'W_HIST' => $php['current_observation']['history_url']);
		
	return $info;
}
// ---------------------------------------------------------------------------------------  
function getAllSpotKeys($db)
{
	$items = $db->getAll("select * from wf_spot_days order by wf_spots_id");

	$singlekeys = array();
	$keypairs = array();
	$allkeys = array();
	for ($i = 0, $str = ''; $i < sizeof($items); $i++) 
	{
		$vals = explode(',', $str = $items[$i]['wf_spot_days_keywords']);
// dumpVar($vals, "explode($str)");
		for ($j = 0; $j < sizeof($vals); $j++) 
		{
			$val = explode('=', trim($vals[$j]));
			// dumpVar($val, "i,j $i,$j");
			if (sizeof($val) == 1)
			{
				if (empty($singlekeys[$val[0]]))
					$singlekeys[$val[0]] = array($items[$i]['wf_spots_id']);
				else if ($singlekeys[$val[0]][sizeof($singlekeys[$val[0]])-1] != $items[$i]['wf_spots_id']) // tricky: save only one instance of spot id         d
					$singlekeys[$val[0]][] = $items[$i]['wf_spots_id'];
			}
			else if (sizeof($val) >= 1)
				$keypairs[trim($val[0])] = trim($val[1]);
			else
				jfdie("parsed poorly: $val");
		}
	}
	unset($singlekeys['']);   // a SpotDay with no keywords shows up as a blank, remove that bunch
	// dumpVar($singlekeys, "singlekeys");
	ksort($singlekeys);
	return $singlekeys;
}

// ---------------------------------------------------------------------------------------  

class SaveForm
{
	function __construct($p)
	{
		$this->props = $p;
		
		$file = getcwd() . '/feedback.csv';
		dumpVar($file, "file");include 'class.Geo.php';
		
		$this->out = new FileWrite($file, 'a');
		$this->out->dodump = false;
	}
	function write($post, $src)
	{
		// date time, purpose, name, email, topic, content, url
		$str = sprintf("%s,%s,%s,%s,%s,%s,%s", date("Y-m-d H:i:s"),
						$this->props->get('choose_purpose'), $this->massageForCsv('f_ndata'), $this->props->get('f_edata'), 
						$this->massageForCsv('f_topic'), $this->massageForCsv('f_comment'), $this->props->get('f_url'));
		
		$this->out->write("$str");
	}
	function massageForCsv($prop)
	{
		$txt = $this->props->get($prop);          // specialized, get the prop here
		$txt = str_ireplace('"', '"""', $txt);    // double quotes in text are doubled
		return '"' . $txt . '"';
	}
}

// ---------------------------------------------------------------------------------------  

class Flickr
{
	var $apiKey = "cb38cb087d9a8de4cf8ed798c149d1d6";
	var $userid = '142792707@N04';
	function __construct() 
	{ 
		$this->flickr = new mdp3_flickr($this->apiKey); 
	}

	function getPhoto($id)
	{
		return $this->flickr->loadPhotoInfo($id);
	}
	
	function makeDateUrl($id)
	{
		return sprintf("https://www.flickr.com/photos/%s/sets/%s/", $this->userid, $id);
	}
	function makeAlbumUrl($id)
	{
		return sprintf("https://www.flickr.com/photos/%s/sets/%s/", $this->userid, $id);
	}
	function makeCollectionUrl($id)
	{
		return sprintf("https://www.flickr.com/photos/%s/collections/%s/", $this->userid, $id);
	}
	function makeSmallSquareUrl($id, $mod = 'sq150')
	{
		$flpic = $this->getPhoto($id);
		$url = $this->flickr->makePhotoImageURLfromArray($flpic['photo']);
		// dumpVar($url, "url");
		return $this->modifyUrl($url, $mod);			
	}
	
	
	function modifyUrl($url, $size = 'sq150') {
		$sizes = array(
				'sq75'  =>	's',	// small square 75x75
				'sq150' =>	'q',	// large square 150x150
				'i100'  =>	't',	// thumbnail, 100 on longest side
				'i240'  =>	'm',	// small, 240 on longest side
				'i320'  =>	'n',	// small, 320 on longest side
				'i500'  =>	'-',	// medium, 500 on longest side
				'i640'  =>	'z',	// medium 640, 640 on longest side
				'i800'  =>	'c',	// medium 800, 800 on longest side
				'i1024' =>	'b',	// large, 1024 on longest side
				'i1600' =>	'h',	// large 1600, 1600 on longest side
				'i2048' =>	'k',	// large 2048, 2048 on longest side
			);
		$ext = substr($url, -4);				// extension
		$str = substr($url, 0, -4);			// main url
		$mod = isset($sizes[$size]) ? $sizes[$size] : '';
		return $str . "_$mod$ext";			// stuff in modifier and rebuild url
	}
}

// ---------------------------------------------------------------------------------------  

class AjaxCode 
{
	var $colWid = 2;
	var $page = 'spots';

	var $oneLink = 
		// <div class="col-md-%s">
		// 	<a class="onecheck" href="?page=%s&type=%s&key=%s">%s (%s)</a>
		// </div>
		// save this CSS in case we resurrect the div style:
		// .onecheck {
		// 	white-space: nowrap;
		// 	padding: .2em .8em;
		// }
		
<<<HTML
		<button class="btn btn-outline-success" type="button"><a href="?page=%s&type=%s&key=%s">%s (%s)</a></button>
HTML;
}

class SpotWeather extends AjaxCode 		// ?page=weather&lat=xx%&lon=xx
{
	var $type = 'place';
	function result($props)
	{
		$info = getWeatherInfo(1, $props->get('lat'), $props->get('lon'));
		// dumpVar($info, "info");

		$templ = new Template('./templates');
		$templ->set_file('main', 'weatherpane.ihtml');
		
		$templ->set_var($info);

		return $templ->parse('MAIN', 'main');
	}
}

class SpotLocation extends AjaxCode 
{
	var $type = 'place';
	function result($page)
	{
		$placeCats = array(-106, 109, -113, 70, 110, 111, 120, 121, 112, 108, 107, 105, 103, 173, 91, 83, 80, 128);
		for ($i = 0, $str = ''; $i < sizeof($placeCats); $i++) 
		{
			$parms = array('wf_categories_id' => ($id = abs($placeCats[$i])));
			$parms['kids'] = ($placeCats[$i] > 0);    // little hack, negative number above means do NOT loop through children

			$spots = $page->build('DbSpots', $parms);
			$cat = $page->build('Category', $id);
			$str .= sprintf($this->oneLink, $this->page, $this->type, $id, $cat->name(), $spots->size());
		}
		return $str;
	}
}
class SpotType extends AjaxCode 
{
	var $colWid = 6;
	var $type = 'camp';
	function result($page)
	{
		$types = WhuDbSpot::$CAMPTYPES;
		// dumpVar($types, "types");
		$str ='';
		foreach ($types as $k => $v)
		{
			$parms = array('camp_type' => $k);
			$spots = $page->build('DbSpots', $parms);
			$str .= sprintf($this->oneLink, $this->page, $this->type, $k, $v, $spots->size());
		}
		return $str;
	}
}
class SpotKey extends AjaxCode 
{
	var $type = 'key';
	function result($page)
	{
		$spotkeys = getAllSpotKeys(new DbWhufu(new Properties(array())));
		// dumpVar($spotkeys, "types");
		$str ='';
		foreach ($spotkeys as $k => $v)
		{
			if (($nv = sizeof($v)) < 2)
				continue;
			$str .= sprintf($this->oneLink, $this->page, $this->type, $k, $k, $nv);
		}
		return $str;
	}
}

class PicPlace extends AjaxCode 
{
	var $page = 'pics';
	var $type = 'cat';
	var $colWid = 3;
	function result($page)
	{
		$cats = $page->build('Categorys', 'all');
		$catlist = $cats->traverse($page->build('Category', $this->root($cats)));
		
		for ($i = 0, $str = ''; $i < sizeof($cats->descendantList()); $i++) 
		{
			$cat = $cats->descendantList()[$i];
			// dumpVar($cat->name(), sprintf("%s. d=%s, id=%s", $i, $cat->depth(), $cat->id()));

			if (($npic = $cat->nPics()) < 2)
				continue;

			$str .= sprintf($this->oneLink, $this->page, $this->type, $cat->id(), sprintf("%s %s", str_repeat('&bull;', $cat->depth()-1), $cat->name()), $npic);
		}
		return $str;
	}
	function root($cats)  { return $cats->placesRoot(); }
}
class PicCat extends PicPlace 
{
	function root($cats)  { return $cats->picCatsRoot();  }
}

?>
