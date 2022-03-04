<?php

global $mpd_dom, $dvb_conformance, $hbbtv_conformance;


global $logger;

$mpd_profiles = $mpd_dom->getAttribute('profiles');

$profilesArray = explode(',', $mpd_dom->getAttribute('profiles'));

if ($this->DVBEnabled) {
    if (
        !in_array('urn:dvb:dash:profile:dvb-dash:2014', $profilesArray) &&
        !in_array('urn:dvb:dash:profile:dvb-dash:isoff-ext-live:2014', $profilesArray) &&
        !in_array('urn:dvb:dash:profile:dvb-dash:isoff-ext-on-demand:2014', $profilesArray)
    ) {
        $profilesArray[] = 'urn:dvb:dash:profile:dvb-dash:2014';
    }
}

if (
    $hbbtv_conformance
) {
    if (!in_array('urn:hbbtv:dash:profile:isoff-live:2012', $profilesArray)) {
        $profilesArray[] = 'urn:hbbtv:dash:profile:isoff-live:2012';
    }
}

$profile_specific_MPDs = array();

foreach ($profilesArray as $profile) {
    $domDocument = new DOMDocument('1.0');
    $domElement = $domDocument->createElement('MPD');
    $domElement = $mpd_dom->cloneNode();

    $domElement->setAttribute('profiles', $profile);
    $domElement = DASHIF\Utility\recursive_generate($mpd_dom, $domDocument, $domElement, $profile);
    $domDocument->appendChild($domDocument->importNode($domElement, true));

    $profile_specific_MPDs[] = $domDocument;
}

## Compare each profile-specific MPD with the original MPD
$mpd_media_types = DASHIF\Utility\media_types($mpd_dom);
$ind = 0;

foreach ($profile_specific_MPDs as $profile_specific_MPD) {
    $mpd_media_types_new = DASHIF\Utility\media_types($profile_specific_MPD->getElementsByTagName('MPD')->item(0));

    $str = '';
    foreach ($mpd_media_types as $mpd_media_type) {
        if (!in_array($mpd_media_type, $mpd_media_types_new)) {
            $str = $str . " $mpd_media_type";
        }
    }
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "MPD",
        "??", ///\todo What does this actually check?
        $str == '',
        "FAIL",
        "All entries found for profile " . $profilesArray[$ind],
        $str . " not found for profile " . $profilesArray[$ind]
    );
    $ind++;
}
