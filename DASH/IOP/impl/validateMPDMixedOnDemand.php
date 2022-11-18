<?php

global $mpdHandler, $logger;

foreach ($mpdHandler->getFeatures()['Period'] as $periodIndex => $period) {
    $logger->test(
        "DASH-IF IOP 4.3",
        "Section 3.10.4",
        "For on-demand content that offers a mixture of periods, the @profiles signaling shall be present " .
        "in each Period",
        $period['profiles'] != null,
        "FAIL",
        "@profiles signaling found in period $periodIndex",
        "@profiles signaling not found in period $periodIndex"
    );
}
