<?php

global $mpdHandler, $logger;


$segmentAccessInfo = array();
$segmentTemplateCombined = DASHIF\Utility\mergeSegmentAccess(
    $period['SegmentTemplate'],
    $adaptationSet['SegmentTemplate']
);
$producerReferenceTimes = $adaptationSet['ProducerReferenceTime'];
$inbandEventStreams = $adaptationSet['InbandEventStream'];

$representations = $adaptationSet['Representation'];
foreach ($representations as $representationId => $representation) {
    $validRepPoints[$representationId] = true;
    $segmentTemplateCombined = DASHIF\Utility\mergeSegmentAccess(
        $segmentTemplateCombined,
        $representation['SegmentTemplate']
    );
    $segmentAccessInfo[$representationId] = $segmentTemplateCombined;

    // Bullet 1
    if ($representation['ProducerReferenceTime'] != null) {
        $producerReferenceTimes = $representation['ProducerReferenceTime'];
    }

    $validProducerReferenceTime = false;
    $acceptedProducerReferenceTimes = array();
    $producerReferenceTimeIds = array();
    foreach ($producerReferenceTimes as $producerReferenceTime) {
        $referenceType = $producerReferenceTime["type"];
        if ($referenceType != null && $referenceType != 'encoder' && $referenceType != 'captured') {
            continue;
        }

        $utcTimingValid = false;
        $utcTiming = $producerReferenceTime['UTCTiming'];
        foreach ($this->utcTimingInfo as $utcTimingMPD) {
            if ($utcTiming != null) {
                if (nodes_equal($utcTiming[0], $utcTimingMPD)) {
                    $utcTimingValid = true;
                    break;
                }
            }
        }
        if (!$utcTimingValid) {
            continue;
        }

        $presentationTimeOffset = 0;
        if ($segmentTemplateCombined != null && $segmentTemplateCombined[0]['presentationTimeOffset'] != null) {
            $presentationTimeOffset = $segmentTemplateCombined[0]['presentationTimeOffset'];
        }
        if ($producerReferenceTime['presentationTime'] != $presentationTimeOffset) {
            continue;
        }

        $availabilityStartTime = $mpdHandler->getFeatures()['availabilityStartTime'];
        if ($availabilityStartTime != null) {
            if (
                (
                  DASHIF\Utility\timeParsing($availabilityStartTime) -
                  DASHIF\Utility\timeParsing($producerReferenceTime['wallClockTime'])
                ) != (int) ($producerReferenceTime['presentationTime'])
            ) {
                continue;
            }
        }

        $producerReferenceTimeIds[] = $producerReferenceTime['id'];

        $acceptedProducerReferenceTimes[] = $producerReferenceTime;
    }

    foreach ($acceptedProducerReferenceTimes as $referenceTime) {
        $indexes = array_keys($producerReferenceTime_ids, $referenceTime['id']);
        $producerReferenceTimeInbandPresent = false;
        if (sizeof($indexes) == 1) {
            $validProducerReferenceTime = true;

            if ($producerReferenceTime['inband'] != null) {
                $producerReferenceTimeInbandPresent = true;
            }

            break;
        }
    }
    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.3",
        "A low latency Adaptation Set SHALL include at least one ProducerReferenceTime element with a unique " .
        "@id, a @type of \'encoder\' or \'captured\', a UTCTiming identical to the one in the MPD, a " .
        "@wallClockTime equal to @presentationTime and a @presentationTime equal to @presentationTimeOffset " .
        "if present or 0 otherwise",
        $validProducerReferenceTime,
        "FAIL",
        "Corresponding element found in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' .
        ($adaptationSetId + 1) . ' or Represetation ' . ($representationId + 1),
        "No corresponding element found in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' .
        ($adaptationSetId + 1) . ' or Represetation ' . ($representationId + 1)
    );

    if (!$validProducerReferenceTime) {
        $validRepPoints[$representationId] = false;
    } else {
      ///\Correctness Check says information, spec says shall
        $logger->test(
            "DASH-IF IOP CR Low Latency Live",
            "Section 9.X.4.3",
            "A low latency Adaptation Set SHALL include at least one ProducerReferenceTime element where @inband " .
            "may be set to TRUE or FALSE",
            !$producerReferenceTimeInbandPresent,
            "WARN",
            "Corresponding element found in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' .
            ($adaptationSetId + 1) . ' or Represetation ' . ($representationId + 1),
            "No corresponding element found in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' .
            ($adaptationSetId + 1) . ' or Represetation ' . ($representationId + 1)
        );
    }

    $validSegmentTemplate = true;
    if ($segmentTemplateCombined == null) {
        $validSegmentTemplate = false;
    } else {
        $check1 = $segmentTemplateCombined[0]['duration'] != null &&
          strpos($segmentTemplateCombined[0]['media'], '$Number') !== false;
        $check2 = $segmentTemplateCombined[0]['SegmentTimeline'] != null &&
          strpos($segmentTemplateCombined[0]['media'], '$Number') !== false &&
          strpos($segmentTemplateCombined[0]['media'], '$Time') !== false;
        if (!($check1 || $check2)) {
            $validSegmentTemplate = false;
        }
    }
    // Bullet 3
    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.3",
        'A low latency Adaptation Set SHALL include either SegmentTemplate@duration and SegmentTemplate@media with ' .
        '$Number$ or SegmentTimeline and SegmentTemplate@media with $Number$ and $Time$',
        $validSegmentTemplate,
        "FAIL",
        "Corresponding Template found in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' .
        ($adaptationSetId + 1) . ' or Represetation ' . ($representationId + 1),
        "No corresponding Template found in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' .
        ($adaptationSetId + 1) . ' or Represetation ' . ($representationId + 1)
    );

    if (!$validSegmentTemplate) {
        $validRepPoints[$representationId] = false;
    }

    // Bullet 4
    $validInbandEventStreamPresent = false;
    if ($representation['InbandEventStream'] != null) {
        $inbandEventStreams = $representation['InbandEventStream'];
    }
    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.3",
        "Inband Event Streams carrying MPD validity expiration events as defined in clause 4.5 SHOULD be present",
        $inbandEventStreams != null,
        "WARN",
        "Inband Event Stream found in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' .
        ($adaptationSetId + 1) . ' or Represetation ' . ($representationId + 1),
        "Inband event stream not found in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' .
        ($adaptationSetId + 1) . ' or Represetation ' . ($representationId + 1)
    );
    if ($inbandEventStreams != null) {
        foreach ($inbandEventStreams as $inbandEventStream) {
          ///\Correctness these checks do not match the spec
            if ($inbandEventStream['schemeIdUri'] == 'urn:mpeg:dash:event:2012') {
                $logger->test(
                    "DASH-IF IOP CR Low Latency Live",
                    "Section 9.X.4.3",
                    "If Inband Event Streams carrying MPD validity expiration events as defined in clause 4.5 " .
                    "is used, the @value SHALL be set to 1",
                    $inbandEventStream['value'] == 1,
                    "WARN",
                    "Valid inband Event Stream found in Period " . ($mpdHandler->getSelectedPeriod() + 1) .
                    ' Adaptation Set ' . ($adaptationSetId + 1) . ' or Represetation ' . ($representationId + 1),
                    "Valid inband event stream not found in Period " . ($mpdHandler->getSelectedPeriod() + 1) . i
                    ' Adaptation Set ' . ($adaptationSetId + 1) . ' or Represetation ' . ($representationId + 1)
                );
                if ($inbandEventStream['value'] == '1') {
                    $validInbandEventStreamPresent = true;
                    break;
                }
            }
        }

        $logger->test(
            "DASH-IF IOP CR Low Latency Live",
            "Section 9.X.4.3",
            "Inband Event Streams carrying MPD validity expiration events as defined in clause 4.5 SHOULD be present",
            $validInbandEventStreamPresent,
            "WARN",
            "Inband Event Stream found in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' .
            ($adaptationSetId + 1) . ' or Represetation ' . ($representationId + 1),
            "Inband event stream not found in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' .
            ($adaptationSetId + 1) . ' or Represetation ' . ($representationId + 1)
        );
    }
}

