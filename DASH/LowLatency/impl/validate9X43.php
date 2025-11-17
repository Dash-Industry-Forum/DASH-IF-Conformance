<?php

global $mpdHandler, $logger;

$x45Logger = new DASHIF\ModuleLogger('', $logger->getCurrentModule(), $logger->getCurrentHook());

$valid9X45 = $this->validate9X45(
    $adaptationSet,
    $adaptationSetId,
    $isLowLatencyAdaptation,
    $segmentAccessInfo,
    $infoFileAdapt,
    $x45Logger
);

if ($valid9X45) {
    $logger->merge($x45Logger);
}


$this->validate9X42($adaptationSet, $adaptationSetId, $valid9X44);

return $isLowLatencyAdaptation;
