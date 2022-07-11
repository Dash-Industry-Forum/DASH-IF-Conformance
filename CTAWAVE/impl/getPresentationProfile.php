<?php

global $logger;

$presentationProfile = "";
if ($encryptedTrackFound === 0) {
    $presentationProfile = "CMFHD";
} elseif ($encryptedTrackFound && $cencSwSetFound && $cbcsSwSetFound) {
    fprintf($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 5: 'Each CMAF Presentation " .
    "Profile contains either all unencrypted samples or some samples encrypted with CENC using 'cenc' or 'cbcs' " .
    "scheme, but not both', here SwSet with 'cenc' and 'cbcs' are found.");
} elseif ($encryptedTrackFound && $cencSwSetFound) {
    $presentationProfile = "CMFHDc";
} elseif ($encryptedTrackFound && $cencSwSetFound) {
    $presentationProfile = "CMFHDs";
}

return $presentationProfile;
