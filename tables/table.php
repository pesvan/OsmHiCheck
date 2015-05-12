<?php
$query = "SELECT id, hstore_to_json(tags) AS tags FROM relations ORDER BY id";
$result = pg_query($db, $query);

function getCheckedValues($kctKey)
{

    return array('destinations', 'complete',  'osmc:symbol', $kctKey, 'network','route');
}


function getWrong($errNum, $kctKey)
{
    $red = array();
    if ($errNum >= 128) {
        $red[] = 'network';
        $errNum -= 128;
    }
    if ($errNum >= 64) {
        $errNum -= 64;
    }
    if ($errNum >= 32) {
        $errNum -= 32;
    }
    if ($errNum >= 16) {
        $red[] = 'complete';
        $errNum -= 16;
    }
    if ($errNum >= 8) {
        $errNum -= 8;
    }
    if ($errNum >= 4) {
        $red[] = 'route';
        $errNum -= 4;
    }
    if ($errNum >= 2) {
        $red[] = 'osmc:symbol';
        $errNum -= 2;
    }
    if ($errNum >= 1) {
        $red[] = $kctKey;
    }
    return $red;

}

function getMissing($tags, $kctKey)
{
    $orange = array();
    $keys = getCheckedValues($kctKey);
    foreach ($keys as $key) {
        if (!(array_key_exists($key, $tags))) {
            $orange[] = $key;
        }

    }
    return $orange;
}

function writeTag($tag, $tags, $kctKey)
{
    if (array_key_exists($tag, $tags)) {
        if (checkValidValue($tag, $tags[$tag], $kctKey)) {
            return "<td class='wrong'>$tags[$tag]</td>";
        }
        return "<td>$tags[$tag]</td>";
    } else {
        return "<td class='missing'></td>";
    }
}

?>
<div class="tables">
    <table>
        <tr>
            <td>id
            <td>destinations
            <td>complete
            <td>osmc:symbol
            <td>kct_
            <td>network
            <td>route
                <?php while ($row = pg_fetch_assoc($result)){
                $tags = json_decode($row['tags'], true);
                $kct = getKctTag($tags);
                $kctKey = count($kct) > 0 ? $kct[0] : "";
                $incorrect = checkTagsValidValues($tags, $kctKey);
                $red = getWrong($incorrect, $kctKey);
                $orange = getMissing($tags, $kctKey);

                $checked = getCheckedValues($kctKey);
                $errorStr = "";
                if(array_key_exists('network', $tags) && array_key_exists($kctKey, $tags)
                    && !(in_array('network', $red)) && !(in_array($kctKey, $red))){
                    if(checkTagNetworkKct($tags['network'], $tags[$kctKey])>0){
                        $errorStr .= "network type nekoresponduje s kct hodnotou; ";
                        $red[] = 'network';
                        $red[] = $kctKey;
                    }
                }
                if(array_key_exists('osmc:symbol', $tags) && array_key_exists('route', $tags) && array_key_exists($kctKey, $tags)
                    && !(in_array('osmc:symbol', $red)) && !(in_array($kctKey, $red)) && !(in_array('route', $red))){
                    if(checkTagOsmcKctRoute($tags['osmc:symbol'], $tags[$kctKey], $tags['route'])>0){
                        $errorStr .= "typ cesty v rozporu u osmc:symbol/kct/route; ";
                        $red[] = 'osmc:symbol';
                        $red[] = $kctKey;
                        $red[] = 'route';
                    }
                }
                if(array_key_exists('route', $tags) && array_key_exists('osmc:symbol', $tags) && array_key_exists($kctKey, $tags)
                    && !(in_array('osmc:symbol', $red)) && !(in_array($kctKey, $red))){
                    $color = getKctTrackColor($kctKey, $tags['route']);
                    if(checkTagOsmcKctColor($tags['osmc:symbol'], $color)>0){
                        $errorStr .= "typ cesty v rozporu u osmc:symbol/kct/route; ";
                        $red[] = 'osmc:symbol';
                        $red[] = $kctKey;
                    }
                }


                $orange = array_diff($orange, $red);
                ?>

        <tr>
            <td><?php echo $row['id']; ?></td>
            <?php
                foreach($checked as $key){
                    if(array_key_exists($key, $tags) && $key==$kctKey){
                        $tags[$key] = "$kctKey: ".$tags[$key];
                    }
                    if(in_array($key, $red)){
                        echo "<td class='wrong'>".$tags[$key]."</td>";
                    } else if(in_array($key, $orange)){
                        echo "<td class='missing'></td>";
                    } else {
                        echo "<td>".$tags[$key]."</td>";
                    }
                }
            ?>

        </tr>
        <?php } ?>
    </table>
</div>