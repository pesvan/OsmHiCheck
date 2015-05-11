<?php
require 'db.php';
require 'func.php';
// Same as error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);// Same as error_reporting(E_ALL);

if((isset($_GET['bbox'])) && (isset($_GET['type'])) && isset($_GET['bicycle'])){
    $type = $_GET['type'];
    list($left,$bottom,$right,$top)=explode(",",$_GET['bbox']);
    if($_GET['bicycle']=='true'){
        $noBicycles =false;
    } else {
        $noBicycles = true;
    }
} else {
    $ajaxResponse=array();
    $ajaxResponse['resp']=4;
    $ajaxResponse['dberror']=0;
    $ajaxResponse['msg']='missing bounding box or type of data';
    exit(prepareData($ajaxResponse));
}

$list = array();
if($type==OSM){
    if($noBicycles) {
        $query_string = "SELECT relation_members.relation_id as route
from relation_members
inner join ways on relation_members.member_id = ways.id
inner join relations on relation_members.relation_id = relations.id
where ways.bbox && ST_MakeEnvelope($left, $bottom, $right, $top)
and relations.tags->'route'!='bicycle'";
    } else {
        $query_string = "SELECT relation_members.relation_id from relation_members
inner join ways on relation_members.member_id = ways.id
 where bbox && ST_MakeEnvelope($left, $bottom, $right, $top)";
    }
} else if($type==USER) {
    $query_string = "SELECT id FROM hicheck.notes where geom && ST_MakeEnvelope($left, $bottom, $right, $top)";
}

$data = pg_query($db, $query_string);

while($row=pg_fetch_row($data)){
    $list[] = $row[0];
}
$list = array_keys(array_flip($list));

exit(prepareData($list));

