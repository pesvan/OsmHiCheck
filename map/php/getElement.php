<?php
/** ziskani vsech dat k dane relaci/ceste/uzlu */
include 'db.php';
// Same as error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);// Same as error_reporting(E_ALL);
if (isset($_GET['data']) && isset($_GET['type'])) {
    $data_id=$_GET['data'];
    $type=$_GET['type'];
} else {
		$ajxres=array();
		$ajxres['resp']=4;
		$ajxres['dberror']=0;
		$ajxres['msg']='missing data identification or type of data';
		send($ajxres);
}

const RELATION = 0;
const WAY = 1;
const NODE = 2;

$query_string = "SELECT id, version, user_id, tstamp, changeset_id, hstore_to_json(tags) as tags from ";

if($type==RELATION){
	$query_string.="relations ";
}else if($type==WAY) {
	$query_string.="ways ";
}else if($type==NODE) {
	$query_string.="nodes ";
} else {
	$ajxres=array();
	$ajxres['resp']=4;
	$ajxres['dberror']=0;
	$ajxres['msg']='unsupported type of data [0=relations;1=ways;2=nodes]';
	exit($ajxres);
}
	
$query_string.="where id='$data_id'";

$data = pg_query($db, $query_string);

$row = pg_fetch_assoc($data);
$row['tags'] = json_decode($row['tags']);
send($row);
function send($data){
	$aux = json_encode($data);
	exit($aux);
}
?>