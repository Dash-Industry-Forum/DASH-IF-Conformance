<?php

global $mpdHandler, $logger;

$logger->test(
    "DASH-IF IOP CR Low Latency Live",
    "Section 9.X.4.2",
    "'Low latency content SHOULD provide a LeapSecondInformation element providing correction for leap seconds'",
    $mpdHandler->getFeatures()['LeapSecondInformation'] != null,
    "WARN",
    "LeapSecondInformation element found in MPD",
    "LeapSecondInformation element not found in MPD"
);
