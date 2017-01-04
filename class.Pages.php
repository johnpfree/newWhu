<?php

// ---------------- Page Class ---------------------------------------------

class ViewWhu extends ViewBase  // ViewDbBase
{	
	var $file = "UNDEF";
	var $curpal = NULL;
	var $pals =	array(
			"deflt" => 	array('boldcolor' => '#3A5950', 'linkcolor' => '#659a8b', 'linkhover' => '#a7c5bc', 'bbackcolor' => '#d7e5e1', 'backcolor' => '#e9f0ee'), 
			"map" => 		array('boldcolor' => '#47894B', 'linkcolor' => '#73b778', 'linkhover' => '#afd6b1', 'bbackcolor' => '#dbecdc', 'backcolor' => '#ebf4eb'), 
			"log" =>		array('boldcolor' => '#2d4976', 'linkcolor' => '#4e78bc', 'linkhover' => '#9ab2d8', 'bbackcolor' => '#d1dced', 'backcolor' => '#e5ebf5'), 
			"txt" => 		array('boldcolor' => '#59463A', 'linkcolor' => '#9a7a65', 'linkhover' => '#c5b3a7', 'bbackcolor' => '#e5dcd7', 'backcolor' => '#f0ece9'), 
			"pic" => 		array('boldcolor' => '#515022', 'linkcolor' => '#a5a345', 'linkhover' => '#d0cf90', 'bbackcolor' => '#eae9cd', 'backcolor' => '#f3f3e3'), 
			"search" => array('boldcolor' => '#B96936', 'linkcolor' => '#d4946c', 'linkhover' => '#e6c2ab', 'bbackcolor' => '#f4e3d9', 'backcolor' => '#f8efea'), 
			// "deflt" => 	array('boldcolor' => '#000000', 'linkcolor' => '#615f5f', 'linkhover' => '#000088', 'bbackcolor' => '#ffffff', 'backcolor' => '#ffffff'),
			// "pic" => 		array('boldcolor' => '#002d92', 'linkcolor' => '#82cdff', 'linkhover' => '#a2edff', 'bbackcolor' => '#e2ffff', 'backcolor' => '#c2ffff'),
			// "log" =>		array('boldcolor' => '#729200', 'linkcolor' => '#615f5f', 'linkhover' => '#729200', 'bbackcolor' => '#fffff2', 'backcolor' => '#ffff92'),
			// "txt" => 		array('boldcolor' => '#8c2b09', 'linkcolor' => '#d9c6ba', 'linkhover' => '#ffcba9', 'bbackcolor' => '#ffffe9', 'backcolor' => '#ffebc9'),
			// "map" => 		array('boldcolor' => '#2d4976', 'linkcolor' => '#8da9a6', 'linkhover' => '#adc9f6', 'bbackcolor' => '#edffff', 'backcolor' => '#cde9ff'),
			// "search" => array('boldcolor' => '#59463A', 'linkcolor' => '#ffcba9', 'linkhover' => '#d9c6ba', 'bbackcolor' => '#fffffa', 'backcolor' => '#f9e6da'),
			// "spot" => 	array('boldcolor' => '#101010', 'linkcolor' => '#909090', 'linkhover' => '#b0b0b0', 'bbackcolor' => '#f0f0f0', 'backcolor' => '#d0d0d0'),
			"spot" => 	array('boldcolor' => '#101010', 'linkcolor' => '#909090', 'linkhover' => '#b0b0b0', 'bbackcolor' => '#f0f0f0', 'backcolor' => '#d0d0d0'), 
			"gray" => 	array('boldcolor' => '#101010', 'linkcolor' => '#909090', 'linkhover' => '#b0b0b0', 'bbackcolor' => '#f0f0f0', 'backcolor' => '#d0d0d0'), 
		);
	
	var $caption = '';		// if $caption is non-blank, use it. Otherwise call getCaption()

