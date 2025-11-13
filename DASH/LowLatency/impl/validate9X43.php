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
