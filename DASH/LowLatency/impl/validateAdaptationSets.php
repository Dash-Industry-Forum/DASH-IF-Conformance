<?php

global $mpdHandler, $logger;

$period =  $mpdHandler->getFeatures()['Period'][$mpdHandler->getSelectedPeriod()];
$adaptationSets = $period['AdaptationSet'];

// At least one low latency adaptation set for each media type
$adaptations = array('video' => array(), 'audio' => array(), 'subtitle' => array());

foreach ($adaptationSets as $adaptationSetId => $adaptationSet) {
    $mediaType = ($adaptationSet['mimeType'] != null) ?
      $adaptationSet['mimeType'] : $adaptationSet['Representation'][0]['mimeType'];

    if (strpos($mediaType, 'video') !== false) {
        $adaptations['video'][$adaptationSetId] = $adaptationSet;
    }
    if (strpos($mediaType, 'audio') !== false) {
        $adaptations['audio'][$adaptationSetId] = $adaptationSet;
    }
    if (strpos($mediaType, 'application') !== false || strpos($media_type, 'text') !== false) {
        $adaptations['subtitle'][$adaptationSetId] = $adaptationSet;
    }
}

foreach ($adaptations as $adaptationGroupName => $adaptationGroup) {
    foreach ($adaptationGroup as $adaptationId => $adaptation) {
        $isAdaptLL = array();
        $infoFileAdaptation = $this->readInfoFile($adaptation, $adaptationId);

        $audioPresent = ($adaptations['audio'] != null);
        $isAdaptLL[] = $this->validate9X43(
            $period,
            $adaptation,
            $adaptationId,
            $infoFileAdaptation,
            $audioPresent,
            $adaptationGroupName
        );
    }

    ///\Correctness this check only parses the last group as is
    if ($adaptationGroup != null) {
        $conformingAdaptationIds = array_keys($isAdaptLL, true);
        $logger->test(
            "DASH-IF IOP CR Low Latency Live",
            "Section 9.X.4.2",
            "For each media type at least one Low Latency Adaptation Set SHALL be present",
            $conformingAdaptationIds != null,
            "FAIL",
            "Low Latency AdaptationSet found for type $adaptationGroupName",
            "Low Latency AdaptationSet not found for type $adaptationGroupName"
        );
    }
}
