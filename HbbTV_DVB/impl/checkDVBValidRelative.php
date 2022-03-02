<?php

global $mpd_dom, $logger;

$baseURLs = $mpd_dom->getElementsByTagName('BaseURL');
foreach ($baseURLs as $url) {
    $isRelative = !isAbsoluteURL($url->nodeValue);
    $isValidRelative = true;
    if (!$isRelative) {
        //Note: Changed this check to conform to written description.
        if (
            $url->getAttribute('serviceLocation') != '' ||
            $url->getAttribute('priority') != '' ||
            $url->getAttribute('weight') != ''
        ) {
            $isValidRelative = false;
        }
    }
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "DVB: Section 11.9.5",
        "'Where BaseURLs contain relative URLs, these SHOULD NOT include " .
        "@serviceLocation, @priority or @weight attributes'",
        !$isRelative || $isValidRelative,
        "WARN",
        ($isRelative ? "Relative URLS do not contain the mentioned attributes" : "No relative urls found"),
        "Relative URLS found for a least one of the attributes"
    );
}
