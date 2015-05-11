<?php
require 'db.php';
require 'func.php';
// Same as error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);// Same as error_reporting(E_ALL);
if ((isset($_GET['bbox']) || (isset($_GET['wid']))) && isset($_GET['type'])) {

    $type=$_GET['type'];
} else {
		$ajxres=array();
		$ajxres['resp']=4;
		$ajxres['dberror']=0;
		$ajxres['msg']='missing bounding box or type of data';
		sendajax($ajxres);
}
if((isset($_GET['bbox']))){
    list($left,$bottom,$right,$top)=explode(",",$_GET['bbox']);
} else {
    $way_id = $_GET['wid'];
}

/*$data = pg_query($db, "SELECT id, ST_AsGeoJSON(linestring) from ways 
	where bbox && ST_MakeEnvelope($left, $bottom, $right, $top)");*/
$ways = array();
$info = array();
$relation_list = array();
$ways['type'] = 'FeatureCollection';
if($type<=2){ //zakladni query string pro cesty
	$query_string = "SELECT relations.id as rid, relations.tags->'kct_red' as red,
		relations.tags->'kct_blue' as blue, relations.tags->'kct_green' as green, 
		relations.tags->'kct_yellow' as yellow, relations.tags->'osmc:symbol' as osmc,
		relations.tags->'route' as route, ways.id as mid, ST_AsGeoJSON(linestring)
		from relations
		inner join relation_members on relations.id = relation_members.relation_id 
		inner join ways on relation_members.member_id = ways.id
		where bbox && ST_MakeEnvelope($left, $bottom, $right, $top)";
    /*$query_string = "SELECT relations.id as rid, ways.id as mid, ST_AsGeoJSON(linestring)
		from relations
		inner join relation_members on relations.id = relation_members.relation_id
		inner join ways on relation_members.member_id = ways.id
		where ways.bbox && ST_MakeEnvelope($left, $bottom, $right, $top)";*/

	if($type==1){ //warnings
		$query_string .= " and (not exist(relations.tags,'network')";
		$query_string .= " or not exist(relations.tags,'complete')";
		$query_string .= " or not exist(relations.tags,'osmc:symbol')";
		$query_string .= " or not exist(relations.tags,'destinations'))";
		//$query_string .= " or not exist(relations.tags,'abandoned'))";
	}

	if($type==2){ //error
		$query_string .= " and exist(relations.tags,'osmc:symbol')";
	}
} else if($type==3){//zakladni query string pro uzly
	$query_string = "SELECT relations.id as rid, nodes.id as mid, 
		relation_members.member_type as type, ST_AsGeoJSON(nodes.geom) from relations 
		inner join relation_members on relations.id = relation_members.relation_id
		inner join nodes on relation_members.member_id = nodes.id
		where nodes.geom && ST_MakeEnvelope($left, $bottom, $right, $top)";
} else if($type==4){
    $query_string = "SELECT id from ways where bbox && ST_MakeEnvelope($left, $bottom, $right, $top)";
}

$data = pg_query($db, $query_string);


if($type<=3){
    while($row = pg_fetch_assoc($data)){
        if($type<=2){
            $color = getKctTrackColor($row);
        }


        if($type==2 &&
            ($row['route']=='bicycle' || kctColorVsOsmcColor(getOsmcTrackColor($row['osmc']),$color))) {
            continue;
        }

        $geom = json_decode($row['st_asgeojson']);
        $aux = array();
        $prop = array();
        $prop['id']=$row['rid'];
        if($type<=2){
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
    $ways['features']=$info;
    send($ways);
} else if($type==4){
    while($row=pg_fetch_row($data)){
        $relation_list[] = $row[0];
    }
    send($relation_list);
}


function send($data){
	$aux = json_encode($data);
	$aux = "".$aux."";
	exit($aux);
}
?>