<?php

global $mpdHandler, $logger;

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

$validateYearMonth(
    $mpdHandler->getDom()->getAttribute('mediaPresentationDuration'),
    "@mediaPresentationDuration in MPD"
);
$validateYearMonth(
    $mpdHandler->getDom()->getAttribute('minimumUpdatePeriod'),
    "@minimumUpdatePeriod in MPD"
);
$validateYearMonth(
    $mpdHandler->getDom()->getAttribute('minBufferTime'),
    "@minBufferTime in MPD"
);
$validateYearMonth(
    $mpdHandler->getDom()->getAttribute('timeShiftBufferDepth'),
    "@timeShiftBufferDepth in MPD"
);
$validateYearMonth(
    $mpdHandler->getDom()->getAttribute('suggestedPresentationDelay'),
    "@suggestedPresentationDelay in MPD"
);
$validateYearMonth(
    $mpdHandler->getDom()->getAttribute('maxSegmentDuration'),
    "@maxSegmentDuration in MPD"
);
$validateYearMonth(
    $mpdHandler->getDom()->getAttribute('maxSubSegmentDuration'),
    "@maxSubSegmentDuration in MPD"
);

foreach ($mpdHandler->getDom()->getElementsByTagName('Period') as $period) {
    $validateYearMonth(
        $period->getAttribute('start'),
        "@start for " . $period->getNodePath()
    );
    $validateYearMonth(
        $period->getAttribute('duration'),
        "@duration for " . $period->getNodePath()
    );
}

foreach ($mpdHandler->getDom()->getElementsByTagName('RandomAccess') as $access) {
    $validateYearMonth(
        $access->getAttribute('minBufferTime'),
        "@minBufferTime for " . $access->getNodePath()
    );
}

foreach ($mpdHandler->getDom()->getElementsByTagName('SegmentTemplate') as $template) {
    $validateYearMonth(
        $template->getAttribute('timeShiftBufferDepth'),
        "@timeShiftBufferDepth for " . $template->getNodePath()
    );
}
foreach ($mpdHandler->getDom()->getElementsByTagName('SegmentBase') as $base) {
    $validateYearMonth(
        $base->getAttribute('timeShiftBufferDepth'),
        "@timeShiftBufferDepth for " . $base->getNodePath()
    );
}
foreach ($mpdHandler->getDom()->getElementsByTagName('SegmentList') as $list) {
    $validateYearMonth(
        $list->getAttribute('timeShiftBufferDepth'),
        "@timeShiftBufferDepth for " . $list->getNodePath()
    );
}


foreach ($mpdHandler->getDom()->getElementsByTagName('Range') as $range) {
    $validateYearMonth($range->getAttribute('time'), "@time for " . $range->getNodePath());
    $validateYearMonth($range->getAttribute('duration'), "@duration for " . $range->getNodePath());
}
