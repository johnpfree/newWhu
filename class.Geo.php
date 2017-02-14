<?php

// So far, just the file-reading parts of image display

class WhuFilePic extends WhuPic
{	
	function latlon()
	{
		$fullpath = sprintf("%s%s/%s", iPhotoPATH, $this->folder(), $this->filename());		
		$exif = @exif_read_data($fullpath);
		
		if (isset($exif["GPSLongitude"]))
		{
			return array(
				'lon'  => $this->getGps($exif["GPSLongitude"], $exif['GPSLongitudeRef']), 
				'lat'  => $this->getGps($exif["GPSLatitude"], $exif['GPSLatitudeRef']),
			);
		}
		return array();
	}
	function getGps($exifCoord, $hemi) 
	{
		$degrees = count($exifCoord) > 0 ? $this->gps2Num($exifCoord[0]) : 0;
		$minutes = count($exifCoord) > 1 ? $this->gps2Num($exifCoord[1]) : 0;
		$seconds = count($exifCoord) > 2 ? $this->gps2Num($exifCoord[2]) : 0;

		$flip = ($hemi == 'W' or $hemi == 'S') ? -1 : 1;
		return $flip * ($degrees + $minutes / 60 + $seconds / 3600);
	}
	function gps2Num($coordPart) 
	{
		$parts = explode('/', $coordPart);
		if (count($parts) <= 0)		return 0;
		if (count($parts) == 1)		return $parts[0];
		return floatval($parts[0]) / floatval($parts[1]);
	}
}

//
//
// 	function makeThumbRow($pic)		// create the array of settings for a thumb pic, note the scale is settable
// 	{
// 		$img = sprintf("%s%s/%s", iPhotoPATH, $pic['wf_images_path'], $pic['wf_images_filename']);
// 		$xb = exif_thumbnail($img, $xw, $xh, $xm);
// // dumpVar(($xb ? "yes" : "no"), "$j={$pic['wf_images_id']} exif_thumbnail({$pic['wf_images_id']}, $xw, $xh, $xm)");
//
// 		$tmb = array("wid" => $this->scale($xw), "hgt" => $this->scale($xh));
// 		$tmb["binpic"]		= base64_encode($xb);
// 		$tmb["picid"]			= $pic['wf_images_id'];
// 		$tmb["picdate"]		= substr($pic['wf_images_localtime'], 0, 10);
// 		return $tmb;
// 	}
// 	///- - -
// 	// got just the right number of pics, set up the Looper
// 	for ($j = 0, $tmbs = array(); $j < sizeof($fpics); $j++)
// 	{
// 		$tmbs[] = $this->makeThumbRow($fpics[$j]);
// 	}
// 	$loop = new Looper($this->template, array('noFields' => true, 'one' => 'pic_row', 'parent' => 'left_col', 'none_msg' => 'no pictures for this spot'));
// 	$loop->do_loop($tmbs);
//
// 	// - onespot
// 	<div class=rowpics>
// <!-- BEGIN pic_rows -->
// 		<a href="?page=pic&type=date&key={PICDATE}&id={PICID}">
// 			<img  width='{WID}' height='{HGT}' border=0 src="data:image/jpg;base64,{BINPIC}">
//   	</a>
// <!-- END pic_rows -->
// 	</div>
//
// 	// - gallery
// 	<!-- BEGIN gal_rows -->
// 	<div>
// 		<a href="?page=pic&type={GAL_TYPE}&key={GAL_KEY}&id={WF_IMAGES_ID}">
// 			<img class="squareme" border=0 src="data:image/jpg;base64,{BINPIC}">
// 			<div class=tinier>{PIC_LABEL}</div>
// 		</a>
// 	</div>
// 	<!-- END gal_rows -->
//

?> 
