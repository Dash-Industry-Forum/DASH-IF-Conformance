<?php

global $session, $mpdHandler;

$repDir = $session->getRepresentationDir($mpdHandler->getSelectedPeriod(), $adaptationSetId, $representationId);
///\RefactorTodo look where this file should come from
if (!($representationInformationFile = fopen("$repDir/representation.txt", 'r'))) {
    return;
}

$selfInitializingSegmentFound = false;
$numSegments = 0;
$line = fgets($representionInformationFile);
while ($line !== false) {
    $lineInfo = explode(' ', $line);

    $numSegments++;
    $selfInitializingSegmentFound = ($numSegments == 1 && $lineInfo[1] > 0) ? true : false;

    $line = fgets($representionInformationFile);
}

//This seems contradicting, but adheres to previously existing logic
if (!$selfInitializingSegmentFound) {
    return true;
}

$returnValue = true;
//This entire function has only "PASS" checks, as validation is done based on the return value.
//Every check is in essence a "FAIL" check, but there are two valid options in the outer scope.
//Therefore, we store the combined result of all tests, and we return whether all succeeded.

$initialTestCount = $logger->testCountCurrentHook();

$sidxBoxes = $xml->getElementsByTagName('sidx');
$moofBoxes = $xml->getElementsByTagName('moof');

$returnValue = $logger->test(
    "DASH-IF IOP CR Low Latency Live",
    "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
    "If the media is contained in a Self-Initializing Segment, " .
    "then exactly one sidx box SHALL be used",
    $sidxBoxes->length == 1,
    "PASS",
    "One sidx box in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' . ($adaptationSetId + 1) .
    ' Representation ' . ($representationId + 1),
    "Multiple sidx boxes in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' . ($adaptationSetId + 1) .
    ' Representation ' . ($representationId + 1)
) && $returnValue;

if ($sidx_boxes->length == 0) {
    return $returnValue;
}


$sidxBox0 = $sidxBoxes->item(0);
$moofBox0 = $moofBoxes->item(0);

$returnValue = $logger->test(
    "DASH-IF IOP CR Low Latency Live",
    "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
    "If the media is contained in a Self-Initializing Segment, " .
    "then sidx box SHALL be placed before any moof boxes",
    $sidxBox0->getAttribute('offset') <= $moofBox0->getAttribute('offset'),
    "PASS",
    "sidx box before moof in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' . ($adaptationSetId + 1) .
    ' Representation ' . ($representationId + 1),
    "sidx box not before first moof in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' . ($adaptationSetId + 1) .
    ' Representation ' . ($representationId + 1)
) && $returnValue;

$referenceID = $sidxBox0->getAttribute('referenceID');
$trackID = $xml->getElementsByTagName('tkhd')->item(0)->getAttribute('trackID');

$returnValue = $logger->test(
    "DASH-IF IOP CR Low Latency Live",
    "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
    "If the media is contained in a Self-Initializing Segment, " .
    "then sidx box reference_ID SHALL be the trackID of the CMAF track",
    $referenceID == $trackID,
    "PASS",
    "sidx box reference ($referenceId) matches track id ($trackID) in Period " . ($mpdHandler->getSelectedPeriod() + 1) .
    ' Adaptation Set ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
    "sidx box reference ($referenceId) does not match track id ($trackID) in Period " . ($mpdHandler->getSelectedPeriod() + 1) .
    ' Adaptation Set ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
) && $returnValue;

$sidxTimescale = $sidxBox0->getAttribute('timeScale');
$mdhdTimescale = $xml->getElementsByTagName('mdhd')->item(0)->getAttribute('timescale');

