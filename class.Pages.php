<?php

// ---------------- Page Class ---------------------------------------------

class ViewWhu extends ViewBase  // ViewDbBase
{	
	var $file = "UNDEF";
	
	var $wirenotes = array(
		'logid'	 		=> "Core data: a trip log.  Metadata: stories/pics/map", 
		
		'txtsid'	 	=> "Core data: blog posts for a trip. Metadata: log/pics/map", 
		'picsid'	 	=> "Core data: set of thumbnails.     Metadata: log/stories/map<br />Thumbnail count ranges from 1 or 2 for a day to 100's for a trip or a search result.<br />How to do navigation interface?", 
		'mapid'	   	=> "Core data: map of a trip.         Metadata: log/stories/pics", 
		
		'txtwpid' 	=> "Core data: a story. 					Metadata: previous/next story, little locator map, maybe selected pics, trip, pictures", 
		'pictrip' 	=> "Core data: a picture. 				Metadata: previous/next picture, little locator map, date/time, camera, file name, trip, story", 
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
	
	var $caption = '';		// if $caption is non-blank, use it. Otherwise call getCaption()

	function __construct($p)
	{
		$this->props = $p;
		parent::__construct(new WhuTemplate()); 

		$pagetype = $this->props->get('page') . $this->props->get('type');
		if (isset($this->wirenotes[$pagetype]))
			$this->template->set_var("WIRENOTES", $this->wirenotes[$pagetype]);
		
		$pagetype = $this->props->get('page') . $this->props->get('type');
dumpVar(get_class($this), "View class, <b>$pagetype</b> --> <b>{$this->file}</b>");
	}
	function showPage()	
	{
		$this->wirenotes['picsdate'] = $this->wirenotes['picsid'];
	}
	function setCaption()	
	{
		$this->template->set_var('CAPTION', ($this->caption != '') ? $this->caption : $this->getCaption());
	}
	function getCaption()	
	{
		return sprintf("%s | %s | %s", $this->props->get('page'), $this->props->get('type'), $this->props->get('key'));	
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
			$row['MAP_CLASS'] = '';//$trip->hasMap() ? '' : "class='vis_hidden'";
			$row['PIC_CLASS'] = $trip->hasPics() ? '' : "class='vis_hidden'";
			$row['STORY_CLASS'] = $trip->hasStories() ? '' : "class='vis_hidden'";
			// dumpVar($row['TRIP_ID'], $row['TRIP_DATE']);
			// dumpVar($row, "row");exit;
			// dumpVar($row['MAP_CLASS'], "row['MAP_CLASS']");
			$rows[] = $row;
		}
		// $loop = new Looper($this->template, array('parent' => 'the_content'));
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

		for ($i = $iPost = $prevPostId = 0, $nodeList = array(); $i < $days->size(); $i++) 
		{
			$day = new WhuDayInfo($days->one($i));

			$row = array('day_name' => $day->dayName(), 'day_date' => $day->date(), 'miles' => $day->miles(), 'cum_miles' => $day->cumulative());

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
			$row['stop_desc'] = $day->baseExcerpt($day->nightDesc(), 30);

			$row['day_pics'] = $npics = $day->pics()->size();
			$row['pics_msg'] = "$npics pics";
			$row['PIC_CLASS'] = $npics > 0 ? '' : "class='vis_hidden'";
			
			$row['wp_id'] = $day->postId();
// $day->postCatId();
			
			if ($row['wp_id'] > 0) 
			{
				if ($prevPostId != $row['wp_id']) {
					$prevPostId = $row['wp_id'];
					$iPost++;
				}
				$row['day_post'] = "Post $iPost";
				$row['POST_CLASS'] = '';
			}
			else
				$row['POST_CLASS'] = "class='vis_hidden'";
				
			$nodeList[] = $row;		
		}
		// $loop = new Looper($this->template, array('parent' => 'the_content'));
		$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true));                                
		$loop->do_loop($nodeList);

		$this->linkBar('log', $tripid);		
		parent::showPage();
	}
}