	function __construct($p)
	{
		$this->props = $p;
		parent::__construct(new WhuTemplate()); 		
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
	function setStyle($page)
	{
		foreach ($this->pals as $k => $v)
		{
			// dumpVar($k, "pt= $page, k");
			if (strpos($page, $k) !== false) {
				$this->curPal = new StyleProps($v, $k);
				break;
			}
		}	
		if (!isset($this->curPal))
			$this->curPal = new StyleProps($this->pals['deflt'], 'default');
		
		$this->template->set_var('BBACKCOLOR', 	$this->curPal->pageBackColor());
		$this->template->set_var('BODYCOLOR', 	$this->curPal->pageLineColor());
		$this->template->set_var('BACKCOLOR', 	$this->curPal->contBackColor());
		$this->template->set_var('BORDERCOLOR', $this->curPal->contLineColor());
		$this->template->set_var('BOLDCOLOR', 	$this->curPal->boldFontColor());
		$this->template->set_var('LINKCOLOR', 	$this->curPal->linkColor()    );
		$this->template->set_var('LINKHOVER', 	$this->curPal->linkHover()    );
		$this->template->set_var('PAL_NAME', 	$this->curPal->palette);
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
			$paltag = $k;
			if ($k == $page)	continue;
			switch ($k) {
				case 'pics':	$paltag = 'pic'; $gotSome = $trip->hasPics();	break;
				case 'txts':	$paltag = 'txt'; $gotSome = $trip->hasStories();	break;
				default:			$gotSome = TRUE;
			}
			$this->template->set_var("VIS_CLASS$i", $gotSome ? '' : "class='vis_hidden'");

// dumpVar(boolStr($gotSome), "linkBar($page, $id) $k, $v");
			$this->template->set_var("PAGE$i", $k);
			$this->template->set_var("LABEL$i", $v);
			$this->template->set_var("TYPE$i", 'id');
			$this->template->set_var("KEY$i", $id);		
				
			$this->template->set_var("BACK$i", $this->pals[$paltag]['bbackcolor']);			
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
			$paltag = $k;
			if ($k == $page)	continue;

			switch ($k) {
				case 'pics':	$gotSome = $day->hasPics();	$paltag = 'pic'; break;
				case 'txt':		$gotSome = $day->hasStory();	break;
				case 'day':		$paltag = 'log'; 	break;
				default:			$gotSome = TRUE;
			}
			$this->template->set_var("VIS_CLASS$i", $gotSome ? '' : "class='vis_hidden'");

// dumpVar(boolStr($gotSome), "linkBar($page, $date) $k, $v");
			$this->template->set_var("PAGE$i", $k);
			$this->template->set_var("LABEL$i", $v);
			$this->template->set_var("TYPE$i", 'date');
			$this->template->set_var("KEY$i", $date);			
			$this->template->set_var("BACK$i", $this->pals[$paltag]['bbackcolor']);			
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
		
		$opts = array();			// assume show all
		if ($this->props->get('submit') == 'Show' && sizeof($srch = $this->props->get('search_fld')) > 0)
		{
			$chkopts = array(
				'chkcamp' => 'CAMP', 
				'chklodg' => 'LODGE', 
				'chkhspr' => 'HOTSPR', 
				'chknwrf' => 'NWR', 
				);
			foreach ($srch as $k => $v) 
			{
				$opts[] = $chkopts[$v];
				$this->template->set_var("CHK_$v", 'checked');
			}
		}

		$spots = $this->build('DbSpots', $opts);
		dumpVar(sizeof($spots->data), "spots->data");
		
		for ($i = 0, $rows = array(); $i < $spots->size(); $i++)
		{
			$spot = $spots->one($i);
			$row = array(
				'spot_id' 		=> $spot->id(), 
				'spot_name' 	=> $spot->name(), 
				'spot_part_of' => $spot->partof(), 
				// 'spot_times' 	=> $spot->visits(),
				'spot_where' 	=> $spot->town(), 
				'spot_type' 	=> $spot->types(), 
 				);
			$rows[] = $row;
		}
		$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true));
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
			// little hack for keeping track of maps
			if ($trip->hasMapboxMap())	$row['TRIP_NAME'] .= "-M";
			if ($trip->hasGoogleMap())	$row['TRIP_NAME'] .= "-G";
			$rows[] = $row;
		}
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
			// $day = new WhuDayInfo($days->one($i));
			$day = $this->build('DayInfo', $days->one($i));

			$row = array('day_name' => $day->dayName(), 'miles' => $day->miles(), 'cum_miles' => $day->cumulative(), 'map_marker' => $i+1);
			$row['nice_date'] = Properties::prettyDate($row['day_date'] = $day->date(), "M"); 

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

		$this->template->set_var('PRV_ID', $navd = $date->previousDayGal());
		$this->template->set_var('PRV_TXT', Properties::prettyDate($navd));
		$this->template->set_var('NXT_ID', $navd = $date->nextDayGal());
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
		$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true));
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
		$this->template->set_var('MAPBOX_TOKEN', MAPBOX_TOKEN);
		$this->template->set_var('PAGE_VAL', 'day');
		$this->template->set_var('TYPE_VAL', 'date');
		$this->template->set_var('MARKER_COLOR', '#8c54ba');
		$this->template->set_var('WHU_URL', $foo = sprintf("https://%s%s", $_SERVER['HTTP_HOST'], parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH)));
		dumpVar($foo, "WHU_URL");				

		$tripid = $this->key;
 	 	$trip = $this->build('Trip', $tripid);		

		$this->template->set_var('MAP_NAME', $trip->name());
		
