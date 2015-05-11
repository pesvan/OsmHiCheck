<?php

const ALL = 0;
const WARNING = 1;

function getCount(){
    $result = array();
    $query = "SELECT ";
    $query .="(SELECT COUNT(*) FROM relations) AS total,";
	$query .="(SELECT COUNT(*) FROM relations where not exist(relations.tags,'network')
				or not exist(relations.tags,'complete') or not exist(relations.tags,'osmc:symbol')
				or not exist(relations.tags,'destinations')) AS not_any_tags,";
    $query .="(SELECT COUNT(*) FROM relations where not exist(relations.tags,'network')) AS not_network,";
    $query .="(SELECT COUNT(*) FROM relations where not exist(relations.tags,'complete')) AS not_complete,";
    $query .="(SELECT COUNT(*) FROM relations where not exist(relations.tags,'osmc:symbol')) AS not_osmc,";
    $query .="(SELECT COUNT(*) FROM relations where not exist(relations.tags,'destinations')) AS not_dest";

    $res = pg_query($query);
    $res = pg_fetch_assoc($res);
    return $res;
}

function getPercentageFromArray($arr, $indexTotal){
    $total = $arr[$indexTotal];
    $res = array();
    foreach ($arr as $i => $value) {
        if($i==$indexTotal){
            $res[$i] = 100;
        } else {
            $res[$i] = getPercent($total , $value);
        }
        $res[$i] = $res[$i]."%";
    }
    return $res;
}

function getPercent($total, $part){
    return round((100/$total)*$part, 2);
}