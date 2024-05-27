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

$logger->test(
    $spec,
    $section,
    "The attribute MPD@maxSegmentDuration shall not change to a larger duration.",
    $features["maxSegmentDuration"] >= $nextFeatures["maxSegmentDuration"],
    "FAIL",
    "Next MPD@maxSegmentDuration smaller or equal",
    "MPD@maxSegmentDuration increased (" . $features["maxSegmentDuration"] . " -> " .
      $nextFeatures["maxSegmentDuration"] . ")"
);

$originalPeriods = $mpd->getPeriodIds();
$nextPeriods = $nextMpd->getPeriodIds();

foreach ($originalPeriods as $origIndex => $origId) {
    $logger->test(
        $spec,
        $section,
        "Period@id and @start for the same period and when MPD@type='dynamic' shall not change.",
        $origId != null,
        "FAIL",
        "Prerequisite pass: original Period@id of $origId found",
        "Prerequisite fail: original Period at index $origIndex does not contain an id"
    );

    if ($origId == null) {
        continue;
    }


    foreach ($nextPeriods as $nextIndex => $nextId) {
        if ($origId != $nextId) {
            continue;
        }
        $logger->test(
            $spec,
            $section,
            "Period@id and @start for the same period and when MPD@type='dynamic' shall not change.",
            $mpd->getPeriodAttribute($origIndex, "start") ==
              $nextMpd->getPeriodAttribute($nextIndex, "start"),
            "FAIL",
            "Period@start for Period@id $origId identical",
            "Period@start for Period@id $origId differs"
        );

        $logger->test(
            $spec,
            $section,
            "The attribute Period@AssetIdentifier shall not change for a corresponding Period element",
            $mpd->getPeriodAttribute($origIndex, "AssetIdentifier") ==
              $nextMpd->getPeriodAttribute($nextIndex, "AssetIdentifier"),
            "FAIL",
            "Period@AssetIdentifier for Period@id $origId identical",
            "Period@AssetIdentifier for Period@id $origId differs"
        );
    }
}
