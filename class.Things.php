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
		function isSpotRecord($key)			{	return (is_array($key) && isset($key['wf_spots_id']));	}
		function isSpotDayRecord($key)	{	return (is_array($key) && isset($key['wf_spot_days_date']));	}
		function isSpotDayParmsArray($key)	{	return (is_array($key) && isset($key['spotId']) && isset($key['date']));	}
		function isTripRecord($key)			{	return (is_array($key) && isset($key['wf_trips_id']));	}
		function isPicRecord($key)			{	return (is_array($key) && isset($key['wf_images_id']));	}

		// --------- utilities
		function isDate($str) 				// true for 
		{
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
	}
	class WhuTrips extends WhuTrip 
	{
		var $isCollection = true;
		function getRecord($parms)
		{
			$defaults = array('order' => "DESC", 'field' => 'wf_trips_start');
			$qProps = new Properties($defaults, $parms);
			// $qProps->dump('$qProps->');
			$q = sprintf("select * from wf_trips ORDER BY %s %s", $qProps->get('field'), $qProps->get('order'));	
			// dumpVar($q, "q");
			return $this->getAll($q);	
			// return $this->getOne(sprintf("select * from wf_trips ORDER BY %s %s"), $qProps->get('field'), $qProps->get('order'));
		}
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
		
		function miles()			{ return $this->dbValue('wf_days_miles'); }
		function cumulative()	{ return $this->dbValue('wf_days_cum_miles'); }
		
		function lat()				{ return $this->dbValue('wf_days_lat'); }
		function lon()				{ return $this->dbValue('wf_days_lon'); }		

		function prettyDate()	{ return Properties::prettyDate($this->date()); }
		
		function pics() 			{	return $this->build('WhuPics', array('date' => $this->date()));	}
		function hasPics() 		{	return $this->pics()->size() > 0;	}
		
		function previous() 
		{
			if (is_null($this->prvnxt))
				$this->getPrvNxt();
			return $this->prvnext['prev'];
		}
		function next() 
		{
			if (is_null($this->prvnxt))
				$this->getPrvNxt();
			return $this->prvnext['next'];
		}
		function getPrvNxt()
		{
			$items = $this->getAll("select * from wf_days order by wf_days_date");
			$wps = array_column($items, 'wf_days_date');
			$idx = array_search($id = $this->date(), $wps);
			
			$this->prvnext = array();
			for ($i = 1; $i < 10; $i++) 
			{
				$prvpics = $this->build('WhuPics', array('date' => $d0 = $wps[$idx - $i]));
				if ($dc = $prvpics->size() > 0)
					break;
			}
			$this->prvnext['prev']  = $d0;
			$this->prvnext['prevc'] = $dc;
						
			for ($i = 1; $i < 10; $i++) 
			{
				$prvpics = $this->build('WhuPics', array('date' => $d0 = $wps[$idx + $i]));
				if ($dc = $prvpics->size() > 0)
					break;
			}
			$this->prvnext['next']  = $d0;
			$this->prvnext['nextc'] = $dc;
		}
	}

	class WhuDbDays extends WhuDbDay {
		var $isCollection = true;
		function getRecord($parm)			// trip id
		{
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
			if (get_class($key) == 'WhuDbDay')
				return $key->data;

			return parent::getRecord($key);
		}
		function nightName()	{	return $this->massageDbText($this->getSpotandDaysArranged('nightName'));	}
		function nightDesc()	{	return $this->getSpotandDaysArranged('nightDesc');	}

		function lat()	{	return $this->getSpotandDaysArranged('lat');	}
		function lon()	{	return $this->getSpotandDaysArranged('lon');	}
	
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
		
			if (!$this->hasSpot())
				return $this->dbValue($keys[$key]);		// no spot, return the day value
		
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
		function getRecord($key)		// parm is spot id OR the record for iteration
		{
			if ($this->isSpotRecord($key))
				return $key;

			return $this->getOne("select * from wf_spots where wf_spots_id=$key");	
		}
		function id()				{ return $this->dbValue('wf_spots_id'); }
		function name()			{ return $this->massageDbText($this->dbValue('wf_spots_name')); }
		function town()			{ return $this->massageDbText($this->dbValue('wf_spots_town')); }
		function partof()		{ return $this->dbValue('wf_spots_partof'); }
		function types()		{ return $this->dbValue('wf_spots_types'); }
		
		function prettyTypes()		// an array of types, suitable for printing
		{
			$keys = WhuProps::parseKeys($this->types());			
			for ($i = 0; $i < sizeof($keys); $i++) 
			{
				$keys[$i] = $this->spottypes[$keys[$i]];
			}
			return $keys;
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
				// dumpVar($allkeys, "$i keys");
			}
			return array_flip($allkeys);
		}

		function lat()		{ return $this->dbValue('wf_spots_lat'); }
		function lon()		{ return $this->dbValue('wf_spots_lon'); }
	}
	class WhuDbSpots extends WhuDbSpot 
	{
		var $isCollection = true;
		function getRecord($searchterms = array())
		{
			$deflts = array(
				'order'	=> 'wf_spots_name',
				'where'	=> array(),
			);
			if (sizeof($searchterms) == 0)			// show all
			{
				$where = " WHERE wf_spots_types NOT LIKE '%DRIVE%' AND wf_spots_types NOT LIKE '%WALK%' AND wf_spots_types NOT LIKE '%HIKE%' AND wf_spots_types NOT LIKE '%PICNIC%' AND wf_spots_types NOT LIKE '%HOUSE%'";				
			}
			else
			{
				// dumpVar($searchterms, "searchterms");
				for ($i = 0, $where = ""; $i < sizeof($searchterms); $i++) 
				{
				 $where .= ($i == 0) ? "WHERE " : " OR ";
				 $where .= "wf_spots_types LIKE '%{$searchterms[$i]}%'";
				}
				dumpVar($where, "where");
			}
	 
			$q = sprintf("SELECT * FROM wf_spots %sORDER BY %s", $where, $deflts['order']);
			dumpVar($q, "q");
			return $this->getAll($q);


			// $props = new Properties(array())

			for ($i = 0, $wherestr = ''; $i < sizeof($deflts['where']); $i++) 
			{
				$wherestr .= sprintf('%s %s', ($i == 0) ? 'WHERE' : "AND", $deflts['where'][$i]);
				dumpVar($wherestr, "$i wherestr");
			}
			dumpVar($wherestr, "wherestr");
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
		function getRecord($key)
		{
			if ($this->isPicRecord($key))
				return $key;

			return $this->getOne("select * from wf_images_id where wf_trips_id=$key");	
		}
		function id()				{ return $this->dbValue('wf_images_id'); }
		function caption()	{ return $this->dbValue('wf_images_text'); }
		function datetime()	{ return $this->dbValue('wf_images_create'); }
		function filename()	{ return $this->dbValue('wf_images_filename'); }
		function path()			{ return $this->dbValue('wf_images_path'); }
	}
	class WhuPics extends WhuPic 
	{
		var $isCollection = true;
		function getRecord($parm)	//  tripid. folder, date
		{
			if (isset($parm['date'])) 
			{				
				$q = sprintf("select * from wf_images where date(wf_images_localtime)='%s' order by wf_images_localtime", $parm['date']);
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
	}

	
	// class WhuMap extends WhuThing
	// {
	// 	function getRecord($key)
	// 	{}
	// }
?>