<?php

global $session, $mpdHandler;

$dashConformsToCmafFrag = array();
$representations = $adaptationSet['Representation'];

foreach ($representations as $representationId => $representation) {
    $rep_xml = $session->getRepresentationDir(
        $mpdHandler->getSelectedPeriod(),
        $adaptationSetId,
        $representationId
    ) . '/atomInfo.xml';

    if (!file_exists($rep_xml)) {
        continue;
    }

    $xml = DASHIF\Utility\parseDOM($rep_xml, 'atomlist');
    if (!$xml) {
        continue;
    }

    $segmentAccessRepresentation = $segmentAccessInfo[$representationId][0];

    if ($segmentAccessRepresentation['SegmentTimeline'] != null) {
        $this->validateSegmentTimeline(
            $adaptationSet,
            $adaptationSetId,
            $representation,
            $representationId,
            $segmentAccessRepresentation,
            $infoFileAdapt,
            $logger
        );
    } else {
        $this->validateSegmentTemplate(
            $adaptationSetId,
            $representationId,
            $segmentAccessRepresentation,
            $infoFileAdapt,
            $logger
        );
    }

    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
        "Event Message Streams MAY be signaled with InbandEventStream elements",
        $representation['InbandEventStream'] != null || $adaptationSet['InbandEventStream'] != null,
        "PASS",
        "InbandEventSteam found for Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
        "InbandEventSteam not found for Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1)
    );

    $validSelfInitializingSegment = true;

    $validCMAFSegment = true;
    $tfdtBoxes = $xml->getElementsByTagName('tfdt');
    for ($i = 1; $i < $tfdtBoxes->length; $i++) {
        $tfdtPreviousDecodeTime = $tfdtBoxes->item($i - 1)->getAttribute('baseMediaDecodeTime');
        $tfdtDecodeTime = $tfdtBoxes->item($i)->getAttribute('baseMediaDecodeTime');
        if ($tfdtPreviousDecodeTime > $tfdtDecodeTime) {
            $validCMAFSegment = false;
            break;
        }
    }
    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
        "Each CMAF Segment SHALL contain one or more complete and consecutive CMAF Fragments in decode order",
        $validCMAFSegment,
        "INFO",
        "All fragments in decode order for Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
        "CMAF Fragments out of decode order found for Period " . ($mpdHandler->getSelectedPeriod() + 1) .
        ' Adaptation ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1)
    );

    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
        "Each Media Segment SHALL conform to a CMAF Addressable Media Object as defined in CMAF 7.3.3",
        $validCMAFSegment || $validSelfInitializingSegment,
        "FAIL",
        "All segments valid for Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
        "Not all segments valid for Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1)
    );


    $errors = file_get_contents(
        $session->getRepresentationDir(
            $mpdHandler->getSelectedPeriod(),
            $adaptationSetId,
            $representationId
        ) . '/stderr.txt'
    );

    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
        "Each Media Segment SHALL conform to a Delivery Unit Media Segment as defined in 6.3.4.2",
        strpos($errors, 'ISO/IEC 23009-1:2012(E), 6.3.4.2') === false,
        "FAIL",
        "All segments conform to Delivery Unit Media Segment found in Period " .
        ($mpdHandler->getSelectedPeriod() + 1) .
        ' Adaptation ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
        "Segment non-conforming to Delivery Unit Media Segment found for Period " .
        ($mpdHandler->getSelectedPeriod() + 1) .
        ' Adaptation ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1)
    );
    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
        "Each Initialization Segment, if present, SHALL conform to a CMAF Header as defined in CMAF 7.3.2.1",
        strpos($errors, 'CMAF checks violated: Section 7.3.2.1') === false,
        "FAIL",
        "All segments conform to CMAF Header in Period " . ($mpdHandler->getSelectedPeriod() + 1) .
        ' Adaptation ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
        "Segment non-conforming to CMAF Header found for Period " . ($mpdHandler->getSelectedPeriod() + 1) .
        ' Adaptation ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1)
    );
    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
        "Each Initialization Segment, if present, SHALL conform to an Initialization Segment as defined in 6.3.3",
        strpos($errors, 'ISO/IEC 23009-1:2012(E), 6.3.3') === false,
        "FAIL",
        "All segments conform to CMAF Initialization in Period " . ($mpdHandler->getSelectedPeriod() + 1) .
        ' Adaptation ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
        "Segment non-conforming to CMAF Initialization found for Period " . ($mpdHandler->getSelectedPeriod() + 1) .
        ' Adaptation ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1)
    );

    $moofBoxes = $xml->getElementsByTagName('moof');
    $trunBoxes = $xml->getElementsByTagName('trun');
    $errorInTrack = false;
    for ($j = 1; $j < $moofBoxes->length; $j++) {
        $cummulatedSampleDurFragPrev = $trunBoxes->item($j - 1)->getAttribute('cummulatedSampleDuration');
        $decodeTimeFragPrev = $tfdtBoxes->item($j - 1)->getAttribute('baseMediaDecodeTime');
        $decodeTimeFragCurr = $tfdtBoxes->item($j)->getAttribute('baseMediaDecodeTime');

        if ($decodeTimeFragCurr != $decodeTimeFragPrev + $cummulatedSampleDurFragPrev) {
            $errorInTrack = true;
        }
    }
    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
        "The Representation SHALL conform to a CMAF Track as defined in CMAF 7.3.2.2",
        !$errorInTrack,
        "FAIL",
        "Valid representation: Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
        "Invalid representation: Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1)
    );

    $startWithSAP = $adaptationSet['startWithSAP'];
    if ($representation['startWithSAP'] != null) {
        $startWithSAP = $representation['startWithSAP'];
    }
    if (strpos($errors, 'CMAF checks violated: Section 7.3.2.4') === false) {
        $dashConformsToCmafFrag[$representationId] = true;
        $logger->test(
            "DASH-IF IOP CR Low Latency Live",
            "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
            "If every DASH segment conforms to CMAF Fragment constraints and @startWithSAP is present, " .
            "it SHALL be set to value 1 or 2",
            $startWithSAP == null || $startWithSAP == '1' || $startWithSAP == '2',
            "FAIL",
            "Start sap has a valid value of $startWithSAP in Period " . ($mpdHandler->getSelectedPeriod() + 1) .
            ' Adaptation ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
            "Start sap has an invalid value of $startWithSAP in Period " . ($mpdHandler->getSelectedPeriod() + 1) .
            ' Adaptation ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1)
        );
    }
}

return $dashConformsToCmafFrag;