// dumpVar($trip->mapboxId(), "trip->");
// dumpVar(boolStr($trip->hasMapboxMap()), "trip->hasMapboxMap()");
// dumpVar(boolStr($trip->hasGoogleMap()), "trip->hasGoogleMap()");
// exit;
		
		if ($trip->hasMapboxMap())
		{
			$filename = $trip->mapboxJson();
			$fullpath = MAP_DATA_PATH . $filename;
// dumpVar($fullpath, "fullpath");
			$this->template->set_var("MAP_JSON", file_get_contents($fullpath));
			$this->template->setFile('JSON_INSERT', 'mapjson.js');
			$this->template->set_var("CONNECT_DOTS", 'false');		// no polylines
		}	
		else if ($trip->hasGoogleMap())
		{
			$filename = $trip->folder();
			$fullpath = 'data/' . $filename;
// dumpVar($fullpath, "fullpath");
			$this->template->set_var("KML_FILE", $fullpath);
			$this->template->setFile('JSON_INSERT', 'mapkml.js');
			$this->template->set_var("CONNECT_DOTS", 'false');		// no polylines
		}	
		else
		{	
			$this->template->set_var("JSON_INSERT", '');
			$this->template->set_var("CONNECT_DOTS", 'true');		// there is no route map, so connect the dots with polylines
		}
		
 	 	$days = $this->build('DbDays', $tripid);
		for ($i = 0, $rows = array(), $prevname = '@'; $i < $days->size(); $i++)
		{
			$day = $this->build('DayInfo', $days->one($i));

			$row = array('marker_val' => $i+1, 'point_lon' => $day->lon(), 'point_lat' => $day->lat(), //'point_loc' => $day->town(), 
										'point_name' => addslashes($day->nightName()), 'key_val' => $day->date(), 'link_text' => Properties::prettyDate($day->date()));
										
			if ($row['point_lat'] * $row['point_lon'] == 0) {						// skip if no position
				dumpVar($row, "NO POSITION! $i row");
				continue;
			}
			if ($row['point_name'] == $prevname) {											// skip if I'm at the same place as yesterday
				dumpVar($row['point_name'], "skipping same $i");
				continue;
			}
			$prevname = $row['point_name'];
						
			$rows[] = $row;
		}
		// dumpVar($rows, "rows");
		$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true));
		$loop->do_loop($rows);
		
		$this->tripLinkBar('map', $tripid);		
		parent::showPage();
	}
}
class SpotMap extends OneMap
{
	function showPage()	
	{
		$this->template->set_var('MAPBOX_TOKEN', MAPBOX_TOKEN);
		$this->template->set_var('LINK_BAR', '');
		$this->template->set_var("JSON_INSERT", '');
		$this->template->set_var("CONNECT_DOTS", 'false');		// no polylines
		$this->template->set_var('PAGE_VAL', 'spot');
		$this->template->set_var('TYPE_VAL', 'id');
		$this->template->set_var('WHU_URL', $foo = sprintf("https://%s%s", $_SERVER['HTTP_HOST'], parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH)));
		dumpVar($foo, "WHU_URL");				
		
