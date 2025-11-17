<?php

global $mpdHandler, $first_option, $second_option, $presentation_times, $decode_times;

$representations = $adaptationSet['Representation'];

// Test second option
// Lowest bw Representations
$validLowestBandwidthFound = false;
$lowestBandwidthRepresentationIds = array_keys($second_option['bandwidth'], min($second_option['bandwidth']));
foreach ($lowestBandwidthRepresentationIds as $lowestBandwidthRepresentationId) {
    $lowestBandwidthPoints[$lowestBandwidthRepresentationId] = 1;

    $representation = $representations[$lowestBandwidthRepresentationId];
    $resyncs = $second_option['Resync'][$lowestBandwidthRepresentationId];
    $timescale = $second_option['timescale'][$lowestBandwidthRepresentationId];
    $target = $second_option['target'][$lowestBandwidthRepresentationId];
    $qualityRanking = $second_option['qualityRanking'][$lowestBandwidthRepresentationId];

    // Check the Resync
    $validResyncFound = false;
    foreach ($resyncs as $resync) {
        $validResyncWarning = false;
        if ($resync['type'] == '1' || $resync['type'] == '2') {
            if ($target == null) {
                continue;
            }
            if ($resync['dT'] / $timescale <= $target) {
                if ($resync['dT'] / $timescale >= $target * 0.5) {
                    $validResyncWarning = true;
                }
                if ($resync['marker'] == 'TRUE') {
                    $validResyncFound = true;
                    break;
                }
            }
        }
    }
    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.5",
        "For Adaptation Set that contains more than one Representation, at least one Representation is present with " .
        "a Resync element with @type set to 1 or 2, @dT normalized by @timescale is at most the signaled target " .
        "latency, and @marker set to 'TRUE'",
        $validResyncFound,
        "INFO",
        "Appropriate resync element found for Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . " Representation $lowestBandwidthRepresentationId",
        "Appropriate resync element not found for Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . " Representation $lowestBandwidthRepresentationId",
    );
    if (!$validResyncFound) {
        $lowestBandwidthPoints[$lowestBandwidthRepresentationId]--;
    } else {
        $logger->test(
            "DASH-IF IOP CR Low Latency Live",
            "Section 9.X.4.5",
            "For Adaptation Set that contains more than one Representation, at least one Representation is present " .
            "with a Resync element with @type set to 1 or 2, @dT normalized by @timescale is at most should be " .
            "smaller than half of the signalled target latency",
            !$validResyncWarning,
            "INFO",
            "Appropriate resync with normalized duration less than half of target latency found for Period " .
            ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
            ($adaptationSetId + 1) . " Representation $lowestBandwidthRepresentationId",
            "Appropriate resync with normalized duration less than half of target latency not found for Period " .
            ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
            ($adaptationSetId + 1) . " Representation $lowestBandwidthRepresentationId",
        );
    }

    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.5",
        "For Adaptation Set that contains more than one Representation, at least one Representation is present with " .
        "@bandwidth value is the lowest in the Adaptation Set and @qualityRanking SHOULD be used",
        $qualityRanking != null,
        "INFO",
        "@qualityRanking found for Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . " Representation $lowestBandwidthRepresentationId",
        "@qualityRanking not found for Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . " Representation $lowestBandwidthRepresentationId",
    );

    // Analyze the findings for this low bw rep
    if ($lowestBandwidthPoints[$lowestBandwidthRepresentationId] == 1) {
        $validLowestBandwidthFound = true;
        break;
    }
}

