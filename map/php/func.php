<?php 
/** to getList.php */
const OSM = 0;
const USER_NOTES = 1;
const ALL_WAYS = 2;
const USER_PARTS=3;

/** barvy turisticke relace */
const UNKNOWN = 0;
const RED = 1;
const BLUE = 2;
const GREEN = 3;
const YELLOW = 4;
const BICYCLE = 5;

const NODES_WAYS_EDGE = 12;

/**
 * platne hodnoty tagu network
 * @return array
 */
function getNetworkValueList(){
    return array(array("lwn", "rwn", "nwn"), array("lcn", "rcn", "ncn"));
}

/**
 * @return array
 */
function getKctKeyList(){
    return array("kct_blue", "kct_green", "kct_red", "kct_white", "kct_yellow", "kct_none", "kct_black", "kct_purple", "kct_orange");
}

/**
 * platne hodnoty tagu kct_barva
 * @return array
 */
function getKctValueList(){
    return array("major","local", "learning","ski","wheelchair","horse","peak","ruin","spring","interesting_object", "bicycle");
}

/**
 * platne hodnoty tagu route
 * @return array
 */
function getRouteValueList(){
    return array("foot", "hiking", "wheelchair", "ski", "horse", "bicycle");
}

/**
 * platne hodnoty tagu osmc:symbol
 * @return array
 */
function getOsmcValueList(){
    return array(
        "f_color" => array("yellow", "red", "green", "blue", "white", "black", "purple", "orange"),
        "b_color" => array("white", "yellow", "orange"),
        "symbol"  => array("bar", "corner", "backslash", "wheelchair", "dot", "triangle", "L", "bowl", "turned_T")
    );
}

/**
 * tabulka kompatibility mezi hodnotami tagu netvork a kct_barva
 * @return array
 */
function getNetworkKctTable(){
    return array(
        "major" =>              array("lwn" => 1, "rwn" => 1, "nwn" => 1),
        "local" =>              array("lwn" => 1, "rwn" => 0, "nwn" => 0),
        "learning" =>           array("lwn" => 1, "rwn" => 0, "nwn" => 0),
        "ski" =>                array("lwn" => 1, "rwn" => 1, "nwn" => 0),
        "wheelchair" =>         array("lwn" => 1, "rwn" => 0, "nwn" => 0),
        "horse" =>              array("lwn" => 1, "rwn" => 1, "nwn" => 0),
        "peak" =>               array("lwn" => 1, "rwn" => 0, "nwn" => 0),
        "ruin" =>               array("lwn" => 1, "rwn" => 0, "nwn" => 0),
        "spring" =>             array("lwn" => 1, "rwn" => 0, "nwn" => 0),
        "interesting_object" => array("lwn" => 1, "rwn" => 0, "nwn" => 0),
    );
}

/**
 * tabulka kompatibility mezi hodnotami tagu route a kct_barva
 * @return array
 */
function getRouteKctTable(){
    return array(
        "foot" =>       array("major", "local", "learning", "peak", "ruin", "spring", "interesting_object"),
        "hiking" =>     array("major", "local", "learning", "peak", "ruin", "spring", "interesting_object"),
        "ski" =>        array("ski"),
        "wheelchair" => array("wheelchair"),
        "bicycle"=>     array("bicycle"),
        "horse"=>       array("horse")
    );
}

/**
 * tabulka kompatibility mezi hodnotami tagu route a osmc:symbol
 * @return array
 */
function getRouteOsmcTable(){
    return array(
        "foot" =>       array("white", "bar", "corner", "backslash", "triangle", "L", "bowl", "turned_T"),
        "hiking" =>     array("white", "bar", "corner", "backslash", "triangle", "L", "bowl", "turned_T"),
        "ski" =>        array("orange", "bar"),
        "wheelchair" => array("white", "wheelchair"),
        "bicycle"=>     array("yellow", "bar"),
        "horse"=>       array("white", "dot")
    );
}

/**
 * tabulka kompatibility mezi hodnotami tagu osmc:symbol a kct_barva
 * @return array
 */
function getKctOsmcTable(){
    return array(
        "major" =>              array("bar","white"),
        "local" =>              array("corner","white"),
        "learning" =>           array("backslash","white"),
        "ski" =>                array("bar", "orange"),
        "wheelchair" =>         array("wheelchair","white"),
        "horse" =>              array("dot","white"),
        "peak" =>               array("triangle","white"),
        "ruin" =>               array("L","white"),
        "spring" =>             array("bowl","white"),
        "interesting_object" => array("turned_T","white"),
        "bicycle" =>            array("bar", "yellow")
    );
}

/** funkce pro zajisteni automaticke kontroly - kontrola validity hodnot */

/**
 * souhrnna kontrola validity hodnot
 * @param $tags
 * @param $kctKey
 * @return int - chybova hodnota
 */
