<?php

global $logger, $mpdHandler;

$profile1 = $this_>mediaProfiles[$mpdHandler->getSelectedPeriod()][$mpdHandler->getSelectedAdaptationSet()][$representation1]['cmafMediaProfile'];
$profile2 = $this->mediaProfiles[$mpdHandler->getSelectedPeriod()][$mpdHandler->getSelectedAdaptationSet()][$representation2]['cmafMediaProfile'];

$validProfile = true;
if ($profile1 != $profile2) {
    $validProfile = false;
}
if ($profile1 == 'unknown') {
    $validProfile = false;
}
if (strpos($profile1, $profile2) === false || strpos($profile2, $profile1) === false) {
    $validProfile = false;
}

$selectedPeriod = $mpdHandler->getSelectedPeriod();
$selectedAdaptation = $mpdHandler->getSelectedAdaptationSet();

$logger->test(
    "CMAF",
    "Section 7.3.4.1",
    "All CMAF Tracks in a CMAF Switching Set SHALL conform to one CMAF Media Profile",
    $validProfile,
    "FAIL",
    "Period $selectedPeriod, Adaptation set $selectedAdaptation: Representation $representation1 and " .
    "$representation2 found conforming to the same profile",
    "Period $selectedPeriod, Adaptation set $selectedAdaptation: Representation $representation1 and " .
    "$representation2 not found conforming to the same profile ($profile1 and $profile2 respectively)"
);