$returnValue = $logger->test(
    "DASH-IF IOP CR Low Latency Live",
    "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
    "If the media is contained in a Self-Initializing Segment, " .
    "then sidx box timescale SHALL be identical to the timescale of the mdhd box of the CMAF track",
    $sidxTimescale == $mdhdTimescale,
    "PASS",
    "sidx box timescale ($sidxTimescale) matches mdhd timescale ($mdhdTimescale) in Period " . ($mpdHandler->getSelectedPeriod() + 1) .
    ' Adaptation Set ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
    "sidx box timescale ($sidxTimescale) does not match mdhd timescale ($mdhdTimescale) in Period " .
    ($mpdHandler->getSelectedPeriod() + 1) .
    ' Adaptation Set ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
) && $returnValue;

$returnValue = $logger->test(
    "DASH-IF IOP CR Low Latency Live",
    "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
    "If the media is contained in a Self-Initializing Segment, " .
    "then sidx box reference_type SHALL be set to 0",
    $sidxBox0->getAttribute('reference_type_1') == '0',
    "PASS",
    "sidx box reference type is 0 in Period " . ($mpdHandler->getSelectedPeriod() + 1) .
    ' Adaptation Set ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
    "sidx box reference type not 0 in Period " . ($mpdHandler->getSelectedPeriod() + 1) .
    ' Adaptation Set ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
) && $returnValue;


$earliestPresentationTime = $sidxBox0->getAttribute('earliestPresentationTime');
$isSegmentStart = $infoFileAdapt[$representationId]['isSegmentStart'];
$presStart = $infoFileAdapt[$representationId]['PresStart'];
$presEnds = $infoFileAdapt[$representationId]['NextPresStart'];
$segmentIndexes = array_keys($isSegmentStart, '1');
$presentationStartsFirst = $presStart[$segmentIndexes[0]] * $mdhdTimescale;


$returnValue = $logger->test(
    "DASH-IF IOP CR Low Latency Live",
    "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
    "If the media is contained in a Self-Initializing Segment, " .
    "then sidx box earliest_presentation_time SHALL be set to earliest presentation time of the first CMAF Fragment",
    $earliestPresentationTime == $presentationStartsFirst,
    "PASS",
    "sidx box earliestPresentationTime matches in Period " . ($mpdHandler->getSelectedPeriod() + 1) .
    ' Adaptation Set ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
    "sidx box earliestPresentationTime does not match in Period " . ($mpdHandler->getSelectedPeriod() + 1) .
    ' Adaptation Set ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
) && $returnValue;



$returnValue = $logger->test(
    "DASH-IF IOP CR Low Latency Live",
    "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
    "If the media is contained in a Self-Initializing Segment, " .
    "then sidx box reference_count SHALL be set to the number of CMAF Fragments in the CMAF Track",
    $sidxBox0->getAttribute('referenceCount') == $moofBoxes->length,
    "PASS",
    "sidx box reference count correct in Period " . ($mpdHandler->getSelectedPeriod() + 1) .
    ' Adaptation Set ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
    "sidx box reference count not correct in Period " . ($mpdHandler->getSelectedPeriod() + 1) .
    ' Adaptation Set ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
) && $returnValue;

