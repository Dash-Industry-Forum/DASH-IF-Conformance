<?php

global $profiles, $mpdHandler, $current_adaptation_set, $current_representation, $logger;

if (
    strpos(
        $profiles[$mpdHandler->getSelectedPeriod()][$current_adaptation_set][$current_representation],
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
    "Exactly one 'sidx' box found for Period $mpdHandler->getSelectedPeriod() Adaptation Set $current_adaptation_set " .
    "Representation $current_representation",
    $sidxBoxes->length . " 'sidx' boxes found for Period $mpdHandler->getSelectedPeriod() Adaptation Set $current_adaptation_set " .
    "Representation $current_representation"
);

$repDir = $session->getRepresentationDir($mpdHandler->getSelectedPeriod(), $current_adaptation_set, $current_representation);
///\RefactorTodo Check where this file should come from.
$fileName = "$repDir/representation.txt";

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
