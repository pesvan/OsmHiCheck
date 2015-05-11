<?php
phpinfo();
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
$data = $_POST['data'];
$name = pg_escape_string($_POST['name']);
$file = time()."_".$name;
$info = getimagesize($data);

/** kontrola obrazku - velikost, typ*/
if($info[0]*$info[1] > 5242880 || ($info['mime']!="image/jpeg" && $info['mime']!="image/png")){
    exit(1);
}
/** zmenseni */
if($info['mime']=="image/png"){
    $width = $info[0];
    $height = $info[1];
    $image = imagecreatefrompng($name);
    if($width>=$height){
        if($width>1200){
            $newWidth = 1200;
            $newHeight= $height*($newWidth/$width);
        }
    } else {
        if($height>1200){
            $newHeight = 1200;
            $newWidth = $width*($newHeight/$height);
        }
    }
    imagecopyresampled($image, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    imagepng($image, "../../upload/".$file);


}


exit($_POST['name']);