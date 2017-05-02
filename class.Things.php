<?php

	class WhuThing extends DbWhufu
	{
		var $data = NULL;
		var $isCollection = false;
		var $hasData = true;
		function __construct($p, $key = NULL)
		{
			// new hack!  Single parameter means I'm casting to a child class
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
		function isSpotCatSearch($key)		{	return (is_array($key) && isset($key['wf_categories_id']));	}
		function isSpotKeySearch($key)		{	return (is_array($key) && isset($key['wf_spot_days_keywords']));	}
		function isSpotCampSearch($key)		{	return (is_array($key) && isset($key['camp_type']));	}
		function isTripRecord($key)				{	return (is_array($key) && isset($key['wf_trips_id']));	}
		function isPicRecord($key)				{	return (is_array($key) && isset($key['wf_images_id']));	}
		function isCategoryRecord($key)		{	return (is_array($key) && isset($key['wf_categories_parent']));	}		// spots also have a cat id
		function isDayRecord($key)				{	return (is_array($key) && isset($key['wf_days_date']));	}
		function isTextSearch($key)				{	return (is_array($key) && isset($key['searchterm']));	}
		function isVidRecord($key)				{	return (is_array($key) && isset($key['wf_resources_id']));	}
		// function isVidCollection($key)		{	return (is_array($key) && is_array($key[0]) && isset($key[0]['wf_resources_id']));	}

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
		
		function random($num)						// chops the data array down to a maximum of $num items (unchanged if there aren't $num items)
		{	
			shuffle($this->data);
			dumpVar(sizeof($this->data), "random($num) size in");
			$this->data = array_slice($this->data, 0, $num);
			// dumpVar(sizeof($this->data), "size out");
		}
		// add more items to the collection
		function add($more) { $this->data = array_merge($this->data, $more->data); }
		
		function sortByField($data, $field)
		{
			$column = array_column($data, $field);
			array_multisort($column, $data);
			// dumpVar($data, "data");
			return $data;
		}

	
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

		function fid()				{ return $this->dbValue('wf_trips_map_fid'); }
		function mapboxId()		{ return $this->dbValue('wf_trips_extra'); }
		function isNewMap()		{ return (strlen($this->fid()) > 1); }
	}
	class WhuTrip extends WhuDbTrip 
	{
		
		var $multiMaps = array(
			"johnpfree.02do91ob" => array('name' => "Eureka"		, 'file' => "multiEureka.js"), 
			"johnpfree.pl58eik5" => array('name' => "395" 	  	, 'file' => "multi395.js"), 
			"johnpfree.29opambf" => array('name' => "Louisville", 'file' => "mLouisvilleRtes.json"), 
		);
		function makeStoriesLink()
		{}
		function hasPics()
		{
			$pics = $this->build('Pics', array('tripid' => $this->id())); 
			return $pics->size() > 0;
		}
		function hasVideos()
		{
			$q = sprintf("select * from wf_images where wf_images_path='%s' and wf_resources_id>0", $this->folder());
			$items = $this->getAll($q);
			return sizeof($items);				// note that I am returning the number of items
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
		function numVids() 	{	return $this->getOne("select count(*) RES from wf_resources")['RES'];	}
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

			// dumpVar($key, "select * from wf_days where wf_days_date='$key'");
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

		function pics() 		{	return $this->build('WhuPics', array('date' => $this->date()));	}
		function hasPics() 		{	return $this->pics()->size() > 0;	}
		function hasVideos()
		{
			$q = sprintf("select * from wf_images where DATE(wf_images_localtime)='%s' and wf_resources_id>0", $this->date());
			$items = $this->getAll($q);
			return sizeof($items);				// note that I am returning the number of items
		}

		function daystart()	{ return $this->dbValue('wf_days_daystart'); }
		function dayend()  	{ return $this->dbValue('wf_days_dayend'); }
		
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
// dumpVar($this->date(), "this->date(), id, idx  $id, $idx");

			$this->prvnxt = array('prev' => FALSE, 'prevc' => FALSE, 'next' => FALSE, 'nextc' => FALSE, );
			
			if ($idx > 0) {			// Skip this if this is the VERY first day (idx==0), so 'prev' remains its default of false
				for ($i = 1; $i < 10; $i++) 
				{
					$prvpics = $this->build('WhuPics', array('date' => $d0 = $wps[$idx - $i]));
					if ($dc = $prvpics->size() > 0)
						break;
				}
				$this->prvnxt['prev']  = $d0;
				$this->prvnxt['prevc'] = $dc;
			}

			for ($i = 1; $i < 10; $i++) 
			{
				$prvpics = $this->build('WhuPics', array('date' => $d0 = $wps[$idx + $i]));
				if ($dc = $prvpics->size() > 0)
					break;
			}
			$this->prvnxt['next']  = $d0;
			$this->prvnxt['nextc'] = $dc;
			// dumpVar($this->prvnxt, "this->prvnxt");
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
				// dumpVar($q, "q");
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
		public static $CAMPTYPES = array(
					'usfs'		=> 'Forest Service Campgrounds',
					'usnp'		=> 'National Park Campgrounds',
					'state'		=> 'State Park Campgrounds',
					'blm'			=> 'Bureau of Land Management Campgrounds',
					'ace'			=> 'Army Corps of Engineers Campgrounds',
					'nwr'			=> 'Wildlife Refuge Campgrounds',
					'county'	=> 'County Campgrounds',
					'private'	=> 'private Campgrounds',
					'roadside' => 'pullover when there\'s no place to camp',
					'parking'	=> 'parking lot',
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
						if ($k1 == 'CAMP' && isset($this->CAMPTYPES[$v1]))
						{
							$ret[$v] = $this->CAMPTYPES[$v1];
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
				
			// dumpVar($ndays, "vfld = $vfld, day recs=" . $this->lazyDays->size() . "RESULT");
			return $ndays;
		}
		function keywords()
		{
			if ($this->lazyDays == NULL)
				$this->lazyDays = $this->build('DbSpotDays', $this->id());
			
			for ($i = 0, $allkeys = array(); $i < $this->lazyDays->size(); $i++)
			{
				$spotDay = $this->lazyDays->one($i);
				$allkeys = array_merge(array_flip($spotDay->keywords()), $allkeys);		// flipping makes duplicates disappear
				// dumpVar($spotDay->keywords(), "spotDay->keywords()");
				// dumpVar($allkeys, "$i keys");
			}
			unset($allkeys['']);		// a SpotDay with no keywords shows up as a blank, remove that one
			return array_merge(array_flip($allkeys));			// flipped there may be holes in the array, merge reorders it with no holes
		}

		function lat()		{ return $this->dbValue('wf_spots_lat'); }
		function lon()		{ return $this->dbValue('wf_spots_lon'); }
		function bath()		{ return $this->dbValue('wf_spots_bath'); }
		function water()	{ return $this->dbValue('wf_spots_water'); }

		function getInRadius($dist = 100.)		// returns an array of spot records, suitable for creating a DbSpots collection
		{
			$lat = $this->lat();
			$lon = $this->lon();
		
			// $q = "SELECT *, ((ACOS(SIN($lat * PI() / 180) * SIN(wf_spots_lat * PI() / 180) + COS($lat * PI() / 180) * COS(wf_spots_lat * PI() / 180) * COS((-wf_spots_lon + $lon) * PI() / 180)) * 180 / PI()) * 60 * 1.1515) AS distance FROM wf_spots WHERE wf_spots_lon != '' ORDER BY distance ASC";
			$q = sprintf("SELECT *, ((ACOS(SIN($lat * PI() / 180) * SIN(wf_spots_lat * PI() / 180) + COS(%s * PI() / 180) * COS(wf_spots_lat * PI() / 180) * COS((-wf_spots_lon + %s) * PI() / 180)) * 180 / PI()) * 60 * 1.1515) AS distance FROM wf_spots WHERE wf_spots_lon != '' AND %s ORDER BY distance ASC", $this->lat(), $this->lon(), $this->excludeString);
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
			else if ($this->isSpotCampSearch($searchterms))
			{
				$q = sprintf("select * FROM wf_spots WHERE wf_spots_status LIKE '%%%s%%'", $searchterms['camp_type']);
				$ret = $this->getAll($q);
				// dumpVar(sizeof($ret), "$q -> ret=");
				return $ret;
			}
			else if ($this->isSpotCatSearch($searchterms))
			{
				$q = sprintf("select * FROM wf_spots WHERE wf_categories_id=%s", ($id = $searchterms['wf_categories_id']));
				$ret = $this->getAll($q);
				// dumpVar(assizeof($ret), "$q -> ret=");
				
				if (isset($searchterms['kids'])) 
				{
					$q = sprintf("select * FROM wf_categories WHERE wf_categories_parent=%s", $id);
					$items = $this->getAll($q);
					for ($i = 0; $i < sizeof($items); $i++) 		// just do one level search, haven't implemented recursive
					{
						$q = sprintf("select * FROM wf_spots WHERE wf_categories_id=%s", $items[$i]['wf_categories_id']);
						$ret = array_merge($ret, $this->getAll($q));
					}
				}
				return $ret;
			}
			else if ($this->isSpotKeySearch($searchterms))
			{
				$clean = $this->real_escape_string($key = $searchterms['wf_spot_days_keywords']);
				$q = "select s.* from wf_spot_days d RIGHT OUTER JOIN wf_spots s ON s.wf_spots_id=d.wf_spots_id WHERE d.wf_spot_days_keywords LIKE '%$clean%' ORDER BY s.wf_spots_name";
				// dumpVar($searchterms, "$q, searchterms");
				$items = $this->getAll($q);	
				for ($i = $id = 0, $ret = array(); $i < sizeof($items); $i++) 	// JOIN adds record for each day with the keyword => weed out duplicates
				{
					$item = $items[$i];
					if ($id != $item['wf_spots_id'])
						$ret[] = $item;
					$id = $item['wf_spots_id'];
				}
				// dumpVar($ret, "ret");
				return $ret;
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
		function cost()		{ return $this->dbValue('wf_spot_days_cost'); }
		function senior()		{ return $this->dbValue('wf_spot_days_senior'); }
		function desc()		{ return $this->massageDbText($this->dbValue('wf_spot_days_desc')); }
		function htmldesc()	
		{ 
			$stuff = explode("\n\r\n", $this->desc());
			// dumpVar($stuff, "stuff nrn, char=" . ord($stuff[1][0]));
			for ($i = 0, $html = "\n"; $i < sizeof($stuff); $i++) 
			{
				$html .= sprintf("<p>%s</p>\n", $stuff[$i]);
			}
			return $html;
		}
		function keywords()	
		{
			return WhuProps::parseKeys($this->dbValue('wf_spot_days_keywords'));
		}
		function tripId()		// used so far only to detect that it is NOT in a trip (returns 0)
		{
			$day = $this->build('DbDay', $this->date());
			if ($day->hasData)
				return $day->tripId();
			return 0;
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
			// dumpVar(sizeof($posts), "N posts");
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
		function getRecord($parm)	// key = trip id
		{
			if ($this->isTextSearch($parm))						// for text search
			{
				return $this->doWPQuery("s={$parm['searchterm']}");
			}

			WhuThing::getRecord($key);		// FAIL
		}
	}
	// ------------------------------------------- WhuVisual -> WhuPic | WhuVid -------------------
	class WhuVisual extends WhuThing 
	{
		var $prvnxt = NULL;
		function getRecord($key)
		{
			if ($this->isPicRecord($key))
				return $key;
			return $this->getOne("select * from wf_images where wf_images_id=$key");	
		}
		function filename()	{ return $this->dbValue('wf_images_filename'); }
		function id()				{ return $this->dbValue('wf_images_id'); }
		function caption()	{ return $this->dbValue('wf_images_text'); }
		function name()			{ return $this->caption(); }
		function datetime()	{ return $this->dbValue('wf_images_localtime'); }
		function date()			{ return substr($this->datetime(), 0, 10); }
		function time()			{ return substr($this->datetime(), 10); }
		function folder()		{ return $this->dbValue('wf_images_path'); }
		function camera()		{ return $this->dbValue('wf_images_origin'); }
		function cameraDesc()
		{
			$names = array('Canon650' => 'my good ole Canon 650', 'Ericsson' => 'Ericsson W350i phone', 
											'CanonG9X' => 'my state of the art Powershot G9X', 
											'iPhone4S' => 'iPhone 4S', 'iPhone6S' => 'iPhone 6S', );
			return (isset($names[$this->camera()])) ? $names[$this->camera()] : "unknown";
		}

		function vidId()			{ return $this->dbValue('wf_resources_id'); }
		function isImage() 		{ return ($this->vidId() == 0); }
		function isVideo() 		{ return ($this->vidId() > 0); }
		
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
			$q = sprintf("select * from wf_images where wf_images_localtime > '%s%s' order by wf_images_localtime ASC LIMIT 3", $this->date(), $this->time());
			$items = $this->getAll($q);
			// dumpVar($items, "Pitems $q");
			$this->prvnxt = array('next' => $this->build('Pic', $items[0]));
			
			$q = sprintf("select * from wf_images where wf_images_localtime < '%s%s' order by wf_images_localtime DESC LIMIT 3", $this->date(), $this->time());
			$items = $this->getAll($q);
			// dumpVar($items, "Nitems $q");
			$this->prvnxt['prev'] = $this->build('Pic', $items[0]);
		}
	}			
	class WhuVideo extends WhuVisual 
	{
		function getRecord($key)		// key = pic id
		{
			if (is_object($key) && (get_class($key) == 'WhuVisual'))		// cast a Visual to a Video, add the video data
			{
				$item = $this->getOne("select * from wf_resources where wf_resources_id=" . $key->vidId());					
				return array_merge($key->data, $item);
			}
			dumpVar($key, "key");
			WhuThing::getRecord();
		}
		function token()		{ return $this->dbValue('wf_resources_token'); }
		function lat()			{ return $this->dbValue('wf_resources_lat'); }
		function lon()			{ return $this->dbValue('wf_resources_lon'); }
		function spotId()		{ return $this->dbValue('wf_resources_spot_id'); }
	}
	class WhuPic extends WhuVisual 
	{
		var $prvnxt = NULL;
		function getRecord($key)		// key = pic id
		{
			// dumpVar($key, "key");
			if (is_object($key) && (get_class($key) == 'WhuVisual'))		// cast a Visual to a Pic
				return $key->data;
			if ($this->isPicRecord($key))
				return $key;
			return $this->getOne("select * from wf_images where wf_images_id=$key");	
		}

		function isPano()
		{
			$q = sprintf("select * from wf_idmap where wf_type_1='pic' AND wf_type_2='cat' AND wf_id_1=%s and wf_id_2=155", $this->id());
			return is_array($this->getOne($q));
		}
					
		// image FILE stuff - extract GPS, extract thumbnail
		function latlon()
		{
			$fullpath = $this->fullpath();		
			$exif = @exif_read_data($fullpath);
		
			if (isset($exif["GPSLongitude"]))
			{
				return array(
					'lon'  => $this->getGps($exif["GPSLongitude"], $exif['GPSLongitudeRef']), 
					'lat'  => $this->getGps($exif["GPSLatitude"], $exif['GPSLatitudeRef']),
				);
			}
			return array();		// empty array == no latlon in the exif data
		}
		function getGps($exifCoord, $hemi) 
		{
			$degrees = count($exifCoord) > 0 ? $this->gps2Num($exifCoord[0]) : 0;
			$minutes = count($exifCoord) > 1 ? $this->gps2Num($exifCoord[1]) : 0;
			$seconds = count($exifCoord) > 2 ? $this->gps2Num($exifCoord[2]) : 0;

			$flip = ($hemi == 'W' or $hemi == 'S') ? -1 : 1;
			return $flip * ($degrees + $minutes / 60 + $seconds / 3600);
		}
		function gps2Num($coordPart) 
		{
			$parts = explode('/', $coordPart);
			if (count($parts) <= 0)		return 0;
			if (count($parts) == 1)		return $parts[0];
			return floatval($parts[0]) / floatval($parts[1]);
		}
		function fullpath() { return sprintf("%s%s/%s", iPhotoPATH, $this->folder(), $this->filename());	}
	
		function thumbImage()
		{
			$xb = @exif_thumbnail($this->fullpath(), $xw, $xh, $xm);
			return base64_encode($xb);
		}
	}
	class WhuPics extends WhuPic 
	{
		var $isCollection = true;
		function getRecord($parm)	//  tripid. folder, date
		{
			// dumpVar($parm, "WhuPics parm");
			if ($this->isTextSearch($parm))						// for text search
			{
				$qterm = $parm['searchterm'];
				$q = "SELECT * FROM wf_images WHERE wf_images_text LIKE '$qterm' OR wf_images_desc LIKE '$qterm' OR wf_images_filename LIKE '$qterm'";
				// dumpVar($q, "q");
				return $this->getAll($q);
			}

			if (isset($parm['date'])) 
			{				
				$q = sprintf("select * from wf_images where date(wf_images_localtime)='%s' order by wf_images_localtime", $parm['date']);
			// dumpVar($parm, "parm $q");
				return $this->getAll($q);
			}
			
			if (isset($parm['night'])) 			// night = evening pics and morning pics - for a spot
			{
				$tonight = $parm['night'];
				$day = $this->build('WhuDbDay', $tonight);
				if (!$day->hasData)						// I sometimes make spot_day entries for times I'm not on a trip, so handle it
					return array();

			 	$q = sprintf("SELECT * from wf_images WHERE DATE(wf_images_localtime)='%s' and TIME(wf_images_localtime) > SEC_TO_TIME(3600 * %s)", $tonight, $day->dayend());
				// dumpVar($q, "q pm");
				$pmpics = $this->getAll($q);

				$tomorrow = Properties::sqlDate("$tonight +1 day");
				$day = $this->build('WhuDbDay', $tomorrow);
			 	$q = sprintf("SELECT * from wf_images WHERE DATE(wf_images_localtime)='%s' 
							and TIME(wf_images_localtime) < SEC_TO_TIME(3600 * %s)", $tomorrow, $day->daystart());
				// dumpVar($q, "q am");
				return array_merge($pmpics, $this->getAll($q));
			}
			
			if (isset($parm['cat'])) 
			{
				$q = sprintf("select i.* from wf_images i join wf_idmap im on i.wf_images_id=im.wf_id_1 where wf_type_1='pic' and wf_type_2='cat' and wf_id_2=%s", $parm['cat']);
				$ret = $this->getAll($q);
				if (isset($parm['max']))
					return $this->random($parm['max']);
				return $ret;
			}
			
			if (isset($parm['folder'])) 
				$folder = $parm;
			else if (isset($parm['tripid']))
			{
				$trip = $this->build('DbTrip', $parm['tripid']);
				$folder = $trip->folder();
			}
			return $this->getAll($q = "select * from wf_images where wf_images_path='$folder' order by wf_images_localtime");
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
		function xxchoose($num)					// returns a picture collection of $num favored pictures 
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

			return $this->build('Pic', $one);
		}
		private function favorites()					// returns an array, NOT an object, of ids of favorites in this collection
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
	class WhuVisuals extends WhuVisual 				// slightly hacky, but this is a collection of images AND videos
	{
		var $isCollection = true;
		function getRecord($parm)
		{
			dumpVar($parm, "parm");
			if (isset($parm['date'])) 			// just date -> get all pics/vids for a date 
			{
				$q = sprintf("select * from wf_images where DATE(wf_images_localtime)='%s' order by wf_images_localtime", $parm['date']);
				// dumpVar($q, "q");
				return $this->getAll($q);
			}
		}
	}	
	
	class WhuCategory extends WhuThing 
	{
		var $rootRoot 	= 7;
		var $placesRoot = 40;
		var $catsRoot 	= 176;
		function getRecord($key)		// key = cat id
		{
			if ($this->isCategoryRecord($key))
				return $key;
			return $this->getOne("select * from wf_categories where wf_categories_id=$key");	
		}
		function root() 				{ return $this->rootRoot; }
		function placesRoot() 	{ return $this->placesRoot; }
		function picCatsRoot() 	{ return $this->catsRoot; }

		function id()				{ return $this->dbValue('wf_categories_id'); }
		function name()			{ return $this->dbValue('wf_categories_text'); }
		function parent()		{ return $this->dbValue('wf_categories_parent'); }
		function order()		{ return $this->dbValue('wf_categories_order'); }
		
		function nPics()	
		{ 
			$q = sprintf("select COUNT(*) count from wf_idmap WHERE wf_type_1='pic' and wf_type_2='cat' and wf_id_2=%s", $this->id());
			$item = $this->getOne($q);
			return $item['count'];
		}
		
		function children() { return $this->build('Categorys', array('children' => $this->id())); }
		function depth()				{ return isset($this->data['depth']) ? $this->data['depth'] : 0; }
		function setDepth($d)		{ $this->data['depth'] = $d; }
	}
	class WhuCategorys extends WhuCategory 
	{
		var $isCollection = true;
		function getRecord($parm)	//  picid
		{
			if ($parm == 'all') 
			{				
				return $this->getAll("select * from wf_categories");
			}		
			if (isset($parm['children'])) 
			{				
				$q = sprintf("select * FROM wf_categories where wf_categories_parent=%s order by wf_categories_order", $parm['children']);
				return $this->getAll($q);
			}
			if (isset($parm['picid'])) 
			{				
				$q = sprintf("select * from wf_idmap i join wf_categories c on c.wf_categories_id=i.wf_id_2 where i.wf_type_1='pic' and i.wf_id_1=%s and i.wf_type_2='cat' order by i.wf_type_2", $parm['picid']);
				return $this->getAll($q);
			}
			WhuThing::getRecord($parm);		// FAIL
		}
		function traverse($root, $depth = 0)
		{
			if ($depth == 0)
				$this->desc = array();
			$depth++;
			// dumpVar(sizeof($this->desc), sprintf("dep=%s, root=%s,%s", $depth, $root->id(), $root->name()));
			$cats = $root->children();
			for ($i = 0, $rows = array(); $i < $cats->size(); $i++)
			{
				$cat = $cats->one($i);
				$cat->setDepth($depth);
				$this->desc[] = $cat;
				$this->traverse($cat, $depth);
			}
		}
		function descendantList()	{ return $this->desc; }
	}
	
	?>
