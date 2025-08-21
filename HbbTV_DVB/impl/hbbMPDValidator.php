<?php

global $logger, $mpdHandler;

//NOTE: Uses TLS Bitrate check from DVB as well

$docType = $mpdHandler->getDom()->doctype;
$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "HbbTV: Section 'MPD'",
    "The MPD must not contain an XML Document Type Definition",
    $docType === null,
    "FAIL",
    "No Doctype found",
    "Doctype found",
);

//TODO: Re-implement check for "HbbTV Section E.2.5", as it allows for an older schemeId as well

