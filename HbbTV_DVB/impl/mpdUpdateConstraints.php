<?php

global $logger;

$spec = "TS 103 285 v1.4.1";
$section = "4.8.3 - Constraints to MPD updates";

$features = $mpd->getFeatures();
$nextFeatures = $nextMpd->getFeatures();

$logger->test(
    $spec,
    $section,
    "MPD update is succesful",
    $features && $nextFeatures,
    "FAIL",
    "We have valid features on both original and updated MPD",
    "Either the original or updated MPD is invalid"
);
if (!$features || !$nextFeatures) {
    return;
}


$logger->test(
    $spec,
    $section,
    "The attribute MPD@timeShiftBufferDepth shall not change.",
    $mpd->getFeature("timeShiftBufferDepth") == $nextMpd->getFeature("timeShiftBufferDepth"),
    "FAIL",
    "MPD@timeShiftBufferDepth did not change",
    "MPD@timeShiftBufferDepth did change (" . $mpd->getFeature("timeShiftBufferDepth") . " -> " .
    $nextMpd->getFeature("timeShiftBufferDepth") . ")"
);

$logger->test(
    $spec,
    $section,
    "The attribute MPD@availabilityStartTime shall not change.",
    $mpd->getFeature("availabilityStartTime") == $nextMpd->getFeature("availabilityStartTime"),
    "FAIL",
    "MPD@availabilityStartTime did not change",
    "MPD@availabilityStartTime did change (" . $mpd->getFeature("availabilityStartTime") . " -> " .
    $nextMpd->getFeature("availabilityStartTime") . ")"
);

$logger->test(
    $spec,
    $section,
    "The attribute MPD@maxSegmentDuration shall not change to a larger duration.",
    $mpd->getFeature("maxSegmentDuration") <= $nextMpd->getFeature("maxSegmentDuration"),
    "FAIL",
    "Next MPD@maxSegmentDuration smaller or equal",
    "MPD@maxSegmentDuration increased (" . $mpd->getFeature("maxSegmentDuration") . " -> " .
    $nextMpd->getFeature("maxSegmentDuration") . ")"
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

        $this->mpdUpdateConstraintsWithinPeriod($mpd, $nextMpd, $origIndex, $nextIndex);
    }
}
