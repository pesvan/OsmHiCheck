<?php
/* zpracovani obrazku*/
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
$data = $_POST['data'];
$name = pg_escape_string($_POST['name']);
$file = time()."_".$name;
$info = getimagesize($data);

/** kontrola obrazku - velikost, typ*/
if(($info['mime']!="image/jpeg" && $info['mime']!="image/png")){
    exit();
}

list($type, $data) = explode(';', $data);
list(, $data) = explode(',',$data);
$data = base64_decode($data);

/** zmenseni */
if($info['mime']=="image/png"){
    file_put_contents("../../uploads/".$file.".png", $data);
    $width = $info[0];
    $height = $info[1];
    $image = imagecreatefrompng("../../uploads/".$file.".png");
    if($width>=$height){
        if($width>1200){
            $newWidth = 1200;
            $newHeight= $height*($newWidth/$width);
            $image_new = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($image_new, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        }
    } else {
        if($height>1200){
            $newHeight = 1200;
            $newWidth = $width*($newHeight/$height);
            $image_new = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($image_new, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        }
    }
    
    imagepng($image, "../../uploads/".$file.".png");
    exit($file.".png");
} else if($info['mime']=="image/jpeg"){
    file_put_contents("../../uploads/".$file.".jpg", $data);
    $width = $info[0];
    $height = $info[1];
    $image = imagecreatefromjpeg("../../uploads/".$file.".jpg");
    if($width>=$height){
        if($width>1200){
            $newWidth = 1200;
            $newHeight= $height*($newWidth/$width);
            $image_new = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($image_new, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        }
    } else {
        if($height>1200){
            $newHeight = 1200;
            $newWidth = $width*($newHeight/$height);
            $image_new = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($image_new, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        }
    }
    
    imagejpeg($image, "../../uploads/".$file.".jpg");
    exit($file.".jpg");
}
