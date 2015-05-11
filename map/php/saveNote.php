<?php
include 'db.php';

$lng = pg_escape_string($_POST['lng']);
$lat = pg_escape_string($_POST['lat']);
$name = pg_escape_string($_POST['name']);
$note = pg_escape_string($_POST['note']);
$date = pg_escape_string($_POST['date']);
$point = "POINT $lng $lat";
var_dump($date);
$sql = "INSERT INTO hicheck.notes (hi_user_id, geom, note, date)
     VALUES ('$name', ST_SetSRID(ST_MakePoint($lng,$lat), 4326),'$note', '$date')";

pg_query($db, $sql);

exit(pg_last_error($db));