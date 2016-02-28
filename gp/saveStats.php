<?php
/* skript pro ulozeni pravidelnych statistik */
require dirname(__FILE__).'/../tables/php/db.php';

$node_total = 0;
$node_ok = 0;
$node_bad = 0;
$node_cor = 0;

$img_total = 0;
$img_used = 0;

$file_all = file_get_contents("/tmp/osm.gp2.html");

sscanf(strstr($file_all, 'Guideposts nodes '), '%[^(](total:%d, OK: %d, have photo but no ref: %d, missing photo and ref: %d',
       $dummy, $node_total, $node_ok, $node_cor, $node_bad);
sscanf(strstr($file_all, 'Guideposts photo entries'), '%[^(](total: %d, used: %d', $dummy, $img_total, $img_used);

$date = date('Ymd');
$check_q = "SELECT id FROM hicheck.gp_stats WHERE date='$date'";
if(pg_num_rows(pg_query($check_q))==0){
	pg_query("INSERT INTO hicheck.gp_stats (date, node_total, img_total, img_used, node_ok, node_bad, node_cor) 
	VALUES('$date','$node_total','$img_total','$img_used','$node_ok','$node_bad','$node_cor')");
} else {
	pg_query("UPDATE hicheck.gp_stats SET node_total='$node_total',img_total='$img_total',img_used='$img_used',node_ok='$node_ok',node_bad='$node_bad',node_cor='$node_cor' WHERE date='$date'");
}
