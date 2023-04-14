<?php

global $MediaProfDatabase;

global $logger, $session, $mpdHandler;

$adaptations = $mpdHandler->getFeatures()['Period'][$mpdHandler->getSelectedPeriod()]['AdaptationSet'];
$adaptationCount = sizeof($adaptations);

$waveVideoTrackFound = 0;
$waveVideoSwitchingSetFound = 0;
$videoSelectionSetFound = 0;
$waveAudioTrackFound = 0;
$waveAudioSwitchingSetFound = 0;
$audioSelectionSetFound = 0;
$waveSubtitleTrackFound = 0;
$waveSubtitleSwitchingSetFound = 0;
$subtitleSelectionSetFound = 0;
$hdlrType = "";
$videomediaProfileArray = array("HD", "HHD10", "UHD10", "HLG10","HDR10");
$audiomediaProfileArray = array("AAC_Core", "Adaptive_AAC_Core", "AAC_Multichannel",
                      "Enhanced_AC-3","AC-4_SingleStream","MPEG-H_SingleStream");
$subtitlemediaProfileArray = array("TTML_IMSC1_Text", "TTML_IMSC1_Image");

for ($adaptationIndex = 0; $adaptationIndex < $adaptationCount; $adaptationIndex++) {
    $switchingSetMediaProfile = array();
    $location = $session->getAdaptationDir($mpdHandler->getSelectedPeriod(), $adaptationIndex);
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
        $hdlrType = $hdlrBox->getAttribute("handler_type");
        $mediaProfileTrackResult = $this->getMediaProfile($xml, $hdlrType, $fileIndex, $adaptationIndex);
        $mediaProfileTrack = $mediaProfileTrackResult;

        //Update the MP database for future checks
        $MediaProfDatabase[$mpdHandler->getSelectedPeriod()][$adaptationIndex][$fileIndex] = $mediaProfileTrack;

        if ($hdlrType == "vide") {
            $videoSelectionSetFound = 1;
            if (in_array($mediaProfileTrack, $videomediaProfileArray)) {
                    $waveVideoTrackFound = 1;
            }
        }
        if ($hdlrType == "soun") {
            $audioSelectionSetFound = 1;
            if (in_array($mediaProfileTrack, $audiomediaProfileArray)) {
                    $waveAudioTrackFound = 1;
            }
        }
        if ($hdlrType == "subt") {
            $subtitleSelectionSetFound = 1;
            if (in_array($mediaProfileTrack, $subtitlemediaProfileArray)) {
                    $waveSubtitleTrackFound = 1;
            }
        }
        array_push($switchingSetMediaProfile, $mediaProfileTrack);
    }

    $singleMediaProfile = $logger->test(
        "CTAWAVE",
        "WAVE Content Spec 2018Ed-Section 4.1",
        "WAVE content SHALL include one or more Switching Sets conforming to at least one WAVE " .
        "approved CMAF Media Profile",
        count(array_unique($switchingSetMediaProfile)) == 1,
        "FAIL",
        "Switching Set $adaptationIndex found with only tracks conforming to Media Profile " .
        $switchingSetMediaProfile[0],
        "Switching Set $adaptationIndex found with Tracks of different Media Profiles"
    );
    if (!$singleMediaProfile) {
        continue;
    }
    if ($hdlrType === "vide" && in_array(array_unique($switchingSetMediaProfile)[0], $videomediaProfileArray)) {
        $waveVideoSwitchingSetFound = 1;
    } elseif ($hdlrType == "soun" && in_array(array_unique($switchingSetMediaProfile)[0], $audiomediaProfileArray)) {
        $waveAudioSwitchingSetFound = 1;
    } elseif ($hdlrType == "subt" && in_array(array_unique($switchingSetMediaProfile)[0], $subtitlemediaProfileArray)) {
        $waveSubtitleSwitchingSetFound = 1;
    }
}
//Check if at least one wave SwitchingSet found.
if ($videoSelectionSetFound) {
    $trackFound = $logger->test(
        "CTAWAVE",
        "WAVE Content Spec 2018Ed-Section 4.1",
        "WAVE content SHALL include one or more Switching Sets conforming to at least one WAVE " .
        "approved CMAF Media Profile",
        $waveVideoTrackFound,
        "FAIL",
        "WAVE video track found",
        "Video track found, but WAVE video track missing"
    );
    if ($trackFound) {
        $logger->test(
            "CTAWAVE",
            "WAVE Content Spec 2018Ed-Section 4.1",
            "WAVE content SHALL include one or more Switching Sets conforming to at least one WAVE " .
            "approved CMAF Media Profile",
            $waveVideoSwitchingSetFound,
            "FAIL",
            "WAVE video switching set found",
            "No WAVE video switching set found"
        );
    }
}
if ($audioSelectionSetFound) {
    $trackFound = $logger->test(
        "CTAWAVE",
        "WAVE Content Spec 2018Ed-Section 4.1",
        "WAVE content SHALL include one or more Switching Sets conforming to at least one WAVE " .
        "approved CMAF Media Profile",
        $waveAudioTrackFound,
        "FAIL",
        "WAVE audio track found",
        "Audio track found, but WAVE audio track missing"
    );
    if ($trackFound) {
        $logger->test(
            "CTAWAVE",
            "WAVE Content Spec 2018Ed-Section 4.1",
            "WAVE content SHALL include one or more Switching Sets conforming to at least one WAVE " .
            "approved CMAF Media Profile",
            $waveAudioSwitchingSetFound,
            "FAIL",
            "WAVE audio switching set found",
            "No WAVE audio switching set found"
        );
    }
}
if ($subtitleSelectionSetFound) {
    $trackFound = $logger->test(
        "CTAWAVE",
        "WAVE Content Spec 2018Ed-Section 4.1",
        "WAVE content SHALL include one or more Switching Sets conforming to at least one WAVE " .
        "approved CMAF Media Profile",
        $waveSubtitleTrackFound,
        "FAIL",
        "WAVE subtitle track found",
        "Subtitle track found, but WAVE subtitle track missing"
    );
    if ($trackFound) {
        $logger->test(
            "CTAWAVE",
            "WAVE Content Spec 2018Ed-Section 4.1",
            "WAVE content SHALL include one or more Switching Sets conforming to at least one WAVE " .
            "approved CMAF Media Profile",
            $waveSubtitleSwitchingSetFound,
            "FAIL",
            "WAVE subtitle switching set found",
            "No WAVE subtitle switching set found"
        );
    }
}
