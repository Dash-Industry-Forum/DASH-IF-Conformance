<?php

$conformant = true;

$progressiveWidth  = array('1920', '1600', '1280', '1024', '960', '852', '768', '720', '704', '640', '512', '480',
  '384', '320', '192', '3840', '3200', '2560');
$progressiveHeight = array('1080', '900',  '720',  '576',  '540', '480', '432', '404', '396', '360', '288', '270',
  '216', '180', '108', '2160', '1800', '1440');

$interlacedWidth  = array('1920', '704', '544', '352');
$interlacedHeight = array('1080', '576', '576', '288');

$scanType = $adaptation['scanType'];
if ($scanType == '') {
    $scanType = $representation['scanType'];

    if ($scanType == '') {
        $scanType = 'progressive';
    }
}

$width = $adaptation['width'];
$height = $adaptation['height'];
if ($width == '' && $height == '') {
    $width = $representation['width'];
    $height = $representation['height'];

    if ($width != '' && $height != '') {
        if ($scanType == 'progressive') {
            $ind1 = array_search($width, $progressiveWidth);
            if ($ind1 !== false) {
                if ($height != $progressiveHeight[$ind1]) {
                    $conformant = false;
                }
            }
        } elseif ($scanType == 'interlaced') {
            $ind1 = array_search($width, $interlacedWidth);
            if ($ind1 !== false) {
                if ($height != $interlacedHeight[$ind1]) {
                    $conformant = false;
                }
            }
        }
    }
}

return array($conformant, $width, $height);
