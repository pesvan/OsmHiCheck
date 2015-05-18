<?php

const ALL = 0;
const WARNING = 1;

const NOT_CYCLO = "relations.tags->'route'!='bicycle' and (
    not exist(relations.tags,'kct_red') or 
    not exist(relations.tags,'kct_blue') or 
    not exist(relations.tags,'kct_yellow') or 
    not exist(relations.tags,'kct_greeen') or 
    not exist(relations.tags,'kct_none'))";

function getCount(){
    $result = array();

    
    $query = "SELECT ";
    $query .="(SELECT COUNT(*) FROM relations where ".NOT_CYCLO.") AS total,";
	$query .="(SELECT COUNT(*) FROM relations where ".NOT_CYCLO." and (not exist(relations.tags,'network')
				or not exist(relations.tags,'complete') or not exist(relations.tags,'osmc:symbol')
				or not exist(relations.tags,'destinations'))) AS not_any_tags,";
    $query .="(SELECT COUNT(*) FROM relations where ".NOT_CYCLO." and not exist(relations.tags,'network')) AS not_network,";
    $query .="(SELECT COUNT(*) FROM relations where ".NOT_CYCLO." and not exist(relations.tags,'complete')) AS not_complete,";
    $query .="(SELECT COUNT(*) FROM relations where ".NOT_CYCLO." and not exist(relations.tags,'osmc:symbol')) AS not_osmc,";
    $query .="(SELECT COUNT(*) FROM relations where ".NOT_CYCLO." and not exist(relations.tags,'destinations')) AS not_dest";

    $res = pg_query($query);
    $res = pg_fetch_assoc($res);
    return $res;
}

function getCountErrors(){
    $ret = array();
    $ret['count'] = 0;
    $ret['network'] = 0;
    $ret['osmc:symbol'] = 0;
    $ret['route'] = 0;
    $ret['complete'] = 0;
    $ret['kct'] = 0;
    $ret['err_color'] = 0;
    $ret['err_type'] = 0;
    $ret['err_network'] = 0;
    $query = "SELECT hstore_to_json(tags) as tags FROM relations where ".NOT_CYCLO;
    $result = pg_query($query);
    $isWrong = false;
    while ($row = pg_fetch_assoc($result)){
        $isWrong = false;
        $tags = json_decode($row['tags'], true);
        $kct = getKctTag($tags);
        $kctKey = count($kct) > 0 ? $kct[0] : "";
        $checked = getCheckedValues($kctKey);
        $incorrect = checkTagsValidValues($tags, $kctKey);
        $incorrect = getWrong($incorrect, $kctKey);
        foreach ($incorrect as $key => $value) {
            if(array_key_exists($value, $ret)){
                $ret[$value]++;
                if(!$isWrong){
                    $isWrong = true;
                }
            } else if($value==$kctKey){
                $ret['kct']++;
                if(!$isWrong){
                    $isWrong = true;
                }
            }
        }
        if(array_key_exists('network', $tags) && array_key_exists($kctKey, $tags)
            && !(in_array('network', $incorrect)) && !(in_array($kctKey, $incorrect))){
            if(checkTagNetworkKct($tags['network'], $tags[$kctKey])>0){
                if(!$isWrong){
                    $isWrong = true;
                }
                $ret['err_network']++;     
            }
        }
        if(array_key_exists('osmc:symbol', $tags) && array_key_exists('route', $tags) && array_key_exists($kctKey, $tags)
            && !(in_array('osmc:symbol', $incorrect)) && !(in_array($kctKey, $incorrect)) && !(in_array('route', $incorrect))){
            if(checkTagOsmcKctRoute($tags['osmc:symbol'], $tags[$kctKey], $tags['route'])>0){
                if(!$isWrong){
                    $isWrong = true;
                }
                $ret['err_type']++;     
            }
        }
        if(array_key_exists('route', $tags) && array_key_exists('osmc:symbol', $tags) && array_key_exists($kctKey, $tags)
            && !(in_array('osmc:symbol', $incorrect)) && !(in_array($kctKey, $incorrect))){
            $color = getKctTrackColor($kctKey, $tags['route']);
            if(checkTagOsmcKctColor($tags['osmc:symbol'], $color)>0){
                if(!$isWrong){
                    $isWrong = true;
                }              
                $ret['err_color']++;         
            }
        }
        if($isWrong){
            $ret['count']++;
        }
    }

    return $ret;
}

function getStatsForGraphs(){
    $result = pg_query("SELECT date, relations_missing, relations_wrong FROM hicheck.stats ORDER BY date DESC LIMIT 31");
    $ret = array();
    $cnt = 0;
    while($row=pg_fetch_assoc($result)){
        $ret[$cnt][0]=$row['date'];
        $ret[$cnt][1]=intval($row['relations_missing']);
        $ret[$cnt]['relations_wrong']=intval($row['relations_wrong']);
        $cnt++;
    }
    return $ret;
}

function prepareGraphs($data){
    $cnt = count($data);
    if($cnt<31){
        for($i = $cnt; $i<31; $i++){
            array_push($data, array("",0,0));
        }

    }    
    for ($i=0; $i < 31; $i++) { 
        $data[$i][0] = substr($data[$i][0], 6)."/".substr($data[$i][0], 4, 2);
    }
    return array_reverse($data);
}

function getPercentageFromArray($arr, $total, $percent){
    $res = array();
    foreach ($arr as $i => $value) {
        if($value==$total){
            $res[$i] = 100;
        } else {
            $res[$i] = getPercent($total , $value);
        }
        if($percent){
            $res[$i] = $res[$i]."%";
        }
        
    }
    return $res;
}

function getPercent($total, $part){
    return $total!=0 ? round((100/$total)*$part, 2) : 0.00;
}

function getCheckedValues($kctKey)
{

    return array('destinations', 'complete',  'osmc:symbol', $kctKey, 'network','route');
}


function getWrong($errNum, $kctKey)
{
    $red = array();
    if ($errNum >= 128) {
        $red[] = 'network';
        $errNum -= 128;
    }
    if ($errNum >= 64) {
        $errNum -= 64;
    }
    if ($errNum >= 32) {
        $errNum -= 32;
    }
    if ($errNum >= 16) {
        $red[] = 'complete';
        $errNum -= 16;
    }
    if ($errNum >= 8) {
        $errNum -= 8;
    }
    if ($errNum >= 4) {
        $red[] = 'route';
        $errNum -= 4;
    }
    if ($errNum >= 2) {
        $red[] = 'osmc:symbol';
        $errNum -= 2;
    }
    if ($errNum >= 1) {
        $red[] = $kctKey;
    }
    return $red;

}

function getMissing($tags, $kctKey)
{
    $orange = array();
    $keys = getCheckedValues($kctKey);
    foreach ($keys as $key) {
        if (!(array_key_exists($key, $tags))) {
            $orange[] = $key;
        }

    }
    return $orange;
}

function writeTag($tag, $tags, $kctKey)
{
    if (array_key_exists($tag, $tags)) {
        if (checkValidValue($tag, $tags[$tag], $kctKey)) {
            return "<td class='wrong'>$tags[$tag]</td>";
        }
        return "<td>$tags[$tag]</td>";
    } else {
        return "<td class='missing'></td>";
    }
}