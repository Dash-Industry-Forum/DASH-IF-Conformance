<?php

global $logger;

$parts = explode('.', $codec);
$pcl = strlen($parts[1]);
$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB: Section 5.1.3",
    "If (AVC video codec is) present the value of @codecs attribute SHALL be set in accordance with " .
    "RFC 6381, clause 3.3"
    $pcl == 6,
    "FAIL",
    "Valid avc codec value found",
    "Invalid avc codec value found: $codec"
);
