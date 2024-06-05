<?php

//$mpd
//$nextMpd
//$periodIndex
//$nextPeriodIndex

global $logger;

$spec = "TS 103 285 v1.4.1";
$section = "4.8.3 - Constraints to MPD updates";

$shallNotChangeInPeriod = "for a corresponding Representation or AdaptationSet in a Period shall not change.";

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
            "A Role Element " . $shallNotChangeInPeriod,
            $mpd->getAdaptationSetAttribute($periodIndex, $origIndex, "Role") ==
              $nextMpd->getAdaptationSetAttribute($nextPeriodIndex, $nextIndex, "Role"),
            "FAIL",
            "Role for AdaptationSet@id $origId within period $periodId identical",
            "Role for AdaptationSet@id $origId within period $periodId differs"
        );
        $logger->test(
            $spec,
            $section,
            "An AudioConfiguration Element " . $shallNotChangeInPeriod,
            $mpd->getAdaptationSetAttribute($periodIndex, $origIndex, "AudioConfiguration") ==
              $nextMpd->getAdaptationSetAttribute($nextPeriodIndex, $nextIndex, "AudioConfiguration"),
            "FAIL",
            "AudioConfiguration for AdaptationSet@id $origId within period $periodId identical",
            "AudioConfiguration for AdaptationSet@id $origId within period $periodId differs"
        );
        $logger->test(
            $spec,
            $section,
            "The attribute @contentType " . $shallNotChangeInPeriod,
            $mpd->getAdaptationSetAttribute($periodIndex, $origIndex, "contentType") ==
            $nextMpd->getAdaptationSetAttribute($nextPeriodIndex, $nextIndex, "contentType"),
            "FAIL",
            "@contentType for AdaptationSet@id $origId within period $periodId identical",
            "@contentType for AdaptationSet@id $origId within period $periodId differs"
        );
        $logger->test(
            $spec,
            $section,
            "The attribute @codecs " . $shallNotChangeInPeriod,
            $mpd->getAdaptationSetAttribute($periodIndex, $origIndex, "codecs") ==
            $nextMpd->getAdaptationSetAttribute($nextPeriodIndex, $nextIndex, "codecs"),
            "FAIL",
            "@codecs for AdaptationSet@id $origId within period $periodId identical",
            "@codecs for AdaptationSet@id $origId within period $periodId differs"
        );
        $logger->test(
            $spec,
            $section,
            "The attribute @lang " . $shallNotChangeInPeriod,
            $mpd->getAdaptationSetAttribute($periodIndex, $origIndex, "lang") ==
            $nextMpd->getAdaptationSetAttribute($nextPeriodIndex, $nextIndex, "lang"),
            "FAIL",
            "@lang for AdaptationSet@id $origId within period $periodId identical",
            "@lang for AdaptationSet@id $origId within period $periodId differs"
        );
    }
}
