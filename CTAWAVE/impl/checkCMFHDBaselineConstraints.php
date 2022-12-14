<?php

global $MediaProfDatabase, $session, $logger;

//Check for CMFHD presentation profile for all periods/presentations
//and then check WAVE Baseline constraints . If both are satisfied, then CMFHD Baseline Constraints are satisfied.
$periodCount = sizeof($MediaProfDatabase);
$presentationProfileArray = array();
for ($i = 0; $i < $periodCount; $i++) {
    $adaptationCount = sizeof($MediaProfDatabase[$i]);
    $presentationProfile = $this->CTACheckPresentation($adaptationCount, $i);
    array_push($presentationProfileArray, $presentationProfile);
}

$logger->test(
    "WAVE Content Spec 2018Ed",
    "Section 6.2",
    "WAVE CMFHD Baseline Program Shall contain a sequence of one or more CMAF Presentations conforming to CMAF " .
    "CMFHD profile",
    count(array_unique($presentationProfileArray)) === 1 && array_unique($presentationProfileArray)[0] == "CMFHD",
    "FAIL",
    "All CMAF Swithcing sets are CMFHD conformant",
    "Not all CMAF Swithcing sets are CMFHD conformant"
);
