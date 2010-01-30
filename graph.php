<?php
    $height = 15;
    $width = 150;
    $percent = $_REQUEST['p'];
    $im = imagecreatetruecolor($width, $height);
    $translated = imagecolorallocate($im, 0, 255, 0);
    $missing = imagecolorallocate($im, 255, 0, 0);
    imagefill($im, 0, 0, $missing);
    imagefilledrectangle($im, 0, 0, $width * $percent / 100, $height, $translated);
    header("Content-type: image/png");
    imagepng($im);
?>
