<?php
/* skript pro vykresleni grafu pomoci knihony phplot*/

include dirname(__FILE__).'/../tables/php/db.php';
require_once dirname(__FILE__).'/../tables/php/phplot/phplot.php';

define('DAYS_MAX', 42); #6 weeks

function getStatsForGraphs(){ //{{{
    $result = pg_query("SELECT date, node_total, node_ok, node_bad, node_cor, img_total, img_used FROM hicheck.gp_stats ORDER BY date DESC LIMIT ".DAYS_MAX);
    $ret = array();
    $cnt = 0;
    while($row=pg_fetch_assoc($result)){
        $ret[$cnt][0]=$row['date'];
        $ret[$cnt][1]=intval($row['node_total']);
        $ret[$cnt][2]=intval($row['node_ok']);
        $ret[$cnt][3]=intval($row['node_bad']);
        $ret[$cnt][4]=intval($row['node_cor']);
        $ret[$cnt][5]=intval($row['img_total']);
        $ret[$cnt][6]=intval($row['img_used']);
        $cnt++;
    }
    return $ret;
} //}}}

function prepareGraphs($data){ //{{{
    $cnt = count($data);
    if($cnt<DAYS_MAX){
        for($i = $cnt; $i<DAYS_MAX; $i++){
            array_push($data, array("",0,0,0));
        }

    }
    for ($i=0; $i < DAYS_MAX; $i++) {
        $data[$i][0] = substr($data[$i][0], 6)."/".substr($data[$i][0], 4, 2);
    }
    return array_reverse($data);
} //}}}

$plot = new PHPlot(1200, 400);

$data = getStatsForGraphs();
$data = prepareGraphs($data);
$plot->SetDataValues($data);

$plot->SetLegend(array("nodes total", "node OK", "node miss", "node check", "photos total", "photos used"));
$plot->SetLegendPosition(0.0,0.0, 'image', 0.05,0.05);

$plot->DrawGraph();