function checkTagsValidValues($tags, $kctKey){
    $errNum = 0;
    if(array_key_exists($kctKey, $tags)){
        $errNum += checkTagKct($tags[$kctKey]);
    }
    if(array_key_exists('osmc:symbol', $tags)){
        $errNum += checkTagOsmc($tags['osmc:symbol'])*2;
    }
    if(array_key_exists('route', $tags)){
        $errNum += checkTagRoute($tags['route'])*4;
    }
    if(array_key_exists('type', $tags)){
        $errNum += checkTagType($tags['type'])*8;
    }
    if(array_key_exists('complete', $tags)){
        $errNum += checkTagCompleteAbandoned($tags['complete'])*16;
    }
    if(array_key_exists('abandoned', $tags)){
        $errNum += checkTagCompleteAbandoned($tags['abandoned'])*32;
    }
    $errNum += checkKeyKct($kctKey)*64;
    if(array_key_exists('network', $tags)){
        $errNum += checkTagNetwork($tags['network'])*128;
    }
    return $errNum;
}

/**
 * @param $network
 * @return int
 */
function checkTagNetwork($network){
    $netList = getNetworkValueList();
    return in_array($network, $netList[0]) || in_array($network, $netList[1]) ? 0 : 1;
}

/**
 * @param $kct
 * @return int
 */
function checkKeyKct($kct){
    return in_array($kct, getKctKeyList()) ? 0 : 1;
}

/**
 * @param $value
 * @return int
 */
function checkTagCompleteAbandoned($value){
    return $value == 'yes' || $value == 'no' ? 0 : 1;
}

/**
 * @param $value
 * @return int
 */
function checkTagType($value){
    return $value == 'route' ? 0 : 1;
}

/**
 * @param $kct
 * @return int
 */
function checkTagKct($kct){
    return in_array($kct, getKctValueList()) ? 0 : 1;
}

/**
 * @param $osmc
 * @return int
 */
function checkTagOsmc($osmc){
    $parts = parseOsmc($osmc);
    if($parts==null){
        return 1;
    }
    $osmcValues = getOsmcValueList();
    if(!in_array($parts[0], $osmcValues['f_color']) ||
        !in_array($parts[1], $osmcValues['b_color'])){
        return 1;
    }
    if(!in_array($parts[2], $osmcValues['symbol'])){
        return 1;
    }
    return 0;
}

/**
 * @param $route
 * @return int
 */
function checkTagRoute($route){
    return !in_array($route, getRouteValueList()) ? 1 : 0;
}

/**
 * @param $network
 * @param $kct
 * @return int
 */
function checkTagNetworkKct($network, $kct){
    $networkList = getNetworkValueList();
    if(!in_array($kct,getKctValueList())){
        return 1;
    }
    if($kct!='bicycle'){
        if(!in_array($network, $networkList[0])){
            return 1;
        }
        $table = getNetworkKctTable();
        return $table[$kct][$network] == 1 ? 0 : 1;
    } else {
        return in_array($network, $networkList[1]) ? 0 : 1;
    }
}

/**
 * @param $osmc
 * @param $kct
 * @param $route
 * @return int
 */
function checkTagOsmcKctRoute($osmc, $kct, $route){
    $osmc = parseOsmc($osmc);
    $route_kct = getRouteKctTable();
    $route_osmc = getRouteOsmcTable();
    $kct_osmc = getKctOsmcTable();
    if (!in_array($kct, $route_kct[$route])) { // kct vs route
        return 1;
    }
    if (!in_array($osmc[2], $route_osmc[$route]) || $route_osmc[$route][0] != $osmc[1]) { //kontrola symbolu || kontrola barvy pozadi
        return 1;
    }
    if ($kct_osmc[$kct][0] != $osmc[2] || $kct_osmc[$kct][1] != $osmc[1]) {  //kontrola symbolu || kontrola barvy pozadi
        return 1;
    }
    return 0;
}

/**
 * @param $osmc
 * @param $kctColor
 * @return int
 */
function checkTagOsmcKctColor($osmc, $kctColor){
    $osmcColor = getOsmcTrackColor($osmc);
    return $osmcColor==$kctColor ? 0 : 1;
}


/**
 * @param $osmc
 * @return array|null
 */
function parseOsmc($osmc){
    $parts = explode(":",$osmc);
    if(count($parts)!=3){
        return null;
    }
    $pos = strpos($parts[2], "_");
    $parts[2] = substr($parts[2], $pos+1);
    return $parts;
}

/**
 * @param $tags
 * @return array
 */
function getKctTag($tags){
    $kct_ = array();
    foreach (array_keys($tags) as $key){
        if(preg_match('/^kct_.+/', $key)){
            array_push($kct_, $key);
        }
    }
    return $kct_;
}

/**
 * @param $osmc_color
 * @param $kct_color
 * @return bool
 */
function kctColorVsOsmcColor($osmc_color, $kct_color){
	return $osmc_color == $kct_color ? true : false;
}

/**
 * @param $kctKey
 * @param $route
 * @return int
 */
