<?php

global $session, $mpd_features, $current_period, $current_adaptation_set, $current_representation,
$sizearray, $segment_duration_array;

$bandwidth = $mpd_features['Period'][$current_period]
                          ['AdaptationSet'][$current_adaptation_set]
                          ['Representation'][$current_representation]
                          ['bandwidth'];

$sidxBoxes = $xmlRepresentation->getElementsByTagName('sidx');
$subsegmentSignaling = array();
if ($sidxBoxes->length != 0) {
    foreach ($sidxBoxes as $sidxBox) {
        $subsegmentSignaling[] = (int)($sidxBox->getAttribute('referenceCount'));
    }
}

$timescale = $xmlRepresentation->getElementsByTagName('mdhd')->item(0)->getAttribute('timescale');
$moofBoxCount = $xmlRepresentation->getElementsByTagName('moof')->length;
$bitrateInfo = '';
$segment_duration_array = array();
$sidxIndex = 0;
$cumulativeSubsegmentDuration = 0;

// Here 2 possible cases are considered for sidx -subsegment signalling.
if (empty($subsegmentSignaling)) {
    //First case is for no sidx box.
    for ($j = 0; $j < $moofBoxCount; $j++) {
        $cummulatedSampleDuration = $xmlRepresentation->getElementsByTagName('trun')->item($j)
                                                      ->getAttribute('cummulatedSampleDuration');
        $segmentDuration = $cummulatedSampleDuration / $timescale;
        $segmentSize = $sizearray[$j];
        $segment_duration_array[] = round($segmentDuration, 2);
        $bitrateInfo .= (string)($segmentSize * 8 / $segmentDuration) . ',';
    }
} else {
    //Secondly, sidx exists with non-zero reference counts-
    //  1) all segments have subsegments (referenced by some sidx boxes)
    //  2) only some segments have subsegments.
    for ($j = 0; $j < $moofBoxCount; $j++) {
        if ($sidxIndex > sizeof($subsegmentSignaling) - 1) {
            $ref_count = 1;// This for case 2 of case 2.
        } else {
            $ref_count = $subsegmentSignaling[$sidxIndex];
        }

        $cummulatedSampleDuration = $xmlRepresentation->getElementsByTagName('trun')->item($j)
                                                      ->getAttribute('cummulatedSampleDuration');
        $segmentDuration = $cummulatedSampleDuration / $timescale;
        $cumulativeSubsegmentDuration += $segmentDuration;

        $subsegmentSignaling[$sidxIndex] = $ref_count - 1;
        if ($subsegmentSignaling[$sidxIndex] == 0) {
            $segmentSize = $sizearray[$sidxIndex];
            $bitrateInfo = $bitrateInfo . (string)($segmentSize * 8 / $cumulativeSubsegmentDuration) . ',';
            $segment_duration_array[] = round($cumulativeSubsegmentDuration);
            $sidxIndex++;
            $cumulativeSubsegmentDuration = 0;
        }
    }
}

$sessionDir = $session->getDir();
$bitrateInfo = substr($bitrateInfo, 0, strlen($bitrateInfo) - 2);
$location = $session->getRepresentationDir($current_period, $current_adaptation_set, $current_representation) .
  'bitrateReport.png';

$command = "cd $sessionDir && python bitratereport.py $bitrateInfo $bandwidth $location";
///\RefactorTodo Eliminate Python
//exec($command);
//chmod($location, 777);

return $location;
