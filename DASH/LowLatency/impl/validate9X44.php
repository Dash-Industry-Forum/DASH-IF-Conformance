<?php

global $session, $mpdHandler, $service_description_info, $maxSegmentDurations, $logger;


$representations = $adaptationSet['Representation'];
foreach ($representations as $representationId => $representation) {
    $lowLatencySegmentPoints[$representationId] = 3;
    $isSMDSInSegment = false;
    $isSMDSInSegmentProfiles = array();
    $targetExceeds50Segment = false;
    $targetExceeds30Segment = false;

    $rep_xml = $session->getRepresentationDir($mpdHandler->getSelectedPeriod(), $adaptationSetId, $representationId) . '/atomInfo.xml';

    if (!file_exists($rep_xml)) {
        continue;
    }

    $xml = DASHIF\Utility\parseDOM($rep_xml, 'atomlist');
    if (!$xml) {
        continue;
    }

    $stypBoxes = $xml->getElementsByTagName('styp');
    $segmentProfiles = $adaptationSet['segmentProfiles'];
    if ($representation['segmentProfiles'] != null) {
        $segmentProfiles = $representation['segmentProfiles'];
    }

    $isSegmentStarts = $infoFileAdapt[$representationId]['isSegmentStart'];
    $presStarts = $infoFileAdapt[$representationId]['PresStart'];
    $presEnds = $infoFileAdapt[$representationId]['NextPresStart'];

    $segmentIndexes = array_keys($isSegmentStarts, '1');
    $segmentCount = sizeof($segmentIndexes);
    $maxSegmentDuration = PHP_INT_MIN;
    $segmentDurations = array();
    for ($i = 0; $i < $segmentCount; $i++) {
        if ($i == $segmentCount - 1) {
            $segmentDurations[] = PHP_INT_MAX;
            continue;
        }

        $segmentIndex = $segmentIndexes[$i];
        $nextSegmentIndex = $segmentIndexes[$i + 1];

        $presStart = $presStarts[$segmentIndex];
        $presEnd = $presEnds[$nextSegmentIndex - 1];

        $segmentDuration = $presEnd - $presStart;
        $segmentDurations[] = $segmentDuration;
        if ($segmentDuration > $maxSegmentDuration) {
            $maxSegmentDuration = $segmentDuration;
        }


        // Bullet 1
        if ($nextSegmentIndex - $segmentIndex == 1) {
            if ($stypBoxes->length > 0) {
                $styp = $stypBoxes->item($i);
                $majorBrands = $styp->getAttribute('majorbrand');
                $compatibleBrands = $styp->getAttribute('compatible_brands');

                if (strpos($majorBrands, 'smds') !== false || strpos($compatibleBrands, 'smds') !== false) {
                    $isSMDSInSegment = true;
                    $isSMDSInSegmentProfiles[] = (strpos($segmentProfiles, 'smds') !== false);
                }
            }
        }

        // Bullet 3
        if ($serviceDescriptionInfo != null) {
            $serviceDescription = $serviceDescriptionInfo[0];
            $latency = $serviceDescription['Latency'][0];
            $target = $latency['target'];
            if ($segmentDuration * 1000 > $target * 0.5) {
                $targetExceeds50Segment = true;
            }
            if ($segmentDuration * 1000 > $target * 0.3) {
                $targetExceeds30Segment = true;
            }
        }
    }

    $maxSegmentDurations[$representationId] = $maxSegmentDuration;

    $moofsInSegments = $this->checkSegment($adaptationSetId, $representationId, $segmentDurations);
    if ($moofsInSegments != null) {
        for ($i = 0; $i < $segmentCount; $i++) {
            $logger->test(
                "DASH-IF IOP CR Low Latency Live",
                "Section 9.X.4.4",
                "Each Segment SHOULD include only a single movie fragment box \"moof\"",
                $moofsInSegments == 1,
                "WARN",
                "Exactly 1 \"moof\" found in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' .
                ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
                "Zero or more than 1 \"moof\" found in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' .
                ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1)
            );

            if ($moofsInSegments[$i] > 1) {
                continue;
            }
            $logger->test(
                "DASH-IF IOP CR Low Latency Live",
                "Section 9.X.4.4",
                "If Segments include only a single 'moof', then Segment MAY carry a 'smds' brand",
                $isSMDSInSegment,
                "PASS",
                "\"smds\" found in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' .
                ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
                "\"smds\" not found in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' .
                ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1)
            );
            if (!$isSMDSInSegment) {
                continue;
            }
            $logger->test(
                "DASH-IF IOP CR Low Latency Live",
                "Section 9.X.4.4",
                "If Segments include only a single 'moof' and carries a 'smds' brand, it SHALL signal this by " .
                "providing the @segmentProfiles including the 'smds' brand",
                "If Segments include only a single 'moof', then Segment MAY carry a 'smds' brand",
                $isSMDSInSegmentProfiles[$i],
                "FAIL",
                "Corresponding segmentProfile found in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' .
                ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
                "Corresponding segmentProfile not found in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' .
                ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1)
            );
            if (!$isSMDSInSegmentProfiles[$i]) {
                $lowLatencySegmentPoints[$representationId]--;
            }
        }
    }

    // Bullet 2
    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.4",
        "The @availabilityTimeComplete shall be absent",
        $segmentAccessInfo[$representationId][0]['availabilityTimeComplete'] == null,
        "FAIL",
        "@availabilityTimeComplete is absent in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' .
        ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
        "@availabilityStartTime found in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' .
        ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1)
    );
    if ($segmentAccessInfo[$representationId][0]['availabilityTimeComplete'] != null) {
        $lowLatencySegmentPoints[$representationId]--;
    }
    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.4",
        "The Segment duration SHALL not exceed 50% of the value of the target latency",
        !$targetExceeds50Segment,
        "FAIL",
        "Segment within 50% boundary in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' .
        ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
        "Segment not within 50% boundary found in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' .
        ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1)
    );
    if ($targetExceeds50Segment) {
        $lowLatencySegmentPoints[$representationId]--;
    }
    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.4",
        "The Segment duration SHOULD not exceed 30% of the value of the target latency",
        !$targetExceeds30Segment,
        "FAIL",
        "Segment within 30% boundary in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' .
        ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
        "Segment not within 30% boundary found in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' .
        ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1)
    );
}

$logger->test(
    "DASH-IF IOP CR Low Latency Live",
    "Section 9.X.4.4",
    "A Low Latency Segment Adaptation Set SHALL conform to a Low Latency Adaptation Set",
    $isLowLatency,
    "FAIL",
    "Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' . ($adaptationSetId + 1) .
    " in confomance with low latency",
    "Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' . ($adaptationSetId + 1) .
    " not in confomance with low latency"
);


$isLowLatencySegment = false;
if (sizeof(array_unique($lowLatencySegmentPoints)) == 1 && $lowLatencySegmentPoints[0] == 3 && $isLowLatency) {
    $isLowLatencySegment = true;
}

return $isLowLatencySegment;
