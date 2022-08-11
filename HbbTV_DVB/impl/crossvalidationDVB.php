<?php

global $mpd_features, $current_period;

global $logger;

## Section 4.3 checks for sample entry type and track_ID
$hdlr1 = $xmlDom1->getElementsByTagName('hdlr')->item(0);
$hdlrType1 = $hdlr1->getAttribute('handler_type');
$sdType1 = $xmlDom1->getElementsByTagName($hdlrType1 . '_sampledescription')->item(0)->getAttribute('sdType');

$hdlr2 = $xmlDom2->getElementsByTagName('hdlr')->item(0);
$hdlrType2 = $hdlr2->getAttribute('handler_type');
$sdType2 = $xmlDom2->getElementsByTagName($hdlrType2 . '_sampledescription')->item(0)->getAttribute('sdType');


$sdTypeEqual = ($sdType1 == $sdType2);
$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB: Section 4.3",
    "All the initialization segments for Representations within an Adaptation Set SHALL 
    have the same sample entry type",
    $sdTypeEqual,
    "FAIL",
    "Adaptation $adaptationIndex: Sample entry types for " .
    "representations $xmlIndex1 and $xmlIndex2 are equal",
    "Adaptation $adaptationIndex: Sample entry types for " .
    "representations $xmlIndex1 and $xmlIndex2 differ"
);

if (!$sdTypeEqual) {
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "DVB: Section 'Adaptation Sets'",
        "Non-switchable audio codecs SHOULD NOT be present within the same Adaptation Set " .
        "for the presence of consistent Representations within an Adaptation Set",
        $hdlrType1 != $hdlrType2 || $hdlrType1 != 'soun',
        "WARN",
        "Adaptation $adaptationIndex: Sample entry types for " .
        "representations $xmlIndex1 and $xmlIndex2 are valid in this respect",
        "Adaptation $adaptationIndex: Sample entry types for " .
        "representations $xmlIndex1 and $xmlIndex2 are invalid in this respect"
    );
}

$tkhd1 = $xmlDom1->getElementsByTagName('tkhd')->item(0);
$trackId1 = $tkhd1->getAttribute('trackID');
$tfhdBoxes1 = $xmlDom1->getElementsByTagName('tfhd');

$tkhd2 = $xmlDom2->getElementsByTagName('tkhd')->item(0);
$trackId2 = $tkhd2->getAttribute('trackID');
$tfhdBoxes2 = $xmlDom2->getElementsByTagName('tfhd');

$validTfhd = true;
foreach ($tfhdBoxes1 as $index => $tfhd1) {
    if ($tfhd1->getAttribute('trackID') != $tfhdBoxes2->item($index)->getAttribute('trackID')) {
        $validTfhd = false;
    }
}

$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB: Section 4.3",
    "All Representations within an Adaptation Set SHALL have the same track_ID",
    $validTfhd && $trackId1 == $trackId2,
    "FAIL",
    "Adaptation $adaptationIndex: " .
    "representations $xmlIndex1 and $xmlIndex2 are valid in this respect",
    "Adaptation $adaptationIndex:  " .
    "representations $xmlIndex1 and $xmlIndex2 are invalid in this respect"
);

## Section 5.1.2 check for initialization segment identicalness
if ($sdType1 == $sdType2 && ($sdType1 == 'avc1' || $sdType1 == 'avc2')) {
    $stsd1 = $xmlDom1->getElementsByTagName('stsd')->item(0);
    $stsd2 = $xmlDom2->getElementsByTagName('stsd')->item(0);

    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "DVB: Section 5.1.2",
        "In this case (content offered using either of the 'avc1' or 'avc2' sample entries), " .
        "the Initialization Segment SHALL be common for all Representations within an Adaptation Set",
        nodes_equal($stsd1, $stsd2),
        "FAIL",
        "Adaptation $adaptationIndex: " .
        "representations $xmlIndex1 and $xmlIndex2 are valid in this respect",
        "Adaptation $adaptationIndex:  " .
        "representations $xmlIndex1 and $xmlIndex2 are invalid in this respect"
    );
}

## Section 8.3 check for default_KID value
$tenc1 = $xmlDom1->getElementsByTagName('tenc')->item(0);
$tenc2 = $xmlDom2->getElementsByTagName('tenc')->item(0);

if ($tenc1->length && $tenc2->length) {
    $defaultKIDEqual = ($tenc1->getAttribute('default_KID') == $tenc2->getAttribute('default_KID'));
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "DVB: Section 8.3",
        "All Representations (in the same Adaptation Set) SHALL have the same value of 'default_KID' " .
        "in their 'tenc' boxes in their Initialization Segments",
        $defaultKIDEqual,
        "FAIL",
        "Adaptation $adaptationIndex: " .
        "representations $xmlIndex1 and $xmlIndex2 are valid in this respect",
        "Adaptation $adaptationIndex:  " .
        "representations $xmlIndex1 and $xmlIndex2 are invalid in this respect"
    );
    if (!$defaultKIDEqual) {
        $vide1 = $xmlDom1->getElementsByTagName($hdlrType1 . '_sampledescription')->item(0);
        $vide2 = $xmlDom2->getElementsByTagName($hdlrType2 . '_sampledescription')->item(0);
        $width1 = $vide1->getAttribute('width');
        $height1 = $vide1->getAttribute('height');
        $width2 = $vide2->getAttribute('width');
        $height2 = $vide2->getAttribute('height');

        $haveSDAndHD = ($width1 < 1280 && $height1 < 720 && $width2 >= 1280 && $height2 >= 720) ||
                       ($width2 < 1280 && $height2 < 720 && $width1 >= 1280 && $height1 >= 720);

        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "DVB: Section 8.3",
            "In cases where HD and SD content are contained in one presentation and MPD, but different licence " .
            "rights are given for each resolution, then they SHALL be contained in different HD and SD Adaptation Sets",
            !$haveSDAndHD
            "FAIL",
            "Adaptation $adaptationIndex: " .
            "representations $xmlIndex1 and $xmlIndex2 are valid in this respect",
            "Adaptation $adaptationIndex:  " .
            "representations $xmlIndex1 and $xmlIndex2 are invalid in this respect"
        );
    }
}

## Section 10.4 check for audio switching
if ($hdlrType1 == 'soun' && $hdlrType2 == 'soun') {
    $this->crossValidationDVBAudio($xmlDom1, $xmlDom2, $adaptationIndex, $xmlIndex1, $xmldIndex2);
}

## Section 10.4 check for video switching
if ($hdlrType1 == 'vide' && $hdlrType2 == 'vide') {
    $this->crossValidationDVBVideo($xmlDom1, $xmlDom2, $adaptationIndex, $xmlIndex1, $xmldIndex2);
}