function getKctTrackColor($kctKey, $route){
	$color = UNKNOWN;	
	if($kctKey=='kct_red'){
		$color = RED;
	} else if($kctKey=='kct_blue'){
		$color = BLUE;
	} else if($kctKey=='kct_green'){
		$color = GREEN;
	} else if($kctKey=='kct_yellow'){
		$color = YELLOW;
	}
	if($color == UNKNOWN && $route=='bicycle'){
		$color = BICYCLE;
	}
	return $color;
}
/**
 * funkce pro zjisteni barvy z tagu osmc:symbol
 * @param $osmc
 * @return int
 */
function getOsmcTrackColor($osmc){
	$pieces = explode(":", $osmc);
	$pieces[0] = strtolower($pieces[0]);
	switch ($pieces[0]) {
		case 'red':
			$ret = RED;
			break;
		case 'blue':
			$ret = BLUE;
			break;
		case 'green':
			$ret = GREEN;
			break;
		case 'yellow':
			$ret = YELLOW;
			break;
		default:
			$ret = UNKNOWN;
			break;
	}
	return $ret;
	
}

/**funkce pro pripravu dat na odeslani*/
function prepareData($data){
    $aux = json_encode($data);
    $aux = "".$aux."";
    return $aux;
}
/** funkce pro filtrovani uzlu na zaklade zoomu */
/**
 * slozitejsi filtr na zaklade vypoctu smeru vektoru
 * @param $nodes
 * @param $filter
 * @return array
 */
function efficientFilter($nodes, $filter){
    $cnt = count($nodes);
    $filtered = array();
    $filtered[] = $nodes[0];
    $vector_prev = array();
    $vector_next = array();
    $vector_prev[0] = $nodes[1][0] - $nodes[0][0];
    $vector_prev[1] = $nodes[1][1] - $nodes[0][1];
    for ($i = 1; $i < $cnt-2; $i++){
        $vector_next[0] = $nodes[$i+1][0] - $nodes[$i][0];
        $vector_next[1] = $nodes[$i+1][1] - $nodes[$i][1];
        $x = abs($vector_next[0] - $vector_prev[0]);
        $y = abs($vector_next[1] - $vector_prev[1]);
        $vector_prev = $vector_next;
        if($x > $filter && $y > $filter){
            $filtered[] = $nodes[$i];
        }
    }
    $filtered[] = $nodes[$cnt-1];
    return $filtered;
}

/**
 * funkce pro prispusobeni filtru na zaklade zoomu
 * @param $zoom
 * @return float|int
 */
function getFilterFromZoom($zoom){
    if($zoom >= 16){
        return 0;
    } else if($zoom <16 && $zoom >= 15) {
        return 0.00005;
    } else if($zoom <15 && $zoom >= 13) {
        return 0.0001;
    } else if($zoom == 12) {
        return 0.008;
    } else return 1;
}

//vypocitani vektoru dvou bodu
/**
 * @param $start
 * @param $end
 * @return array
 */
function getVector($start, $end){
    $vector = array();
    $vector[0] = ($end[0] - $start[0]);
    $vector[1] = ($end[1] - $start[1]);
    return $vector;
}

//serazeni cest do jedne relace - mozne vylepseni efektivity zobrazeni do budoucna
/**
 * @param $firstAndLast
 * @param $coordinates
 * @return array
 */
function sortWays($firstAndLast, $coordinates){
    $ordered = getOrderAndReversedList($firstAndLast);
    $reversed = $ordered[1];
    $sorted = $ordered[0];
    //prevraceni urcenych cest
    foreach($reversed as $value){
        $coordinates[$value] = array_reverse($coordinates[$value]);
    }
    $joinedWays = array();
    $count = count($coordinates);
    $innerCount = 0;
    foreach($sorted as $key=>$value){
    }

    for($i = 0; $i < $count-1; $i++){

        if(isset($sorted[$i]) || array_key_exists($i, $sorted)){//nova cast relace
            if($sorted[$i]-$i > 20){
                continue;
            }
            if(!(isset($joinedWays[$innerCount])) || !(array_key_exists($innerCount, $joinedWays))){
                $joinedWays[$innerCount] = $coordinates[$i];
            } //pridavani souradnic
            $joinedWays[$innerCount] = array_merge($joinedWays[$innerCount], $coordinates[$sorted[$i]]);
        } else { //ukonceni casti, navazuje dalsi cast jako dalsi prvek
            $innerCount++;
        }
    }
    return $joinedWays;
}

//pomocna funkce pro spravne razeni cest
/**
 * @param $firstAndLast
 * @return array
 */
function getOrderAndReversedList($firstAndLast){
    $cnt = count($firstAndLast);
    $return = array();
    $sorted = array();
    $reversed = array();
    for($i = 0; $i < $cnt; $i++){
        for($j = 0; $j < $cnt; $j++){
            if($i==$j){
                continue;
            }
            if(pointsAreEqual($firstAndLast[$i][1], $firstAndLast[$j][0])  && $j > $i){
                $sorted[$i] = $j;
                break;
            }
        }
    }
    $return[] = $sorted;
    $return[] = $reversed;
    return $return;
}

/**
 * @param $a
 * @param $b
 * @return bool
 */
function pointsAreEqual($a, $b){
    return $a[0] == $b[0] && $a[1] == $b[1] ? true : false;
}