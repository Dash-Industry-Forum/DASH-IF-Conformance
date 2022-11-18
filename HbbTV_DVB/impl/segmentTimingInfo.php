<?php

global $mpdHandler;

$type = $mpdHandler->getFeatures()['type'];
$moofBoxesCount = $xmlRepresentation->getElementsByTagName('moof')->length;
$trunBoxes = $xmlRepresentation->getElementsByTagName('trun');
$tfdtBoxes = $xmlRepresentation->getElementsByTagName('tfdt');

$sidxBoxes = $xmlRepresentation->getElementsByTagName('sidx');
$subsegment_signaling = array();
if ($sidxBoxes->length) {
    foreach ($sidxBoxes as $sidxBox) {
        $subsegment_signaling[] = (int)($sidxBox->getAttribute('referenceCount'));
    }
}

$elstBoxes = $xmlRepresentation->getElementsByTagName('elst');
$mediaTime = 0;
if ($elstBoxes->length) {
    $mediaTime = (int)($elstBoxes->item(0)->getElementsByTagName('elstEntry')->item(0)->getAttribute('mediaTime'));
}

$timescale = $xmlRepresentation->getElementsByTagName('mdhd')->item(0)->getAttribute('timescale');
$sidxIndex = 0;
$cumulativeSubsegmentDuration = 0;
$EPT = array();
if ($type != 'dynamic') {
    for ($j = 0; $j < $moofBoxesCount; $j++) {
        $decodeTime = $tfdtBoxes->item($j)->getAttribute('baseMediaDecodeTime');
        $compositionTime = $trunBoxes->item($j)->getAttribute('earliestCompositionTime');

        $startTime = ($decodeTime + $compositionTime - $mediaTime) / $timescale;
        if (empty($subsegment_signaling)) {
            $EPT[] = $startTime;
        } else {
            $referenceCount = 1;
            if ($sidxIndex < sizeof($subsegment_signaling)) {
                $referenceCount = $subsegment_signaling[$sidxIndex];
            }

            if ($cumulativeSubsegmentDuration == 0) {
                $EPT[] = $startTime;
            }

            $cumulativeSubsegmentDuration += (($trunBoxes->item($j)->getAttribute('cummulatedSampleDuration')) / $timescale);
            $subsegment_signaling[$sidxIndex] = $referenceCount - 1;
            if ($subsegment_signaling[$sidxIndex] == 0) {
                $sidxIndex++;
                $cumulativeSubsegmentDuration = 0;
            }
        }
    }
}

return $EPT;
