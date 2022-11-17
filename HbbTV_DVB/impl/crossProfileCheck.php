<?php

global $mpdHandler, $logger;


$profilesArray = explode(',', $mpdHandler->getDom()->getAttribute('profiles'));

$supported_profiles = array('urn:mpeg:dash:profile:isoff-on-demand:2011',
  'urn:mpeg:dash:profile:isoff-live:2011',
  'urn:mpeg:dash:profile:isoff-main:2011',
  'http://dashif.org/guidelines/dash264',
  'urn:dvb:dash:profile:dvb-dash:2014',
  'urn:hbbtv:dash:profile:isoff-live:2012',
  'urn:dvb:dash:profile:dvb-dash:isoff-ext-live:2014',
  'urn:dvb:dash:profile:dvb-dash:isoff-ext-on-demand:2014');

foreach ($profilesArray as $profile) {
    $profile_found = false;
    foreach ($supported_profiles as $supported_profile) {
        if (strpos($profile, $supported_profile) !== false) {
            $profile_found = true;
        }
    }

    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "MPD",
        "Validated MPD element scopes",
        $profile_found,
        "PASS",
        "Tool validates against profile " . $profile,
        "Tool doesn't validate against profile " . $profile
    );
}
