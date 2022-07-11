<?php

global $period_timing_info;

$mdhdBox = $xmlRepresentation->getElementsByTagName('mdhd')->item(0);
$timescale = $mdhdBox->getAttribute('timescale');
$moofBoxCount = $xmlRepresentation->getElementsByTagName('moof')->length;
$totalSegmentDuration = 0;

for ($j = 0; $j < $moofBoxCount; $j++) {
    $trun = $xmlRepresentation->getElementsByTagName('trun')->item($j);
    ///\Correctness On various occasions in the code cumulated is misspelled, needs investigation
    $cummulatedSampleDuration = $trun->getAttribute('cummulatedSampleDuration');
    $segmentDuration = ( $cummulatedSampleDuration * 1.00 ) / $timescale;
    $totalSegmentDuration += $segmentDuration;
}

$periodDuration = (float)$period_timing_info[1];

$drift abs((round($totalSegmentDuration, 2) - round($periodDuration, 2)) / round($periodDuration, 2));
return [$drift <= 0.00001, round($totalSegmentDuration, 2), round($periodDuration, 2)];
