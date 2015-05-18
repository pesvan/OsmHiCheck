<?php
//Include the code
include 'db.php';
include 'funcTables.php';
require_once 'phplot/phplot.php';

//Define the object
$plot = new PHPlot(1200, 400);

//Define some data
$data = getStatsForGraphs();
$data = prepareGraphs($data);
$plot->SetDataValues($data);

//Turn off X axis ticks and labels because they get in the way:
$plot->SetXTickLabelPos('none');
$plot->SetXTickPos('none');

$plot->SetLegend(array("%: relace s chybejicimi tagy", "%: relace s chybami"));
$plot->SetLegendPosition(0.0,0.0, 'image', 0.2,0.05);
//Draw it
$plot->DrawGraph();