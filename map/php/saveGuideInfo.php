<?php
/** ulozeni informace o rozcestniku do databaze */
include 'db.php';

$name = pg_escape_string($_POST['name']);
$note = pg_escape_string($_POST['note']);
$date = pg_escape_string($_POST['date']);
$type = pg_escape_string($_POST['type']);
$node = pg_escape_string($_POST['node']);
$osm = pg_escape_string($_POST['osm']);
$images = pg_escape_string($_POST['images']);
var_dump($_POST);
$sql = "INSERT INTO hicheck.checked_guideposts (hi_user_id, type, note, date, node, osm_name, image)
     VALUES ('$name', '$type','$note', '$date', '$node', '$osm', '$images')";

pg_query($db, $sql);

exit(pg_last_error($db));