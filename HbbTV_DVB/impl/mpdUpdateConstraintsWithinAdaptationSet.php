<?php

//$mpd
//$nextMpd
//$periodIndex
//$nextPeriodIndex
//$adaptationIndex
//$nextAdaptationIndex

global $logger;

$spec = "TS 103 285 v1.4.1";
$section = "4.8.3 - Constraints to MPD updates";

$shallNotChangeInPeriod = "for a corresponding Representation or AdaptationSet in a Period shall not change.";

$periodId = $mpd->getPeriodAttribute($periodIndex, 'id');
$nextPeriodId = $nextMpd->getPeriodAttribute($nextPeriodIndex, 'id');

$adaptationId = $mpd->getAdaptationSetAttribute($periodIndex, $adaptationIndex, 'id');
$nextAdaptationId = $nextMpd->getAdaptationSetAttribute($nextPeriodIndex, $nextAdaptationIndex, 'id');

$originalRepresentations = $mpd->getRepresentationIds($periodId, $adaptationId);
$nextRepresentations = $nextMpd->getRepresentationIds($nextPeriodId, $nextAdaptationId);

$identicalMsg = "within period $periodId and adaptation $adaptationId identical";
$differentMsg = "within period $periodId and adaptation $adaptationId differs";

foreach ($originalRepresentations as $origIndex => $origId) {
    $logger->test(
        $spec,
        $section,
        "The attribute @id for a corresponding Representation or AdaptationSet in a corresponding " .
        "Period shall not change.",
        $origId != null,
        "FAIL",
        "Prerequisite pass: original Representation@id of $origId found",
        "Prerequisite fail: original Representation at index $origIndex does not contain an id"
    );

    if ($origId == null) {
        continue;
    }


    foreach ($nextRepresentations as $nextIndex => $nextId) {
        if ($origId != $nextId) {
            continue;
        }
        $logger->test(
            $spec,
            $section,
            "A Role Element " . $shallNotChangeInPeriod,
            $mpd->getRepresentationAttribute(
                $periodIndex,
                $adaptationIndex,
                $origIndex,
                "Role"
            ) ==
            $nextMpd->getRepresentationAttribute(
                $nextPeriodIndex,
                $nextAdaptationIndex,
                $nextIndex,
                "Role"
            ),
            "FAIL",
            "Role for Representation@id $origId $identicalMsg",
            "Role for Representation@id $origId $differentMsg"
        );
        $logger->test(
            $spec,
            $section,
            "An AudioConfiguration Element " . $shallNotChangeInPeriod,
            $mpd->getRepresentationAttribute(
                $periodIndex,
                $adaptationIndex,
                $origIndex,
                "AudioConfiguration"
            ) ==
            $nextMpd->getRepresentationAttribute(
                $nextPeriodIndex,
                $nextAdaptationIndex,
                $nextIndex,
                "AudioConfiguration"
            ),
            "FAIL",
            "AudioConfiguration for Representation@id $origId $identicalMsg",
            "AudioConfiguration for Representation@id $origId $differentMsg"
        );
        $logger->test(
            $spec,
            $section,
            "The attribute @contentType " . $shallNotChangeInPeriod,
            $mpd->getRepresentationAttribute(
                $periodIndex,
                $adaptationIndex,
                $origIndex,
                "contentType"
            ) ==
            $nextMpd->getRepresentationAttribute(
                $nextPeriodIndex,
                $nextAdaptationIndex,
                $nextIndex,
                "contentType"
            ),
            "FAIL",
            "@contentType for Representation@id $origId $identicalMsg",
            "@contentType for Representation@id $origId $differentMsg"
        );
        $logger->test(
            $spec,
            $section,
            "The attribute @codecs " . $shallNotChangeInPeriod,
            $mpd->getRepresentationAttribute(
                $periodIndex,
                $adaptationIndex,
                $origIndex,
                "codecs"
            ) ==
            $nextMpd->getRepresentationAttribute(
                $nextPeriodIndex,
                $nextAdaptationIndex,
                $nextIndex,
                "codecs"
            ),
            "FAIL",
            "@codecs for Representation@id $origId $identicalMsg",
            "@codecs for Representation@id $origId $differentMsg"
        );
        $logger->test(
            $spec,
            $section,
            "The attribute @lang " . $shallNotChangeInPeriod,
            $mpd->getRepresentationAttribute(
                $periodIndex,
                $adaptationIndex,
                $origIndex,
                "lang"
            ) ==
            $nextMpd->getRepresentationAttribute(
                $nextPeriodIndex,
                $nextAdaptationIndex,
                $nextIndex,
                "lang"
            ),
            "FAIL",
            "@lang for Representation@id $origId $identicalMsg",
            "@lang for Representation@id $origId $differentMsg"
        );
    }
}
