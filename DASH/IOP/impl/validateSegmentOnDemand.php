<?php

global $session_dir, $profiles, $current_period, $current_adaptation_set, $current_representation,
$reprsentation_template, $logger;

if (
    strpos(
        $profiles[$current_period][$current_adaptation_set][$current_representation],
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
    "Exactly one 'sidx' box found for Period $current_period Adaptation Set $current_adaptation_set " .
    "Representation $current_representation",
    $sidxBoxes->length . " 'sidx' boxes found for Period $current_period Adaptation Set $current_adaptation_set " .
    "Representation $current_representation"
);

///\Discuss Does all the code below does what it should do?
$rep_dir_name = str_replace(
    array('$AS$', '$R$'),
    array($current_adaptation_set, $current_representation),
    $reprsentation_template
);

if (
    !($selfInitializingSegmentFile =
    open_file($session_dir . '/Period' . $current_period . '/' . $rep_dir_name . '.txt', 'r'))
) {
    echo "Error opening file: " . "$session_dir.'/'.$rep_dir_name" . '.txt';
    return;
}

$self_initializing_segment_found = false;
$numSegments = 0;
$line = fgets($selfInitializingSegmentFile);
while ($line !== false) {
    $line_info = explode(' ', $line);

    $numSegments++;
    ///\Discuss This value gets overwritten for every line in $selfInitializingSegmentFile
    $self_initializing_segment_found = ($numSegments == 1 && $line_info[1] > 0) ? true : false;

    $line = fgets($selfInitializingSegmentFile);
}

$segment_count = count(glob($session_dir . '/' . $rep_dir_name . '/*')) -
  count(glob($session_dir . '/' . $rep_dir_name . '/*', GLOB_ONLYDIR));


$logger->test(
    "DASH-IF IOP 4.3",
    "Section 3.10.3.2",
    "Each Representation SHALL have one Segment that complies with Indexed Self-Initializing Media Segment",
    $self_initializing_segment_found && $segment_count == 1
    "FAIL",
    "Check succesful",
    "found $segment_count Segment(s) and Indexed Self-Initializing Media Segment to be " .
    "$self_initializing_segment_found."
);
