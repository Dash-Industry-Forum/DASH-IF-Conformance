<?php


//All checks in this file are equal to their non-adapter counterparts
//The specific video and audio function have not been translated yet.

global $logger;

$hdlrType1 = $r1->getHandlerType();
$sdType1 = $r1->getSDType();
$hdlrType2 = $r2->getHandlerType();
$sdType2 = $r2->getSDType();

$sdTypeEqual = ($sdType1 == $sdType2);
$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB: Section 4.3",
    "All the initialization segments for Representations within an Adaptation Set SHALL " .
    "have the same sample entry type",
    $sdTypeEqual,
    "FAIL",
    "Sample entry types for " . $r1->getPrintable() . " and " . $r2->getPrintable() . " are equal",
    "Sample entry types for " . $r1->getPrintable() . " and " . $r2->getPrintable() . " differ"
);

if (!$sdTypeEqual) {
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "DVB: Section 'Adaptation Sets'",
        "Non-switchable audio codecs SHOULD NOT be present within the same Adaptation Set " .
        "for the presence of consistent Representations within an Adaptation Set",
        $hdlrType1 != $hdlrType2 || $hdlrType1 != 'soun',
        "WARN",
        $r1->getPrintable() . " and " . $r2->getPrintable() . " are valid in this respect",
        $r1->getPrintable() . " and " . $r2->getPrintable() . " are invalid in this respect"
    );
}

$trackId1 = $r1->getTrackId('TKHD',0);
$trackId2 = $r2->getTrackId('TKHD',0);

$validTfhd = true;
$index = 0;
while ($validTfhd){
  $tfhdTrackId1 = $r1->getTrackId('TFHD', $index); 
  $tfhdTrackId2 = $r2->getTrackId('TFHD', $index); 
  if ($tfhdTrackId1 != $tfhdTrackId2){
    $validTfhd = false;
  }
  if (!$tfhdTrackId1 || !$tfhdTrackId2){
    break;
  }

  $index+=1;
}

$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB: Section 4.3",
    "All Representations within an Adaptation Set SHALL have the same track_ID",
    $validTfhd && $trackId1 == $trackId2,
    "FAIL",
    $r1->getPrintable() . " and " . $r2->getPrintable() . " are valid in this respect",
    $r1->getPrintable() . " and " . $r2->getPrintable() . " are invalid in this respect"
);

## Section 5.1.2 check for initialization segment identicalness
if ($sdType1 == $sdType2 && ($sdType1 == 'avc1' || $sdType1 == 'avc2')) {
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "DVB: Section 5.1.2",
        "In this case (content offered using either of the 'avc1' or 'avc2' sample entries), " .
        "the Initialization Segment SHALL be common for all Representations within an Adaptation Set",
        nodes_equal($r1->getRawBox('STSD',0), $r2->getRawBox('STSD',0)),
        "FAIL",
        $r1->getPrintable() . " and " . $r2->getPrintable() . " are valid in this respect",
        $r1->getPrintable() . " and " . $r2->getPrintable() . " are invalid in this respect"
    );
}

## Section 8.3 check for default_KID value
if ($r1->hasBox('TENC') && $r2->hasBox('TENC')){
    $defaultKIDEqual = $r1->getDefaultKID() == $r2->getDefaultKID();
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "DVB: Section 8.3",
        "All Representations (in the same Adaptation Set) SHALL have the same value of 'default_KID' " .
        "in their 'tenc' boxes in their Initialization Segments",
        $defaultKIDEqual,
        "FAIL",
        $r1->getPrintable() . " and " . $r2->getPrintable() . " are valid in this respect",
        $r1->getPrintable() . " and " . $r2->getPrintable() . " are invalid in this respect"
    );
    if (!$defaultKIDEqual) {
        $width1 = $r1->getWidth();
        $height1 = $r1->getHeight();
        $width2 = $r2->getWidth();
        $height2 = $r2->getHeight();

        $haveSDAndHD = ($width1 < 1280 && $height1 < 720 && $width2 >= 1280 && $height2 >= 720) ||
                       ($width2 < 1280 && $height2 < 720 && $width1 >= 1280 && $height1 >= 720);

        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "DVB: Section 8.3",
            "In cases where HD and SD content are contained in one presentation and MPD, but different licence " .
            "rights are given for each resolution, then they SHALL be contained in different HD and SD Adaptation Sets",
            !$haveSDAndHD,
            "FAIL",
            $r1->getPrintable() . " and " . $r2->getPrintable() . " are valid in this respect",
            $r1->getPrintable() . " and " . $r2->getPrintable() . " are invalid in this respect"
        );
    }
}

//THESE ARE ONLY PLACEHOLDERS IN THE ADAPTER VERSION FOR NOW.
## Section 10.4 check for audio switching
if ($hdlrType1 == 'soun' && $hdlrType2 == 'soun') {
    //$this->crossValidationDVBAudioAdapter($r1,$r2);
}

## Section 10.4 check for video switching
if ($hdlrType1 == 'vide' && $hdlrType2 == 'vide') {
    //$this->crossValidationDVBVideoAdapter($r1, $r2);
}
