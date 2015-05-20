<?php
/* skript pro skryti uziatelskeho vstupu*/
require 'db.php';
/* konstanty pro lepsi orientaci */
const REMOVE_GUIDEPOST_INFO = 7;
const REMOVE_USER_NOTE = 2;
const REMOVE_USER_PART = 5;

if((isset($_POST['uid'])) && (isset($_POST['type']))
    && (isset($_POST['user'])) && (isset($_POST['hash']))){
    $id = $_POST['uid'];
    $type = $_POST['type'];
    $user = $_POST['user'];
    $hash = hash('sha512',$_POST['hash']); //jen autorizovany uzivatel muze tuto akci provadet
    $result = pg_num_rows(pg_query($db, "SELECT id FROM hicheck.superuser WHERE name='$user' and password='$hash'"));
    if($result!=1){
        $ajxres=array();
        $ajxres['resp']=4;
        $ajxres['dberror']=0;
        $ajxres['msg']='wrong username or password';
        exit(json_encode($ajxres));
    }
} else {
    $ajxres=array();
    $ajxres['resp']=4;
    $ajxres['dberror']=0;
    $ajxres['msg']='missing element id or type of data';
    exit(json_encode($ajxres));
}
if($type==REMOVE_GUIDEPOST_INFO){//poznamky u rozcestniku
    $query_string = "UPDATE hicheck.checked_guideposts SET hidden=1 WHERE id='$id'";
} else if($type==REMOVE_USER_NOTE) { //poznamka obecna uzivatelska
    $query_string = "UPDATE hicheck.notes SET hidden=1 WHERE id='$id'";
} else if($type==REMOVE_USER_PART) { //uzivatelem vyznaceny usek
    $query_string = "UPDATE hicheck.parts SET hidden=1 WHERE id='$id'";
}
$query = pg_query($query_string);