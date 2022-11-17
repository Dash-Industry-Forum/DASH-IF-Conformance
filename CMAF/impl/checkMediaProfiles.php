<?php

global $cmaf_mediaProfiles, $current_adaptation_set;
global $logger, $mpdHandler;

$profile1 = $cmaf_mediaProfiles[$mpdHandler->getSelectedPeriod()][$current_adaptation_set][$representation1]['cmafMediaProfile'];
$profile2 = $cmaf_mediaProfiles[$mpdHandler->getSelectedPeriod()][$current_adaptation_set][$representation2]['cmafMediaProfile'];

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

$logger->test(
    "CMAF",
    "Section 7.3.4.1",
    "All CMAF Tracks in a CMAF Switching Set SHALL conform to one CMAF Media Profile",
    $validProfile,
    "FAIL",
    "Period $mpdHandler->getSelectedPeriod(), Adaptation set $current_adaptation_set: Representation $representation1 and " .
    "$representation2 found conforming to the same profile",
    "Period $mpdHandler->getSelectedPeriod(), Adaptation set $current_adaptation_set: Representation $representation1 and " .
    "$representation2 not found conforming to the same profile ($profile1 and $profile2 respectively)"
);
