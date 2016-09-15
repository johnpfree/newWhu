<?php

// ---------------- Page Class ---------------------------------------------

class ViewWhu extends ViewBase  // ViewDbBase
{	
	var $curpage;
	function __construct($p)
	{
		$this->props = $p;
		parent::__construct(new WhuTemplate()); 
// dumpVar(sprintf("left: <b>%s</b> &mdash; right: <b>%s</b>", get_class($l), get_class($r)), 'Pane CLASSES');
	}
	function showPage()	
	{
		$this->template->set_var('CAPTION', $this->getCaption());
	}
	function getCaption()	
	{	return sprintf("Caption page=%s, type=%s, key=%s", $this->props->get('page'), $this->props->get('type'), $this->props->get('key'));	
	}
	function setStyle()
	{
		// include_once("cssSettings.php");
		// foreach ($colors as $k => $v)
		// {
		// 	$this->template->set_var($k, $v);
		// }
		//
		// $items = array();
		// foreach ($colors as $k => $v)
		// 	$items[] = array('name_color' => strtolower($k), 'set_color' => $v);
		// $loop = new Looper($this->template, array('noFields' => true, 'one' => 'color_row', 'parent' => 'main'));
		// $loop->do_loop($items);
	}
	
	function build ($type = '', $key = '') 
	{
		// dumpVar($type, "VIEW Build: type");
		if ($type == '') {
			throw new Exception("Invalid Thing Type = $type.");
		} 
		else 
		{ 
			$className = 'Whu'.ucfirst($type);

			if (class_exists($className)) {
				return new $className($this->props, $key);
			} else {
				throw new Exception("Thing type $className not found.");
			}
		}
	}
}

class HomeHome extends ViewWhu
{
	var $file = "homehome.ihtml";   
	function showPage()	
	{
		parent::showPage();
	}
}

class HomeSearch extends ViewWhu
{
	var $file = "homesearch.ihtml";   
	function showPage()	
	{
		parent::showPage();
	}
}
class HomeLook extends ViewWhu
{
	var $file = "homelook.ihtml";   
	function showPage()	
	{
		parent::showPage();
	}
}
class HomeRead extends ViewWhu
{
	var $file = "homeread.ihtml";   
	function showPage()	
	{
		parent::showPage();
	}
}
class HomeOrient extends ViewWhu
{
	var $file = "homeorient.ihtml";   
	function showPage()	
	{
		parent::showPage();
	}
}

class HomeAbout extends ViewWhu
{
	var $file = "homeabout.ihtml";   
	function showPage()	
	{
		parent::showPage();
	}
}

class OnePic extends ViewWhu
{
	var $file = "onepic.ihtml";   
	function showPage()	
	{
		parent::showPage();
	}
}
class OneSpot extends ViewWhu
{
	var $file = "onespot.ihtml";   
	function showPage()	
	{
		parent::showPage();
	}
}
class OneDay extends ViewWhu
{
	var $file = "oneday.ihtml";   
	function showPage()	
	{
		parent::showPage();
	}
}

class HomeBrowse extends ViewWhu
{
	var $file = "homebrowse.ihtml";   
	function showPage()	
	{
		parent::showPage();
	}
	function getCaption()	{	return "Browse Trips";	}
}

class OneTripLog extends ViewWhu
{
	var $file = "triplog.ihtml";   
	function showPage()	
	{
		$tripid = $this->props->get('key');
		
 	 	$trip = $this->build('DbTrip', $tripid);		
		$days = $this->build('DbDays', $tripid);	
		
		$this->template->set_var('TRIP_NAME', $this->cap = $trip->name());

		for ($i = 0, $nodeList = array(); $i < $days->size(); $i++) 
		{
			$day = $this->build('DayInfo', $days->one($i));

			$row = array('day_name' => $day->dayName(), 'stop_name' => $day->nightName(), 'stop_date' => $day->date());

			$row['day_desc'] = $day->baseExcerpt($day->dayDesc(), 20);
			$row['stop_desc'] = $day->baseExcerpt($day->nightDesc(), 30);

			$row['stop_lat_lon'] = $day->strLatLon();

			$nodeList[] = $row;		
		}
		$loop = new Looper($this->template, array('parent' => 'the_page'));
		$loop->do_loop($nodeList);

		parent::showPage();
	}
	function getCaption()	{	return $this->cap;	}
}

class AllTrips extends ViewWhu
{
	var $file = "tripslist.ihtml";   
	function showPage()	
	{
		parent::showPage();
		$trips = $this->build('DbTrips');
		// $trips->dump();
		for ($i = 0, $rows = array(); $i < $trips->size(); $i++) 
		{
			$trip = $trips->one($i);
			// $trip = $this->build('DbTrip', $trips->one($i));
			$rows[] = array('TRIP_DATE' => $trip->startDate(), 'TRIP_ID' => $trip->id());
		}
		$loop = new Looper($this->template, array('parent' => 'the_page'));
		$loop->do_loop($rows);		
	}
	function getCaption()	{	return "All Trips";	}
}

?>