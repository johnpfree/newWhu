<?php

/**
* Description
*/
class LinkBar
{	
	function __construct($viewwhu)
	{
		$this->template = $viewwhu->template;
	}
	function showLinks($page, $id)
	{
		$this->template->setFile('LINK_BAR', 'linkbar.ihtml');
		$i = 1;
		foreach ($this->allfour as $k => $v) 
		{
			if ($k == $page)	continue;

			$trip = $this->build('Trip', $id);
			switch ($k) {
				case 'pics':				$gotSome = $trip->hasPics();	break;
				case $this->txtKey:	$gotSome = $trip->hasStories();	break;
				default:			$gotSome = TRUE;
			}
			$this->template->set_var("VIS_CLASS$i", $gotSome ? '' : "class='vis_hidden'");

// dumpVar(boolStr($gotSome), "linkBar($page, $id) $k, $v");
			$this->template->set_var("PAGE$i", $k);
			$this->template->set_var("LABEL$i", $v);
			$this->template->set_var("TYPE$i", 'id');
			$this->template->set_var("KEY$i", $id);			
			$i++;
		}		
	}
}
class TripLinks extends LinkBar
{	
	var $allfour = array(
		"map" => "map",
		"log" => "log",
		"pics" => "pictures",
		"txts" => "stories",
		);
}	
class DayLinks extends LinkBar
{	
	var $allfour = array(
		"map" => "map",
		"day" => "day",
		"pics" => "pictures",
		"txt" => "story",
		);
}

// ---------------- Page Class ---------------------------------------------

class ViewWhu extends ViewBase  // ViewDbBase
{	
	var $file = "UNDEF";
	
	var $wirenotes = array(
		'picsdate' 	=> "Core data: set of thumbnails.     Metadata: log/stories/map<br />Thumbnail count ranges from 1 or 2 for a day to 100's for a search result.<br />How to do navigation interface?", 
		
		'txtwpid' 	=> "Core data: a story. 					Metadata: previous/next story, little locator map, maybe selected pics, trip, pictures", 
		'picdate' 	=> "Core data: a picture. 				Metadata: previous/next picture, little locator map, date/time, camera, file name, trip, story", 

		'spotid'	 	=> "Core data: everything about that spot - all the spot information, a list of the day descriptions for when I was there, some pictures, locator map, link to the stories where it appears", 
		'abouthome' 	=> "Core data: Contact info. Other data: tell about the project, tell about us. Metadata: none unless more than one page is required.", 
		'homehome' 	=> "Core data: Where to start. Make the rest of the site inviting and accessible.", 
		
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
		else
			$this->template->set_var("SHOW_WIRE", 'style="display: none"');
		
		$pagetype = $this->props->get('page') . $this->props->get('type');
dumpVar(get_class($this), "View class, <b>$pagetype</b> --> <b>{$this->file}</b>");
	}
	function showPage()	
		{	}
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
	
