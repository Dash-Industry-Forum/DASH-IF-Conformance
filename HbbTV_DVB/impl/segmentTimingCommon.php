<?php

global $mpdHandler, $logger;

$moofBoxesCount = $xmlRepresentation->getElementsByTagName('moof')->length;
$trunBoxes = $xmlRepresentation->getElementsByTagName('trun');
$tfdtBoxes = $xmlRepresentation->getElementsByTagName('tfdt');

## Consistency check of the start times within the segments with the timing indicated by the MPD
// MPD information
$mpdTiming = $this->mpdTimingInfo();

// Segment information
$type = $mpdHandler->getFeatures()['type'];

$sidxBoxes = $xmlRepresentation->getElementsByTagName('sidx');
$subsegmentSignaling = array();
if ($sidxBoxes->length != 0) {
    foreach ($sidxBoxes as $sidxBox) {
        $subsegmentSignaling[] = (int)($sidxBox->getAttribute('referenceCount'));
    }
}

$mediaTime = 0;
$elstBoxes = $xmlRepresentation->getElementsByTagName('elst');
if ($elstBoxes->length > 0) {
    $mediaTime = (int)($elstBoxes->item(0)->getElementsByTagName('elstEntry')->item(0)->getAttribute('mediaTime'));
}

$timescale = $xmlRepresentation->getElementsByTagName('mdhd')->item(0)->getAttribute('timescale');
$sidxIndex = 0;
$cumulativeSubsegmentDuration = 0;

for ($j = 0; $j < $moofBoxesCount; $j++) {
    ## Checking for gaps
    if ($j > 0) {
        $cummulatedSampleDurFragPrev = $trunBoxes->item($j - 1)->getAttribute('cummulatedSampleDuration');
        $previousFragmentDecodeTime = $tfdtBoxes->item($j - 1)->getAttribute('baseMediaDecodeTime');
        $currentFragmentDecodeTime = $tfdtBoxes->item($j)->getAttribute('baseMediaDecodeTime');

        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "Section 'Segments'",
            "Representations SHALL not contain gaps between the segment timings",
            $currentFragmentDecodeTime == $previousFragmentDecodeTime + $cummulatedSampleDurFragPrev,
            "FAIL",
            "No gap between segment $j and its predecessor",
            "Gap found between segment $j and its predecessor",
        );
    }
    ##
    //
    //Empty means that there is no mediaPresentationDuration attribute in which case
    //the media presentation duration is unknown.
    if (!empty($mpdTiming) && $type != 'dynamic') {
        $decodeTime = $tfdtBoxes->item($j)->getAttribute('baseMediaDecodeTime');
        $compositionTime = $trunBoxes->item($j)->getAttribute('earliestCompositionTime');

        $segmentTime = ($decodeTime + $compositionTime - $mediaTime) / $timescale;

        if (empty($subsegmentSignaling)) {
            if ($j < sizeof($mpdTiming)) {
                $logger->test(
                    "HbbTV-DVB DASH Validation Requirements",
                    "Section 'Segments'",
                    "Timing SHALL be consistent with the MPD",
                    abs(($segmentTime - $mpdTiming[$j]) / $mpdTiming[$j]) <= 0.00001,
                    "FAIL",
                    "Start time of segment $j is consistent with the MPD",
                    "Start time of segment $j is not consistent with the MPD"
                );
            }
        } else {
            $referenceCount = 1;
            if ($sidxIndex < sizeof($subsegmentSignaling)) {
                $referenceCount = $subsegmentSignaling[$sidxIndex];
            }

            if ($cumulativeSubsegmentDuration == 0 && $sidxIndex < sizeof($mpdTiming)) {
                $logger->test(
                    "HbbTV-DVB DASH Validation Requirements",
                    "Section 'Segments'",
                    "Timing SHALL be consistent with the MPD",
                    $mpdTiming[$sidxIndex] == 0 ||
                    (abs(($segmentTime - $mpdTiming[$sidxIndex]) / $mpdTiming[$sidxIndex]) <= 0.00001),
                    "FAIL",
                    "Start time of segment $sidxIndex is consistent with the MPD",
                    "Start time of segment $sidxIndex is not consistent with the MPD"
                );
            }

            $cummulatedSampleDuration = $trunBoxes->item($j)->getAttribute('cummulatedSampleDuration');
            $segmentDuration = $cummulatedSampleDuration / $timescale;
            $cumulativeSubsegmentDuration += $segmentDuration;
            $subsegmentSignaling[$sidxIndex] = $referenceCount - 1;
            if ($subsegmentSignaling[$sidxIndex] == 0) {
                $sidxIndex++;
                $cumulativeSubsegmentDuration = 0;
            }
        }
    }
}
##
