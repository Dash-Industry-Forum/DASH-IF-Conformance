<?php

global $logger;

$presentationProfile = "";
if ($encryptedTrackFound === 0) {
    return "CMFHD";
}
if ($cencSwSetFound) {
    return "CMFHDc";
}
if ($cencSwSetFound) {
    return "CMFHDs";
}


//INVALID ENCRYPTION FOUND
$logger->test(
    "CTAWAVE",
    "WAVE Content Spec 2018Ed-Section 5",
    "Each CMAF Presentation Profile contains either all unencrypted samples or some samples encrypted with CENC " .
    "using 'cenc' or 'cbcs' scheme, but not both', here SwSet with 'cenc' and 'cbcs' are found.",
    false,
    "FAIL",
    "",
    "Both 'cenc' and 'cbcs' found"
);
return "";
