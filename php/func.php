<?php 
/** to getList.php */
const OSM = 0;
const USER = 1;

const UNKNOWN = 0;
const RED = 1;
const BLUE = 2;
const GREEN = 3;
const YELLOW = 4;
const BICYCLE = 5;

//jednoduchy filtr pro uzly
function filterNodes($nodes){
    $cnt = count($nodes);
    $filtered = array();
    $filtered[] = $nodes[0];
    //echo $nodes[0][0]." ".$nodes[0][1]." is first in way";
    for ($i = 1; $i < $cnt-1; $i++){
        if($i%2==0){
            //echo $nodes[$i][0]." ".$nodes[$i][1]." is going";
            $filtered[] = $nodes[$i];
        }         else {
           // echo $nodes[$i][0]." ".$nodes[$i][1]. " is not going";
        }
    }
   // echo $nodes[$i][0]." ".$nodes[$i][1]." is last in way";
    $filtered[] = $nodes[$cnt-1];
    //echo "///";
    return $filtered;
}

//slozitejsi filtr na zaklade vypoctu smeru vektoru
function efficientFilter($nodes, $id, $filter){
    //echo "$id + <br/>";
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
        //echo "$x, $y<br/>";
    }
    $filtered[] = $nodes[$cnt-1];
    return $filtered;
}

//funkce pro zjisteni shody tagu kct_barva a osmc:barva
function kctColorVsOsmcColor($osmc_color, $kct_color){
	return $osmc_color == $kct_color ? true : false;
}

//funkce pro zjisteni barvy z tagu kct_barva
function getKctTrackColor($row){
	$color = UNKNOWN;	
	if($row['red']!=null){
		$color = RED;
	} else if($row['blue']!=null){
		$color = BLUE;
	} else if($row['green']!=null){
		$color = GREEN;
	} else if($row['yellow']!=null){
		$color = YELLOW;
	}
	if($color == UNKNOWN && $row['route']=='bicycle'){
		$color = BICYCLE;
	}
	return $color;
}

//funkce pro zjisteni barvy z tagu osmc:symbol
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

//funkce pro prispusobeni filtru na zaklade zoomu
function getFilterFromZoom($zoom){
    if($zoom >= 16){
        return 0;
    } else if($zoom <16 && $zoom >= 14) {
        return 0.00005;
    } else if($zoom <14 && $zoom >= 12) {
        return 0.0001;
    } else if($zoom == 11) {
        return 0.008;
    } else if($zoom == 11) {
        return 0.001;
    } else if($zoom == 10) {
        return 0.005;
    } else if($zoom == 9){
        return 0.008;
    } else return 1;
}

/** momentalne nepouzivano */
function getOsmcBackgroundColor($osmc){
	$pieces = explode(":", $osmc);
	return $pieces[1];
}

//funkce pro pripravu dat na odeslani
function prepareData($data){
    $aux = json_encode($data);
    $aux = "".$aux."";
    return $aux;
}
/** momentalne nepouzivano */
//zjisteni prostredniho bodu cesty
function getCenterPoint($start, $end){
    $center = array();
    $center[0] = ($start[0] + $end[0])/2;
    $center[1] = ($start[1] + $end[1])/2;
    return $center;
}

//vypocitani vektoru dvou bodu
function getVector($start, $end){
    $vector = array();
    $vector[0] = ($end[0] - $start[0]);
    $vector[1] = ($end[1] - $start[1]);
    return $vector;
}

//vypocitani vzdalenosti pomoci z vektoru
function getDistanceFromVector($vec){
    return sqrt(($vec[0]*$vec[0])+($vec[1]*$vec[1]));
}

//vypocet vzdalenosti z bodu
function getDistanceFromPoints($start, $end){
    if($start[0]==$end[0] && $start[1]==$end[1]){
        return 0;
    } else {
        return getDistanceFromVector(getVector($start, $end));
    }
}

//serazeni cest do jedne relace
function sortWays($firstAndLast, $coordinates){
    $ordered = getOrderAndReversedList($firstAndLast);
    $reversed = $ordered[1];
    $sorted = $ordered[0];
    //prevraceni urcenych cest
    //var_dump($firstAndLast);
    foreach($reversed as $value){
        $coordinates[$value] = array_reverse($coordinates[$value]);
    }
    $joinedWays = array();
    $count = count($coordinates);
    $innerCount = 0;
    foreach($sorted as $key=>$value){
       // echo "<br/>$key=>$value";
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
            /*if(pointsAreEqual($firstAndLast[$i][1], $firstAndLast[$j][0]) && $i > $j){
                //echo "$i last a $j first and $i is greater<br/>";
                $firstAndLast[$i] = array_reverse($firstAndLast[$i]);
                $firstAndLast[$j] = array_reverse($firstAndLast[$j]);
                $reversed[] = $i;
                $reversed[] = $j;
                if($i>0){
                    $i--;
                }
                if($j>0){
                    $j--;
                }

            }
            else if(pointsAreEqual($firstAndLast[$i][0], $firstAndLast[$j][0]) && $i!=$j){
                //echo "$i first a $j first<br/>";
                if($i < $j) {
                    $firstAndLast[$i] = array_reverse($firstAndLast[$i]);
                    $reversed[] = $i;
                } else {
                    $firstAndLast[$j] = array_reverse($firstAndLast[$j]);
                    $reversed[] = $j;
                }
            }
            else if(pointsAreEqual($firstAndLast[$i][1], $firstAndLast[$j][1]) && $i!=$j) {
                //echo "$i last a $j last<br/>";
                if ($i < $j) {
                    $firstAndLast[$j] = array_reverse($firstAndLast[$j]);
                    $reversed[] = $j;
                }
            }*/
            if(pointsAreEqual($firstAndLast[$i][1], $firstAndLast[$j][0])  && $j > $i){
                //echo "$i last a $j first<br/>";
                $sorted[$i] = $j;
                break;
            }
        }
    }
    $return[] = $sorted;
    $return[] = $reversed;
    return $return;
}

//...
function pointsAreEqual($a, $b){
    return $a[0] == $b[0] && $a[1] == $b[1] ? true : false;
}