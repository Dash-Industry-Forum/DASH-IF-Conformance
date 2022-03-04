<?php

global $mpd_features, $logger;

$logger->test(
    "DASH-IF IOP CR Low Latency Live",
    "Section 9.X.4.2",
    "'Low latency content SHOULD provide a LeapSecondInformation element providing correction for leap seconds'",
    $mpd_features['LeapSecondInformation'] != null,
    "WARN",
    "LeapSecondInformation element found in MPD",
    "LeapSecondInformation element not found in MPD"
);
