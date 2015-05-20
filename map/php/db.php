<?php
require_once dirname(__FILE__).'/../../db_conf.php';
$db = pg_connect("host=".SERVER." dbname=".DATABASE." user=".USERNAME." password=".PASSWORD);
?>