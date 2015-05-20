<?php
/** ulozeni uzivatelskeho bodu do databaze */

include 'db.php';

$lng = pg_escape_string($_POST['lng']);
$lat = pg_escape_string($_POST['lat']);
$name = pg_escape_string($_POST['name']);
$note = pg_escape_string($_POST['note']);
$date = pg_escape_string($_POST['date']);
$type = pg_escape_string($_POST['type']);
$osm = pg_escape_string($_POST['osm']);
$image = pg_escape_string($_POST['images']);
$point = "POINT $lng $lat";
$sql = "INSERT INTO hicheck.notes (hi_user_id, geom, note, date, type, osm_name, image)
     VALUES ('$name', ST_SetSRID(ST_MakePoint($lng,$lat), 4326),'$note', '$date', '$type', '$osm', '$image')";
var_dump($_POST);
pg_query($db, $sql);

exit(pg_last_error($db));