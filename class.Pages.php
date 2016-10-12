<?php

// ---------------- Page Class ---------------------------------------------

class ViewWhu extends ViewBase  // ViewDbBase
{	
	var $file = "UNDEF";
	
	var $wirenotes = array(
		'logid'	 		=> "Core data: a trip log.  Metadata: stories/pics/map", 
		
		'txtsid'	 	=> "Core data: blog posts for a trip. Metadata: log/pics/map", 
		'picsid'	 	=> "Core data: set of thunbnails.     Metadata: log/stories/map", 
		'mapid'	   	=> "Core data: map of a trip.         Metadata: log/stories/pics", 
		
		'pictrip' 	=> "Core data: a picture. 						Metadata: previous/next picture, little locator map, date/time, camera, file name, trip, story", 
		'daydate' 	=> "Core data: everything about that day- including Date, where tonight's stop, last night's stop, some pictures, a locator map, an excerpt fo the post for that day.  Metadata: links to tomorrow/yesterday", 
		'spotid'	 	=> "Core data: everything about that spot - all the spot information, a list of the day descriptions for when I was there, some pictures, locator map, link to the stories where it appears", 
		'searchhome' 	=> "Find stuff. Core data: UI to specify your search. A term can be found in blog posts, spot names, stop names and descriptions, picture labels, map labels, category names", 
		'abouthome' 	=> "Core data: Contact info. Other data: tell about the project, tell about us. Metadata: none unless more than one page is required.", 
		'homehome' 	=> "Core data: Where to start. Make the rest of the site inviting and accessible.", 
		
		'homesearch' 	=> "Core data: show a ", 
		'homelook' 	=> "Core data: show a ", 
		'homeread' 	=> "Core data: show a ", 
		'homeorient' 	=> "Core data: show a ", 
		'homebrowse' 	=> "Core data: show a ", 
		'tripshome' 	=> "Core data: show a ", 
	);

	function __construct($p)
	{
		$this->props = $p;
		parent::__construct(new WhuTemplate()); 

		$pagetype = $this->props->get('page') . $this->props->get('type');
		$this->template->set_var("WIRENOTES", $this->wirenotes[$pagetype]);
		
		$pagetype = $this->props->get('page') . $this->props->get('type');
dumpVar(get_class($this), "View class, <b>$pagetype</b> --> <b>{$this->file}</b>");
	}
	function showPage()	
	{
		$this->template->set_var('CAPTION', $this->getCaption());
	}
	function getCaption()	
	{
		return sprintf("Caption page=%s, type=%s, key=%s", $this->props->get('page'), $this->props->get('type'), $this->props->get('key'));	
	}
	function setStyle()
	{
	}
	
	function linkBar($page, $id)
	{
		$allfour = array(
			"map" => "map",
			"log" => "log",
			"pics" => "pictures",
			"txts" => "stories",
			);
		$this->template->setFile('LINK_BAR', 'linkbar.ihtml');
		$i = 1;
		foreach ($allfour as $k => $v) 
		{
			if ($k == $page)	continue;
			$this->template->set_var("PAGE$i", $k);
			$this->template->set_var("LABEL$i", $v);
			$this->template->set_var("TYPE$i", 'id');
			$this->template->set_var("KEY$i", $id);
			$i++;
		}		
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
class HomeBrowse extends ViewWhu
{
	var $file = "homebrowse.ihtml";   
	function showPage()	
	{
		parent::showPage();
	}
	function getCaption()	{	return "Browse Trips";	}
}

class AllTrips extends ViewWhu
{
	var $file = "tripslist.ihtml";   
	function showPage()	
	{
		parent::showPage();
		$trips = $this->build('Trips');
		// $trips->dump();
		dumpVar(WP_PATH, "WP_PATH");
		$this->template->set_var('WP_PATH', WP_PATH);
		for ($i = 0, $rows = array(); $i < $trips->size(); $i++) 
		{
			$trip = $trips->one($i);
			$row = array('TRIP_DATE' => $trip->startDate(), 'TRIP_ID' => $trip->id(), 'TRIP_FOLDER' => $trip->folder());
			$row['TRIP_NAME'] = $trip->name();
			$row['MAP_CLASS'] = $trip->hasMap() ? '' : "class='vis_hidden'";
			$row['PIC_CLASS'] = $trip->hasPics() ? '' : "class='vis_hidden'";
			$row['STORY_CLASS'] = $trip->hasStories() ? '' : "class='vis_hidden'";
			$row['WP_CAT_ID'] = $trip->wpCatId();
			// dumpVar($row['TRIP_ID'], $row['TRIP_DATE']);
			$rows[] = $row;
		}
		$loop = new Looper($this->template, array('parent' => 'the_content'));
		$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true));                                
		$loop->do_loop($rows);		
	}
	function getCaption()	{	return "All Trips";	}
}

