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

    // Bullet 4
    $validInbandEventStreamPresent = false;
    if ($representation['InbandEventStream'] != null) {
        $inbandEventStreams = $representation['InbandEventStream'];
    }
    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.2",
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
                    "Section 9.X.4.2",
                    "If Inband Event Streams carrying MPD validity expiration events as defined in clause 4.5 " .
                    "is used, the @value SHALL be set to 1",
                    $inbandEventStream['value'] == 1,
                    "WARN",
                    "Valid inband Event Stream found in Period " . ($mpdHandler->getSelectedPeriod() + 1) .
                    ' Adaptation Set ' . ($adaptationSetId + 1) . ' or Represetation ' . ($representationId + 1),
                    "Valid inband event stream not found in Period " . ($mpdHandler->getSelectedPeriod() + 1) .
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


$x44Logger = new DASHIF\ModuleLogger('', $logger->getCurrentModule(), $logger->getCurrentHook());
$x45Logger = new DASHIF\ModuleLogger('', $logger->getCurrentModule(), $logger->getCurrentHook());

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
