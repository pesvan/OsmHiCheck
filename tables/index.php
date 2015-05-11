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
<?php $statistics = getCount(); ?>
<?php $percentage = getPercentageFromArray($statistics, 'total'); ?>
    <div class="content">
        <div class="content-menu">
             Zakladni statisticke udaje</div>
        <div class="content-inside">
            <table>
                <tr><td>Pocet KCT turistickych relaci v databazi: <td><?php echo $statistics['total']; ?><td><?php echo $percentage['total'];?><br />
                <tr><td>Pocet techto relaci, kde chybi nektery z tagu: <td><?php echo $statistics['not_any_tags']; ?><td><?php echo $percentage['not_any_tags'];?><br />
                <tr><td>Pocet chybejicich tagu:<br />
                <div class="to-right">
                    <tr><td>network: <td><?php echo $statistics['not_network']; ?><td><?php echo $percentage['not_network'];?><br />
                    <tr><td>complete: <td><?php echo $statistics['not_complete']; ?><td><?php echo $percentage['not_complete'];?><br />
                    <tr><td>osmc:symbol: <td><?php echo $statistics['not_osmc']; ?><td><?php echo $percentage['not_osmc'];?><br />
                    <tr><td>destinations: <td><?php echo $statistics['not_dest']; ?><td><?php echo $percentage['not_dest'];?><br />
                </div>
            </table>
        </div>
    </div>
</body>

</html>
