<?php

if (!$this->mpd || !$this->dom) {
    return;
}

$this->segmentUrls = array();

$xmlPeriods = $this->dom->getElementsByTagName('Period');


$mpdAsArray = $this->features;

if (!array_key_exists('Period', $mpdAsArray)) {
    return;
}

foreach ($mpdAsArray['Period'] as $periodIdx => $period) {
    $baseUrls = $this->getPeriodBaseUrl($periodIdx);
    $periodTimingInfo = $this->getPeriodTimingInfo($periodIdx);

    $currentTemplate = $period['SegmentTemplate'];
    $currentBase = $period['SegmentBase'];

    $periodUrls = array();

    $adaptations = $period['AdaptationSet'];
    foreach ($adaptations as $adaptationIdx => $adaptation) {
        $currentTemplate = DASHIF\Utility\mergeSegmentAccess(
            $currentTemplate,
            $adaptation['SegmentTemplate']
        );
        $currentBase = DASHIF\Utility\mergeSegmentAccess(
            $currentBase,
            $adaptation['SegmentBase']
        );

        $adaptationUrls = array();

        foreach ($adaptation['Representation'] as $representationIdx => $representation) {
            $currentTemplate = DASHIF\Utility\mergeSegmentAccess(
                $currentTemplate,
                $representation['SegmentTemplate']
            );
            $currentBase = DASHIF\Utility\mergeSegmentAccess(
                $currentBase,
                $representation['SegmentBase']
            );


            if (!$currentTemplate || !count($currentTemplate)) {
                $adaptationUrls[] = array($baseUrls[$adaptationIdx][$representationIdx]);
                continue;
            }

            $segmentInfo = $this->computeTiming(
                $periodTimingInfo['duration'],
                $currentTemplate[0],
                'SegmentTemplate'
            );
            $urlObj = array();
            $urlObj['segments'] = $this->computeUrls(
                $representation,
                $adaptationIdx,
                $representationIdx,
                $currentTemplate[0],
                $segmentInfo,
                $baseUrls[$adaptationIdx][$representationIdx]
            );
            if (array_key_exists('initialization', $currentTemplate[0])) {
                $urlObj['init'] = array_shift($urlObj['segments']);
            }
            $adaptationUrls[] = $urlObj;
        }

        $periodUrls[] = $adaptationUrls;
    }

    $this->segmentUrls[] = $periodUrls;
}
