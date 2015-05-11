<?php
include 'db.php';

$name = pg_escape_string($_POST['name']);
$note = pg_escape_string($_POST['note']);
$date = pg_escape_string($_POST['date']);
$type = pg_escape_string($_POST['type']);
$node = pg_escape_string($_POST['node']);
$osm = pg_escape_string($_POST['osm']);
var_dump($_POST);
$sql = "INSERT INTO hicheck.checked_guideposts (hi_user_id, type, note, date, node, osm_name)
     VALUES ('$name', '$type','$note', '$date', '$node', '$osm')";

pg_query($db, $sql);

exit(pg_last_error($db));