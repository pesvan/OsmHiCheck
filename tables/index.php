<?php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);// Same as error_reporting(E_ALL);
require "php/db.php";
require "php/funcTables.php";
require "../map/php/func.php";
// Same as error_reporting(E_ALL);
if(!(isset($_GET['pg']))){
    $page = "main";
} else {
    $page = $_GET['pg'];
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>OsmHiCheck - Tabulky</title>
    <meta charset="utf-8"/>
    <link href='http://fonts.googleapis.com/css?family=PT+Sans&subset=latin-ext' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" type="text/css" href="css/stylesheet.css"/>
</head>

<body>

    <div class="content">
        <div class="content-menu">
            <a href="index.php?pg=stats">Rychlé statistiky</a>
            <a href="index.php?pg=table&area=all">Tabulka s relacemi</a>
            <a href="../map/">Prepnout na mapu</a>

        </div>
        <?php $statistics = getCount(); ?>
        <?php $percentage = getPercentageFromArray($statistics, 'total'); ?>
        <div class="content-inside">
            <?php include "$page.php"; ?>
        </div>
    </div>
</body>

</html>
