<?php

global $logger, $session, $mpdHandler;

if (is_null($adaptationCount)) {
    $adaptationCount = sizeof($mpdHandler->getFeatures()['Period'][$mpdHandler->getSelectedPeriod()]['AdaptationSet']);
}

$chfdSwitchingSetFound = 0;
$videoSelectionSetFound = 0;
$caacSwitchingSetFound = 0;
$audioSelectionSetFound = 0;
$im1tSwitchingSetFound = 0;
$subtitleSelectionSetFound = 0;
$hdlrType = "";
$encryptedTrackFound = 0;
$cencSwSetFound = 0;
$cbcsSwSetFound = 0;
$presentationProfile = "";

for ($adaptationIndex = 0; $adaptationIndex < $adaptationCount; $adaptationIndex++) {
    $switchingSetMediaProfiles = array();
    $encryptedTracks = array();
    if(is_null($periodIndex)) {
        $periodIndex = $mpdHandler->getSelectedPeriod();
    }
    $location = $session->getAdaptationDir($periodIndex, $adaptationIndex);
    $fileCount = 0;
    $files = DASHIF\rglob("$location/*.xml");
    if ($files) {
        $fileCount = count($files);
    }

    $exists = $logger->test(
        "CTAWAVE",
        "Presentation Checks",
        "Attempting to open switching sets for adaptationSet $adaptationSetIndex",
        file_exists($location),
        "FAIL",
        "Files exist",
        "Files don't exist: Possible cause: Representations are not valid and no " .
        "file/directory for box info is created.)"
    );
    if (!$exists) {
        continue;
    }
    for ($fileIndex = 0; $fileIndex < $fileCount; $fileIndex++) {
        $xml = DASHIF\Utility\parseDOM($files[$fileIndex], 'atomlist');
        if (!$xml) {
            continue;
        }

        $hdlrBox = $xml->getElementsByTagName("hdlr")->item(0);
        if (!$hdlrBox) {
            continue;
        }
        $hdlrType = $hdlrBox->getAttribute("handler_type");

        $mediaProfileResult = $this->getMediaProfile($xml, $hdlrType, $fileIndex, $adaptationIndex);
        array_push($switchingSetMediaProfiles, $mediaProfileResult);

        if ($hdlrType == "vide") {
            $videoSelectionSetFound = 1;
        }
        if ($hdlrType == "soun") {
            $audioSelectionSetFound = 1;
        }
        if ($hdlrType == "subt") {
            $subtitleSelectionSetFound = 1;
        }

        //Check for encrypted tracks
        if ($xml->getElementsByTagName('tenc')->length > 0) {
            $encryptedTrackFound = 1;
            $schm = $xml->getElementsByTagName('schm');
            if ($schm->length > 0) {
                array_push($encryptedTracks, $schm->item(0)->getAttribute('scheme'));
            }
        }
    }

    if (count(array_unique($switchingSetMediaProfiles)) === 1) {
        $mediaProfile = $switchingSetMediaProfiles[0];
        if ($hdlrType === "vide" && $mediaProfile === "HD") {
            $chfdSwitchingSetFound = 1;
        }
        if ($hdlrType == "soun" && $mediaProfile === "AAC_Core") {
            $caacSwitchingSetFound = 1;
        }
        if ($hdlrType == "subt" && $mediaProfile === "TTML_IMSC1_Text") {
            $im1tSwitchingSetFound = 1;
        }
    }
    if (
        $encryptedTrackFound === 1 &&
        count($encryptedTracks) == $fileCount &&
        count(array_unique($encryptedTracks)) == 1
    ) {
        $encryption = $encryptedTracks[0];
        if ($encryption == "cenc") {
            $cencSwSetFound = 1;
        }
        if ($encryption == "cbcs") {
            $cbcsSwSetFound = 1;
        }
    }
}


$presentationProfileArray = array();
if ($videoSelectionSetFound) {
    $conforms = $logger->test(
        "CTAWAVE",
        "WAVE Content Spec 2018Ed-Section 5",
        "If a video track is included, then conforming Presentation will at least include that video in a CMAF " .
        "SwSet conforming to required AVC (HD) Media Profile",
        $chfdSwitchingSetFound,
        "FAIL",
        "Switching set found",
        "Switching set not found"
    );
    if ($conforms) {
        array_push(
            $presentationProfileArray,
            $this->getPresentationProfile($encryptedTrackFound, $cencSwSetFound, $cbcsSwSetFound)
        );
    } else {
        array_push($presentationProfileArray, "");
    }
}
if ($audioSelectionSetFound) {
    $conforms = $logger->test(
        "CTAWAVE",
        "WAVE Content Spec 2018Ed-Section 5",
        "If an audio track is included, then conforming Presentation will at least include that audio in a CMAF " .
        "SwSet conforming to required AAC (Core) Media Profile",
        $chfdSwitchingSetFound,
        "FAIL",
        "Switching set found",
        "Switching set not found"
    );
    if ($conforms) {
        array_push(
            $presentationProfileArray,
            $this->getPresentationProfile($encryptedTrackFound, $cencSwSetFound, $cbcsSwSetFound)
        );
    } else {
        array_push($presentationProfileArray, "");
    }
}
if ($subtitleSelectionSetFound) {
    $conforms = $logger->test(
        "CTAWAVE",
        "WAVE Content Spec 2018Ed-Section 5",
        "If a subtitle track is included, then conforming Presentation will at least include that subtitle in a " .
        "CMAF SwSet conforming to TTML Text Media Profile",
        $im1tSwitchingSetFound,
        "FAIL",
        "Switching set found",
        "Switching set not found"
    );
    if ($conforms) {
        array_push(
            $presentationProfileArray,
            $this->getPresentationProfile($encryptedTrackFound, $cencSwSetFound, $cbcsSwSetFound)
        );
    } else {
        array_push($presentationProfileArray, "");
    }
}


$presentationProfile = "";
if (count(array_unique($presentationProfileArray)) === 1) {
    $presentationProfile = $presentationProfileArray[0];
}
if (in_array("", $presentationProfileArray)) {
    $presentationProfile = "";
}


if ($presentationProfile != ""){
  $logger->message("Stream found to conform to a CMAF Presentation Profile: $presentationProfile");
}

$this->presentationProfile = $presentationProfile;


return $presentationProfile;
