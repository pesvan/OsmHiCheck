<?php
/** PHP skript pro generovani tabulkove reprezentace relaci */
if(isset($_GET['specific'])){
    $specific = $_GET['specific'];
} else {
    $specific = null;
}
if($specific=='all_missing'){
    $spec_query = "and (not exist(relations.tags,'network')
                or not exist(relations.tags,'complete') or not exist(relations.tags,'osmc:symbol')
                or not exist(relations.tags,'destinations'))";
} else if($specific=='network'){
    $spec_query = "and not exist(relations.tags,'network')";
} else if($specific=='destinations'){
    $spec_query = "and not exist(relations.tags,'destinations')";
} else if($specific=='complete'){
    $spec_query = "and not exist(relations.tags,'complete')";
} else if($specific=='osmc:symbol'){
    $spec_query = "and not exist(relations.tags,'osmc:symbol')";
} else {
    $filter = $specific;
} 
if(!(isset($spec_query))){
    $spec_query = "";
}
if(!(isset($filter))){
    $filter = "";
}
$query = "SELECT id, hstore_to_json(tags) AS tags FROM relations WHERE ".NOT_CYCLO." ".$spec_query." ORDER BY id";
$result = pg_query($db, $query);

?>
<iframe id="hiddenIframe" name="hiddenIframe"></iframe>
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

                    /* filtrovani */
                if( ($filter=='w_network' && !in_array('network', $red)) 
                || ($filter=='w_complete' && !in_array('complete', $red)) 
                || ($filter=='w_osmc:symbol' && !in_array('osmc:symbol', $red)) 
                || ($filter=='route' && !in_array('route', $red)) 
                || ($filter=='kct' && !in_array($kctKey, $red))){
                    continue;
                 }
                    
                 /* vyhledavani chyb */
                
                $orange = getMissing($tags, $kctKey);

                $checked = getCheckedValues($kctKey);
                $errorStr = "";
                if(array_key_exists('network', $tags) && array_key_exists($kctKey, $tags)
                    && !(in_array('network', $red)) && !(in_array($kctKey, $red))){
                    if(checkTagNetworkKct($tags['network'], $tags[$kctKey])>0){
                        $errorStr .= "network type nekoresponduje s kct hodnotou; ";
                        $red[] = 'network';
                        $red[] = $kctKey;
                    } else {
                        if($filter=='err_network'){
                            continue;
                        }                        
                    }
                } else {
                    if($filter=='err_network'){
                            continue;
                    }
                }
                if(array_key_exists('osmc:symbol', $tags) && array_key_exists('route', $tags) && array_key_exists($kctKey, $tags)
                    && !(in_array('osmc:symbol', $red)) && !(in_array($kctKey, $red)) && !(in_array('route', $red))){
                    if(checkTagOsmcKctRoute($tags['osmc:symbol'], $tags[$kctKey], $tags['route'])>0){
                        $errorStr .= "typ cesty v rozporu u osmc:symbol/kct/route; ";
                        $red[] = 'osmc:symbol';
                        $red[] = $kctKey;
                        $red[] = 'route';
                    } else {
                        if($filter=='err_type'){
                            continue;
                        }                        
                    }
                } else {
                    if($filter=='err_type'){
                            continue;
                    }
                }                
                if(array_key_exists('route', $tags) && array_key_exists('osmc:symbol', $tags) && array_key_exists($kctKey, $tags)
                    && !(in_array('osmc:symbol', $red)) && !(in_array($kctKey, $red))){
                    $color = getKctTrackColor($kctKey, $tags['route']);
                    if(checkTagOsmcKctColor($tags['osmc:symbol'], $color)>0){
                        $errorStr .= "barva cesty v rozporu u osmc:symbol/kct; ";
                        $red[] = 'osmc:symbol';
                        $red[] = $kctKey;                        
                    } else {
                        if($filter=='err_color'){
                            continue;
                        }                        
                    }
                } else {
                    if($filter=='err_color'){
                            continue;
                    }
                }
                $orange = array_diff($orange, $red);
                ?>

        <tr>
            <td><?php echo "<a target=\"hiddenIframe\" href=\"http://localhost:8111/load_object?relation_members=true&objects=r".$row['id']."\">".$row['id']."</a>"; ?></td>
            <?php
            /* vyber spravneho stylu na zaklade spravnosti prvku */
            $rid = $row['id'];
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
