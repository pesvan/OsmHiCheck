<?php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);// Same as error_reporting(E_ALL);
require "php/db.php";
require "php/funcTables.php";
// Same as error_reporting(E_ALL);


?>

<!DOCTYPE html>
<html>
<head>
    <title>OsmHiCheck</title>
    <meta charset="utf-8"/>
    <link href='http://fonts.googleapis.com/css?family=PT+Sans&subset=latin-ext' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" type="text/css" href="css/stylesheet.css"/>
</head>

<body>
   
    <div class="content">
        <div class="content-menu">
             Zakladni statisticke udaje</div>
        <div class="content-inside">            
                Pocet KCT turistickych relaci v databazi: <?php echo getCount(ALL); ?><br />
                Pocet techto relaci, kde chybi nektery z tagu: <?php echo getCount(WARNING); ?><br />
                Po
        </div>
    </div>
</body>

</html>
