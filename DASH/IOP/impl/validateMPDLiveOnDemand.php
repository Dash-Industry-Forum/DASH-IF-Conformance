<?php

global $mpdHandler, $logger;

$periods = $mpdHandler->getFeatures()['Period'];
foreach ($periods as $periodIndex => $period) {
    $adaptationSets = $period['AdaptationSet'];
    foreach ($adaptationSets as $adaptationSetIndex => $adaptationSet) {
        $representations = $adaptationSet['Representation'];
        foreach ($representations as $representationIndex => $representation) {
            $representationProfiles = $mpdHandler->getProfiles()[$periodIndex][$adaptationSetIndex][$representationIndex];
            if (strpos($representationProfiles, 'http://dashif.org/guidelines/dash-if-ondemand') !== false) {
                $segmentTemplate = DASHIF\Utility\getSegmentAccess(
                    $segmentTemplate,
                    $representation['SegmentTemplate']
                );
                $logger->test(
                    "DASH-IF IOP 4.3",
                    "Section 3.10.3",
                    "SegmentTemplate@media attribute SHALL be present",
                    $segmentTemplate != null && $segmentTemplate['media'] != null,
                    "FAIL",
                    "SegmentTemplate@media found for period $periodIndex, adaptation $adaptationSetIndex, " .
                    "representation $representationIndex",
                    "SegmentTemplate@media not found for period $periodIndex, adaptation $adaptationSetIndex, " .
                    "representation $representationIndex"
                );
            }
        }
    }
}