// Rest of the Representations
foreach ($representations as $representationId => $representation) {
    if (in_array($representationId, $lowestBandwidthRepresentationIds)) {
        //We already checked these representations above
        continue;
    }
    $resyncs = $second_option['Resync'][$representationId];
    $timescale = $second_option['timescale'][$representationId];
    $target = $second_option['target'][$representationId];
    $qualityRanking = $second_option['qualityRanking'][$representationId];

    // Check the Resync
    $validResyncFound = false;
    foreach ($resyncs as $resync) {
        if ($resync['type'] == '1' || $resync['type'] == '2' || $resync['type'] == '3') {
            if ($resync['dT'] / $timescale <= $target) {
                if ($resync['marker'] == 'TRUE') {
                    $validResyncFound = true;
                    break;
                }
            }
        }
    }
    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.5",
        "For Adaptation Set that contains more than one Representation, additional Representations with higher " .
        "values for @bandwidth MAY be present with Resync set as above",
        $validResyncFound,
        "INFO",
        "Optional resync element found for Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . " Representation $representationId",
        "Optional resync element not found for Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . " Representation $representationId",
    );
    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.5",
        "For Adaptation Set that contains more than one Representation, additional Representations with higher " .
        "values should use @qualityRanking",
        $qualityRanking != null,
        "INFO",
        "@qualityRanking used in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . " Representation $representationId",
        "@qualityRanking not used in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . " Representation $representationId",
    );
}

// All of the Representations
for ($i = 0; $i < sizeof($representations); $i++) {
    $allRepresentationPoints[$i] = 1;

    if ($chunkOverlapWithinRep != '') {
        $allRepresentationPoints[$i]--;
        if (
            !$logger->test(
                "DASH-IF IOP CR Low Latency Live",
                "Section 9.X.4.5",
                "chunkOverlapWithinRep shall be empty",
                $second_option['chunkOverlapWithinRep'][$i] == '',
                "INFO",
                "Empty in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
                ($adaptationSetId + 1) . " Representation $i",
                "Not empty in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
                ($adaptationSetId + 1) . " Representation $i",
            )
        ) {
            $allRepresentationPoints[$i]--;
        }

        $presentationTime1 = $presentation_times[$mpdHandler->getSelectedPeriod()][$adaptationSetId][$i];
        $decodeTime1 = $decode_times[$mpdHandler->getSelectedPeriod()][$adaptationSetId][$i];
        $allRepresentationCrossChecks = true;
        for ($j = 1; $j < sizeof($representations); $j++) {
            $presentationTime2 = $presentation_times[$mpdHandler->getSelectedPeriod()][$adaptationSetId][$j];
            $decodeTime2 = $decode_times[$mpdHandler->getSelectedPeriod()][$adaptationSetId][$j];

            $allRepresentationCrossChecks = $logger->test(
                "DASH-IF IOP CR Low Latency Live",
                "Section 9.X.4.5",
                "CMAF chunks SHALL be aligned in presentation time across all Representations",
                empty(array_diff($presentationTime1, $presentationTime2)),
                "INFO",
                "Presentation times aligned between representation $i and $j in Period " .
                ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' . ($adaptationSetId + 1),
                "Presentation times not aligned between representation $i and $j in Period " .
                ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' . ($adaptationSetId + 1)
            ) && $allRepresentationCrossChecks;
            $allRepresentationCrossChecks = $logger->test(
                "DASH-IF IOP CR Low Latency Live",
                "Section 9.X.4.5",
                "CMAF chunks SHALL be aligned in decode time across all Representations",
                empty(array_diff($decodeTime1, $decodeTime2)),
                "INFO",
                "Decode times aligned between representation $i and $j in Period " .
                ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' . ($adaptationSetId + 1),
                "Decode times not aligned between representation $i and $j in Period " .
                ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' . ($adaptationSetId + 1)
            ) && $allRepresentationCrossChecks;
        }
        if (!$allRepresentationCrossChecks) {
            $allRepresentationPoints[$i]--;
        }
    }
}

$validAllRepresentationsFound = false;
if (sizeof(array_unique($allRepresentationPoints)) == 1 && $allRepresentationPoints[0] == 1) {
    $validAllRepresentationsFound = true;
}

$validSecondOption = false;
if ($validLowestBandwidthFound && $validAllRepresentationsFound) {
    $validSecondOption = true;
}

return [$validFirstOption, $validSecondOption];
