<?php
require 'db.php';
// Same as error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);// Same as error_reporting(E_ALL);

if(isset($_GET['nid'])){
    $node_id = $_GET['nid'];
} else {
    $ajxres=array();
    $ajxres['resp']=4;
    $ajxres['dberror']=0;
    $ajxres['msg']='missing bounding box or type of data';
    send($ajxres);
}

$arrayOfValues = array();

$query_string = "SELECT id, tstamp, hi_user_id, type, note, image, date, osm_name FROM hicheck.checked_guideposts WHERE node='$node_id'";

$query = pg_query($query_string);

while ($row = pg_fetch_assoc($query)){
    $arrayOfValues[] = $row;
}

exit(json_encode($arrayOfValues));