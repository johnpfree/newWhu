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
class WhuLink
{
	var $classes = '';
	var $params = '';
	var $albumList = false;
	// Can really overload param 3 with whatever you want based on the first two
	function __construct($p, $t, $kparm = '', $txt = '-')
	{
		$defaults = array('classes' => '', 'params' => '', 'txt' => $txt, 'page' => $p, 'type' => $t, 'key' => $kparm);
		$this->props = new WhuProps($defaults);		// default settings
		$this->pagetype = "$p$t";
	}
	function setKey($val)	{	$this->props->set('key', $val);	}
	function url()
	{
		$curkey  = $this->props->get('key');
		dumpVar($this->pagetype, "curkey=$curkey this->pagetype");
		// $this->props->dump("Whulink $curkey");		exit;
		switch ($this->pagetype) {
			case 'picsdate':
				if (substr($curkey, 0, 4) == '2018')			// Flickr
				{
					$this->flik = new Flickr();
					$this->lazyLoadAlbums();								// get all albums just once
					if (isset($this->albumList[$curkey]))		// there is an albom for this date
					{
						$album = $this->albumList[$curkey];
						$url = $this->flik->makeAlbumUrl($album['album_id']);	
						// dumpVar($album['title'], "got an album for $curkey");
						return sprintf("<a target='_blank' title='Flickr Album' %s href='%s'>(%s)</a>", $this->classes, $url, $album['npics']);
					}
					else                    			    		 	// no album, link to first pic of the day
					{
						// dumpVar($curkey, "no album");
						if (empty($this->picsbyday[$curkey]))
							return "-";
						$npic = sizeof($this->picsbyday[$curkey]);
						$pic1 = array_shift($this->picsbyday[$curkey]);
						$url = $this->flik->makePicUrl($pic1['id']);
						return sprintf("<a target='_blank' title='First Flickr Pic for the Day' %s href='%s'>[%s]</a>", $this->classes, $url, $npic);
					}				
				}
				break;
				
			case 'txtdate':
				$day = new WhuDbDay($this->props, $curkey);
				$link = ViewWhu::makeWpPostLink($day->postId(), $this->props->get('key'));
				return sprintf("<a target='_blank' href='%s'>%s</a>", $link, $this->props->get('txt'));				
		}
		return $this->canonicalWhu();
	}
	function lazyLoadAlbums() {
		if (is_array($this->albumList))
			return;
		// dumpVar(gettype($this->albumList), "GET0 ALBUMLIST");
		$this->albumList = $this->flik->getAlbums();
		// dumpVar(gettype($this->albumList), "GET1 ALBUMLIST");
		// dumpVar($this->albumList, "this->albumList");
	}
	function preLoadPics($trip) {
		$flik = new Flickr();
		$start = $trip->startDate();
		$end = date("Y-m-d", strtotime($trip->endDate()) + 86400);
		$pics = $flik->getPhotosForIntervalRaw($start, $end);
		// dumpVar($pics[0], "pics[0] N=" . sizeof($pics));
		for ($i = 0, $this->picsbyday = array(); $i < sizeof($pics); $i++) // whiffle the pics
		{
			$pic = $pics[$i];
			$date = substr($pic['datetaken'], 0, 10);
			if (empty($this->picsbyday[$date]))											// the key of the array is the date
				$this->picsbyday[$date] = array();
			$this->picsbyday[$date][$pic['datetaken']] = $pic;		// add the pic to that array, keyed on it's date/time so they are sorted
			// $this->picsbyday[$date][] = $pic;										// add the pic to that array
		}
		ksort($this->picsbyday);
		foreach ($this->picsbyday as $k => $v) 
		{
			// dumpVar(sizeof($v), "$k, N=");
			ksort($v);
			// foreach ($v as $d => $a) echo sprintf("<br>d=%s t=%s||%s", $k, $d, $a['title']);
		}
		return $this->picsbyday;
	}
	
