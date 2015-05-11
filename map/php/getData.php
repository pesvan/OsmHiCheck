<?php
require 'db.php';
require 'func.php';
// Same as error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);// Same as error_reporting(E_ALL);

if((isset($_GET['rid'])) && (isset($_GET['control'])) && (isset($_GET['zoom']))){
    $relation_id = $_GET['rid'];
    $controlLevel = $_GET['control'];
    $zoom = getFilterFromZoom($_GET['zoom']);
} else {
    $ajxres=array();
    $ajxres['resp']=4;
    $ajxres['dberror']=0;
    $ajxres['msg']='missing bounding box or type of data';
    send($ajxres);
}


 //zakladni query string pro cesty
if($controlLevel<=2){
    $query_string = "SELECT relations.id as rid, relations.tags->'kct_red' as red,
    relations.tags->'kct_blue' as blue, relations.tags->'kct_green' as green,
    relations.tags->'kct_yellow' as yellow, relations.tags->'osmc:symbol' as osmc,
    relations.tags->'route' as route, ways.id as mid, ST_AsGeoJSON(linestring)
    from relations
    inner join relation_members on relations.id = relation_members.relation_id
    inner join ways on relation_members.member_id = ways.id
    where relations.id = '$relation_id'";

    if($controlLevel==1){ //warnings
        $query_string .= " and (not exist(relations.tags,'network')";
        $query_string .= " or not exist(relations.tags,'complete')";
        $query_string .= " or not exist(relations.tags,'osmc:symbol')";
        $query_string .= " or not exist(relations.tags,'destinations'))";
        //$query_string .= " or not exist(relations.tags,'abandoned'))";
    }

    if($controlLevel==2){ //error
        $query_string .= " and exist(relations.tags,'osmc:symbol')";
    }
} else if($controlLevel==3){
    $query_string = "SELECT relation_members.relation_id as rid, nodes.id as mid, relation_members.member_type as type, ST_AsGeoJSON(nodes.geom) from relation_members inner join nodes on relation_members.member_id = nodes.id where relation_members.relation_id = '$relation_id'";
}
$data = pg_query($db, $query_string);
$info = array();
while($row = pg_fetch_assoc($data)){
    if($controlLevel<=2){
        $color = getKctTrackColor($row);
    }

    if($controlLevel==2 &&
        ($row['route']=='bicycle' || kctColorVsOsmcColor(getOsmcTrackColor($row['osmc']),$color))) {
        continue;
    }
    $geom = json_decode($row['st_asgeojson']);
    if($controlLevel!=3){
        $geom->coordinates = efficientFilter($geom->coordinates, $row['mid'], $zoom);
    }

    $aux = array();
    $prop = array();
    $prop['id']=$row['rid'];
    if($controlLevel<=2){
        $prop['color'] = $color;
        $aux['int_type']=1;
    } 	else {
        $aux['int_type']=2;
    }
    $aux['type']='Feature';
    $aux['relation_id'] = $row['rid'];
    $aux['member_id']  = $row['mid'];
    $aux['properties']=$prop;
    $aux['geometry']=$geom;
    $info[] = $aux;
} 


$ways = array();

$ways['type'] = 'FeatureCollection';
$ways['features']=$info;

exit(prepareData($ways));