		$spotid = $this->key;
		$radius = $this->props->get('id');
 	 	$spot = $this->build('DbSpot', $spotid);		

		$this->template->set_var('MAP_NAME', sprintf("Spots in a %s mile radius of %s", $radius, $spot->name()));
		
		$items = $spot->getInRadius($radius);

		$markers = array('CAMP' => 'campsite', 'LODGE' => 'lodging', 'HOTSPR' => 'swimming', 'PARK' => 'parking', 'NWR' => 'wetland');	// , 'veterinary', 'shelter', 'dog-park', 'zoo'
		// CAMP(286), HOTSPR(30) • LODGE(31) • NWR(19) •
// dumpVar($markers, "markers");
	
 	 	$spots = $this->build('DbSpots', $items);
		for ($i = 0, $rows = array(); $i < $spots->size(); $i++)
		{
			$spot = $spots->one($i);
		
			$row = array('point_lon' => $spot->lon(), 'point_lat' => $spot->lat(), 
										'point_name' => addslashes($spot->name()), 'key_val' => $spot->id(), 'link_text' => addslashes($spot->town()));
			$row['marker_color'] = ($i == 0) ? '#000' : '#8c54ba';
										
			$types = $spot->prettyTypes();
			foreach ($types as $k => $v)	{
				$row['marker_val'] = $markers[$k];			// effectively, the larker is whichever TYPE was last in that field.
			}
			// $row['marker_val'] = $markers[($i % sizeof($markers))];

			if ($row['point_lat'] * $row['point_lon'] == 0) {						// skip if no position
				dumpVar($row, "NO POSITION! $i row");
				continue;
			}
			$rows[] = $row;
		}
		$loop = new Looper($this->template, array('parent' => 'the_content'));
		$loop->do_loop($rows);
		
		ViewWhu::showPage();
	}
}

class OnePic extends ViewWhu
{
	var $file = "onepic.ihtml";   
	function showPage()	
	{
		parent::showPage();

 	 	$pic = $this->build('Pic', $picid = $this->props->get('id'));
		
		$this->template->set_var('WF_IMAGES_PATH', $pic->folder());
		$this->template->set_var('WF_IMAGES_FILENAME', $pic->filename());
		$this->template->set_var('WF_IMAGES_TEXT', $pic->caption());
		$this->template->set_var('REL_PICPATH', iPhotoURL);
		// $this->template->set_var('REL_PICPATH', REL_PICPATH);
		
		$this->template->set_var('COLLECTION_NAME', Properties::prettyDate($date = $pic->date()));
		$this->template->set_var('THIS_KEY', $date);
		$this->template->set_var('VIS_CLASS1', '');
		$this->template->set_var('VIS_CLASS2', '');

		$this->template->set_var('NXT_TXT', 'next');
		$this->template->set_var('NXT_KEY', $pic->next()->date());
		$this->template->set_var('NXT_ID' , $pic->next()->id());

		$this->template->set_var('PRV_TXT', 'previous');
		$this->template->set_var('PRV_KEY', $pic->prev()->date());
		$this->template->set_var('PRV_ID' , $pic->prev()->id());

		// pic info
		$this->template->set_var('PRETTIEST_DATE', Properties::prettiestDate($date));
		$this->template->set_var('PIC_TIME', $pic->time());
		$this->template->set_var('PIC_CAMERA', $pic->cameraDesc());
		// keywords
		$keys = $this->build('PicKeywords', array('picid' => $picid));
		for ($i = 0, $rows = array(); $i < $keys->size(); $i++)
		{
			$key = $keys->one($i);	
			$row = array('WF_CATEGORIES_ID' => $key->id(), 'WF_CATEGORIES_TEXT' => $key->name());
			$rows[] = $row;
		}
		$loop = new Looper($this->template, array('parent' => 'the_content'));
		$loop->do_loop($rows);
		
	}
}

