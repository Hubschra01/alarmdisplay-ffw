<?php
/*
 2018 A.Hübner

Version 0.9.0

Dieses Script stellt das Modul zur Auflösung der Einsatzadresse in der Openfiremap dar.

Dieses Programm ist Freie Software: Sie können es unter den Bedingungen 
der GNU General Public License, wie von der Free Software Foundation,
Version 3 der Lizenz oder (nach Ihrer Option) jeder späteren
veröffentlichten Version, weiterverbreiten und/oder modifizieren.

Dieses Programm wird in der Hoffnung, dass es nützlich sein wird, aber
OHNE JEDE GEWÄHRLEISTUNG, bereitgestellt; sogar ohne die implizite
Gewährleistung der MARKTFÄHIGKEIT oder EIGNUNG FÜR EINEN BESTIMMTEN ZWECK.
Siehe die GNU General Public License für weitere Details.

Sie sollten eine Kopie der GNU General Public License zusammen mit diesem
Programm erhalten haben. Wenn nicht, siehe <http://www.gnu.org/licenses/>.

*/
// ---->>>> Ich gehe davon aus, dass eine übermäßige Nutzung durch eine Ortsfeuerwehr mit vielleicht einem Abruf pro Tag, nicht gegeben ist. <<<<------

// Die Abfrage erfolgt direkt über den Nomination-Dienst in wie weit das von den Nutzungsregelen geht ist noch nicht
// bis zum Ende geklärt. 
// User-Agent Tag sollte geändert werden damit nicht alle Abfragen über einen Tag läufen. 
// Ab einer bestimmten Nutzungsaktivität sollte man sich einen eigen TileServer aufbauen. -> Grenze noch unbekannt..

// Create a stream
$opts = array('http'=>array('header'=>"User-Agent: einsatz_map_OSM 0.0.1\r\n"));
$context = stream_context_create($opts);

// Open the file using the HTTP headers set above
// Daten aus dem Aufruf...
$e_adresse = $_GET['strasse']." ".$_GET['hausnr'].", ".$_GET['ort'];

function char_repl($text)
{
$search  = array ('ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü', 'ß', '[', ']');
$replace = array ('ae', 'oe', 'ue', 'Ae', 'Oe', 'Ue', 'ss', '', '');
$str  = str_replace($search, $replace, $text);
return $str;
}
$adresse=char_repl($e_adresse); // Konvertieren / ersetzen von Zeichen 

$url = "https://nominatim.openstreetmap.org/search?format=json&q=" . rawurlencode($adresse);
$response = file_get_contents($url,false,$context);
echo "This map shows fire hydrants which have been entered into the OpenStreetMap database ";
echo "Die Daten stammen von OpenStreetMap.org und unterliegen den dort festgelegten rechtlichen Bedingungen";
$parsed = json_decode($response);
//echo $parsed;
$result0 = $parsed[0];
$lat=$result0->lat;
$lon=$result0->lon;

?>

<!---

This map shows fire hydrants which have been entered into the OpenStreetMap database.
<br><br>
Further information can be found at OpenStreetMap Wiki in
http://wiki.openstreetmap.org/wiki/Tag:emergency%3Dfire_hydrant" target="_blank">,
http://wiki.openstreetmap.org/wiki/DE:OpenFireMap" target="_blank">
http://wiki.openstreetmap.org/wiki/Pl:OpenFireMap" target="_blank">

