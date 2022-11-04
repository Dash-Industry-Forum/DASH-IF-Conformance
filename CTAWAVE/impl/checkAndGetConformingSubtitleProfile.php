<?php

global $logger;

$mimeType = $mediaProfileParameters['mimeType'];

$mimeTypeConforms = $logger->test(
    "CTAWAVE",
    "WAVE Content Spec 2018Ed-Section 4.4.1",
    "Each WAVE subtitle Media Profile SHALL conform to normative ref. listed in Table 3",
    $mimeType == "application" || $mimeType == "ttml+xml" || $mimeType == "mp4",
    "FAIL",
    "Mimetype conformant for track $representationIndex of switching set $adaptationIndex",
    "Mimetype not conformant for track $representationIndex of switching set $adaptationIndex"
);

if (!$mimeTypeConforms) {
    return "unknown";
}

$codec = $mediaProfileParameters['codec'];
if ($codec == "im1t") {
    return "TTML_IMSC1_Text";
}
if ($codec == "im1i") {
    return "TTML_IMSC1_Image";
}

$mimeTypeConforms = $logger->test(
    "CTAWAVE",
    "WAVE Content Spec 2018Ed-Section 4.4.1",
    "Each WAVE subtitle Media Profile SHALL conform to normative ref. listed in Table 3",
    false,
    "FAIL",
    "",
    "Codec not conformant for track $representationIndex of switching set $adaptationIndex"
);
return "unknown";
