<?php

global $logger;

$sizes = $representation->getSegmentSizes();
$durations = $representation->getSegmentDurations();

$spec = "CTA-5005-A";
$section = "4.1.2 - Basic On-Demand and Live Streaming";
$bitrateExplanation = "For presentations presented in an on-demand environment: The Average Bitrate of a CMAF " .
  "Fragment within a CMAF Track SHOULD be within 10% of the Average Bitrate calculated over the full duration " .
  "of the Track.";

if (!count($sizes) || !count($durations)) {
    $logger->test(
        $spec,
        $section,
        $bitrateExplanation,
        false,
        "FAIL",
        "",
        "No segment sizes or no segment durations could be found for " . $representation->getPrintable()
    );
    return;
}


if (count($sizes) != count($durations)) {
    $logger->test(
        $spec,
        $section,
        $bitrateExplanation,
        false,
        "WARN",
        "",
        "Segment sizes and durations do not match for " . $representation->getPrintable()
    );
    return;
}

$count = count($sizes);

$bitrates = array();
$totalDuration = 0;
$totalSize = 0;

for ($i = 0; $i < $count; $i++) {
    $totalDuration += $durations[$i];
    $totalSize += $sizes[$i];
    if ($durations[$i] == 0) {
        continue;
    }
    $bitrates[] = $sizes[$i] / $durations[$i];
}

if (count($bitrates) != $count) {
    $logger->test(
        $spec,
        $section,
        $bitrateExplanation,
        false,
        "WARN",
        "",
        "Not all bitrates could be determined for " . $representation->getPrintable()
    );
}

$totalBitrate = 0;
if ($totalDuration) {
    $totalBitrate = $totalSize / $totalDuration;
}
$upperLimit = $totalBitrate * 1.1;
$lowerLimit = $totalBitrate * 0.9;

$allBitratesValid = true;
foreach ($bitrates as $bitrate) {
    if ($bitrate > $upperLimit || $bitrate < $lowerLimit) {
        $allBitratesValid = false;
    }
}

$logger->test(
    $spec,
    $section,
    $bitrateExplanation,
    $allBitratesValid,
    "FAIL",
    "All bitrates within bounds for " . $representation->getPrintable(),
    "Not all bitrates withing bounds for " . $representation->getPrintable()
);
