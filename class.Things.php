<?php

	class WhuThing extends DbWhufu
	{
		var $data = NULL;
		var $isCollection = false;
		var $hasData = true;
		function __construct($p, $key = NULL)
		{
			// new hack!  Single parameter means am casting to a child class
			if (is_null($key))
			{
				parent::__construct($p->props);
				$this->data = $p->data;
				return;
			}
			parent::__construct($p);
			$this->data = $this->getRecord($key);
			// dumpVar(boolStr(is_array($this->data)), "$key");
			if (!is_array($this->data))
				$this->hasData = false;
		}

		// --------- debugging, protection
		function dump($txt = "")
		{
			$txt = (($txt == "")  ? "dump " : "$txt") . " -- class=" . get_Class($this);
			 dumpVar($this->data, $txt);
		 }
		function assert($val, $txt = "Assert FAILED") {
			if ($val) return;
			jfDie($txt);
		}
		function assertIsCollection()  { $this->assert($this->isCollection, "NOT a Collection");  }

		// --------- generalized get()
		function dbValue($key)   
		{
			$this->assert($this->hasData, sprintf("Object of class %s is empty, key=%s.", get_class($this), $key));
			$this->assert(isset($this->data[$key]), "this->data[$key] not found for class=" . get_class($this));
			return $this->data[$key];  
		}

		// --------- getRecord() should come here to die
		function getRecord($key) 
		{ 
			dumpVar($key, "key");
			jfdie(get_Class($this) . "::getRecord parameter not recognized"); 
		}
		
		// --------- getRecord() overloading utilities
		function isSpotArray($key)				{	return (is_array($key) && isset($key[0]) && isset($key[0]['wf_spots_id']));	}
		function isSpotRecord($key)				{	return (is_array($key) && isset($key['wf_spots_id']));	}
		function isSpotDayRecord($key)		{	return (is_array($key) && isset($key['wf_spot_days_date']));	}
		function isSpotDayParmsArray($key){	return (is_array($key) && isset($key['spotId']) && isset($key['date']));	}
		function isTripRecord($key)				{	return (is_array($key) && isset($key['wf_trips_id']));	}
		function isPicRecord($key)				{	return (is_array($key) && isset($key['wf_images_id']));	}
		function isPicCatRecord($key)			{	return (is_array($key) && isset($key['wf_categories_id']));	}
		function isDayRecord($key)				{	return (is_array($key) && isset($key['wf_days_date']));	}
		function isTextSearch($key)				{	return (is_array($key) && isset($key['searchterm']));	}

		// --------- utilities
		function isDate($str) 				// true for 
		{
			if (!is_string($str))
				return false;
			$parts = explode('-', $str);
	// dumpVar($parts, "indate($str)");
			if (sizeof($parts) < 3)		return FALSE;
			if ($parts[0] < 2007)			return FALSE;
			if ($parts[1] > 12)				return FALSE;
			if ($parts[2] > 31)			return FALSE;
			return TRUE;
		}
		function baseExcerpt($str, $chop=400)
		{
	// dumpVar($str, "str");
			$str = strip_tags($str);
			if (strlen($str) < $chop)											// don't need to truncate?
				return $str;
			$chop -= 4;																		// lop off 4 more for " ..."
			$newlen = strrpos(substr($str, 0, $chop), ' ');		// find the last space before limit
			return substr($str, 0, $newlen) . " ...";
		}
		function massageDbText($txt) 
		{
			return stripslashes($txt);
		}
	
		// --------- collections
		function one($i)		// one() creates an object from the collection daya and returns it
		{
			$this->assertIsCollection();
			$this->assert(isset($this->data[$i]), "this->data[$i] is NOT set!");
			$pclass = get_parent_class($this);
			// dumpVar($pclass, "pclass");
			// $this->props->dump();
			return $this->build($pclass, $this->data[$i]);
		}
		function size() { return sizeof($this->data);  }
		function isEmpty() { return $this->size() == 0;  }
	
		// --------- factory
		function build ($type = '', $key) 
		{
			// dumpVar($type, "THING Build: type key=$key");
			if ($type == '') {
				throw new Exception("THING Build is blank.");
				// throw new Exception("Invalid Thing Type = $type.");
			} 
			else 
			{ 
				$className = (substr($type, 0, 3) == "Whu") ? $type : 'Whu'.ucfirst($type);

				if (class_exists($className)) {
					return new $className($this->props, $key);
				} else {
					throw new Exception("Thing type $type=>$className not found.");
				}
			}
		}
	}
	
	class WhuDbTrip extends WhuThing 
	{
		var $lazyWpCat = 0;
		function getRecord($key)
		{
			if ($this->isTripRecord($key))
				return $key;

			return $this->getOne("select * from wf_trips where wf_trips_id=$key");	
		}

		function id()					{ return $this->dbValue('wf_trips_id'); }
		function name()				{ return $this->dbValue('wf_trips_text'); }
		function desc()				{ return $this->dbValue('wf_trips_desc'); }
		function folder()			{ return $this->dbValue('wf_trips_picfolder'); }
		function startDate()	{ return $this->dbValue('wf_trips_start'); }
		function endDate()		{ return $this->dbValue('wf_trips_end'); }

		// function name()				{ return $this->dbValue('wf_trips_odometer'); }
		// function name()				{ return $this->dbValue('wf_trips_map_lat'); }
		// function name()				{ return $this->dbValue('wf_trips_map_lon'); }

		function fid()				{ return $this->dbValue('wf_trips_map_fid'); }
		function mapboxId()		{ return $this->dbValue('wf_trips_extra'); }
		function isNewMap()		{ return (strlen($this->fid()) > 1); }

		// function wpCatId()
		// {
		// 	if ($this->lazyWpCat)
		// 		return $this->lazyWpCat;
		//
		// 	$day = $this->build('DbDay', $this->startDate());
		// 	if ($day->hasData) {
		// 		$cat = wp_get_post_categories($day->postId());
		// 		return $this->lazyWpCat = $cat[0];
		// 	}
		// 	// NOTE! Our convention is that if the first day has no post, the whole thing hsa no posts
		// 	return $this->lazyWpCat = 0;
		// }
	}
	class WhuTrip extends WhuDbTrip 
	{
		
		var $multiMaps = array(
			"johnpfree.02do91ob" => array('name' => "Eureka"		, 'file' => "multiEureka.js"), 
			"johnpfree.pl58eik5" => array('name' => "395" 	  	, 'file' => "multi395.js"), 
			"johnpfree.29opambf" => array('name' => "Louisville", 'file' => "mLouisvilleRtes.json"), 
			// "johnpfree.0hb1fke9" => array('name' => "Louisville", 'file' => "mLouisville.js"   ),
		);
		function makeStoriesLink()
		{}
		function hasPics()
		{
			$pics = $this->build('Pics', array('tripid' => $this->id())); 
			return $pics->size() > 0;
		}
		function hasStories()	{
			$count = $this->getOne("select COUNT(wp_id) nposts from wf_days where wp_id>0 AND wf_trips_id=" . $this->id());		
			return $count['nposts'] > 0;
		}
		function hasMap()
		{
		// dumpVar($this->data['wf_trips_map_fid'], "this->data['wf_trips_map_fid']");
			if ($this->isNewMap())			// cheapest test
				return true;
			return false;
			// not a new map, look for routes for old map
			return $this->getAll("select * from wf_routes where wf_trips_map=" . $this->id());				
		}
		function hasMapboxMap()	{	return ((substr($this->mapboxId(), 0, 10) == 'johnpfree.') != '');	}
		function hasGoogleMap()	{	return ($this->mapboxId() == 'kml');	}
		
		function mapboxJson()		{ return $this->multiMaps[$this->mapboxId()]['file'];	}
		function getGeocode($name)
		{
			$geocode_pending = true;
			$delay = 1;
			$res = array('stat' => 'none', 'name' => $name);

	// June 2013, try new url
		    $request_url = sprintf("http://maps.google.com/maps/api/geocode/json?address=%s&sensor=false", urlencode($name));
				$raw=@file_get_contents($request_url);
	dumpVar($raw, "file_get_contents($request_url)");	// exit;
				$json_data=json_decode($raw, true);
				if ($json_data['status'] == "OK")
				{
					$jres = $json_data['results'][0]['geometry'];
	dumpVar($jres['location'], "res");

					$res['lat'] = $jres['location']['lat'];
					$res['lon'] = $jres['location']['lng'];
					$res['stat'] = "yes";
				}
	dumpVar($res, "result");
	exit;
			return $res;
		}
	}
	class WhuTrips extends WhuTrip 
	{
		var $isCollection = true;
		function getRecord($parms = true)
		{
			if ($parms)			// little hack 'cuz I don't need data for functions below
				return true;
			
			$defaults = array('order' => "DESC", 'field' => 'wf_trips_start');
			$qProps = new Properties($defaults, $parms);
			// $qProps->dump('$qProps->');
			$q = sprintf("select * from wf_trips ORDER BY %s %s", $qProps->get('field'), $qProps->get('order'));	
			// dumpVar($q, "q");
			return $this->getAll($q);	
			// return $this->getOne(sprintf("select * from wf_trips ORDER BY %s %s"), $qProps->get('field'), $qProps->get('order'));
		}
		// good a place as any to put the global queries for home page
		function numPics() 	{	return $this->getOne("select count(*) RES from wf_images")['RES'];	}
		function numSpots()	{	return $this->getOne("select count(*) RES from wf_spots" )['RES'];	}
		function numPosts()	{	return $this->getOne("select count(distinct wp_id) RES from wf_days" )['RES'];	}
		function numMaps() 	{	return $this->getOne("select count(*) RES from wf_trips where wf_trips_extra !='' OR wf_trips_map_fid !=''")['RES'];	}
	}


	class WhuDbDay extends WhuThing 
	{
		var $prvnxt = NULL;			// save calculation
		function getRecord($key)
		{
			// dumpVar($key, "key");
			// dumpBool(is_array($key), "arr");
			if (is_array($key))					// $key == the record?
				return $key;

			if ($this->isDate($key))		// $key == date?
				return $this->getOne("select * from wf_days where wf_days_date='$key'");	

			WhuThing::getRecord($key);		// FAIL
		}
		function date()				{ return $this->dbValue('wf_days_date'); }
		function tripId()			{ return $this->dbValue('wf_trips_id'); }
		function spotId()			{ return $this->dbValue('wf_spots_id'); }
		function hasSpot()		{ return $this->spotId() > 0; }
		function dayName()		{ return $this->dbValue('wf_route_name'); }
		function dayDesc()		{ return $this->dbValue('wf_route_desc'); }
		function nightName()	{ return $this->massageDbText($this->dbValue('wf_stop_name')); }
		function nightDesc()	{ return $this->dbValue('wf_stop_desc'); }
		function postId()			{ return $this->dbValue('wp_id'); }
		function hasStory()		{ return $this->postId() > 0; }

		function day()
		{
			$q = sprintf("select COUNT(wf_days_date) num from wf_days where wf_trips_id=%s AND wf_days_date < '%s'", $this->tripId(), $this->date());
			$item = $this->getOne($q);
			return $item['num'] + 1;
		}
		
		function miles()			{ return $this->dbValue('wf_days_miles'); }
		function cumulative()	{ return $this->dbValue('wf_days_cum_miles'); }
		
		function lat()				{ return $this->dbValue('wf_days_lat'); }
		function lon()				{ return $this->dbValue('wf_days_lon'); }		

		function pics() 			{	return $this->build('WhuPics', array('date' => $this->date()));	}
		function hasPics() 		{	return $this->pics()->size() > 0;	}
		
		function yesterday() {	return $this->anotherDate("-1");	}		// set of functions for getting the next and previous dates. Doesn't care if it's in a trip
		function tomorrow()  {	return $this->anotherDate("1");	}
		function anotherDate($offset)
		{
			$date = Properties::sqlDate($x = sprintf("%s $offset day", $this->date()));
			return $date;
		}
				
		function previousDayGal() 				// set of functions for day gallery navigation - some days don't have pictures and must be skipped
		{
			if (is_null($this->prvnxt))
				$this->getPrvNxtDayGal();
			return $this->prvnxt['prev'];
		}
		function nextDayGal() 
		{
			if (is_null($this->prvnxt))
				$this->getPrvNxtDayGal();
			return $this->prvnxt['next'];
		}
		function getPrvNxtDayGal()
		{
			$items = $this->getAll("select * from wf_days order by wf_days_date");
			$wps = array_column($items, 'wf_days_date');
			$idx = array_search($id = $this->date(), $wps);
			
			$this->prvnxt = array();
			for ($i = 1; $i < 10; $i++) 
			{
				$prvpics = $this->build('WhuPics', array('date' => $d0 = $wps[$idx - $i]));
				if ($dc = $prvpics->size() > 0)
					break;
			}
			$this->prvnxt['prev']  = $d0;
			$this->prvnxt['prevc'] = $dc;
						
			for ($i = 1; $i < 10; $i++) 
			{
				$prvpics = $this->build('WhuPics', array('date' => $d0 = $wps[$idx + $i]));
				if ($dc = $prvpics->size() > 0)
					break;
			}
			$this->prvnxt['next']  = $d0;
			$this->prvnxt['nextc'] = $dc;
		}
	}

	class WhuDbDays extends WhuDbDay {
		var $isCollection = true;
		function getRecord($parm)			// trip id
		{
			if ($this->isTextSearch($parm))						// for text search
			{
				$qterm = $parm['searchterm'];
				$q = "SELECT d.wf_days_date,d.wf_stop_name
							FROM wf_days d
							JOIN wf_spots s ON s.wf_spots_id=d.wf_spots_id
							JOIN wf_spot_days sd ON sd.wf_spot_days_date=d.wf_days_date
							WHERE d.wf_stop_name LIKE '$qterm' OR d.wf_stop_desc LIKE '$qterm'
							OR d.wf_route_name LIKE '$qterm' OR d.wf_route_desc LIKE '$qterm'
							OR s.wf_spots_name LIKE '$qterm' OR sd.wf_spot_days_desc LIKE '$qterm'";
				dumpVar($q, "q");
				return $this->getAll($q);
			}

			if (is_array($parm))					// an array of day records (for post days)
			{
				$this->assert(isset($parm[0]['wf_days_date']));
				return $parm;
			}

			$this->assert($parm > 0);
			return $this->getAll("select * from wf_days where wf_trips_id=$parm order by wf_days_date");
		}
	}
	class WhuDayInfo extends WhuDbDay // WhuDbDay collects all the day, spot, and spot_day shit together
	{
		function getRecord($key)
		{
			if ($this->isDate($key))		// $key == date?
		 	 	$key = $this->build('DbDay', $key);

			if (get_class($key) == 'WhuDbDay')
				return $key->data;

			return parent::getRecord($key);
		}
		function nightName()		{	return $this->massageDbText($this->getSpotandDaysArranged('nightName'));	}
		function nightDesc()		{	return $this->getSpotandDaysArranged('nightDesc');	}
		function nightNameUrl()			// No Spot => no URL
		{	
			$name = $this->nightName();
			if ($this->hasSpot())
				return sprintf("<a href='?page=spot&type=id&key=%s'>%s</a>", $this->spotId(), $name);
			return $name;
		}

		function lat()	{	return $this->getSpotandDaysArranged('lat');	}
		function lon()	{	return $this->getSpotandDaysArranged('lon');	}
		// function town()	{	return $this->hasSpot() ? $this;	}
	
		function getSpotandDaysArranged($key)
		{
			$keys = array(
				// 'dayName' => 'wf_route_name',
				// 'dayDesc' => 'wf_route_desc',
				'nightName' => 'wf_stop_name',
				'nightDesc' => 'wf_stop_desc',
				'lat' => 'wf_days_lat',
				'lon' => 'wf_days_lon',
			);
			assert(isset($keys[$key]), "getSpotandDaysArranged() cannot handle $key.");
		                           
			if (!$this->hasSpot()) { //dumpVar($key, "key"); dumpVar($this->dbValue($keys[$key]), "this->dbValue($keys[$key])");
				return $this->dbValue($keys[$key]);		// no spot, return the day value
			}
		
			if (!isset($this->spot))								// first request, remember the Spot
				$this->spot = $this->build('DbSpot', $this->spotId());	// create the spot
			
			switch ($key) {
				case 'nightName':	return $this->massageDbText($this->spot->name());
				case 'lat':				return $this->spot->lat();
				case 'lon':				return $this->spot->lon();
			}
		
			// done with everything else, below is for desc
			// get the spot day
			$nightDay = $this->build('DbSpotDay', array('spotId' => $this->spotId(), 'date' => $this->date()));
			// does it exist?
			if ($nightDay->hasData)
				return $nightDay->desc();
			// if not, return the day's night desc
			return parent::nightDesc();
		}
		
		function strLatLon($precision = 5)
		{
			return sprintf("%s, %s", round($this->lat(), $precision), round($this->lon(), $precision));
		}
	}

	class WhuDbSpot extends WhuThing 
	{
		var $lazyDays = NULL;
		var $spottypes = array(
					'CAMP'		=> 'Place to overnight in the van',
					'LODGE'		=> 'Hotel/Motel',
					'HOTSPR'	=> 'Hot Soak',
					'HOUSE'		=> 'Somebody\'s house',
					'NWR'			=> 'Wildlife Refuge',
					);
		var $camptypes = array(
					'usfs'		=> 'Forest Service Campground',
					'usnp'		=> 'National Park Campground',
					'state'		=> 'State Park Campground',
					'blm'			=> 'Bureau of Land Management Campground',
					'ace'			=> 'Army Corps of Engineers Campground',
					'nwr'			=> 'Campground on a Wildlife Refuge',
					'county'	=> 'County Campground',
					'private'	=> 'Private Campground',
					'roadside'	=> 'Pullover when there\'s no place to camp',
				);
		var $excludeString = " wf_spots_types NOT LIKE '%DRIVE%' AND wf_spots_types NOT LIKE '%WALK%' AND wf_spots_types NOT LIKE '%HIKE%' AND wf_spots_types NOT LIKE '%PICNIC%' AND wf_spots_types NOT LIKE '%HOUSE%'";
					
		function getRecord($key)		// parm is spot id OR the record for iteration
		{
			if ($this->isSpotRecord($key))
				return $key;

			return $this->getOne($q = "select * from wf_spots where wf_spots_id=$key");	
		}
		function id()				{ return $this->dbValue('wf_spots_id'); }
		function name()			{ return $this->massageDbText($this->dbValue('wf_spots_name')); }
		function town()			{ return $this->massageDbText($this->dbValue('wf_spots_town')); }
		function partof()		{ return $this->dbValue('wf_spots_partof'); }
		function types()		{ return $this->dbValue('wf_spots_types'); }
		function status()		{ return $this->dbValue('wf_spots_status'); }
		
		function shortName() 
		{
			$name = $this->name();
			// $n0 = $name;
			$name = str_ireplace(" and campground", '', $name);
			$name = str_ireplace(" campground", '', $name);
			$name = str_ireplace(" camping", '', $name);
			$name = str_ireplace(" state park", '', $name);
			$name = str_ireplace(" state beach", '', $name);
			$name = str_ireplace(" county park", '', $name);
			$name = str_ireplace(" rv park", '', $name);
			$name = str_ireplace(" state recreation area", '', $name);
			$name = str_ireplace(" recreation area", '', $name);
			$name = str_ireplace(" and", '', $name);
			// dumpVar($name, "name in=$n0, oty=");
			return $name;
		}
		
		function prettyTypes()		// an array of types, suitable for printing
		{
			$types = WhuProps::parseKeys($this->types());	
			foreach ($types as $k => $v) 
			{
				if ($v == 'CAMP')
				{
					$stats = WhuProps::parseParms($this->status());	
					// dumpVar($stats, "this->status");
					foreach ($stats as $k1 => $v1) 
					{
						// dumpVar($v1, "v1 $k1");
						if ($k1 == 'CAMP' && isset($this->camptypes[$v1]))
						{
							$ret[$v] = $this->camptypes[$v1];
							// dumpVar($ret[$v], "Cret[$v]");
							continue 2;
						}
					}
				}
				$ret[$v] = $this->spottypes[$v];
			}
			return $ret;
		}
		
		function visits()		{ 
			$vfld = $this->dbValue('wf_spots_visits');
			if ($vfld == 'many')
				return 'many';
			if ($vfld == 'none')
				return 'never';

			if ($this->lazyDays == NULL)
				$this->lazyDays = $this->build('DbSpotDays', $this->id());

			if (($ndays = $this->lazyDays->size()) == 0)
				return 'never';

			if (isset($vfld[0]) && $vfld[0] == "+")
				$ndays += substr($vfld, 1);		
				
			dumpVar($ndays, "vfld = $vfld, day recs=" . $this->lazyDays->size() . "RESULT");
			return $ndays;
		}
		
		function keywords()
		{
			if ($this->lazyDays == NULL)
				$this->lazyDays = $this->build('DbSpotDays', $this->id());
			
			for ($i = 0, $allkeys = array(); $i < $this->lazyDays->size(); $i++)
			{
				$spotDay = $this->lazyDays->one($i);
				$allkeys = array_merge(array_flip($spotDay->keywords()), $allkeys);
				// dumpVar($spotDay->keywords(), "spotDay->keywords()");
				// dumpVar($allkeys, "$i keys");
			}
			return array_flip($allkeys);
		}

		function lat()		{ return $this->dbValue('wf_spots_lat'); }
		function lon()		{ return $this->dbValue('wf_spots_lon'); }

		function getInRadius($dist = 100.)		// returns an array of spot records, suitable for creating a DbSpots collection
		{
			$lat = $this->lat();
			$lon = $this->lon();
			
			$q = "SELECT *, ((ACOS(SIN($lat * PI() / 180) * SIN(wf_spots_lat * PI() / 180) + COS($lat * PI() / 180) * COS(wf_spots_lat * PI() / 180) * COS((-wf_spots_lon + $lon) * PI() / 180)) * 180 / PI()) * 60 * 1.1515) AS distance FROM wf_spots WHERE wf_spots_lon != '' AND WhuDbSpot::excludeString ORDER BY distance ASC";
			$items = $this->getAll($q);

			// NOTE, the spot we searched for is the first item here, with distance = 0
			for ($i = 0, $ret = array(); $i < sizeof($items); $i++) 
			{
	// dumpVar(sprintf("id:%04d., %s. -%s-", $items[$i]['wf_spots_id'], $items[$i]['distance'], $this->fullSpotName($items[$i])), $items[$i]['wf_spots_date']);
				if ($items[$i]['distance'] > $dist)
					break;
				$ret[] = $items[$i];
			}
	dumpVar(sizeof($ret), "within $dist");
			return $ret;
		}
	}
	class WhuDbSpots extends WhuDbSpot 
	{
		var $isCollection = true;
		function getRecord($searchterms = array())
		{
			if ($this->isSpotArray($searchterms))		// already have a list?
				return $searchterms;
			
			if (is_string($searchterms))												// for text search
			{
				$q = "SELECT * FROM wf_spots s JOIN wf_spot_days d ON s.wf_spots_id=d.wf_spots_id WHERE s.wf_spots_name LIKE '$searchterms' OR d.wf_spot_days_desc LIKE '$searchterms' GROUP BY s.wf_spots_id";
				// dumpVar($q, "q");
				return $this->getAll($q);
			}
			
			// assume this is an array of search terms
			$deflts = array(
				'order'	=> 'wf_spots_name',
				'where'	=> array(),
			);
			if (sizeof($searchterms) == 0)			// show all
			{
				$where = " WHERE " . $this->excludeString;				
			}
			else
			{
				$where = "WHERE ";
				foreach ($searchterms as $k => $v) 
				{
				 $where .= "$v LIKE '%$k%' OR ";
				}
			 $where = substr($where, 0, -3);
			}	 
			$q = sprintf("SELECT * FROM wf_spots %sORDER BY %s", $where, $deflts['order']);
			// dumpVar($searchterms, "$q - st");
			return $this->getAll($q);

			// $props = new Properties(array())
			// for ($i = 0, $wherestr = ''; $i < sizeof($deflts['where']); $i++)
			// {
			// 	$wherestr .= sprintf('%s %s', ($i == 0) ? 'WHERE' : "AND", $deflts['where'][$i]);
			// 	dumpVar($wherestr, "$i wherestr");
			// }
			// dumpVar($wherestr, "wherestr");
		}
	}	
	class WhuDbSpotDay extends WhuThing 
	{
		function getRecord($key)
		{
			if ($this->isSpotDayRecord($key))
				return $key;

			if ($this->isSpotDayParmsArray($key))
			{
				return $this->getOne($q = sprintf("select * from wf_spot_days where wf_spots_id=%s AND wf_spot_days_date='%s'", $key['spotId'], $key['date']));
			}
			WhuThing::getRecord($key);
		}
		function id()			{ return $this->dbValue('wf_spots_id'); }
		function date()		{ return $this->dbValue('wf_spot_days_date'); }
		function desc()		{ return $this->dbValue('wf_spot_days_desc'); }
		function keywords()	
		{
			return WhuProps::parseKeys($this->dbValue('wf_spot_days_keywords'));
// dumpVar($foo, "foo");			exit;
		}
	}
	class WhuDbSpotDays extends WhuDbSpotDay			//  So far this can be a collection of days for a date, or of days for a Spot
	{
		var $isCollection = true;
		function getRecord($key)
		{
			if ($this->isDate($key))
				return $this->getAll("select * from wf_spot_days where wf_spot_days_date='$key'");

			if ($key > 0)
				return $this->getAll($q = "select * from wf_spot_days where wf_spots_id=$key order by wf_spot_days_date");

			WhuThing::getRecord($key);
		}
		
		function closestDay($date)		// no know use for this functino, but I don't want to toss the code :)
		{
			$dateObj1 = date_create($date);
			for ($i = 0, $smallestDiff = 999999; $i < $daysForSpot->size(); $i++)
			{
				$day = $daysForSpot->one($i);
				$dateObj2 = date_create($date = $day->date());
				$diffObj = date_diff($dateObj1, $dateObj2);
				// dumpVar($diff, "diff = " . $day->date());
				$diff = $diffObj->format('%a');
				dumpVar("$diff days", "diff = " . $day->date());
				if ($diff < $smallestDiff)
				{
					$smallestDiff = $diff;
					$bestDay = $date;
				}
			}
		}
	}
	class WhuDbSpotAndDays extends WhuDbSpotDay
	{
		function getRecord($key)
		{
			return $this->getAll("select * from wf_spot_days where wf_spot_days_date='$key'");// order by wf_days_date");
		}
	}
	
	class WhuPost extends WhuThing 
	{
		var $lazyPostRec = 0;
		function getRecord($parm)	//  parm = array('wpid' => wpid)  OR jsut the wpid
		{
			if (is_array($parm) && isset($parm['wpid']))
			{
				return $this->doWPQuery("p={$parm['wpid']}");
			}
			else if ($parm > 0)
				return $this->doWPQuery("p=$parm");

			jfDie("WhuPost($parm)");
		}
		function wpid()				{ return $this->data[0]['wpid']; }
		function title()			{ return $this->data[0]['title']; }
		function content()		{ return $this->data[0]['content']; }		
		function date()				{ return $this->data[0]['date']; }						// NOTE! this is the wordpress date, NOT the dateS that ref this post
		function firstDate()	{	return $this->dates()[0]['wf_days_date'];	}	// first date for post - this is the one ypu want		
		
		function next()			{	return explode('=', $this->data[0]['next'])[1];	}
		function previous() {	return explode('=', $this->data[0]['prev'])[1];	}

		function dates()
		{
			$q = sprintf("select * from wf_days where wp_id=%s", $this->wpid());
			return $this->getAll($q);
		}
		
		function doWPQuery($args)
		{
			// invoke the WP loop right here and now!
			define('WP_USE_THEMES', false);
			require(WP_PATH . 'wp-load.php');											// Include WordPress			
			query_posts($args);																	// get collection (of 1) post
			$posts = array();

			while (have_posts()): the_post();										// The Loop
				$posts[] = array(
					'title' 	=> the_title('', '', false),				// first two can return a string
					'date'		=> the_date('Y-m-d', '', '', false), 
					'wpid'		=> get_the_ID(),
					'prev' 	 	=> get_permalink(get_adjacent_post(false,'',true)),			// remember, WP's default is newest to oldest
					'next' 	 	=> get_permalink(get_adjacent_post(false,'',false)),
					'content' => $this->the_content(),						// the_content does NOT, so copy/modify below to do so
				);
			endwhile;
			return $posts;
		}
		// straight outta Wordpress:
		function the_content($more_link_text = null, $stripteaser = false) {
			$content = get_the_content($more_link_text, $stripteaser);
			$content = apply_filters('the_content', $content);
			$content = str_replace(']]>', ']]&gt;', $content);
			return $content;
		}
	}
	class WhuPosts extends WhuPost 
	{
		var $isCollection = true;
		function getRecord($key)	// key = trip id
		{
			WhuThing::getRecord($key);		// FAIL
		}
	}

	class WhuPic extends WhuThing 
	{
		var $prvnxt = NULL;
		function getRecord($key)		// key = pic id
		{
			if ($this->isPicRecord($key))
				return $key;
			return $this->getOne("select * from wf_images where wf_images_id=$key");	
		}
		function id()				{ return $this->dbValue('wf_images_id'); }
		function caption()	{ return $this->dbValue('wf_images_text'); }
		function datetime()	{ return $this->dbValue('wf_images_localtime'); }
		function date()			{ return substr($this->datetime(), 0, 10); }
		function time()			{ return Properties::prettyTime($this->datetime()); }
		function filename()	{ return $this->dbValue('wf_images_filename'); }
		function folder()		{ return $this->dbValue('wf_images_path'); }
		function camera()		{ return $this->dbValue('wf_images_origin'); }
		function cameraDesc()
		{
			$names = array('Canon650' => 'my good ole Canon 650', 'Ericsson' => 'Ericsson W350i phone', 
											'CanonG9X' => 'my state of the art Powershot G9X', 
											'iPhone4S' => 'iPhone 4S', 'iPhone6S' => 'iPhone 6S', );
			return (isset($names[$this->camera()])) ? $names[$this->camera()] : "unknown";
		}
		
		
		function prev() 				// set of functions for day gallery navigation - some days don't have pictures and must be skipped
		{
			if (is_null($this->prvnxt))
				$this->getPrvNxtPic();
			return $this->prvnxt['prev'];
		}
		function next() 
		{
			if (is_null($this->prvnxt))
				$this->getPrvNxtPic();
			return $this->prvnxt['next'];
		}
		function getPrvNxtPic()
		{
			$this->prvnxt = array();

			$q = sprintf("select * from wf_images where wf_images_localtime > '%s' order by wf_images_localtime ASC LIMIT 3", $dt = $this->datetime());
			$items = $this->getAll($q);			
			$this->prvnxt['next'] = $this->build('Pic', $items[0]);
			
			$q = sprintf("select * from wf_images where wf_images_localtime < '%s' order by wf_images_localtime DESC LIMIT 3", $dt);
			$items = $this->getAll($q);
			$this->prvnxt['prev'] = $this->build('Pic', $items[0]);
		}
	}
	class WhuPics extends WhuPic 
	{
		var $isCollection = true;
		function getRecord($parm)	//  tripid. folder, date
		{
			if (isset($parm['date'])) 
			{				
				$q = sprintf("select * from wf_images where date(wf_images_localtime)='%s' order by wf_images_localtime", $parm['date']);
			// dumpVar($parm, "parm $q");
				return $this->getAll($q);
			}
			
			if (isset($parm['folder'])) 
				$folder = $parm;
			else if (isset($parm['tripid']))
			{
				$trip = $this->build('DbTrip', $parm['tripid']);
				$folder = $trip->folder();
			}			
			return $this->getAll($q = "select * from wf_images where wf_images_path='$folder' order by wf_images_localtime");

			// else if (isset($parm['folder']))
			// switch ($parm) {
			// 	case 'tripid':
			// 		break;
			// 	case 'folder':
			// 	case 'date':
			// 		break;
			// 	case 'catid':
			// 		break;
			// 	case 'search':
			// 		break;
			// }
			WhuThing::getRecord($parm);		// FAIL
		}
		function favored()					// returns a picture object for a favored picture
		{	
			$faves = $this->favorites();

			if (sizeof($faves) > 0)
				$one = $faves[array_rand($faves)];
			else
				$one = $this->data[array_rand($this->data)];

			return $this->build('Pic', $one);
		}
		function choose($num)					// returns a picture collection of $num favored pictures 
		{	
			$faves = $this->favorites();		
			$ret = array_rand($faves, $num);
			
			if (sizeof($ret) < $num)		// still need pics
			{
				$ids = array_column('wf_images_id');
				$fids = array_flip($ids);
				dumpVar($fids, "fids");
				for ($i = 0; $i < sizeof($ret); $i++) 
				{
					unset($fids[$ret[$i]]);
					dumpVar(sizeof($fids), "$i Nfids");
				}
				exit;
			}

			if (sizeof($faves) > 0)
				$one = $faves[array_rand($faves)];
			else
				$one = $this->data[array_rand($this->data)];

				return $this->build('Pics', $one);
			return $this->build('Pic', $one);
		}
		function favorites()
		{
			for ($i = 0, $idlist = ''; $i < sizeof($this->data); $i++) {
				$idlist .= $this->data[$i]['wf_images_id'] . ',';
			}
			$idlist = substr($idlist, 0, -1);
			// dumpVar($idlist, "idlist");
			
			$items = $this->getAll("select * from wf_favepics where wf_images_id IN($idlist)");
			for ($i = 0, $faves = array(); $i < sizeof($items); $i++) 
			{
				$faves[] = $items[$i]['wf_images_id'];
			}
			// dumpVar($faves, "faves");
			return $faves;
		}
	}
	
	class WhuPicKeyword extends WhuThing 
	{
		function getRecord($key)		// key = pic id
		{
			if ($this->isPicCatRecord($key))
				return $key;
			return $this->getOne("select * from wf_categories where wf_categories_id=$key");	
		}
		function id()			{ return $this->dbValue('wf_categories_id'); }
		function name()		{ return $this->dbValue('wf_categories_text'); }
		function parent()	{ return $this->dbValue('wf_categories_parent'); }
	}
	class WhuPicKeywords extends WhuPicKeyword 
	{
		var $isCollection = true;
		function getRecord($parm)	//  picid
		{
			if (isset($parm['picid'])) 
			{				
				$q = sprintf("select * from wf_idmap i join wf_categories c on c.wf_categories_id=i.wf_id_2 where i.wf_type_1='pic' and i.wf_id_1=%s and i.wf_type_2='cat' order by i.wf_type_2", $parm['picid']);
				return $this->getAll($q);
			}
			WhuThing::getRecord($parm);		// FAIL
		}
	}
	
	?>