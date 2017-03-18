<?php
/* Orphans that don't fit into the class structure
-- mostly because I don't need to instantiate a WhuThing to use them

-- getGeocode()				uses Google location services to get the GPS of a place

-- getAllSpotKeys()		returns an array of all spot keywords. It does need the database, which I hack up in the call.
*/
function getGeocode($name)
{
	$geocode_pending = true;
	$delay = 1;
	$res = array('stat' => 'none', 'name' => $name);

  $request_url = sprintf("http://maps.google.com/maps/api/geocode/json?address=%s&sensor=false", urlencode($name));
	$raw = @file_get_contents($request_url);
// dumpVar($raw, "file_get_contents($request_url)");	// exit;

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
function getAllSpotKeys($db)
{
	$items = $db->getAll("select * from wf_spot_days");

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
				else
					$singlekeys[$val[0]][] = $items[$i]['wf_spots_id'];
			}
			else if (sizeof($val) >= 1)
				$keypairs[trim($val[0])] = trim($val[1]);
			else
				jfdie("parsed poorly: $val");
		}
	}
	unset($singlekeys['']);		// a SpotDay with no keywords shows up as a blank, remove that bunch
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
		dumpVar($file, "file");
		$this->out = new FileWrite($file, 'a');
		$this->out->dodump = false;
	}
	function write($post, $src)
	{
		// date time, purpose, name, email, topic, content, url
		$str = sprintf("%s,%s,%s,%s,%s,%s,%s", date("Y-m-d H:i:s"),
						$this->props->get('choose_purpose'), $this->props->get('f_name'), $this->props->get('f_email'), 
						$this->props->get('f_topic'), $this->props->get('f_comment'), $this->props->get('f_url'));
		
		$this->out->write("$str");
	}
}

?> 

