<?php
/** ziskani informaci o uzivatelskem bodu */
require 'db.php';
require 'func.php';

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

const USERNOTES = 4;
const USERPARTS = 6;

if((isset($_GET['nid'])) && (isset($_GET['type']))){
    $note_id = $_GET['nid'];
    $type = $_GET['type'];
} else {
    $ajxres=array();
    $ajxres['resp']=4;
    $ajxres['dberror']=0;
    $ajxres['msg']='missing bounding box or type of data';
    send($ajxres);
}

$ways = array();
$info = array();
$ways['type'] = 'FeatureCollection';


if($type==USERNOTES){
    $query_string = "SELECT id, tstamp, type, hi_user_id, note, date, osm_name, ST_AsGeoJSON(geom), image
from hicheck.notes where hicheck.notes.id = '$note_id' and hidden='0'";
} else if($type==USERPARTS){
    $query_string = "SELECT id, tstamp, type, hi_user_id, note, date, osm_name, ST_AsGeoJSON(geom)
from hicheck.parts where hicheck.parts.id = '$note_id' and hidden='0'";
}



$data = pg_query($db, $query_string);

while($row = pg_fetch_assoc($data)){
    $geom = json_decode($row['st_asgeojson']);
    $aux = array();
    $prop = array();
    $prop['id']=$row['id'];
    $prop['timestamp']=$row['tstamp'];
    $prop['date']=$row['date'];
    $prop['user']=$row['hi_user_id'];
    $prop['note']=$row['note'];
    $prop['type']=$row['type'];
    if($type==USERNOTES){
        $prop['image']=$row['image'];
    }
    $prop['osm']=$row['osm_name'];
    $aux['type']='Feature';
    $aux['id']  = $row['id'];
    $aux['properties']=$prop;
    $aux['geometry']=$geom;
    $info[] = $aux;
}
$ways['features']=$info;

exit(prepareData($ways));

