<?php

global $mpd_features;

$mpd_profiles = $mpd_features['profiles'];
if (strpos($mpd_profiles, 'http://dashif.org/guidelines/dash') !== false) {
    $this->validateMPDCommon();
}
if (
    strpos($mpd_profiles, 'http://dashif.org/guidelines/dash') !== false &&
    strpos($mpd_profiles, 'urn:mpeg:dash:profile:isoff-live:2011') !== false
) {
    $this->validateMPDLiveOnDemand();
}
if (strpos($mpd_profiles, 'http://dashif.org/guidelines/dash-if-ondemand') !== false) {
    $this->validateMPDOnDemand();
}
if (strpos($mpd_profiles, 'http://dashif.org/guidelines/dash-if-mixed') !== false) {
    $this->validateMPDMixedOnDemand();
}