-->

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="de">
<head>
  <meta http-equiv="Content-Type" content="text/html;charset=utf-8" >
  <title>Hydrants</title>
  <link rel="shortcut icon" href="http://openfiremap.org/favicon_hy.png">
  <link rel="stylesheet" href="http://openfiremap.org/style.css" type="text/css">
  <script src="http://openfiremap.org/OpenLayers.js" type="text/javascript"></script>
  <script src="http://openfiremap.org/OpenStreetMap.js" type="text/javascript"></script>
  <script type="text/javascript">
    // Start position for the map 
	// lon und lat vom nomination Dienst
    var lon="<?php echo $lon; ?>";
	var lat="<?php echo $lat; ?>";

    var zoom=17;
    var map; //complex object of type OpenLayers.Map
 
    OpenLayers.Protocol.HTTPex= new OpenLayers.Class(OpenLayers.Protocol.HTTP, {
      read: function(options) {
        OpenLayers.Protocol.prototype.read.apply(this,arguments);
        options= OpenLayers.Util.applyDefaults(options,this.options);
        options.params= OpenLayers.Util.applyDefaults(
        options.params,this.options.params);
        options.params.resolution= map.getResolution();
        options.params.zoom= map.getZoom();
        if(options.filter) {
          options.params= this.filterToParams(
            options.filter, options.params);
          }
        var readWithPOST= (options.readWithPOST!==undefined)?
          options.readWithPOST: this.readWithPOST;
        var resp= new OpenLayers.Protocol.Response({requestType: "read"});
        if(readWithPOST) {
          resp.priv= OpenLayers.Request.POST({
            url: options.url,
            callback: this.createCallback(this.handleRead,resp,options),
            data: OpenLayers.Util.getParameterString(options.params),
            headers: {"Content-Type": "application/x-www-form-urlencoded"}
            });
        } else {
          resp.priv= OpenLayers.Request.GET({
            url: options.url,
            callback: this.createCallback(this.handleRead,resp,options),
            params: options.params,
            headers: options.headers
            });
          }
        return resp;
        },
      CLASS_NAME: "OpenLayers.Protocol.HTTPex"
      });

    function init() {
      var args= OpenLayers.Util.getParameters(); ///

      OpenLayers.Util.onImageLoadError= function() {
        this.src= "emptytile.png"; };

      map= new OpenLayers.Map("map", {
        controls:[
          new OpenLayers.Control.Navigation(),
          //new OpenLayers.Control.PanZoomBar(),
          //new OpenLayers.Control.LayerSwitcher(),
          //new OpenLayers.Control.ScaleLine(),
          new OpenLayers.Control.Permalink(),
          new OpenLayers.Control.Permalink('permalink'),
          new OpenLayers.Control.MousePosition(),                    
          new OpenLayers.Control.Attribution()],
        maxExtent: new OpenLayers.Bounds(-20037508.34,-20037508.34,20037508.34,20037508.34),
        maxResolution: 156543.0399,
        numZoomLevels: 17,
        units: 'm',
        projection: new OpenLayers.Projection("EPSG:900913"),
        displayProjection: new OpenLayers.Projection("EPSG:4326")

        } );

    map.addLayer(new OpenLayers.Layer.OSM.Mapnik("Map",
      { maxZoomLevel: 17, numZoomLevels: 18 }));
 
    map.addLayer(new OpenLayers.Layer.OSM.Mapnik("Map, pale",
      { maxZoomLevel: 17, numZoomLevels: 18, opacity: 0.5 }));
 
    map.addLayer(new OpenLayers.Layer.OSM("Black/white map","http://toolserver.org/tiles/bw-mapnik/${z}/${x}/${y}.png",
      { maxZoomLevel: 17, numZoomLevels: 18 }));

    map.addLayer(new OpenLayers.Layer.OSM("Grey/white map","http://toolserver.org/tiles/bw-mapnik/${z}/${x}/${y}.png",
      { maxZoomLevel: 17, numZoomLevels: 18, opacity: 0.5 }));

    map.addLayer(new OpenLayers.Layer.OSM("No Background","http://openfiremap.org/img/blank.png",
      { maxZoomLevel: 17, numZoomLevels: 18 }));

    map.addLayer(new OpenLayers.Layer.OSM("Fire hydrants","http://openfiremap.org/hytiles/${z}/${x}/${y}.png",
      { maxZoomLevel: 17, numZoomLevels: 18, alpha: true, isBaseLayer: false }));

    map.addLayer(new OpenLayers.Layer.OSM("Emergency rooms","http://openfiremap.org/eytiles/${z}/${x}/${y}.png",
      { visibility: false, maxZoomLevel: 17, numZoomLevels: 18, alpha: true, isBaseLayer: false }));

    if(!map.getCenter()){
      var lonLat= new OpenLayers.LonLat(lon, lat).transform(new OpenLayers.Projection("EPSG:4326"),
        map.getProjectionObject());
      map.setCenter(lonLat,zoom);
	  
	  layerMarkers = new OpenLayers.Layer.Markers("Markers");
	  map.addLayer(layerMarkers);
	  var size = new OpenLayers.Size(21, 25);
	  var offset = new OpenLayers.Pixel(-(size.w/2), -size.h);
	  var icon = new OpenLayers.Icon('http://www.openstreetmap.org/openlayers/img/marker.png',size,offset);
	  layerMarkers.addMarker(new OpenLayers.Marker(lonLat,icon));

    }
	  


    }
  </script>
</head>
<!-- body.onload is called once the page is loaded (call the 'init' function) -->
<body onload="init();">
<noscript><div class="descriptionPopup"><h1>Please activate Javascript.
<br><br><br><br>Bitte aktivieren Sie Javascript.</h1>
</div></noscript>
<!--
Trial run. There may be incomplete displays and interruptions during style updates.
Testlauf. Während Style-Updates kann es zu unvollständigen Anzeigen und Unterbrechungen kommen.-->
<div style="width:100%; height:100%;" id="map"></div><br>

</body>
</html>
