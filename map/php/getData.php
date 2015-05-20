<?php
/** skript stahujee geograficka data ke konkretni relaci, prohihaji zde take kontroly a optimalizace */
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
require 'db.php';
require 'func.php';

const BASE = 0;
const ERRORS = 2;
const WARNINGS = 1;
const GUIDEPOSTS = 3;
const ALL_WAYS_ = 5;

if((isset($_GET['rid'])) && (isset($_GET['control'])) && (isset($_GET['zoom']))){
    $id = $_GET['rid'];
    $controlLevel = $_GET['control'];
    $zoom = getFilterFromZoom($_GET['zoom']);
    $zoomLevel = $_GET['zoom'];
} else {
    $ajxres=array();
    $ajxres['resp']=4;
    $ajxres['dberror']=0;
    $ajxres['msg']='missing bounding box, type of control or type of data';
    send($ajxres);
}


 //zakladni query string pro cesty
if($controlLevel<=ERRORS){
    $query_string = "SELECT relations.id as rid,
    hstore_to_json(relations.tags) as tags, ways.id as mid, ST_AsGeoJSON(linestring)
    from relations
    inner join relation_members on relations.id = relation_members.relation_id
    inner join ways on relation_members.member_id = ways.id
    where relations.id = '$id'";

    if($controlLevel==WARNINGS){ //warnings
        $query_string .= " and (not exist(relations.tags,'network')";
        $query_string .= " or not exist(relations.tags,'complete')";
        $query_string .= " or not exist(relations.tags,'osmc:symbol')";
        $query_string .= " or not exist(relations.tags,'destinations'))";
        //$query_string .= " or not exist(relations.tags,'abandoned'))";
    }
    if($controlLevel==ERRORS){ //error
        $query_string .= " and exist(relations.tags,'osmc:symbol')";
    }
} else if($controlLevel==GUIDEPOSTS){ //rozcestniky
    $query_string = "SELECT relation_members.relation_id as rid, nodes.id as mid, relation_members.member_type as type, ST_AsGeoJSON(nodes.geom) from relation_members inner join nodes on relation_members.member_id = nodes.id where relation_members.relation_id = '$id'";
} else if($controlLevel==ALL_WAYS_){ //vsechny cesty
    $query_string = "SELECT id, ST_AsGeoJSON(linestring) FROM ways WHERE id='$id'";
}
$data = pg_query($db, $query_string);
$info = array();
if($controlLevel==ALL_WAYS_){
    $row = pg_fetch_assoc($data);
    $geom = json_decode($row['st_asgeojson']);
    $aux = array();
    $prop = array();
    $prop['id']=$row['id'];
    $aux['type']='Feature';
    $aux['properties']=$prop;
    $aux['geometry']=$geom;
    $info[] = $aux;
    $ways = array();

    $ways['type'] = 'FeatureCollection';
    $ways['features']=$info;
    exit(prepareData($ways));
} else {
    $firstIteration = true;
    while($row = pg_fetch_assoc($data)) {
        if($firstIteration){
            if($controlLevel!=GUIDEPOSTS){
                $tags = json_decode($row['tags'], true);
                $kct = getKctTag($tags);
                $kctKey = count($kct) > 0 ? $kct[0] : null;
            } else {
                $kctKey = null;
            }

            $errorValue = 0;
            $incorrectValues = 0;
            if ($controlLevel <= ERRORS) { //ziskani kct barvy
                $color = getKctTrackColor($kctKey, $tags['route']);
            }

            if($controlLevel==ERRORS && $kctKey!=null){



                //kontrola povolenych hodnot - complete, abandoned, network, kct_, osmc:symbol, route, type
                $incorrectValues = checkTagsValidValues($tags, $kctKey);



                //kontrola spravnych vzlathu mezi network a kct_
                if(array_key_exists('network', $tags)){
                    $errorValue += checkTagNetworkKct($tags['network'], $tags[$kctKey]);
                }
                //kontrola spravnych vztahu mezi osmc:symbol, route a kct_ - typ cesty
                if(array_key_exists('osmc:symbol', $tags) && array_key_exists('route', $tags)){
                    $errorValue += checkTagOsmcKctRoute($tags['osmc:symbol'], $tags[$kctKey], $tags['route'])*2;
                }
                //kontrola spravnych vztahu mezi osmc:symbol a kct_ - barva cesty
                if(array_key_exists('osmc:symbol', $tags)){
                    $errorValue += checkTagOsmcKctColor($tags['osmc:symbol'], $color)*4;
                }
                //nepokracuje se dal, nenalezena chyba
                if($errorValue==0){
                    exit();
                }
            } else if($controlLevel==ERRORS && $kctKey==null){
                $errorValue = 128;
            }
        }
        $firstIteration = false;
        $aux = array();
        $prop = array();
        $prop['id']=$row['rid'];
        $prop['errValue']=$errorValue;
        $prop['incValue']=$incorrectValues;
        $prop['kctkey']=$kctKey;
        if($controlLevel<=ERRORS){
            $prop['color'] = $color;
            $aux['int_type']=1;
        } 	else {
            $aux['int_type']=2;
        }
        $geom = json_decode($row['st_asgeojson']);
        if($controlLevel!=GUIDEPOSTS && $zoomLevel>=NODES_WAYS_EDGE){
            $geom->coordinates = efficientFilter($geom->coordinates, $zoom);
        } else if($zoomLevel<NODES_WAYS_EDGE && $controlLevel!=GUIDEPOSTS){ //redukce relace na jeden bod
            $geom->coordinates = $geom->coordinates[0];
            $geom->type = "Point";
        }
        $aux['type']='Feature';
        $aux['relation_id'] = $row['rid'];
        $aux['member_id']  = $row['mid'];
        $aux['properties']=$prop;
        $aux['geometry']=$geom;
        $info[] = $aux;
        if($zoomLevel<NODES_WAYS_EDGE && $controlLevel!=GUIDEPOSTS){ //redukce relace na jeden bod, v iteraci se nepokracuje
            break;
        }
    }

    $ways = array();

    $ways['type'] = 'FeatureCollection';
    $ways['features']=$info;

    exit(prepareData($ways));
}



