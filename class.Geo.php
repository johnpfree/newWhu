<?php

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

?> 