	function addClass($str)	{	if ($this->classes != '') $this->classes .= ' '; $this->classes .= $str;	}
	function addParam($k,$v)	{	$this->params .= "&$k=$v";	}
	function canonicalWhu()
	{
		return sprintf("<a %s href='?page=%s&type=%s&key=%s%s'>%s</a>", 
				$this->props->get('classes'), 
				$this->props->get('page'), 
				$this->props->get('type'), 
				$this->props->get('key'), 
				$this->props->get('params'), 
				$this->props->get('txt')
			);
	}
}
class WhuSimpleLink extends WhuLink
{
	var $type = 'id';
	function __construct($parm = '')
	{
		$defaults = array('classes' => '', 'params' => '', 'txt' => '', 'page' => $this->page, 'type' => $this->type, 'key' => '');
		$this->props = new WhuProps($defaults);		// default settings
		$this->trip = $parm;
	}
	function url() 
	{
		$this->props->set('key', $this->trip->id());			// overload param 3
		if ($this->hasStuff()) {
			$this->props->set('txt', $this->myIcon);
			return $this->canonicalWhu();
		}
		else
			return '';			
	}
	function hasStuff() { return false; }
}
class WhumapidLink extends WhuSimpleLink
{
	var $page = 'map';
	var $myIcon = "<img src='./resources/icons/glyphicons-503-map.png' width='26' height='20' title='Map'>";
	function hasStuff() { return $this->trip->hasMap(); }
}
class WhuvidsidLink extends WhuSimpleLink
{
	var $page = 'vids';
	var $myIcon = "<img src='./resources/icons/glyphicons-181-facetime-video.png' width='26' height='20' title='Videos'>";
	function hasStuff() { return $this->trip->hasVideos(); }
}
class WhupicsidLink extends WhuSimpleLink
{
	var $page = 'pics';
	var $cameraimg = "<img src='./resources/icons/glyphicons-12-camera.png' width='26' height='20' title='Pictures'>";
	var $flickrimg = "<img src='./resources/icons/social-36-flickr.png' width='26' height='20' title='Flick Pics'>";
	function url()
	{
		$this->props->set('key', $this->trip->id());			// overload param 3
		if ($this->trip->hasFlicks())
		{
			$this->props->set('txt', $this->flickrimg);
			return sprintf("<a target='_blank' href='https://www.flickr.com/photos/142792707@N04/albums/%s'>%s</a>", $this->trip->flickToken(), $this->flickrimg);
		}
		else if ($this->trip->hasWhuPics()) 
		{
			$this->props->set('txt', $this->cameraimg);
			return $this->canonicalWhu();
		}
		else
			return '';
	}
}
class WhutxtsidLink extends WhuLink
{
	function __construct($t)	{ $this->trip = $t; }
	function url($txt = '')
	{
		$myIcon = "<img src='./resources/icons/glyphicons-331-blog.png' width='26' height='20' title='Map'>";
		if (($wp_ref = $this->trip->wpReferenceId()) == 0)
			return '';
		// $this->trip->dump('WhutxtsidLink');
		switch ($wp_ref[0]) {
			case 'cat':			$link = ViewWhu::makeWpCatLink($wp_ref[1]);		break;
			case 'post':		$link = ViewWhu::makeWpPostLink($wp_ref[1]);	break;			
			default:				dumpVar($wp_ref, "BAD RETURN! wp_ref");		exit;
		}
		return sprintf("<a href='%s'>%s</a>", $link, ($txt == '') ? $myIcon : $txt);				//  target='_blank'
	}
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
	function getPhotosForDate($date)
	{
		$tomorrow = date("Y-m-d", strtotime($date) + 86400);
		return $this->getPhotosForIntervalRaw($date, $tomorrow);
	}
	function getPhotosForIntervalRaw($start, $end)
	{
		dumpVar('xx', "getPhotosForIntervalRaw($start, $end)");
		$params = array(
        'api_key'   => $this->apiKey,
        'method'    => 'flickr.photos.search',
        'user_id'   => $this->userid,
        'per_page'  => '500',
        'extras'    => 'date_taken',
        'min_taken_date'	=> $start,
        'max_taken_date'	=> $end,
        'format'    => 'php_serial',
        );
		// dumpVar($params, "params");
    $rsp_obj = $this->flickr->query($params);
		// dumpVar($rsp_obj, "rsp_obj");
		if (sizeof($rsp_obj) == 0)								// offline, call fail
			return array();

		$nphoto = sizeof($photos = $rsp_obj['photos']['photo']);
		// dumpVar($photos[0], "rsp_obj 0, n=$nphoto");

		$iPage = 1;
		while ($nphoto == $params['per_page']) {
			$params['page'] = ++$iPage;
			dumpVar($iPage, "iPage");
	    $rsp_obj = $this->flickr->query($params);
			$nphoto = sizeof($rsp_obj['photos']['photo']);			
			
			dumpVar(sizeof($rsp_obj['photos']['photo']), "page $iPage");
			$photos = array_merge($photos, $rsp_obj['photos']['photo']);
		}
		dumpVar(sizeof($photos), "final size");
    return $photos;  
	}
	function getAlbums($gdate = '')		// return all albums unless one matches $gdate, then return just that one
	{
	  $photosets = $this->flickr->getUserSetList($this->userid);
		// dumpVar($photosets['photoset'][0], "photosets0");
		// dumpVar($photosets, "photosets");
		// exit;
		
		for ($i = 0, $albums = array(); $i < sizeof($photosets); $i++) 
		{
			$photoset = $photosets['photoset'][$i];
			// dumpVar($photoset, "photoset");exit;
			
			$album = array(
				'album_id' => $photoset['id'], 
				'title' => $photoset['title']['_content'], 
				'desc' => $photoset['description']['_content'], 
				'npics' => $photoset['photos'], 
			);
			$album['whu_date'] = $adate = substr($album['title'], 0, 10);
			if ($album['whu_date'] == $gdate)
				return $album;										// Just want this one. fall thru to return 'em all

			$albums[$adate] = $album;
		}
		return (sizeof($albums) > 0) ? $albums : false;
	}
	
	function makePicUrl($id)
	{
		// dumpVar($id, "makePicUrl");
		return sprintf("https://www.flickr.com/photos/%s/%s/", $this->userid, $id);	
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
