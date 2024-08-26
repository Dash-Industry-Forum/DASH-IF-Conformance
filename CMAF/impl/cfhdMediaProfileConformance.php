<?php

global $mpdHandler;

$videoSampleDescription = $xml->getElementsByTagName('vide_sampledescription');
if (!$videoSampleDescription->length) {
    return false;
}

$sdType = $videoSampleDescription->item(0)->getAttribute('sdType');
if ($sdType != "avc1" && $sdType != "avc3") {
    return false;
}

$width = $videoSampleDescription->item(0)->getAttribute('width');
$height = $videoSampleDescription->item(0)->getAttribute('height');
if ($width > 1920 && $height > 1080) {
    return false;
}

$avcC = $xml->getElementsByTagName('avcC');
$avcProfile = $avcC->item(0)->getAttribute('profile');
if ($avcProfile != 100 && $avcProfile != 110 && $avcProfile != 122 && $avcProfile != 144) {
    return false;
}

$avcComment = $avcC->item(0)->getElementsByTagName('Comment');
$level = $avcComment->item(0)->getAttribute('level');
if ($level != 31 && $level != 40) {
    return false;
}

$nalUnits = $xml->getElementsByTagName('NALUnit');
if ($nalUnits->length > 0) {
    $nalComments = $nalUnits->item(0)->getElementsByTagName('comment');
    if ($nalComments->length > 0) {
        $nalComment = $nalComments->item(0);
        if (
            $nalComment->getAttribute(video_signal_type_present_flag) != 0x0 &&
            $nalComment->getAttribute('colour_description_present_flag') != 0x0
        ) {
            $colorPrimaries = $nalComment->getAttribute('colour_primaries');
            if ($colorPrimaries != 0x1 && $colorPrimaries != 0x5 && $colorPrimaries != 0x6) {
                return false;
            }

            $tranferChar = $nalComment->getAttribute('transfer_characteristics');
            if ($tranferChar != 0x1 && $tranferChar != 0x6) {
                return false;
            }

            $matrixCoeff = $nalComment->getAttribute('matrix_coefficients');
            if ($matrixCoeff != 0x1 && $matrixCoeff != 0x5 && $matrixCoeff != 0x6) {
                return false;
            }
        }

        if ($mpdHandler->getFrameRate() > 60) {
            return false;
        }
    }
}
return true;