	function tripLinkBar($page, $id)
	{
		$allfour = array(
			"map" => "map",
			"log" => "log",
			"pics" => "pictures",
			"txts" => "stories",
			);
		$this->template->setFile('LINK_BAR', 'linkbar.ihtml');
		$trip = $this->build('Trip', $id);
		$i = 1;
		
		foreach ($allfour as $k => $v) 
		{
			if ($k == $page)	continue;
			switch ($k) {
				case 'pics':	$gotSome = $trip->hasPics();	break;
				case 'txts':	$gotSome = $trip->hasStories();	break;
				default:			$gotSome = TRUE;
			}
			$this->template->set_var("VIS_CLASS$i", $gotSome ? '' : "class='vis_hidden'");

// dumpVar(boolStr($gotSome), "linkBar($page, $id) $k, $v");
			$this->template->set_var("PAGE$i", $k);
			$this->template->set_var("LABEL$i", $v);
			$this->template->set_var("TYPE$i", 'id');
			$this->template->set_var("KEY$i", $id);			
			$i++;
		}		
	}
	function dayLinkBar($page, $date)
	{
		$allfour = array(
			"map" => "map",
			"day" => "day",
			"pics" => "pictures",
			"txt" => "story",
			);
		$this->template->setFile('LINK_BAR', 'linkbar.ihtml');
		
		$day = $this->build('DbDay', $date);
		
		$trip = $this->build('DbTrip', $id = $day->tripId());
		$this->template->set_var("TRIP_ID", $id);
		$this->template->set_var("TRIP_NAME", $trip->name());
		
		$i = 1;
		foreach ($allfour as $k => $v) 
		{
			if ($k == $page)	continue;

			switch ($k) {
				case 'pics':	$gotSome = $day->hasPics();	break;
				case 'txt':		$gotSome = $day->hasStory();	break;
				default:			$gotSome = TRUE;
			}
			$this->template->set_var("VIS_CLASS$i", $gotSome ? '' : "class='vis_hidden'");

// dumpVar(boolStr($gotSome), "linkBar($page, $date) $k, $v");
			$this->template->set_var("PAGE$i", $k);
			$this->template->set_var("LABEL$i", $v);
			$this->template->set_var("TYPE$i", 'date');
			$this->template->set_var("KEY$i", $date);			
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

class SpotsHome extends ViewWhu
{
	var $file = "spotshome.ihtml";   
	function showPage()	
	{
		parent::showPage();
return;
		$spots = $this->build('DbSpots');
		for ($i = 0, $rows = array(); $i < $spots->size(); $i++)
		{
			$spot = $spots->one($i);
			$row = array(
				'spot_name' 	=> $spot->name(), 
				'spot_part_of' => $spot->partof(), 
				'spot_times' 	=> $spot->visits(), 
				'spot_where' 	=> 'yy',//$spot->(), 
				'spot_type' 	=> 'zz',//$spot->(), 
 				);
			$rows[] = $row;
		}
		$loop = new Looper($this->template, array('parent' => 'the_content'));
		$loop->do_loop($rows);
		
	}
	function getCaption()	{	return "Browse Spots";	}
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
			$row['MAP_CLASS'] = '';//$trip->hasMap() ? '' : "class='vis_hidden'";		// everybody gets a map!
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
		$tripid = $this->key;
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

		$this->tripLinkBar('log', $tripid);		
		parent::showPage();
	}
}

class TripPictures extends ViewWhu
{
	var $file = "trippics.ihtml";   
	function showPage()	
	{
		parent::showPage();
		
		$trip = $this->build('Trip', $this->key);
		$this->template->set_var('GAL_TITLE', $trip->name());
		
		$days = $this->build('DbDays', $this->key);	
		for ($i = $count = 0, $rows = array(); $i < $days->size(); $i++)
		{
			$day = $days->one($i);
			$row = array('gal_date' => $date = $day->date(), 'date_count' => $dc = $day->pics()->size());
			if ($dc == 0)
				continue;
			$row['nice_date'] = Properties::prettyShortest($date);
			$rows[] = $row;
			$count += $dc;
		}
		// dumpVar($rows, "rows $count");
		$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true));
		$loop->do_loop($rows);
		
		$this->template->set_var('NUM_DAYS', $days->size());
		$this->template->set_var('NUM_PICS', $count);
		
 		$this->tripLinkBar('pics', $this->props->get('key'));
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
		
		// do nav
		$date = $this->build('DbDay', $key);

		$this->template->set_var('PRV_ID', $navd = $date->previous());
		$this->template->set_var('PRV_TXT', Properties::prettyDate($navd));
		$this->template->set_var('NXT_ID', $navd = $date->next());
		$this->template->set_var('NXT_TXT', Properties::prettyDate($navd));

		$pics = $this->getPictures($key);
		$this->template->set_var('GAL_KEY', $key);

		for ($i = 0, $rows = array(); $i < $pics->size(); $i++) 
		{
			$pic = $pics->one($i);
			$row = array('PIC_ID' => $pic->id(), 'PIC_name' => $pic->filename(), 'PIC_CAPTION' => $pic->caption());
			$rows[] = $row;
			// if ($i > 4) break;
		}
		$loop = new Looper($this->template, array('parent' => 'the_content'));
		// $loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true));
		$loop->do_loop($rows);
		
		parent::showPage();
	}
	function galleryTitle($key)				{	return "Undefined!";	}
}
	function showPage()	
	{
		parent::showPage();
	}
// class TripGallery extends Gallery
// {
// 	var $galtype = "trip";
// 	function getPictures($key)	{ return $this->build('Pics', (array('tripid' => $key))); }
// 	function getCaption()				{	return "tripid=" . $this->props->get('key');	}
// 	function galleryTitle($key)	{	$trip = $this->build('Trip', $key);  return $trip->name(); }
// }
class DateGallery extends Gallery
{
	var $galtype = "date";   
	function showPage()	
	{
		$this->dayLinkBar('pics', $this->key);		
		parent::showPage();
	}
	function getPictures($key)	{ return $this->build('Pics', (array('date' => $key))); }	
	function getCaption()				{	return "tripid=" . $this->props->get('key');	}
	function galleryTitle($key)	{	return Properties::prettyDate($key); }
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
		
		$this->tripLinkBar('map', $tripid);		
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

class OneDay extends ViewWhu
{
	var $file = "oneday.ihtml";   
	function showPage()	
	{
		$dayid = $this->key;
 	 	$day = $this->build('DbDay', $dayid);

		$this->template->set_var('PRETTY_DATE', $this->caption = $day->prettyDate());

		$this->dayLinkBar('day', $dayid);		
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
		$this->tripLinkBar('txts', $tripid);		

		parent::showPage();
	}
}
class TripStory extends ViewWhu
{
	var $file = "onestory.ihtml";   
	function showPage()	
	{
		$postid = $this->key;
 	 	$post = $this->build('Post', array('wpid' => $postid));	

		$this->template->set_var('POST_TITLE', $post->title());
		$this->template->set_var('POST_CONTENT', $post->content());
		
 	 	$navpost = $this->build('Post', array('wpid' => $navid = $post->previous()));			
		$this->template->set_var('PRV_TXT', $navpost->title());
		$this->template->set_var('PRV_ID', $navid);
 	 	$navpost = $this->build('Post', array('wpid' => $navid = $post->next()));			
		$this->template->set_var('NXT_TXT', $navpost->title());
		$this->template->set_var('NXT_ID', $navid);

		// $dates = $post->dates();
		// $days = $this->build('DbDays', $dates);
		// dumpVar($post->firstDate(), "post->firstDate()");
		if ($this->props->get('type') != 'date')
			$this->dayLinkBar('txt', $post->firstDate());		// if type==date we have already done linkbar
		parent::showPage();
	}
}
class TripStoryByDate extends TripStory
{
	function showPage()	
	{
		$this->dayLinkBar('txt', $this->key);				// the key is the magic value here
		$day = $this->build('DbDay', $this->key);		// key here is the date
		$this->key = $day->postId();
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