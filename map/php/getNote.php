<?php
require 'db.php';
require 'func.php';
// Same as error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);// Same as error_reporting(E_ALL);

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

$query_string = "SELECT id, tstamp, hi_user_id, note, ST_AsGeoJSON(geom)
from hicheck.notes";

if($type==1){
    $query_string .= " where hicheck.notes.id = '$note_id'";
}



$data = pg_query($db, $query_string);

while($row = pg_fetch_assoc($data)){
    $geom = json_decode($row['st_asgeojson']);
    $aux = array();
    $prop = array();
    $prop['id']=$row['id'];
    $prop['timestamp']=$row['tstamp'];
    $prop['user']=$row['hi_user_id'];
    $prop['note']=$row['note'];
    $aux['type']='Feature';
    $aux['id']  = $row['id'];
    $aux['properties']=$prop;
    $aux['geometry']=$geom;
    $info[] = $aux;
}
$ways['features']=$info;

exit(prepareData($ways));

