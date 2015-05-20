<?php
/* skript pro vykresleni grafu pomoci knihony phplot*/

include 'db.php';
include 'funcTables.php';
require_once 'phplot/phplot.php';

$plot = new PHPlot(1200, 400);


$data = getStatsForGraphs();
$data = prepareGraphs($data);
$plot->SetDataValues($data);

$plot->SetLegend(array("%: relace s chybejicimi tagy", "%: relace s chybami"));
$plot->SetLegendPosition(0.0,0.0, 'image', 0.2,0.05);

$plot->DrawGraph();