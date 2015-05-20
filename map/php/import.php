<?php
/** skript pro hromadny import dat */
include 'db.php';

const IMPORT_NOTES = 1;
const IMPORT_PARTS = 2;
const IMPORT_GUIDEPOST_INFO = 3;

function getNumberFromType($type){
    if($type=='OK'){
        return 1;
    } else if($type=='PROBLEM'){
        return 2;
    } else if($type=='COMMENT'){
        return 3;
    } else {
        return 0;
    }
}
 /** TODO: funguje, ale osetrit */
function getImageFromUrl($url, $user){
    $parts = explode("/", $url);
    $file = time()."_".$user;
    copy($url, '../../uploads/'.$file."_".$parts[count($parts)-1]);
    return $file."_".$parts[count($parts)-1];
}

$type = $_POST['type'];
$data = json_decode($_POST['data'], true);
$import_id = time().rand(10,99);
$query_string = "";
$cnt = count($data);
if($type==IMPORT_NOTES) {
    $query_string = "INSERT INTO hicheck.notes (hi_user_id, geom, note, date, type, osm_name, import_id, image) VALUES ";

    for ($i = 0; $i < $cnt; $i++) {
        $user = $data[$i]['user'];
        $date = $data[$i]['date'];
        $note = $data[$i]['note'];
        $image = getImageFromUrl($data[$i]['image'], $user);
        $type = getNumberFromType($data[$i]['type']);
        $osm = $data[$i]['user_is_osm'];
        $lng = $data[$i]['geometry']['coordinates'][0];
        $lat = $data[$i]['geometry']['coordinates'][1];
        $query_string .= " ('$user',ST_SetSRID(ST_MakePoint($lng,$lat), 4326),
    '$note','$date','$type', '$osm', '$import_id', '$image')";
        if ($i != $cnt - 1) {
            $query_string .= ", ";
        }
    }
}else if($type==IMPORT_PARTS){
    $query_string = "INSERT INTO hicheck.parts (hi_user_id, geom,note, date, type, osm_name, import_id) VALUES ";
    for ($i = 0; $i < $cnt; $i++) {
        $user = $data[$i]['user'];
        $date = $data[$i]['date'];
        $note = $data[$i]['note'];
        $type = getNumberFromType($data[$i]['type']);
        $osm = $data[$i]['user_is_osm'];
        $linestring = "LINESTRING";
        foreach ($data[$i]['geometry']['coordinates'] as $key=>$part) {
            if($key==0){
                $linestring .= "(";
            } else {
                $linestring .= ",";
            }
            $linestring .= $part[0]." ".$part[1];
        }
        $linestring .= ")";
        $query_string .= " ('$user',ST_GeomFromText('$linestring', 4326),
    '$note','$date','$type', '$osm', '$import_id')";
        if ($i != $cnt - 1) {
            $query_string .= ", ";
        }
    }
}else if($type==IMPORT_GUIDEPOST_INFO){
    $query_string = "INSERT INTO hicheck.checked_guideposts (hi_user_id, type, note, date, node, osm_name, import_id) VALUES ";
    for ($i = 0; $i < $cnt; $i++) {
        $user = $data[$i]['user'];
        $node = $data[$i]['node_id'];
        $date = $data[$i]['date'];
        $note = $data[$i]['note'];
        $type = getNumberFromType($data[$i]['type']);
        $osm = $data[$i]['user_is_osm'];
        $query_string .= " ('$user', '$type', '$note','$date', '$node','$osm', '$import_id')";
        if ($i != $cnt - 1) {
            $query_string .= ", ";
        }
    }
}

pg_query($db, $query_string);

exit(pg_last_error($db));