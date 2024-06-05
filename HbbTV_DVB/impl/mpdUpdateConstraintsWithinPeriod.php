<?php

//$mpd
//$nextMpd
//$periodIndex
//$nextPeriodIndex

global $logger;

$spec = "TS 103 285 v1.4.1";
$section = "4.8.3 - Constraints to MPD updates";

$periodId = $mpd->getPeriodAttribute($periodIndex, 'id');

$originalAdaptations = $mpd->getAdaptationSetIds($mpd->getPeriodAttribute($periodIndex, 'id'));
$nextAdaptations = $nextMpd->getAdaptationSetIds($mpd->getPeriodAttribute($nextPeriodIndex, 'id'));

foreach ($originalAdaptations as $origIndex => $origId) {
    $logger->test(
        $spec,
        $section,
        "The attribute @id for a corresponding Representation or AdaptationSet in a corresponding " .
        "Period shall not change.",
        $origId != null,
        "FAIL",
        "Prerequisite pass: original AdaptationSet@id of $origId found",
        "Prerequisite fail: original AdaptationSet at index $origIndex does not contain an id"
    );

    if ($origId == null) {
        continue;
    }


    foreach ($nextAdaptations as $nextIndex => $nextId) {
        if ($origId != $nextId) {
            continue;
        }
        $logger->test(
            $spec,
            $section,
            "A Role Element in a corresponding Representation or AdaptationSet shall not change.",
            $mpd->getAdaptationSetAttribute($periodIndex, $origIndex, "role") ==
              $nextMpd->getAdaptationSetAttribute($nextPeriodIndex, $nextIndex, "role"),
            "FAIL",
            "Adaptation@role for AdaptationSet@id $origId within period $periodId identical",
            "Adaptation@role for AdaptationSet@id $origId within period $periodId differs"
        );
    }
}
