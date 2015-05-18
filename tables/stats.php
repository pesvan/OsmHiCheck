<?php $statistics = getCount(); ?>
<?php $percentage = getPercentageFromArray($statistics, $statistics['total'], true); ?>
<?php $error_stats = getCountErrors(); ?>
<?php $error_perc = getPercentageFromArray($error_stats, $statistics['total'], true); ?>
<div class="tables">
<table>
    <tr><td colspan="3">Zakladni statisticke udaje
    <tr><td>Pocet KCT turistickych relaci v databazi: <td><a href="index.php?pg=table&area=all"><?php echo $statistics['total']; ?></a><td><?php echo $percentage['total'];?>
    <tr><td>Pocet techto relaci, kde chybi nektery z tagu: <td><a href="index.php?pg=table&area=all&specific=all_missing"><?php echo $statistics['not_any_tags']; ?></a><td><?php echo $percentage['not_any_tags'];?>
    <tr><td>Pocet techto relaci, kde se vyskutuje chybny tag nebo rozpor: <td><?php echo $error_stats['count']; ?><td><?php echo $error_perc['count']; ?>
    <tr><td class="missing" colspan="3">Chybejici hodnoty tagu:
    <tr><td>network: <td><a href="index.php?pg=table&area=all&specific=network"><?php echo $statistics['not_network']; ?></a><td><?php echo $percentage['not_network'];?>
    <tr><td>complete: <td><a href="index.php?pg=table&area=all&specific=complete"><?php echo $statistics['not_complete']; ?></a><td><?php echo $percentage['not_complete'];?>
    <tr><td>osmc:symbol: <td><a href="index.php?pg=table&area=all&specific=osmc:symbol"><?php echo $statistics['not_osmc']; ?></a><td><?php echo $percentage['not_osmc'];?>
    <tr><td>destinations: <td><a href="index.php?pg=table&area=all&specific=destinations"><?php echo $statistics['not_dest']; ?></a><td><?php echo $percentage['not_dest'];?>
    <tr><td class="wrong" colspan="3">Chybne hodnoty tagu:
    <tr><td>network: <td><a href="index.php?pg=table&area=all&specific=w_network"><?php echo $error_stats['network']; ?></a><td><?php echo $error_perc['network'];?>
    <tr><td>complete: <td><a href="index.php?pg=table&area=all&specific=w_complete"><?php echo $error_stats['complete']; ?></a><td><?php echo $error_perc['complete'];?>
    <tr><td>osmc:symbol: <td><a href="index.php?pg=table&area=all&specific=w_osmc:symbol"><?php echo $error_stats['osmc:symbol']; ?></a><td><?php echo $error_perc['osmc:symbol'];?>
    <tr><td>route: <td><a href="index.php?pg=table&area=all&specific=route"><?php echo $error_stats['route']; ?></a><td><?php echo $error_perc['route'];?>
    <tr><td>kct_*: <td><a href="index.php?pg=table&area=all&specific=kct"><?php echo $error_stats['kct']; ?></a><td><?php echo $error_perc['kct'];?>
    <tr><td class="wrong" colspan="3">Nalezene rozpory:
    <tr><td>hodnota network nekoresponduje s hodnotou kct_*: <td><a href="index.php?pg=table&area=all&specific=err_network"><?php echo $error_stats['err_network']; ?></a><td><?php echo $error_perc['err_network'];?>
    <tr><td>typ cesty v rozporu s ostatnimi u tagu osmc:symbol, kct_* nebo route: <td><a href="index.php?pg=table&area=all&specific=err_type"><?php echo $error_stats['err_type']; ?></a><td><?php echo $error_perc['err_type'];?>
    <tr><td>barva cesty v rozporu u osmc:symbol a kct_*: <td><a href="index.php?pg=table&area=all&specific=err_color"><?php echo $error_stats['err_color']; ?></a><td><?php echo $error_perc['err_color'];?>
</table>
<?php getCountErrors(); ?>
</div>

<div class="graphs">
 <img src="http://osm.fit.vutbr.cz/xsvana00/tables/php/graph.php"/>
</div>