<?php

global $mpdHandler, $logger;

$selectedPeriod = $mpdHandler->getSelectedPeriod();
$selectedAdaptation = $mpdHandler->getSelectedAdaptationSet();
$selectedRepresentation = $mpdHandler->getSelectedRepresentation();

if (
    strpos(
        $mpdHandler->getProfiles[$selectedPeriod][$selectedAdaptation][$selectedRepresentation],
        'http://dashif.org/guidelines/dash-if-ondemand'
    ) === false
) {
    return;
}

$sidxBoxes = $xml->getElementsByTagName('sidx');

$logger->test(
    "DASH-IF IOP 4.3",
    "Section 3.10.3.2",
    "Only a single 'sidx' SHALL be present",
    $sidxBoxes->length == 1,
    "FAIL",
    "Exactly one 'sidx' box found for Period $selectedPeriod Adaptation Set $selectedAdaptation " .
    "Representation $selectedRepresentation",
    $sidxBoxes->length . " 'sidx' boxes found for Period $selectedPeriod Adaptation Set $selectedAdaptation " .
    "Representation $selectedRepresentation"
);

$repDir = $session->getSelectedRepresentationDir();
$fileName = "$repDir/assemblerInfo.txt";

if (!($selfInitializingSegmentFile = fopen($fileName, 'r'))) {
    return;
}

$selfInitializingSegmentFound = false;
$numSegments = 0;
$line = fgets($selfInitializingSegmentFile);
while ($line !== false) {
    $line_info = explode(' ', $line);

    $numSegments++;
    ///\Correctness This value gets overwritten for every line in $selfInitializingSegmentFile
    $selfInitializingSegmentFound = ($numSegments == 1 && $line_info[1] > 0) ? true : false;

    $line = fgets($selfInitializingSegmentFile);
}

$segment_count = count(glob("$repDir/*")) -
  count(glob("$repDir/*", GLOB_ONLYDIR));


$logger->test(
    "DASH-IF IOP 4.3",
    "Section 3.10.3.2",
    "Each Representation SHALL have one Segment that complies with Indexed Self-Initializing Media Segment",
    $selfInitializingSegmentFound && $segment_count == 1,
    "FAIL",
    "Check succesful",
    "found $segment_count Segment(s) and Indexed Self-Initializing Media Segment to be " .
    "$selfInitializingSegmentFound."
);
