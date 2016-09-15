<?php

Class ViewEdit extends ViewBase
{
	function __construct($p)
	{
		parent::__construct();
		$this->props = $p;
	}
	public function showPage()
	{
		$tripid = 44;
		
 	 	$trip = $this->build('DbTrip', $tripid);		
		$days = $this->build('DbDays', $tripid);	
		
		$this->template->set_var('TRIP_NAME', $trip->name());

// <tr><td>{stop_date}</td><td>{}</td><td>{stop_full_name}</td><td>{stop_desc}</td><td>{}</td><td>{}</td><td>{stop_lat_lon}</td>

		for ($i = 0, $nodeList = array(); $i < $days->size(); $i++) 
		{
			$day = $this->build('DayInfo', $days->one($i));

			$row = array('day_name' => $day->dayName(), 'stop_name' => $day->nightName(), 'stop_date' => $day->date());

			$row['day_desc'] = $day->baseExcerpt($day->dayDesc(), 20);
			$row['stop_desc'] = $day->baseExcerpt($day->nightDesc(), 30);

			$row['stop_lat_lon'] = $day->strLatLon();

			$nodeList[] = $row;		
		}
		$loop = new Looper($this->template, array('parent' => 'main'));
		$loop->do_loop($nodeList);
	}
	function build ($type = '', $key) 
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

?> 
