<?php
/* skript pro ulozeni pravidelnych statistik */
require dirname(__FILE__).'/../tables/php/db.php';

$node_total = 0;
$img_total = 0;
$img_used = 0;

$file_all = file_get_contents("/tmp/osm.gp2.html");

sscanf(strstr($file_all, 'Nodes with information=guidepost'), '%[^(](%d)', $dummy, $node_total);
sscanf(strstr($file_all, 'Guideposts photo entries'), '%[^(](total: %d, used: %d', $dummy, $img_total, $img_used);

$date = date('Ymd');
$check_q = "SELECT id FROM hicheck.gp_stats WHERE date='$date'";
if(pg_num_rows(pg_query($check_q))==0){
	pg_query("INSERT INTO hicheck.gp_stats (date, node_total, img_total, img_used) 
	VALUES('$date','$node_total','$img_total','$img_used')");
} else {
	pg_query("UPDATE hicheck.gp_stats SET node_total='$node_total',img_total='$img_total',img_used='$img_used' WHERE date='$date'");
}