class OneTripLog extends ViewWhu
{
	var $file = "triplog.ihtml";   
	function showPage()	
	{
		$tripid = $this->props->get('key');
 	 	$trip = $this->build('DbTrip', $tripid);		
		$days = $this->build('DbDays', $tripid);	
		$this->template->set_var('TRIP_NAME', $this->caption = $trip->name());

		for ($i = 0, $nodeList = array(); $i < $days->size(); $i++) 
		{
			$day = new WhuDayInfo($days->one($i));

			$row = array('day_name' => $day->dayName(), 'stop_date' => $day->date(), 'stop_lat_lon' => $day->strLatLon());

			$parms = array('stop', 'date', $day->date());
			if ($day->hasSpot())
				$parms = array('spot', 'id', $day->spotId());
			// $parms = $day->hasSpot() ? array('spot', 'id', $day->spotId()) : array('stop', 'date', $day->date());
			$j = 0;
			foreach (array('SDPAGE', 'SDTYPE', 'SDKEY') as $v)
			{
				$row[$v] = $parms[$j++];
			}
			$row['stop_name'] = $day->nightName();
				
			$row['day_desc'] = $day->baseExcerpt($day->dayDesc(), 20);
			$row['stop_desc'] = $day->baseExcerpt($day->nightDesc(), 30);

			$nodeList[] = $row;		
		}
		$loop = new Looper($this->template, array('parent' => 'the_content'));
		$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true));                                
		$loop->do_loop($nodeList);

		$this->linkBar('log', $tripid);		
		parent::showPage();
	}
	function getCaption()	{	return $this->caption;	}
}

class Gallery extends ViewWhu
{
	var $file = "gallery.ihtml";   
	var $galtype = "UNDEF";   
	function showPage()	
	{
		$this->template->set_var('GAL_TYPE', $this->galtype);
		
		$pics = $this->getPictures($key = $this->props->get('key'));
		for ($i = 0, $rows = array(); $i < $pics->size(); $i++) 
		{
			$pic = $pics->one($i);
			$row = array('PIC_ID' => $pic->id(), 'PIC_name' => $pic->filename(), 'PIC_CAPTION' => $pic->caption());
			$rows[] = $row;
			if ($i > 12) break;
		}
		$loop = new Looper($this->template, array('parent' => 'the_content'));
		$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true));                                
		$loop->do_loop($rows);
		
		parent::showPage();
	}
}

class TripGallery extends Gallery
{
	var $galtype = "trip";   
	function showPage()	
	{
		$this->linkBar('pics', $this->props->get('key'));
		parent::showPage();
	}
	function getPictures($key)	
	{ 
		$this->template->set_var('GAL_KEY', $key);
		return $this->build('Pics', (array('tripid' => $key))); 
	}
	
	function getCaption()	{	return "tripid=" . $this->props->get('key');	}
}
class OnePic extends ViewWhu
{
	var $file = "onepic.ihtml";   
	function showPage()	
	{
		parent::showPage();
	}
}

class OneMap extends ViewWhu
{
	var $file = "onemap.ihtml";   
	function showPage()	
	{
		$tripid = $this->props->get('key');
 	 	$trip = $this->build('DbTrip', $tripid);		

		$this->template->set_var('TRIP_NAME', $trip->name());

		$this->linkBar('map', $tripid);		
		parent::showPage();
	}
}

class OneDay extends ViewWhu
{
	var $file = "oneday.ihtml";   
	function showPage()	
	{
		$dayid = $this->props->get('key');
 	 	$day = $this->build('DbDay', $dayid);

		$this->template->set_var('PRETTY_DATE', $foo = $day->prettyDate());

		$this->linkBar('map', $dayid);

		parent::showPage();
	}
}

class OneSpot extends ViewWhu
{
	var $file = "onespot.ihtml";   
	function showPage()	
	{
		$spotid = $this->props->get('key');
 	 	$spot = $this->build('DbSpot', $spotid);		

		$this->template->set_var('SPOT_NAME', $spot->name());
		parent::showPage();
	}
}

class TripStories extends ViewWhu
{
	var $file = "tripstories.ihtml";   
	function showPage()	
	{
		$tripid = $this->props->get('key');
 	 	$trip = $this->build('DbTrip', $tripid);		

		$this->template->set_var('TRIP_NAME', $trip->name());

		$this->linkBar('txts', $tripid);		
		parent::showPage();
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
class About extends ViewWhu
{
	var $file = "about.ihtml";   
	function showPage()	
	{
			parent::showPage();
	}
}
class Search extends ViewWhu
{
	var $file = "search.ihtml";   
	function showPage()	
	{
		parent::showPage();
	}
}
?>