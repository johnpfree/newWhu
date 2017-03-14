
var filename = '{KML_FILE}.kml';		// ka-ching! 
console.log('file', filename);
var runLayer = omnivore.kml(filename)		// has markers
    .on('ready', function() {
// 				llBnds = runLayer.getBounds();
// // console.log(llBnds.toBBoxString());
//         map.fitBounds(llBnds);
    })
    .addTo(map);