class OneDay extends ViewWhu
{
	var $file = "oneday.ihtml";   
	function showPage()	
	{
		$dayid = $this->key;
 	 	$day = $this->build('DayInfo', $dayid);

		$this->caption = Properties::prettyDate($date = $day->date());
		$this->template->set_var('PRETTY_DATE', Properties::prettiestDate($date));
		
		$this->template->set_var('ORDINAL', $day->day());
		$this->template->set_var('MILES', $day->miles());
		$this->template->set_var('CUMMILES', $day->cumulative());
		
		$this->template->set_var('WPID', $wpid = $day->postId());
		if ($wpid > 0)
			$this->template->set_var('STORY', $this->build('Post', $wpid)->title());
		$this->template->set_var("VIS_CLASS_TXT", $day->hasStory() ? '' : "class='vis_hidden'");
		
		$this->template->set_var('DAY_DESC', $day->dayDesc());
		$this->template->set_var('NIGHT_DESC', $day->nightDesc());
		$this->template->set_var('PM_STOP', $day->nightNameUrl());
	
		// do next|prev nav - as long as I have yesterday, show where I woke up today
		$navday = $this->build('DbDay', $d = $day->yesterday());
		$this->template->set_var('AM_STOP', $this->build('DayInfo', $d)->nightNameUrl());

		$this->template->set_var('PRV_DATE', $d);
		$this->template->set_var('PRV_LABEL', $navday->hasData ? 'yesterday' : '');
		$navday = $this->build('DbDay', $d = $day->tomorrow());
		$this->template->set_var('NXT_DATE', $d);
		$this->template->set_var('NXT_LABEL', $navday->hasData ? 'tomorrow' : '');

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

		$this->template->set_var('SPOT_NAME', 	$this->caption = $spot->name());
		$this->template->set_var('SPOT_ID', 		$spot->id());
		$this->template->set_var('SPOT_TOWN', 	$spot->town());
		$this->template->set_var('SPOT_PARTOF', $spot->partof());
		$this->template->set_var('SPOT_NUM',  	$spot->visits());
		
		$types = $spot->prettyTypes();
		// dumpVar($types, "types"); exit;
		$str = '';
		foreach ($types as $k => $v) 
		{
			$str .= $v . ', ';
		}		
		$this->template->set_var('SPOT_TYPES', substr($str, 0, -2));

		$keys = $spot->keywords();
		// dumpVar($keys, "keys");
		for ($i = 0; $i < sizeof($keys); $i++) 
		{
			// dumpVar($keys[$i], "keys[$i]");
			$rows[] = array('spot_key' => $keys[$i]);
		}
		// dumpVar($rows, "rows");
		$loop = new Looper($this->template, array('parent' => 'the_content', 'one' => 'keyrow', 'noFields' => true));
		$loop->do_loop($rows);
		$days = $this->build('DbSpotDays', $spot->id());	
		for ($i = $count = 0, $rows = array(); $i < $days->size(); $i++)
		{
			$day = $days->one($i);
			$row = array('stay_date' => $date = $day->date());
			$row['nice_date'] = Properties::prettyDate($date);
			$rows[] = $row;
		}
		// dumpVar($rows, "rows $count");
		$loop = new Looper($this->template, array('parent' => 'the_content', 'noFields' => true));
		$loop->do_loop($rows);

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
	// var $file = "blank.ihtml";
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
		$opts = array(
			'chk_stti', 
			'chk_stst', 
			'chk_pcti', 
			'chk_trip', 
			'chk_maps', 
		);
		if ($this->props->isProp('search_for_term') && ($term = $this->props->get('search_term')) != '' && sizeof($srch = $this->props->get('search_fld')) > 0)
		{
			dumpVar($srch, "term=$term, srch");
			if (isset($srch['chk_stti']))		// search blog
			{
				// ((wp_posts.post_title LIKE '%football%') OR
				// (wp_posts.post_content LIKE '%football%'))
			}
		}
		else 
		{
			foreach ($opts as $k => $v) 
			{
				$this->template->set_var(strtoupper($v), 'checked');
			}
		}
		parent::showPage();
	}
}
?>