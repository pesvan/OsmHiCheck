<?php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);// Same as error_reporting(E_ALL);
require 'db.php';
require 'funcTables.php';
require dirname(__FILE__).'/../../map/php/func.php';

$statistics = getCount(); 
$percentage = getPercentageFromArray($statistics, $statistics['total'], false);
$error_stats = getCountErrors(); 
$error_perc = getPercentageFromArray($error_stats, $statistics['total'], false);

$date = date('Ymd');
$missing = $percentage['not_any_tags'];
$wrong = $error_perc['count'];
$check_q = "SELECT id FROM hicheck.stats WHERE date='$date'";
if(pg_num_rows(pg_query($check_q))==0){
	pg_query("INSERT INTO hicheck.stats (date, relations_missing, relations_wrong) 
	VALUES('$date','$missing','$wrong')");
} else {
	pg_query("UPDATE hicheck.stats SET relations_missing='$missing',relations_wrong='$wrong' WHERE date='$date'");
}