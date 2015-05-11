<?php
include 'db.php';
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
$obj = pg_escape_string($_POST['obj']);
$name = pg_escape_string($_POST['name']);
$note = pg_escape_string($_POST['note']);
$date = pg_escape_string($_POST['date']);
$type = pg_escape_string($_POST['type']);
$osm = pg_escape_string($_POST['osm']);

//zpracovani objektu souradnic jako priprava na vytvoreni linestringu
$coords = explode('{', $obj);
$parts = array();
foreach ($coords as $key => $value) {
    if($key==0) {
        continue;
    } else {
        $parts[$key-1] = explode("g", $value);
        $pos_start = strpos($parts[$key-1][0], ":");
        $pos_end = strpos($parts[$key-1][0], ",");
        $parts[$key-1][0] = substr($parts[$key-1][0], $pos_start+1, $pos_end - $pos_start-1);
        $pos_start = strpos($parts[$key-1][1], ":");
        $pos_end = strpos($parts[$key-1][1], "}");
        $parts[$key-1][1] = substr($parts[$key-1][1], $pos_start+1, $pos_end - $pos_start-1);
    }
}

//vytvoreni linestringu

$linestring = "LINESTRING";
foreach ($parts as $key=>$part) {
    if($key==0){
        $linestring .= "(";
    } else {
        $linestring .= ",";
    }
    $linestring .= $part[1]." ".$part[0];
}
$linestring .= ")";

$sql = "INSERT INTO hicheck.parts (hi_user_id, note, geom, date, type, osm_name)
     VALUES ('$name', '$note', ST_GeomFromText('$linestring', 4326), '$date', '$type', '$osm')";

pg_query($db, $sql);

exit(pg_last_error($db));