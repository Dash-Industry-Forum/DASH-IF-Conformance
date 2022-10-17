<?php

global $mpd_dom, $logger;

$validateYearMonth = function ($property, $location) {
    $logger->test(
        "DASH-IF IOP",
        "Section 3.2.7.4",
        "MPD fields having datatype xs:duration shall not use year or month units",
        !checkYearMonth($property),
        "FAIL",
        "$location is valid",
        "$location contains year and/or month units",
    );
};

$validateYearMonth($mpd_dom->getAttribute('mediaPresentationDuration'), "@mediaPresentationDuration in MPD");
$validateYearMonth($mpd_dom->getAttribute('minimumUpdatePeriod'), "@minimumUpdatePeriod in MPD");
$validateYearMonth($mpd_dom->getAttribute('minBufferTime'), "@minBufferTime in MPD");
$validateYearMonth($mpd_dom->getAttribute('timeShiftBufferDepth'), "@timeShiftBufferDepth in MPD");
$validateYearMonth($mpd_dom->getAttribute('suggestedPresentationDelay'), "@suggestedPresentationDelay in MPD");
$validateYearMonth($mpd_dom->getAttribute('maxSegmentDuration'), "@maxSegmentDuration in MPD");
$validateYearMonth($mpd_dom->getAttribute('maxSubSegmentDuration'), "@maxSubSegmentDuration in MPD");

foreach ($mpd_dom->getElementsByTagName('Period') as $period) {
    $validateYearMonth(
        $period->getAttribute('start'),
        "@start for " . $period->getNodePath()
    );
    $validateYearMonth(
        $period->getAttribute('duration'),
        "@duration for " . $period->getNodePath()
    );
}

foreach ($mpd_dom->getElementsByTagName('RandomAccess') as $access) {
    $validateYearMonth(
        $access->getAttribute('minBufferTime'),
        "@minBufferTime for " . $access->getNodePath()
    );
}

foreach ($mpd_dom->getElementsByTagName('SegmentTemplate') as $template) {
    $validateYearMonth(
        $template->getAttribute('timeShiftBufferDepth'),
        "@timeShiftBufferDepth for " . $template->getNodePath()
    );
}
foreach ($mpd_dom->getElementsByTagName('SegmentBase') as $base) {
    $validateYearMonth(
        $base->getAttribute('timeShiftBufferDepth'),
        "@timeShiftBufferDepth for " . $base->getNodePath()
    );
}
foreach ($mpd_dom->getElementsByTagName('SegmentList') as $list) {
    $validateYearMonth(
        $list->getAttribute('timeShiftBufferDepth'),
        "@timeShiftBufferDepth for " . $list->getNodePath()
    );
}


foreach ($mpd_dom->getElementsByTagName('Range') as $range) {
    $validateYearMonth($range->getAttribute('time'), "@time for " . $range->getNodePath());
    $validateYearMonth($range->getAttribute('duration'), "@duration for " . $range->getNodePath());
}
