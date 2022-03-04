<?php

global $mpd_features, $logger;
$logger->test(
    "DASH-IF IOP CR Low Latency Live",
    "Section 9.X.4.1",
    "A Media Presentation that follows a DASH-IF Low-Latency Service Offering according to this specification " .
    "SHOULD be signalled with the @profiles identifier 'http://www.dashif.org/guidelines/low-latency-live-v5'",
    (strpos($mpd_features['profiles'], 'http://www.dashif.org/guidelines/low-latency-live-v5') !== false),
    "WARN",
    "Identifier found",
    "Identifier not found"
);
