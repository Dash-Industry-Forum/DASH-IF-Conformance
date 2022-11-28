<?php

global $mpdHandler, $logger;

// Bullet 2
$dashSegCmafFrag = true;

$cmafDash = $this->validateDASHProfileCMAF($adaptationSet, $adaptationSetId, $segmentAccessInfo, $infoFileAdapt);

$logger->test(
    "DASH-IF IOP CR Low Latency Live",
    "Section 9.X.4.5",
    "Each Segment SHALL conform to a CMAF Fragment",
    sizeof(array_unique($cmafDash)) == 1 && $cmafDash[0] == true,
    "FAIL",
    "All segments conform in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' . ($adaptationSetId + 1),
    "Not all segments conform in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' . ($adaptationSetId + 1)
);

if (!(sizeof(array_unique($cmafDash)) == 1 && $cmafDash[0] == true)) {
    $dashSegCmafFrag = false;
}

$representations = $adaptationSet['Representation'];
foreach ($representations as $representationId => $representation) {
    $chunkedAdaptationPoints[$representationId] = 3;

    // Bullet 3
    $isSegmentStarts = $infoFileAdapt[$representationId]['isSegmentStart'];
    $presStarts = $infoFileAdapt[$representationId]['PresStart'];
    $presEnds = $infoFileAdapt[$representationId]['NextPresStart'];
    $segmentIndexes = array_keys($isSegmentStarts, '1');
    foreach ($segmentIndexes as $segmentIndexId => $segmentIndex) {
        $presStart = $presStarts[$segmentIndex];
        $presSnd = $presEnds[$segmentIndex];

        if ($segmentIndexId != sizeof($segmentIndexes) - 1) {
            $segmentDurations[] = $presEnd - $presStart;
        } else {
            $segmentDurations[] = PHP_INT_MAX;
        }
    }
    $moofsInSegments = $this->checkSegment($adaptationSetId, $representationId, $segmentDurations);
    foreach ($segmentIndexes as $segmentIndexId => $segmentIndex) {
        $moofsInSegment = $moofsInSegments[$segmentIndexId];

        ///\Correctness same check twice, different statements?
        $logger->test(
            "DASH-IF IOP CR Low Latency Live",
            "Section 9.X.4.5",
            "Each Segment MAY contain more than one CMAF chunk",
            $moofsInSegment > 1,
            "PASS",
            "More than one CMAF chunk found in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
            ($adaptationSetId + 1) . ' or Representation ' . ($representationId + 1) . ' Segment ' .
            ($segmentIndexId + 1),
            "One CMAF chunk found in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
            ($adaptationSetId + 1) . ' or Representation ' . ($representationId + 1) . ' Segment ' .
            ($segmentIndexId + 1)
        );

        $logger->test(
            "DASH-IF IOP CR Low Latency Live",
            "Section 9.X.4.5",
            "Each Segment typically SHOULD contain more than one CMAF chunk",
            $moofsInSegment > 1,
            "WARN",
            "More than one CMAF chunk found in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
            ($adaptationSetId + 1) . ' or Representation ' . ($representationId + 1) . ' Segment ' .
            ($segmentIndexId + 1),
            "One CMAF chunk found in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
            ($adaptationSetId + 1) . ' or Representation ' . ($representationId + 1) . ' Segment ' .
            ($segmentIndexId + 1)
        );
    }

    $chunkOverlapWithinRep = $this->validateTimingsWithinRepresentation(
        $adaptationSet,
        $adaptationSetId,
        $representationId,
        $infoFileAdapt
    );

    // Bullet 4
    $resyncs = ($representation['Resync'] != null) ? $representation['Resync'] : $adaptationSet['Resync'];
    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.5",
        "A Resync element SHOULD be assigned to each Representation (possibly defaulted)",
        $resyncs != null,
        "WARN",
        "Resync element found in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . ' or Representation ' . ($representationId + 1),
        "Resync element not found in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . ' or Representation ' . ($representationId + 1)
    );

    // Bullet 5
    if ($this->serviceDescriptionInfo == null) {
        $chunkedAdaptationPoints[$representationId]--;
    } else {
        $serviceDescription = $serviceDescriptionInfo[0];
        $target = $serviceDescription['Latency'][0]['target'];
        $availabilityTimeOffset = $segmentAccessInfo[$representationId][0]['availabilityTimeOffset'];
        $logger->test(
            "DASH-IF IOP CR Low Latency Live",
            "Section 9.X.4.5",
            "The @availabilityTimeOffset SHALL be present",
            $availabilityTimeOffset != null,
            "FAIL",
            "@availabilityTimeOffset found in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
            ($adaptationSetId + 1) . ' or Representation ' . ($representationId + 1),
            "@availabilityTimeOffset not found in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
            ($adaptationSetId + 1) . ' or Representation ' . ($representationId + 1)
        );
        if ($availabilityTimeOffset == null) {
            $chunkedAdaptationPoints[$representationId]--;
        } else {
            $check1 = $availabilityTimeOffset > 0;
            $check2 = $availabilityTimeOffset < $this->maxSegmentDurations[$representationId];
            $check3 = abs($availabilityTimeOffset - $this->maxSegmentDurations[$representationId]) < $target;
            if (!$check1 || !$check2 || !$check3) {
                $chunkedAdaptationPoints[$representationId]--;
            }
            $logger->test(
                "DASH-IF IOP CR Low Latency Live",
                "Section 9.X.4.5",
                "The SegmentBase@availabilityTimeOffset SHALL be greater than zero",
                $check1,
                "FAIL",
                "Correct time offset of $availabilityTimeOffset found in Period " . ($mpdHandler->getSelectedPeriod() + 1) .
                ' Adaptation ' . ($adaptationSetId + 1) . ' or Representation ' . ($representationId + 1),
                "Negative or zero time offset of $availabilityTimeOffset found in Period " . ($mpdHandler->getSelectedPeriod() + 1) .
                ' Adaptation ' . ($adaptationSetId + 1) . ' or Representation ' . ($representationId + 1)
            );
            $logger->test(
                "DASH-IF IOP CR Low Latency Live",
                "Section 9.X.4.5",
                "The SegmentBase@availabilityTimeOffset SHALL be smaller than the maximum segment duration for " .
                "this Representation",
                $check2,
                "FAIL",
                "Conforming time offset of $availabilityTimeOffset found in Period " . ($mpdHandler->getSelectedPeriod() + 1) .
                ' Adaptation ' . ($adaptationSetId + 1) . ' or Representation ' . ($representationId + 1),
                "Too large time offset of $availabilityTimeOffset found in Period " . ($mpdHandler->getSelectedPeriod() + 1) .
                ' Adaptation ' . ($adaptationSetId + 1) . ' or Representation ' . ($representationId + 1) .
                ', maximum is ' . $this->maxSegmentDurations[$representationId]
            );
            $logger->test(
                "DASH-IF IOP CR Low Latency Live",
                "Section 9.X.4.5",
                "The SegmentBase@availabilityTimeOffset SHALL be such that the difference of the " .
                "availabilityTimeOffset and the maximum segment duration for this Representation is smaller " .
                "than the target latency",
                $check3,
                "FAIL",
                "Conforming time offset of $availabilityTimeOffset found in Period " . ($mpdHandler->getSelectedPeriod() + 1) .
                ' Adaptation ' . ($adaptationSetId + 1) . ' or Representation ' . ($representationId + 1),
                "Too large time offset of $availabilityTimeOffset found in Period " . ($mpdHandler->getSelectedPeriod() + 1) .
                ' Adaptation ' . ($adaptationSetId + 1) . ' or Representation ' . ($representationId + 1) .
                ', maximum is ' . $this->maxSegmentDurations[$representationId] . ', target is ' . $target
            );

            ///\Correctness this check used to be different
            $availabilityTimeOffsetOnSegmentTemplate = true;
            if ($adaptationSet['SegmentTemplate'] == null) {
                $availabilityTimeOffsetOnSegmentTemplate = false;
            } elseif ($adaptationSet['SegmentTemplate'][0]['availabilityTimeOffset'] == null) {
                $availabilityTimeOffsetOnSegmentTemplate = false;
            }
            $logger->test(
                "DASH-IF IOP CR Low Latency Live",
                "Section 9.X.4.5",
                "The @availabilityTimeOffset SHOULD be present on Adaptation Set level",
                $availabilityTimeOffsetOnSegmentTemplate,
                "WARN",
                "Present in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' . ($adaptationSetId + 1),
                "Not present in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' . ($adaptationSetId + 1),
            );
            if (!$availabilityTimeOffsetOnSegmentTemplate) {
                foreach ($segmentAccessInfo as $segmentAccessInfoRep) {
                    $availabilityTimeOffsetRep[] = $segmentAccessInfoRep[0]['availabilityTimeOffset'];
                }
                $logger->test(
                    "DASH-IF IOP CR Low Latency Live",
                    "Section 9.X.4.5",
                    "The @availabilityTimeOffset, if not present on AdaptationSet, SHALL be present on " .
                    "Representation and SHALL be the same for each Representation",
                    sizeof(array_unique($availabilityTimeOffsetRep)) == 1 &&
                    $availabilityTimeOffsetRep[0] == false,
                    "FAIL",
                    "All and equal values found Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
                    ($adaptationSetId + 1),
                    "Not all or differing values found Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
                    ($adaptationSetId + 1),
                );
                if (
                    !(sizeof(array_unique($availabilityTimeOffsetRep)) == 1 &&
                    $availabilityTimeOffsetRep[0] == false)
                ) {
                    $chunkedAdaptationPoints[$representationId]--;
                }
            }
        }
    }

    // Bullet 6
    $availabilityTimeCompletePresent = $segmentAccessInfo[$representationId][0]['availabilityTimeComplete'] != null;
    $availabilityTimeCompleteFalse = false;
    if ($availabilityTimeCompletePresent) {
        $availabilityTimeCompleteFalse =
          ($segmentAccessInfo[$representationId][0]['availabilityTimeComplete'] == false);
    }

    ///\Correctness These checks seem somewhat contradicting?
    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.5",
        "The @availabilityTimeComplete SHALL be present and SHALL be set to FALSE",
        $availabilityTimeCompletePresent && $availabilityTimeCompleteFalse,
        "FAIL",
        "Value found and set to false Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . ', representation ' . ($representationId + 1),
        "Value not found or not set to false Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . ', representation ' . ($representationId + 1),
    );
    if (!$availabilityTimeCompletePresent || !$availabilityTimeCompleteFalse) {
        $chunkedAdaptationPoints[$representationId]--;
    }
    if ($availabilityTimeCompletePresent) {
        foreach ($segmentAccessInfo as $segmentAccessInfoRep) {
            $availabilityTimeCompleteRep[] = $segmentAccessInfoRep[0]['availabilityTimeComplete'];
        }
        $logger->test(
            "DASH-IF IOP CR Low Latency Live",
            "Section 9.X.4.5",
            "The @availabilityTimeComplete, if not present on AdaptationSet, SHALL be present on " .
            "Representation and SHALL be the same for each Representation",
            sizeof(array_unique($availabilityTimeCompleteRep)) == 1 &&
            $availabilityTimeCompleteRep[0] == false,
            "FAIL",
            "All and equal values found Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
            ($adaptationSetId + 1),
            "Not all or differing values found Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
            ($adaptationSetId + 1),
        );
        if (
            !(sizeof(array_unique($availabilityTimeComplete_rep)) == 1 &&
            !$availabilityTimeComplete_rep[0])
        ) {
            $chunkedAdaptationPoints[$representationId]--;
        }
        $availabilityTimeCompleteOnSegmentTemplate = true;
        if ($adaptationSet['SegmentTemplate'] == null) {
            $availabilityTimeCompleteOnSegmentTemplate = false;
        } elseif ($adaptationSet['SegmentTemplate'][0]['availabilityTimeComplete'] == null) {
            $availabilityTimeCompleteOnSegmentTemplate = false;
        }
        $logger->test(
            "DASH-IF IOP CR Low Latency Live",
            "Section 9.X.4.5",
            "The @availabilityTimeComplete SHOULD be present on Adaptation Set level",
            $availabilityTimeCompleteOnSegmentTemplate,
            "WARN",
            "Present in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' . ($adaptationSetId + 1),
            "Not present in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' . ($adaptationSetId + 1),
        );
    }

    // Bullet 7
    if (!$this->validateEmsg($adaptationSet, $adaptationSetId, $representationId, $infoFileAdapt)) {
        $chunkedAdaptationPoints[$representationId]--;
    }

    // Bullet 8 info collect
    $this->firstOption['maxSegmentDuration'][$representationId] = $this->maxSegmentDurations[$representationId];
    $this->firstOption['target'][$representationId] = $target;

    $this->secondOption['bandwidth'][$representationId] = $representation['bandwidth'];
    $this->secondOption['Resync'][$representationId] = $resyncs;
    $this->secondOption['timescale'][$representationId] = $segmentAccessInfo[$representationId][0]['timescale'];
    $this->secondOption['target'][$representationId] = $target;
    $this->secondOption['qualityRanking'][$representationId] = $representation['qualityRanking'];
    $this->secondOption['chunkOverlapWithinRep'][$representationId] = $chunkOverlapWithinRep;
}

    // Bullet 8
if (sizeof($representations) > 1) {
    $extendedChecks = $this->validate9X45Extended($adaptationSet, $adaptationSetId);
    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.5",
        "For any Adaptation Set that contains more than one Representation, one of two options " .
        "listed in Bullet 8 in this clause SHOULD be applied",
        $extendedChecks[0] || $extendedChecks[1],
        "WARN",
        "At least one of the options applied in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
        ($adaptationSetId + 1),
        "Neither of the options applied in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
        ($adaptationSetId + 1)
    );
}

$logger->test(
    "DASH-IF IOP CR Low Latency Live",
    "Section 9.X.4.4",
    "A Low Latency Chunked Adaptation Set SHALL conform to a Low Latency Adaptation Set",
    $isLowLatency,
    "FAIL",
    "Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' . ($adaptationSetId + 1) .
    " in confomance with low latency",
    "Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' . ($adaptationSetId + 1) .
    " not in confomance with low latency"
);

$isLowLatencyChunked = false;
if (
    sizeof(array_unique($chunkedAdaptationPoints)) == 1 &&
    $chunkedAdaptationPoints[0] == 3 &&
    $dashSegCmafFrag &&
    $isLowLatency
) {
    $isLowLatencyChunked = true;
}

return $isLowLatencyChunked;
