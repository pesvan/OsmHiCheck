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
$cnt = 0;
$coordinates = array();
$firstAndLast = array();
$row_still = array();
while($row = pg_fetch_assoc($data)){
    if($controlLevel==3 || $row['route']=='bicycle' || $zoom<=0.005 ){
        if($controlLevel<=2){
            $color = getKctTrackColor($row);
        }

        if($controlLevel==2 &&
            ($row['route']=='bicycle' || kctColorVsOsmcColor(getOsmcTrackColor($row['osmc']),$color))) {
            continue;
        }
        $geom = json_decode($row['st_asgeojson']);
        // print_r($geom);
        //$geom->coordinates = filterNodes($geom->coordinates);
        if($controlLevel!=3){
            $geom->coordinates = efficientFilter($geom->coordinates, $row['mid'], $zoom);
        }

        // print_r($geom);
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
    } else {
        if($cnt==0){
            $row_still = $row;
        }
        $tmp = json_decode($row['st_asgeojson']);
        //$coords = array_merge($coords, $tmp->coordinates);
        $coordinates[$cnt] = $tmp->coordinates;
        $firstAndLast[$cnt][] = $tmp->coordinates[0];
        $firstAndLast[$cnt][] = $tmp->coordinates[count($tmp->coordinates)-1];
        $cnt++;
    }
}
if(isset($row_still)){
    $sortedAndOrderedWays = sortWays($firstAndLast, $coordinates);
    foreach($sortedAndOrderedWays as $part){
        if($controlLevel<=2){
            $color = getKctTrackColor($row_still);
        }
        $aux = array();
        $prop = array();
        $prop['id']=$row_still['rid'];
        if($controlLevel<=2){
            $prop['color'] = $color;
            $aux['int_type']=1;
        } 	else {
            $aux['int_type']=2;
        }
        $part = efficientFilter($part, 0, $zoom);
        $geom['type'] = "LineString";
        $geom['coordinates'] = $part;
        $aux['type']='Feature';
        $aux['relation_id'] = $row_still['rid'];
        $aux['member_id']  = $row_still['mid'];
        $aux['properties']=$prop;
        $aux['geometry']=$geom;
        $info[] = $aux;

    }
}


$ways = array();

$ways['type'] = 'FeatureCollection';
$ways['features']=$info;

exit(prepareData($ways));
