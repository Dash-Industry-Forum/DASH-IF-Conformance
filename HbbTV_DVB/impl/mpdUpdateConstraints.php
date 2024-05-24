<?php

global $logger;

$spec = "TS 103 285 v1.4.1";
$section = "4.8.3 - Constraints to MPD updates";

$features = $mpd->getFeatures();
$nextFeatures = $nextMpd->getFeatures();

$logger->test(
    $spec,
    $section,
    "The attribute MPD@timeShiftBufferDepth shall not change.",
    $features["timeShiftBufferDepth"] == $nextFeatures["timeShiftBufferDepth"],
    "FAIL",
    "MPD@timeShiftBufferDepth did not change",
    "MPD@timeShiftBufferDepth did change (" . $features["timeShiftBufferDepth"] . " -> " .
      $nextFeatures["timeShiftBufferDepth"] . ")"
);

$logger->test(
    $spec,
    $section,
    "The attribute MPD@availabilityStartTime shall not change.",
    $features["availabilityStartTime"] == $nextFeatures["availabilityStartTime"],
    "FAIL",
    "MPD@availabilityStartTime did not change",
    "MPD@availabilityStartTime did change (" . $features["availabilityStartTime"] . " -> " .
      $nextFeatures["availabilityStartTime"] . ")"
);
