<?php

$time_start = microtime(true);

require_once dirname(__FILE__).'/../db_conf.php';
$db = pg_connect("host=".SERVER." dbname=".DATABASE." user=".USERNAME." password=".PASSWORD);

$max_ok_distance = 30;

echo <<<EOF
<html>
<header>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</header>
<style>
td { 
  border: 1px solid black;
}
.ok tr { background-color:#ff0000; }
table { 
  border-collapse: collapse;
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
<li><a href="./?analyse">Analyse current DB on osm.fit.vutbr.cz</a></li>
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

  $query="SELECT id, ST_AsText(geom) AS geom, tags->'ref' AS ref FROM nodes WHERE tags @> '\"information\"=>\"guidepost\"'::hstore ORDER BY id";
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
    if(isset($close[$data->n_id])){
      unset($gp[$close[$data->n_id]->g_id]);
    }
    $count[$data->n_id]++;
    $close[$data->n_id] = $data;
  }
  pg_free_result($res);

  echo "<p>Nodes with information=guidepost (".count($no).")</p>\n";

  echo "<table>";
  echo '<tr><th>node ID</th><th>node coord</th><th>node ref</th><th>img ID</th><th>distance</th><th>img ref</th><th>img count</th></tr>'."\n";
  foreach($no as $n){
    echo "<tr>";
    //POINT(12.5956722222222 49.6313222222222)
    $geom = preg_replace('/POINT\(([-0-9.]{1,8})[0-9]* ([-0-9.]{1,8})[0-9]*\)/', '$2 $1', $n->geom);
    echo "<td><a href=\"http://openstreetmap.org/node/".$n->id."\">".$n->id."</a></td>";
    echo "<td><a target=\"hiddenIframe\" href=\"http://localhost:8111/load_object?objects=n".$n->id."\">".$geom."</a></td><td>".$n->ref."</td>";
    if(isset($close[$n->id])){
      $g_id = $close[$n->id]->g_id;
      $d = sprintf("%0.2f", $close[$n->id]->dist);
      $c = isset($count[$n->id]) ? $count[$n->id] : '';
      echo '<td><a href="http://api.openstreetmap.cz/table/id/'.$g_id.'">'.$g_id.'</a>';
      echo '</td><td>'.$d.'</td><td>'.$gp[$g_id]->ref.'</td><td>'.$c.'</td>'."\n";

      unset($gp[$g_id]);
    } else {
      echo '<td></td><td></td><td></td><td></td>'."\n";
    }
    echo "</tr>";
  }
  echo "</table>";

  echo "<p>Unused guideposts photo entries (".count($gp).")</p>\n";

  echo "<table>";
  foreach($gp as $p){
    $geom = preg_replace('/POINT\(([-0-9.]{1,8})[0-9]* ([-0-9.]{1,8})[0-9]*\)/', '$2 $1', $p->geom);
    echo '<tr><td><a href="http://api.openstreetmap.cz/table/id/'.$p->id.'">'.$p->id.'</a></td><td>'.$p->ref.'</td><td>'.$geom.'</td></tr>'."\n";
  }
  echo "</table>";
} // }}}

$time_end = microtime(true);

printf("<p>Total execution time: %.04fs</p>\n",($time_end - $time_start));

echo <<<EOF
</body>
</html>
EOF;

pg_close($db);