$subsegments = $xml->getElementsByTagName('subsegment');
$subsegmentCount = $subsegments->length;
for ($i = 0; $i < $subsegmentCount; $i++) {
    $subsegment = $subsegments->item($i);
    $subsegmentDuration = $subsegment->getAttribute('subsegment_duration');
    $sapType = $subsegment->getAttribute('SAP_type');
    $SAP_delta_time = $subsegment->getAttribute('SAP_delta_time');

    ///\Correctness This check was on '0', but message says 1. Updated check.
    $returnValue = $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
        "If the media is contained in a Self-Initializing Segment, " .
        "then sidx box starts_with_SAP SHALL be set to 1",
        $subsegment->getAttribute('starts_with_SAP') == '1',
        "PASS",
        "sidx box startsWithSAP equal to 0 in Period " . ($mpdHandler->getSelectedPeriod() + 1) .
        ' Adaptation Set ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1) . "subsegment $i",
        "sidx box startsWithSAP not equal to 0 in Period " . ($mpdHandler->getSelectedPeriod() + 1) .
        ' Adaptation Set ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1) . "subsegment $i",
    ) && $returnValue;

    ///\Correctness Updated check from if ($SAP_type != '1' || $SAP_type != '2') {
    $returnValue = $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
        "If the media is contained in a Self-Initializing Segment, " .
        "then sidx box SAP_type SHALL be set to 1 or 2",
        $sapType == 1 || $sapType == 2,
        "PASS",
        "sidx box SAP_type valid in Period " . ($mpdHandler->getSelectedPeriod() + 1) .
        ' Adaptation Set ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1) . "subsegment $i",
        "sidx box SAP_type not valid in Period " . ($mpdHandler->getSelectedPeriod() + 1) .
        ' Adaptation Set ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1) . "subsegment $i",
    ) && $returnValue;

    $returnValue = $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
        "If the media is contained in a Self-Initializing Segment, " .
        "then sidx box SAP_delta_time SHALL be set to 0",
        $subsegment->getAttribute('SAP_delta_time') == '0',
        "PASS",
        "sidx box SAP_delta_time set to 0 in Period " . ($mpdHandler->getSelectedPeriod() + 1) .
        ' Adaptation Set ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1) . "subsegment $i",
        "sidx box SAP_delta_time not set to 0 in Period " . ($mpdHandler->getSelectedPeriod() + 1) .
        ' Adaptation Set ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1) . "subsegment $i",
    ) && $returnValue;

    $validDuration = (sizeof($presEnds) == $subsegmentCount && sizeof($presStart) == $subsegmentCount);
    if ($validDuration) {
        $duration = $presEnds[$i] - $presStart[$i];
        $validDuration = ($duration == $subsegmentDuration);
    }
    $returnValue = $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
        "If the media is contained in a Self-Initializing Segment, " .
        "then each CMAF Fragment SHALL be mapped to exactly one Subsegment with CMAF Fragment duration equal to " .
        "subsegment_duration",
        $validDuration,
        "PASS",
        "Durations matches in Period " . ($mpdHandler->getSelectedPeriod() + 1) .
        ' Adaptation Set ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1) . "subsegment $i",
        "Durations don't match, or unmapped fragments exist in Period " . ($mpdHandler->getSelectedPeriod() + 1) .
        ' Adaptation Set ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1) . "subsegment $i",
    ) && $returnValue;
}

$returnValue = $logger->test(
    "DASH-IF IOP CR Low Latency Live",
    "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
    "If the media is contained in a Self-Initializing Segment, " .
    "then the Segment SHALL conform to CMAF Track File",
    sizeof($segmentIndexes) != 0 &&
    $xml->getElementsByTagName('tfdt')->item(0)->getAttribute('baseMediaDecodeTime') != '0',
    "PASS",
    "Segment conforms to CMAF track file for Period " . ($mpdHandler->getSelectedPeriod() + 1) .
    ' Adaptation Set ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
    "Durations does not conform to CMAF track file for Period " . ($mpdHandler->getSelectedPeriod() + 1) .
    ' Adaptation Set ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1)
) && $returnValue;

///\Correctness Message and check don't correspond at all.
$returnValue = $logger->test(
    "DASH-IF IOP CR Low Latency Live",
    "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
    "If the media is contained in a Self-Initializing Segment, " .
    "then the Segment SHALL conform to the Indexed Self-Initializing Media Segment",
    strpos($xml->getElementsByTagName('ftyp')->item(0)->getAttribute('compatible_brands'), 'dash') !== false,
    "PASS",
    "Segment conforms to Self-Initializing Media Segement for Period " . ($mpdHandler->getSelectedPeriod() + 1) .
    ' Adaptation Set ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
    "Segment does not conform to Self-Initializing Media Segement for Period " . ($mpdHandler->getSelectedPeriod() + 1) .
    ' Adaptation Set ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1)
) && $returnValue;

return $returnValue;
