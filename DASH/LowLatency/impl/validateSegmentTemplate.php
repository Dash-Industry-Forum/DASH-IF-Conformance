<?php

global $mpdHandler;


$isSegmentStarts = $infoFileAdaptiation[$representationId]['isSegmentStart'];
$presStarts = $infoFileAdaptation[$representationId]['PresStart'];

$segmentIndexes = array_keys($isSegmentStarts, '1');
$segmentCount = sizeof($segmentIndexes);

$duration = $segmentAccessRepresentation['duration'];
$timescale = ($segmentAccessRepresentation['timescale'] ? $segmentAccessRepresentation['timescale'] : 1);

$presStartFirst = $presStarts[$segmentIndexes[0]] * $timescale;

for ($i = 1; $i < $segmentCount; $i++) {
    $presStartElem = $presStarts[$segmentIndexes[$i]] * $timescale;
    $diff = $presStartElem - $presStartFirst;
    $lowerBound = (($i - 1) + 0.5) * $duration;
    $upperBound = ($i + 0.5) * $duration;

    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
        "If @duration attribute is present then for every CMAF Fragment the tolerance for the earliest presentation " .
        "time of the CMAF Fragment relative to the earliest presentation time of the first CMAF Fragment SHALL not " .
        "exceed 50%",
        $diff >= $lowerBound && $diff <= $upperBound,
        "FAIL",
        "Tolererance valid for Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1) . "Segment $i",
        "Tolererance exceeded for Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1) . "Segment $i",
    );
}
