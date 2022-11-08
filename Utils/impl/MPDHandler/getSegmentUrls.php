<?php

global $segment_accesses;

$periodIdx = $periodIndex;
if ($periodIdx == null) {
    $periodIdx = $this->selectedPeriod;
}

$periodTimingInfo = $this->getPeriodTimingInfo($periodIdx);
$baseUrls = $this->getPeriodBaseUrl($periodIdx);

$period = $this->features['Period'][$periodIdx];
$adaptationSets = $period['AdaptationSet'];
$adaptationSegmentUrls = array();

foreach ($adaptationSets as $adaptationIndex => $adaptationSet) {
    $segmentTemplateAdaptation = DASHIF\Utility\mergeSegmentAccess($period['SegmentTemplate'], $adaptationSet['SegmentTemplate']);
    $segmentBaseAdaptation = DASHIF\Utility\mergeSegmentAccess($period['SegmentBase'], $adaptationSet['SegmentBase']);



    $representations = $adaptationSet['Representation'];
    $segmentAccess = array();
    $segmentUrls = array();
    foreach ($representations as $representationIndex => $representation) {
        $segmentTemplate = DASHIF\Utility\mergeSegmentAccess($segmentTemplateAdaptation, $representation['SegmentTemplate']);
        $segmentBase = DASHIF\Utility\mergeSegmentAccess($segmentBaseAdaptation, $representation['SegmentBase']);

        if ($segmentTemplate) {
            $segmentAccess[] = $segmentTemplate;
            $segmentInfo = $this->computeTiming($periodTimingInfo['duration'], $segmentTemplate[0], 'SegmentTemplate');
            $segmentUrls[] = $this->computeUrls($representation, $adaptationIndex, $representationIndex, $segmentTemplate[0], $segmentInfo, $baseUrls[$adaptationIndex][$representationIndex]);
            continue;
        }
        if ($segmentBase) {
            $segmentAccess[] = $segmentBase;
            $segmentUrls[] = array($baseUrls[$adaptationIndex][$representationIndex]);
            continue;
        }
        $segmentAccess[] = '';
        $segmentUrls[] = array($baseUrls[$adaptationIndex][$representationIndex]);
    }
    $adaptationSegmentUrls[] = $segmentUrls;
    $segment_accesses[] = $segmentAccess;
}

return $adaptationSegmentUrls;
