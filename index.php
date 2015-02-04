<?php
include 'db.php';
  $result = pg_query($db, "SELECT * FROM relations WHERE tags->'operator' = 'cz:KČT' LIMIT 1");
  while($row=pg_fetch_array($result)){
    $relid = $row['id'];
    $pom = pg_query($db, "SELECT * FROM relation_members WHERE relation_id = '$relid' LIMIT 5");
    $ways = array();
    while($member = pg_fetch_array($pom)){
      if($member['member_type']=='W'){
        $wayid = $member['member_id'];
        $ww = pg_query($db, "SELECT * FROM way_nodes WHERE way_id = '$wayid'");
        $x = array();
        $y = array();
        while($node = pg_fetch_array($ww)){
          $nodeid = $node['node_id'];
          $node_loc = pg_query($db, "SELECT ST_X(geom), ST_Y(geom) FROM nodes WHERE id='$nodeid'");
          $location = pg_fetch_assoc($node_loc);
          $x[] = $location['st_x'];
          $y[] = $location['st_y'];
        }
      }
    }   
  }
?>

<!DOCTYPE html>
<html>
<head>

<title>xsvana00</title>
<meta charset="utf-8" />
<link rel="stylesheet" type="text/css" href="http://fit.pesvan.cz/BP/osm/leaflet.css" />
<link rel="stylesheet" href="http://fit.pesvan.cz/BP/osm/Control.OSMGeocoder.css" />
  <link rel="stylesheet" href="leaflet.draw.css" />

<link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css" />
<script type="text/javascript" src="http://fit.pesvan.cz/BP/osm/leaflet.js"></script>
<script src="http://fit.pesvan.cz/BP/osm/Control.OSMGeocoder.js"></script>
<script src="http://makinacorpus.github.io/Leaflet.FileLayer/leaflet.filelayer.js"></script>
<script src="http://makinacorpus.github.io/Leaflet.FileLayer/togeojson/togeojson.js"></script>
<script src="leaflet.draw.js"></script>
<script type="text/javascript" src="leaflet.js"></script>
<script type="text/javascript">
<?php
  echo "var x = ". json_encode($x) . ";\n";
  echo "var y = ". json_encode($y) . ";\n";
?>
  </script>
<script type="text/javascript">
  var map;
  var ajaxRequest;
  var plotlist;
  var plotlayers=[];

  function initmap() {
    // set up the map
    map = new L.Map('mymap');

    // create the tile layer with correct attribution
    var osmUrl='http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
    var ruianUrl = 'http://tile.poloha.net/{z}/{x}/{y}.png';
    var pokus = 'http://osm.fit.vutbr.cz/xsvana00/layer/{z}/{x}/{y}.png';
    var osmAttrib='Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors';
    var osm = new L.TileLayer(osmUrl, {minZoom: 1, maxZoom: 19, attribution: osmAttrib});
    var ruian = new L.TileLayer(ruianUrl, {minZoom: 1, maxZoom: 19, attribution: osmAttrib});   
   	var pokusl = new L.TileLayer(pokus, {maxzoom: 19});
	 var mraky = new 
  L.tileLayer('http://{s}.tile.openweathermap.org/map/clouds/{z}/{x}/{y}.png', {
      attribution: 'Map data © OpenWeatherMap',
      maxZoom: 18
  });
    // start the map in Brno
    map.setView(new L.LatLng(48.952, 16.734),15);
    map.addLayer(osm);

    //add scale
	    L.control.scale().addTo(map);

    //searching
    var osmGeocoder = new L.Control.OSMGeocoder();
    map.addControl(osmGeocoder);

      var overlays = {
      "ruian": ruian,
      "osm": osm,
  };

    L.control.layers(overlays,{mraky,pokusl}).addTo(map);


    var drawnItems = new L.FeatureGroup();
    map.addLayer(drawnItems);

    var drawControl = new L.Control.Draw({
      draw: {
        position: 'topleft',
        polygon: {
          title: 'Draw a sexy polygon!',
          allowIntersection: false,
          drawError: {
            color: '#b00b00',
            timeout: 1000
          },
          shapeOptions: {
            color: '#bada55'
          },
          showArea: true
        },
        polyline: {
          metric: false
        },
        circle: {
          shapeOptions: {
            color: '#662d91'
          }
        }
      },
      edit: {
        featureGroup: drawnItems
      }
    });
    map.addControl(drawControl);
    var latLon = [];
    var str = "";
     for (var i = 0; i < x.length; i++){
      latLon[i] = L.latLng(y[i],x[i]);
    //L.marker( [y[i], x[i]]).addTo(map);
    //console.log(y[i]);
    str += latLon[i];
  }
  L.polyline(latLon).bindPopup(str+"<input type=text/>").addTo(map);
  console.log(x.length);
    map.on('draw:created', function (e) {
      var type = e.layerType,
        layer = e.layer;

      if (type === 'polyline') {
        layer.bindPopup(layer.getLatLngs().toString());
      }

      drawnItems.addLayer(layer);
    });
  }
 
</script>
<style type="text/css">
  #mymap { 
    position: absolute;
    top:0;
    left: 0;
    right: 0;
    bottom:0;
  }
</style>

</head>

<body onload="initmap()">
  <div id="mymap"></div>
  <i class="fa fa-camera-retro"></i> fa-camera-retro
</body>
</html>


