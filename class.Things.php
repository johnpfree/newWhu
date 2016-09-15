<?php

// class Foo {	function gimme()		{		return 42;	}  }
// $fooo = (new Foo)->gimme();
// dumpVar($fooo, "fooo");
//
// $fname = 'foo';
// $fc = new $fname();
// $fooo = $fc->gimme();
// dumpVar($fooo, "fcooo");
// function argue() {
// 	$numargs = func_num_args();
//   echo "<hr>Number of arguments: $numargs<br />\n";
// 	foreach (func_get_args() as $k => $v)
// 	{
//     echo "Argument $k is: $v<br />\n";
// 	}
// }
// argue(123);
// argue('hi', 123);
// argue(123, 'new Properties(array())');
// function getArgs() {
//   if (($numargs = func_num_args()) < 2)
// 		throw new Exception("WhuThing must have at least two args");
// 	$props = func_get_arg(0);
// }

	// var $prefix = 'wf_categories';
	// var $prefix = 'wf_favepics';
	// var $prefix = 'wf_geocoords';
	// var $prefix = 'wf_idmap';
	// var $prefix = 'wf_images';
	// var $prefix = 'wf_post_map';
	// var $prefix = 'wf_post_status';
	// var $prefix = 'wf_resources';
	// var $prefix = 'wf_routes';
	// var $prefix = 'wf_segmentmap';
	// var $prefix = 'wf_segments';


	class WhuThing extends DbWhufu
	{
		var $data = NULL;
		var $prefix = NULL;
		var $isCollection = false;
		var $verbose = false;
		var $hasData = true;
		function __construct($p, $key)
		{
			parent::__construct($p);
			$this->data = $this->getRecord($key);
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
			jfTrace();
			jfDie($txt);
		}
		function assertIsCollection()  { $this->assert($this->isCollection, "NOT a Collection");  }

		// --------- generalized get()
		function dbValue($key)   
		{
			$this->assert($this->hasData, sprintf("Object of class %s is empty, key=%s.", get_class($this), $key));
			$this->assert(isset($this->data[$key]), "this->data[$key] not found for prefix={$this->prefix}");
			return $this->data[$key];  
		}

		// --------- getRecord() overloading utilities
		function isSpotDayRecord($key)	{	return (is_array($key) && isset($key['wf_spot_days_date']));	}
		function isSpotDayParmsArray($key)	{	return (is_array($key) && isset($key['spotId']) && isset($key['date']));	}
		function isTripRecord($key)			{	return (is_array($key) && isset($key['wf_trips_id']));	}

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
	
		// --------- collections
		function one($i)
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
			if ($this->verbose)	dumpVar($type, "THING Build: type");
			
			if ($type == '') {
				throw new Exception("Invalid Thing Type = $type.");
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
		var $prefix = 'wf_trips';
		function getRecord($key)
		{
			if ($this->isTripRecord($key))
				return $key;

			return $this->getOne("select * from wf_trips where wf_trips_id=$key");	
		}

		function id()					{ return $this->dbValue('wf_trips_id'); }
		function name()				{ return $this->dbValue('wf_trips_text'); }
		function desc()				{ return $this->dbValue('wf_trips_desc'); }
		function startDate()	{ return $this->dbValue('wf_trips_start'); }
		function endDate()		{ return $this->dbValue('wf_trips_end'); }

		// function name()				{ return $this->dbValue('wf_trips_picfolder'); }
		// function name()				{ return $this->dbValue('wf_trips_odometer'); }
		// function name()				{ return $this->dbValue('wf_trips_map_lat'); }
		// function name()				{ return $this->dbValue('wf_trips_map_lon'); }
	}
	class WhuDbTrips extends WhuDbTrip 
	{
		var $isCollection = true;
		function getRecord($parms)
		{
			$defaults = array('order' => "DESC", 'field' => 'wf_trips_start');
			$qProps = new Properties($defaults, $parms);
			// $qProps->dump('$qProps->');
			$q = sprintf("select * from wf_trips ORDER BY %s %s", $qProps->get('field'), $qProps->get('order'));	
			dumpVar($q, "q");
			return $this->getAll($q);	
			// return $this->getOne(sprintf("select * from wf_trips ORDER BY %s %s"), $qProps->get('field'), $qProps->get('order'));
		}
	}


	class WhuDbDay extends WhuThing 
	{
		var $prefix = 'wf_days';
		function getRecord($key)
		{
			if (is_array($key))		// $key == the record?
				return $key;
			return $this->getOne("select * from wf_trips where wf_trips_id=$key");	
		}
		function date()				{ return $this->dbValue('wf_days_date'); }
		function spotId()			{ return $this->dbValue('wf_spots_id'); }
		function hasSpot()		{ return $this->spotId() > 0; }
		function dayName()		{ return $this->dbValue('wf_route_name'); }
		function dayDesc()		{ return $this->dbValue('wf_route_desc'); }
		function nightName()	{ return $this->dbValue('wf_stop_name'); }
		function nightDesc()	{ return $this->dbValue('wf_stop_desc'); }
		
		function lat()				{ return $this->dbValue('wf_days_lat'); }
		function lon()				{ return $this->dbValue('wf_days_lon'); }
		
	}
	class WhuDbDays extends WhuDbDay {
		var $isCollection = true;
		function getRecord($key)
		{
			return $this->getAll("select * from wf_days where wf_trips_id=$key order by wf_days_date");
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
		function nightName()	{	return $this->getSpotandDaysArranged('nightName');	}
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
				case 'nightName':	return $this->spot->name();
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
		var $prefix = 'wf_spots';
		function getRecord($key)
		{
			return $this->getOne("select * from wf_spots where wf_spots_id=$key");	
		}
		function name()		{ return $this->dbValue('wf_spots_name'); }

		function lat()		{ return $this->dbValue('wf_spots_lat'); }
		function lon()		{ return $this->dbValue('wf_spots_lon'); }
	}
	class WhuDbSpotDay extends WhuThing 
	{
		var $prefix = 'wf_spot_days';
		function getRecord($key)
		{
			if ($this->isSpotDayRecord($key))
				return $key;

			if ($this->isSpotDayParmsArray($key))
				return $this->getOne($q = sprintf("select * from wf_spot_days where wf_spots_id=%s AND wf_spot_days_date='%s'", $key['spotId'], $key['date']));
			
			dumpVar($key, "key");
			jfdie("WhuDbSpotDay::getRecord($key) not recognized");
		}
		function spotId()		{ return $this->dbValue('wf_spots_id'); }
		function date()			{ return $this->dbValue('wf_spot_days_date'); }
		function desc()			{ return $this->dbValue('wf_spot_days_desc'); }
	}
	//  So far this can be a collection of days for a date, or of days for a Spot
	class WhuDbSpotDays extends WhuDbSpotDay
	{
		var $isCollection = true;
		function getRecord($key)
		{
			if ($this->isDate($key))
				return $this->getAll("select * from wf_spot_days where wf_spot_days_date='$key'");

			if ($key > 0)
				return $this->getAll("select * from wf_spot_days where wf_spots_id=$key order by wf_spot_days_date");
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
?>