class Gallery extends ViewWhu
{
	var $file = "gallery.ihtml";   
	var $galtype = "UNDEF";   
	function showPage()	
	{
		$this->template->set_var('GAL_TYPE', $this->galtype);
		$this->template->set_var('GAL_TITLE', $this->galleryTitle($key = $this->props->get('key')));
		$this->template->set_var('GAL_COUNT', $this->props->get('extra'));
		
		$pics = $this->getPictures($key);
		$this->template->set_var('GAL_KEY', $key);

		for ($i = 0, $rows = array(); $i < $pics->size(); $i++) 
		{
			$pic = $pics->one($i);
			$row = array('PIC_ID' => $pic->id(), 'PIC_name' => $pic->filename(), 'PIC_CAPTION' => $pic->caption());
			$rows[] = $row;
			if ($i > 4) break;
		}
		$loop = new Looper($this->template, array('parent' => 'the_content'));
		// $loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true));
		$loop->do_loop($rows);
		
		parent::showPage();
	}
	function galleryTitle($key)				{	return "Undefined!";	}
}
class TripGallery extends Gallery
{
	var $galtype = "trip";   
	function showPage()	
	{
		$this->linkBar('pics', $this->props->get('key'));
		parent::showPage();
	}
	function getPictures($key)	{ return $this->build('Pics', (array('tripid' => $key))); }	
	function getCaption()				{	return "tripid=" . $this->props->get('key');	}
	function galleryTitle($key)	{	$trip = $this->build('Trip', $key);  return $trip->name(); }
}
class DateGallery extends Gallery
{
	var $galtype = "date";   
	function showPage()	
	{
		$this->linkBar('pics', $this->props->get('key'));
		parent::showPage();
	}
	function getPictures($key)	{ return $this->build('Pics', (array('date' => $key))); }	
	function getCaption()				{	return "tripid=" . $this->props->get('key');	}
	function galleryTitle($key)	{	return Properties::prettyDate($key); }
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

 	 	$days = $this->build('DbDays', $tripid);
		for ($i = 0, $rows = array(); $i < $days->size(); $i++)
		{
			$day = $this->build('DayInfo', $days->one($i));
		
			$row = array('point_ind' => $i+1, 'point_lon' => $day->lon(), 'point_lat' => $day->lat(), 
										'point_name' => addslashes($day->nightName()), 'point_desc' => $day->date(), );
			$rows[] = $row;
		}
		// dumpVar($rows, "rows");
		$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true));
		$loop->do_loop($rows);
		

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

		$this->template->set_var('PRETTY_DATE', $this->caption = $day->prettyDate());

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

		$this->template->set_var('SPOT_NAME', $this->caption = $spot->name());
		parent::showPage();
	}
}

class TripStories extends ViewWhu
{
	var $file = "storylist.ihtml";   
	function showPage()	
	{
		$tripid = $this->props->get('key');
 	 	$trip = $this->build('DbTrip', $tripid);	
		$this->template->set_var('TRIP_NAME', $trip->name());
		
		// collect unique Post ids
		$days = $this->build('DbDays', $tripid);
		for ($i = 0, $wpids = $wpdates = $wpdate = array(); $i < $days->size(); $i++)
		{
			$day = $days->one($i);
			$wpdate[1] = $day->date();
			
			if (in_array($wpid = $day->postId(), $wpids))
				continue;
			// fall through => this is first date for this post. NOTE ALWAYS happens for first day
			$wpids[] = $wpid;
			if (isset($wpdate[0]))
				$wpdates[] = $wpdate;
			$wpdate[0] = $day->date();
		}
		$wpdates[] = $wpdate;

		// now fill the loop
		for ($i = 0, $rows = array(); $i < sizeof($wpids); $i++) 
		{ 
			$post = $this->build('Post', $wpids[$i]);
			// $post->dump();exit;
			$row = array('story_title' => $post->title(), 'story_id' => $wpids[$i]);
			
			$str = Properties::prettyDate($wpdates[$i][0]);
			$str .= " - ";
			$str .= Properties::prettyDate($wpdates[$i][1]);
			$row['story_dates'] = $str;
			
			$row['story_excerpt'] = $post->baseExcerpt($post->content(), 300);
			
			$rows[] = $row;
		}          

		$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true));
		$loop->do_loop($rows);

		parent::showPage();
	}
}
class TripStory extends ViewWhu
{
	var $file = "onestory.ihtml";   
	// var $file = "tripstory.ihtml";
	function showPage()	
	{
		$postid = $this->props->get('key');
 	 	$post = $this->build('Post', array('wpid' => $postid));	
		dumpVar($post->title(), "post->title()");

		$this->template->set_var('POST_TITLE', $post->title());
		$this->template->set_var('POST_CONTENT', $post->content());
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