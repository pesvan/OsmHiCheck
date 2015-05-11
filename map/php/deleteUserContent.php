<?php
require 'db.php';

const REMOVE_GUIDEPOST_INFO = 7;
const REMOVE_USER_NOTE = 2;
const REMOVE_USER_PART = 5;

// Same as error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);// Same as error_reporting(E_ALL);

if((isset($_GET['uid'])) && (isset($_GET['type']))){
    $id = $_GET['uid'];
    $type = $_GET['type'];
} else {
    $ajxres=array();
    $ajxres['resp']=4;
    $ajxres['dberror']=0;
    $ajxres['msg']='missing bounding box or type of data';
    send($ajxres);
}
if($type==REMOVE_GUIDEPOST_INFO){//poznamky u rozcestniku
    $query_string = "DELETE FROM hicheck.checked_guideposts WHERE id='$id'";
} else if($type==REMOVE_USER_NOTE) { //poznamka obecna uzivatelska
    $query_string = "DELETE FROM hicheck.notes WHERE id='$id'";
} else if($type==REMOVE_USER_PART) { //uzivatelem vyznaceny usek
    $query_string = "DELETE FROM hicheck.parts WHERE id='$id'";
}
$query = pg_query($query_string);

exit(json_encode($arrayOfValues));