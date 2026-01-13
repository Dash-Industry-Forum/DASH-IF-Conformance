<?php

global $logger;

$codec = $mediaProfileParameters['codec'];
$sampleRate = $mediaProfileParameters['sampleRate'];
$channels = $mediaProfileParameters['channels'];
$level = $mediaProfileParameters['level'];
$profile = $mediaProfileParameters['profile'];

if ($codec == "AAC") {
    if (in_array($channels, array(1, 2))) {
        //Level is checked here , however level can not be found always from the atom xml as the
        //IODS atom is not always present in the track.
        $validLevel = $logger->test(
            "CTAWAVE",
            "WAVE Content Spec 2018Ed-Section 4.3.1",
            "Each WAVE audio Media Profile SHALL conform to normative ref. listed in Table 2",
            $level == "" || strpos($level, "AAC@L2") !== false,
            "FAIL",
            "Valid level for track $representationIndex of switching set $adaptationIndex",
            "Invalid level for track $representationIndex of switching set $adaptationIndex",
        );
        if (!$validLevel) {
            return "unknown";
        }
        $validProfile = $logger->test(
            "CTAWAVE",
            "WAVE Content Spec 2018Ed-Section 4.3.1",
            "Each WAVE audio Media Profile SHALL conform to normative ref. listed in Table 2",
            in_array($profile, array(2, 5, 29)),
            "FAIL",
            "Valid profile for track $representationIndex of switching set $adaptationIndex",
            "Invalid profile for track $representationIndex of switching set $adaptationIndex",
        );
        if (!$validProfile) {
            return "unknown";
        }

        if ($mediaProfileParameters["brand"] == "caaa") {
            return "Adaptive_AAC_Core";
        }
        return "AAC_Core";
    }

    if (in_array($channels, array(5,6,7,12,14))) {
        $validProfile = $logger->test(
            "CTAWAVE",
            "WAVE Content Spec 2018Ed-Section 4.3.1",
            "Each WAVE audio Media Profile SHALL conform to normative ref. listed in Table 2",
            $profile == 5 || ($profile == 2 && $level == "High Quality Audio@L6"),
            "FAIL",
            "Valid profile for track $representationIndex of switching set $adaptationIndex",
            "Invalid profile for track $representationIndex of switching set $adaptationIndex",
        );
        if (!$validProfile) {
            return "unknown";
        }
        return "AAC_Multichannel";
    }
    //Found invalid channels config
    $logger->test(
        "CTAWAVE",
        "WAVE Content Spec 2018Ed-Section 4.3.1",
        "Each WAVE audio Media Profile SHALL conform to normative ref. listed in Table 2",
        false,
        "FAIL",
        "",
        "Invalid channels for track $representationIndex of switching set $adaptationIndex",
    );
    return "unknown";
}

if ($codec == "EAC-3" || $codec == "AC-3") {
    return "Enhanced_AC-3";
}
if ($codec == "AC-4") {
    $validLevel = $logger->test(
        "CTAWAVE",
        "WAVE Content Spec 2018Ed-Section 4.3.1",
        "Each WAVE audio Media Profile SHALL conform to normative ref. listed in Table 2",
        $level == 3,
        "FAIL",
        "Valid level for track $representationIndex of switching set $adaptationIndex",
        "Invalid level for track $representationIndex of switching set $adaptationIndex",
    );
    if (!$validLevel) {
        return "unknown";
    }
    return "AC-4_SingleStream";
}

if ($codec == "MPEG-H") {
    $validSampleRate = $logger->test(
        "CTAWAVE",
        "WAVE Content Spec 2018Ed-Section 4.3.1",
        "Each WAVE audio Media Profile SHALL conform to normative ref. listed in Table 2",
        $sampleRate <= 48000,
        "FAIL",
        "Valid sample rate for track $representationIndex of switching set $adaptationIndex",
        "Invalid sample rate for track $representationIndex of switching set $adaptationIndex",
    );
    if (!$validSampleRate) {
        return "unknown";
    }

    $validProfile = $logger->test(
        "CTAWAVE",
        "WAVE Content Spec 2018Ed-Section 4.3.1",
        "Each WAVE audio Media Profile SHALL conform to normative ref. listed in Table 2",
        in_array($profile, array("11","12","13")),
        "FAIL",
        "Valid profile for track $representationIndex of switching set $adaptationIndex",
        "Invalid profile for track $representationIndex of switching set $adaptationIndex",
    );
    if (!$validProfile) {
        return "unknown";
    }

    $validChannels = $logger->test(
        "CTAWAVE",
        "WAVE Content Spec 2018Ed-Section 4.3.1",
        "Each WAVE audio Media Profile SHALL conform to normative ref. listed in Table 2",
        in_array($channels, array("1","2","3","4","5","6","7","9","10","11","12","14","15","16","17","19")),
        "FAIL",
        "Valid channels for track $representationIndex of switching set $adaptationIndex",
        "Invalid channels for track $representationIndex of switching set $adaptationIndex",
    );
    if (!$validChannels) {
        return "unknown";
    }

    return "MPEG-H_SingleStream";
}

//Found invalid codec config
$logger->test(
    "CTAWAVE",
    "WAVE Content Spec 2018Ed-Section 4.3.1",
    "Each WAVE audio Media Profile SHALL conform to normative ref. listed in Table 2",
    false,
    "FAIL",
    "",
    "Invalid codecfor track $representationIndex of switching set $adaptationIndex",
);
return "unknown";