$isLowLatencyAdaptation =  (sizeof(array_unique($validRepPoints)) == 1 && $validRepPoints[0] == true);

//The check below has two options, so we run both with a separate non-session-backed logger.
//Then, depending on the validity, we merge the correct logs into the session version
//NOTE: This does mean that if one option is valid, failing checks for the other will not get merged


$x44Logger = new ModuleLogger('');
$x45Logger = new ModuleLogger('');

$valid9X44 = $this->validate9X44(
    $adaptationSet,
    $adaptationSetId,
    $isLowLatencyAdaptation,
    $segmentAccessInfo,
    $infoFileAdapt,
    $x44Logger
);
$valid9X45 = $this->validate9X45(
    $adaptationSet,
    $adaptationSetId,
    $isLowLatencyAdaptation,
    $segmentAccessInfo,
    $infoFileAdapt,
    $x45Logger
);

if ($valid9X44 || !$valid9X45) {
    $logger->merge($x44Logger);
}
if ($valid9X45 || !$valid9X44) {
    $logger->merge($x45Logger);
}

$logger->test(
    "DASH-IF IOP CR Low Latency Live",
    "Section 9.X.4.3",
    "A Low Latency Adaptation Set SHALL either be a Low Latency Segment Adaptation Set or a Low Latency Chunked 
    Adaptation Set",
    $valid9X44 || $valid9X45,
    "FAIL",
    "Valid for Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' .
    ($adaptationSetId + 1) . ' or Represetation ' . ($representationId + 1),
    "Neither found in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' .
    ($adaptationSetId + 1) . ' or Represetation ' . ($representationId + 1)
);

$this->validate9X42($adaptationSet, $adaptationSetId, $valid9X44);

return $isLowLatencyAdaptation;
