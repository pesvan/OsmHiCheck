<?php

$time_start = microtime(true);

$gpx_file="/tmp/guideposts.gpx";

$gpx_time=file_exists($gpx_file) ? '('.date('d.m.Y H:i', filemtime($gpx_file)).')' : '';
$db_time='('.file_get_contents("../last_update.txt").')';

//output prepared GPX if any
if(isset($_GET['gpx'])){ //{{{

  if(file_exists($gpx_file)){
    header('Content-Description: File Transfer');
    header('Content-Type: application/gpx+xml');
    header('Content-Disposition: attachment; filename="'.basename($gpx_file).'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: '.filesize($gpx_file));
    readfile($gpx_file);
    exit;
  } else {
    echo "Generate GPX by running analysis!\n";
  }
}
//}}}

require_once dirname(__FILE__).'/../db_conf.php';
$db = pg_connect("host=".SERVER." dbname=".DATABASE." user=".USERNAME." password=".PASSWORD);

$max_ok_distance = 20;

echo <<<EOF
<html>
<header>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</header>
<style>
td { 
  border: 1px solid black;
}
tr.ok { background-color:#b0ffa0; }
tr.bad { background-color:#ffa090; }
tr.cor { background-color:#ff5010; }
table { 
  border-collapse: collapse;
  font-size: 9pt;
}
/* iframe for josm and rawedit links */
iframe#hiddenIframe {
    display: none;
      position: absolute;
}
</style>
<body>
<iframe id="hiddenIframe" name="hiddenIframe"></iframe>

<p>Max node and img distance: $max_ok_distance m</p>

<ul>
<li><a href="./?fetch">Fetch DB from api.osm.cz to osm.fit.vutbr.cz</a></li>
<li><a href="./?analyse">Analyse current DB on osm.fit.vutbr.cz</a> $db_time</li>
<li><a href="./?gpx">Download GPX with guideposts without correct photos</a> $gpx_time</li>
</ul>

EOF;

if(isset($_GET['fetch'])){ //{{{
  $query="WITH data AS (SELECT '";

  $response = file_get_contents('http://api.openstreetmap.cz/table/all?output=geojson');
  $query .= $response;

  $query .= "'::json AS fc)
    INSERT INTO hicheck.guideposts (\"id\", \"url\", \"ref\", \"geom\") (
    SELECT 
      CAST (feat->'properties'->>'id' AS int),
      feat->'properties'->>'url',
      feat->'properties'->>'ref',
      ST_SetSRID(ST_GeomFromGeoJSON(feat->>'geometry'), 4326)
    FROM (
      SELECT json_array_elements(fc->'features') AS feat
      FROM data
    ) AS f
    );";
  //echo $query;

  $res = pg_query($db, 'TRUNCATE TABLE "hicheck"."guideposts";');
  pg_free_result($res);
  $res = pg_query($query);
  echo "Proceesed and inserted ".pg_affected_rows($res)." entries.";
  pg_free_result($res);
} // }}}

if(isset($_GET['analyse'])){ //{{{
  
  $query="SELECT id, ref, ST_AsText(geom) AS geom FROM hicheck.guideposts";
  $res = pg_query($query);
  while ($data = pg_fetch_object($res)) {
    $gp[$data->id] = $data;
  }
  pg_free_result($res);
  //echo "Loaded guideposts from DB.<br/>\n";
  //ob_flush();

  $query="SELECT id, ST_AsText(geom) AS geom, tags->'ref' AS ref, tags->'name' AS name FROM nodes WHERE tags @> '\"information\"=>\"guidepost\"'::hstore ORDER BY id";
  $res = pg_query($query);
  while ($data = pg_fetch_object($res)) {
    $no[$data->id] = $data;
  }
  pg_free_result($res);
  //echo "Loaded nodes from DB.<br/>\n";
  //ob_flush();

  $query="SELECT n.id AS n_id, g.id AS g_id, ST_Distance_Sphere(n.geom, g.geom) AS dist
    FROM hicheck.guideposts AS g, (
      SELECT id, geom FROM nodes WHERE tags @> '\"information\"=>\"guidepost\"'::hstore
    ) AS n
    WHERE ST_Distance_Sphere(n.geom, g.geom) < $max_ok_distance;";
  $res = pg_query($query);
  while ($data = pg_fetch_object($res)) {
    $close[$data->n_id][] = $data;
  }
  pg_free_result($res);
  
  //prepare GPX header
  $gpx=fopen($gpx_file, 'w');
  fwrite($gpx, '<?xml version="1.0" encoding="utf-8" standalone="yes"?>'."\n");
  fwrite($gpx, '<gpx version="1.1" creator="Locus Android"'."\n");
  fwrite($gpx, '  xmlns="http://www.topografix.com/GPX/1/1"'."\n");
  fwrite($gpx, '  xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd">'."\n");

  echo "<p>Nodes with information=guidepost (".count($no).")</p>\n";

  echo "<table>";
  echo '<tr><th>node ID</th><th>node coord</th><th>node ref</th><th>images</th></tr>'."\n";
  foreach($no as $n){

    //check for row class - OK (have ref and img), BAD, CORRECT
    echo "<tr";
    if(!isset($n->ref)){
      echo isset($close[$n->id]) ? ' class="cor"' : ' class="bad"'; 
    } else {
      echo isset($close[$n->id]) ? ' class="ok"' : '';
    }
    echo ">";

    //check if need to put to GPX
    $geom = preg_replace('/POINT\(([-0-9.]{1,8})[0-9]* ([-0-9.]{1,8})[0-9]*\)/', 'lat="$2" lon="$1"', $n->geom);
    $name = isset($n->name) ? $n->name : 'n'.$n->id;
    if(isset($close[$n->id])) {
      if (!isset($n->ref)){
        //gp with image but without REF
        fwrite($gpx, "<wpt $geom>"."\n");
        fwrite($gpx, "<name>$name</name>"."\n");
        fwrite($gpx, '<sym>transport-accident</sym>'."\n");
        fwrite($gpx, '</wpt>'."\n");
      }
    } else {
      //gp without image
      fwrite($gpx, "<wpt $geom>"."\n");
      fwrite($gpx, "<name>$name</name>"."\n");
      fwrite($gpx, '<sym>misc-sunny</sym>'."\n");
      fwrite($gpx, '</wpt>'."\n");
    }

    //POINT(12.5956722222222 49.6313222222222)
    $geom = preg_replace('/POINT\(([-0-9.]{1,8})[0-9]* ([-0-9.]{1,8})[0-9]*\)/', '$2 $1', $n->geom);
    echo "<td><a href=\"http://openstreetmap.org/node/".$n->id."\">".$n->id."</a></td>";
    echo "<td><a target=\"hiddenIframe\" href=\"http://localhost:8111/load_object?objects=n".$n->id."\">".$geom."</a></td><td>".$n->ref."</td>";
    if(isset($close[$n->id])) {
      echo '<td>';
      foreach($close[$n->id] as $d){
        $g_id = $d->g_id;
        $dist = sprintf("%0.2f", $d->dist);
        echo '<a href="http://api.openstreetmap.cz/table/id/'.$g_id.'">'.$g_id.'</a>';
        echo '('.$dist.'m, '.$gp[$g_id]->ref.') ';

        $gp_used[$g_id] = 1;
      }
      //$cnt = count($close[$n->id]);
      //echo '<td>'.$cnt.'</td>'."\n";
      echo "\n";
    } else {
      echo '<td></td>'."\n";
    }
    echo "</tr>";
  }
  echo "</table>";

  echo "<p>Guideposts photo entries (total:".count($gp).", used: ".count($gp_used).", unused: ".(count($gp)-count($gp_used)).")</p>\n";

  echo "<table>";
  foreach($gp as $p){
    //skip used images and only left unused ones
    if(isset($gp_used[$p->id])) continue;

    $geom = preg_replace('/POINT\(([-0-9.]{1,8})[0-9]* ([-0-9.]{1,8})[0-9]*\)/', '$2 $1', $p->geom);
    echo '<tr><td><a href="http://api.openstreetmap.cz/table/id/'.$p->id.'">'.$p->id.'</a></td><td>'.$p->ref.'</td><td>'.$geom.'</td></tr>'."\n";
  }
  echo "</table>";

  fwrite($gpx, '</gpx>'."\n");
  fclose($gpx);
} // }}}

$time_end = microtime(true);

printf("<p>Total execution time: %.04fs</p>\n",($time_end - $time_start));

echo <<<EOF
</body>
</html>
EOF;

pg_close($db);

