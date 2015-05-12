
<div class="tables">
<table>
    <tr><td colspan="3">Zakladni statisticke udaje
    <tr><td>Pocet KCT turistickych relaci v databazi: <td><?php echo $statistics['total']; ?><td><?php echo $percentage['total'];?><br />
    <tr><td>Pocet techto relaci, kde chybi nektery z tagu: <td><?php echo $statistics['not_any_tags']; ?><td><?php echo $percentage['not_any_tags'];?><br />
    <tr><td colspan="3">Pocet chybejicich tagu:<br />
            <div class="to-right">
                <tr><td>network: <td><?php echo $statistics['not_network']; ?><td><?php echo $percentage['not_network'];?><br />
                <tr><td>complete: <td><?php echo $statistics['not_complete']; ?><td><?php echo $percentage['not_complete'];?><br />
                <tr><td>osmc:symbol: <td><?php echo $statistics['not_osmc']; ?><td><?php echo $percentage['not_osmc'];?><br />
                <tr><td>destinations: <td><?php echo $statistics['not_dest']; ?><td><?php echo $percentage['not_dest'];?><br />
            </div>
</table>
</div>