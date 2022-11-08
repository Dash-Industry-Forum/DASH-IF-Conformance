<?php

global $adapt_video_count, $adapt_audio_count, $main_audio_found, $main_audios, $hoh_subtitle_lang, $period_count,
            $audio_bw, $video_bw, $subtitle_bw, $supported_profiles, $mpd_dom, $mpd_url;
global $onRequest_array, $xlink_not_valid_array;

global $logger;


$onRequestValue = "";
if (!empty($onRequest_array)) {
    $onRequestValue  = implode(', ', array_map(
        function ($v, $k) {
            return sprintf(" %s with index (starting from 0) '%s'", $v, $k);
        },
        $onRequest_array,
        array_keys($onRequest_array)
    ));
}

$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB: Section 'xlink'",
    "MPD SHALL NOT have xlink:actuate set to onRequest'",
    $onRequestValue == '',
    "FAIL",
    "No onRequest set",
    "onRequest set to " . $onRequestValue
);

$xlinkNotValidValue = "";
if (!empty($xlink_not_valid_array)) {
    $xlinkNotValidValue  = implode(', ', array_map(
        function ($v, $k) {
            return sprintf(" %s with index (starting from 0) '%s'", $v, $k);
        },
        $xlink_not_valid_array,
        array_keys($xlink_not_valid_array)
    ));
}

$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB: Section 'xlink'",
    "Check for valid 'xlink:href'",
    $xlinkNotValidValue == '',
    "FAIL",
    "Valid 'xlink:href' found",
    "Invalid 'xlink:href' found in: " . $xlinkNotValidValue
);

$this->tlsBitrateCheck();


$mpd_doc = get_doc($mpd_url);
$mpd_string = $mpd_doc->saveXML();
$mpd_bytes = strlen($mpd_string);

$logger->test(
    "DVB",
    "Section 4.5",
    "The MPD size after xlink resolution SHALL NOT exceed 256 Kbytes",
    $mpd_bytes <= 1024 * 256,
    "FAIL",
    "MPD size of " . $mpd_bytes . " bytes is within bounds",
    "MPD size of " . $mpd_bytes . " bytes is not within bounds",
);

## Warn on low values of MPD@minimumUpdatePeriod (for now the lowest possible value is assumed to be 1 second)
$minimumUpdateWarning = false;
if ($mpd_dom->getAttribute('minimumUpdatePeriod') != '') {
    $mup = DASHIF\Utility\timeParsing($mpd_dom->getAttribute('minimumUpdatePeriod'));
    if ($mup < 1) {
        $minimumUpdateWarning = true;
    }
}

$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB: Section 'MPD'",
    "MPD@minimumUpdatePeriod should have a value of 1 second or higher",
    $minimumUpdateWarning == false,
    "WARN",
    "Check succeeded",
    "Check failed"
);


$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB: Section E.2.1",
    "The MPD SHALL indicate either or both of the following profiles: \"urn:dvb:dash:profile:dvb-dash:2014\"" .
    " and \"urn:hbbtv:dash:profile:isoff-live:2012\"",
    DASHIF\Utility\mpdProfilesContainsAtLeastOne(
        array("urn:dvb:dash:profile:dvb-dash:2014","urn:hbbtv:dash:profile:isoff-live:2012")
    ),
    "FAIL",
    "Check succeeded",
    "Check failed"
);


$containsDVBDash = DASHIF\Utility\mpdContainsProfile('urn:dvb:dash:profile:dvb-dash:2014');
$containsExtension = DASHIF\Utility\mpdProfilesContainsAtLeastOne(
    array('urn:dvb:dash:profile:dvb-dash:isoff-ext-live:2014','urn:dvb:dash:profile:dvb-dash:isoff-ext-on-demand:2014')
);


$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB: Section 11.1",
    "All Representations that are intended to be decoded and presented by a DVB conformant Player SHOULD " .
    "be such that they will be inferred to have an @profiles attribute that includes the profile name defined " .
    "in clause 4.1 as well as either the one defined in 4.2.5 or the one defined in 4.2.8'",
    $containsDVBDash && $containsExtension,
    "WARN",
    "Check succeeded",
    "Check failed: Contains clause 4.1: " . ($containsDVBDash ? "Yes" : "No") .
    ", contains either 4.2.5 or 4.2.8: " . ($containsExtension ? "Yes" : "No"),
);

$profileExists = ($containsDVBDash && $containsExtension);


$this->checkDVBValidRelative();

## Verifying the DVB Metric reporting mechanism according to Section 10.12.3
$this->dvbMetricReporting();

$type = $mpd_dom->getAttribute('type');
$AST = $mpd_dom->getAttribute('availabilityStartTime');

if ($type == 'dynamic' || $AST != '') {
    $UTCTimings = $mpd_dom->getElementsByTagName('UTCTiming');
    $acceptedURIs = array('urn:mpeg:dash:utc:ntp:2014',
                          'urn:mpeg:dash:utc:http-head:2014',
                          'urn:mpeg:dash:utc:http-xsdate:2014',
                          'urn:mpeg:dash:utc:http-iso:2014',
                          'urn:mpeg:dash:utc:http-ntp:2014');

    $utcTimingValid = false;
    foreach ($UTCTimings as $UTCTiming) {
        if (in_array($UTCTiming->getAttribute('schemeIdUri'), $acceptedURIs)) {
            $utcTimingValid = true;
        }
    }

    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "DVB: Section 4.7.2",
        "'If the MPD is dynamic or if the MPD@availabilityStartTime is present then the MPD SHOULD countain at least " .
        "one UTCTiming element with the @schemeIdUri attribute set to one of the following: " .
        join(', ', $acceptedTimingURIs),
        $utcTimingValid,
        "WARN",
        "At least one valid UTCTiming element found",
        "None of thevalid UTCTiming elements found"
    );
}

$this->periodCount = 0;

$hasVideoService = false;

$cencAttribute = $mpd_dom->getAttribute("xmlns:cenc");

foreach ($mpd_dom->childNodes as $node) {
    if ($node->nodeName != 'Period') {
        continue;
    }
    $this->periodCount++;

    $this->adaptationVideoCount = 0;
    $this->adaptationAudioCount = 0;
    $this->mainVideoFound = false;
    $this->mainAudios = array();

    $invalidSegmentListFound = false;
    $invalidSegmentTemplateFound = false;

    foreach ($node->childNodes as $child) {
        if ($child->nodeName == 'SegmentList') {
            $invalidSegmentListFound = true;
        }

        if ($child->nodeName == 'EventStream') {
            $this->dvbEventChecks($child);
        }
        if ($child->nodename == 'SegmentTemplate') {
            if (DASHIF\Utility\mpdContainsProfile('urn:dvb:dash:profile:dvb-dash:isoff-ext-on-demand:2014')) {
                $invalidSegmentTemplateFound = true;
            }
        }
    }

    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "DVB: Section 4.2.2",
        "'The period SegmentList SHALL not be present'",
        !$invalidSegmentListFound,
        "FAIL",
        "Period SegmentList not found for period $this->periodCount",
        "Period SegmentList found for period $this->periodCount"
    );
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "DVB: Section 4.2.6",
        "'The period SegmentTemplate SHALL not be present for Period elements conforming to On Demand profile'",
        !$invalidSegmentTemplateFound,
        "FAIL",
        "Period SegmentTemplate not found or no On Demand profile in period $this->periodCount",
        "Period SegmentTemplate found for On Demand profile in period $this->periodCount"
    );


    // Adaptation Sets within each Period
    $adaptationSets = $node->getElementsByTagName('AdaptationSet');
    $adaptationSetCount = $adaptationSets->length;

    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "DVB: Section 4.5",
        "'The MPD has a maximum of 16 adaptation sets per period'",
        $adaptationSetCount <= 16,
        "FAIL",
        "$adaptationSetCount adaption sets found for period $this->periodCount",
        "$adaptationSetCount adaption sets found for period $this->periodCount, exceeding the maximum"
    );

    $audioAdaptations = array();
    for ($i = 0; $i < $adaptationSetCount; $i++) {
        $adaptationSet = $adaptationSets->item($i);
        $videoFound = false;
        $audioFound = false;

        $adaptationSetProfiles = $adaptationSet->getAttribute('profiles');
        $adaptationContainsDVBDash = DASHIF\Utility\profileListContainsProfile(
            $adaptationSetProfiles,
            'urn:dvb:dash:profile:dvb-dash:2014'
        );
        $adaptationContainsExtension = DASHIF\Utility\profileListContainsAtLeastOne(
            $adaptationSetProfiles,
            array('urn:dvb:dash:profile:dvb-dash:isoff-ext-live:2014',
            'urn:dvb:dash:profile:dvb-dash:isoff-ext-on-demand:2014')
        );
        $adaptationSetProfileExists = ($adaptationContainsDVBDash && $adaptationContainsExtension);

        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "DVB: Section 11.1",
            "All Representations that are intended to be decoded and presented by a DVB conformant Player SHOULD " .
            "be such that they will be inferred to have an @profiles attribute that includes the profile name " .
            "defined in clause 4.1 as well as either the one defined in 4.2.5 or the one defined in 4.2.8'",
            $adaptationSetProfileExists,
            "WARN",
            "Check succeeded",
            "Check failed: Contains clause 4.1: " . ($adaptationSetContainsDVBDash ? "Yes" : "No") .
            ", contains either 4.2.5 or 4.2.8: " . ($adaptationSetContainsExtension ? "Yes" : "No"),
        );

        $representations = $adaptationSet->getElementsByTagName("Representation");
        $representationCount = $representations->length;

        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "DVB: Section 4.5",
            "The MPD has a maximum of 16 represtentations per adaptation set",
            $representationCount <= 16,
            "FAIL",
            "$representationCount representations in period $this->periodCount, set " . ($i + 1),
            "$representationCount representations in period $this->periodCount, set " . ($i + 1) . ", exceeding bound",
        );

        $videoComponentFound = false;
        $audioComponentFound = false;

        if ($ch) {
            $contentComponents = $ch->getElementsByTagName("ContentComponent");
            foreach ($contentComponents as $component) {
                $contentType = $component->getAttribute("contentType");
                if ($contentType == "video") {
                    $videoComponentFound = true;
                }
                if ($contentType == "audio") {
                    $audioComponentFound = true;
                }
            }
        }

        foreach ($representations as $representation) {
            if ($profileExists && $adaptationSetProfileExists) {
                $representationProfiles = $adaptationSet->getAttribute('profiles');
                $representationContainsDVBDash = DASHIF\Utility\profileListContainsProfile(
                    $representationProfiles,
                    'urn:dvb:dash:profile:dvb-dash:2014'
                );
                $representationContainsExtension = DASHIF\Utility\profileListContainsAtLeastOne(
                    $representationProfiles,
                    array('urn:dvb:dash:profile:dvb-dash:isoff-ext-live:2014',
                    'urn:dvb:dash:profile:dvb-dash:isoff-ext-on-demand:2014')
                );
                $representationProfileExists = ($representationContainsDVBDash && $representationContainsExtension);
                $logger->test(
                    "HbbTV-DVB DASH Validation Requirements",
                    "DVB: Section 11.1",
                    "All Representations that are intended to be decoded and presented by a DVB conformant Player " .
                    "SHOULD be such that they will be inferred to have an @profiles attribute that includes the " .
                    "profile name defined in clause 4.1 as well as either the one defined in 4.2.5 or the one " .
                    "defined in 4.2.8'",
                    $representationProfileExists,
                    "WARN",
                    "Check succeeded",
                    "Check failed: Contains clause 4.1: " . ($representationContainsDVBDash ? "Yes" : "No") .
                    ", contains either 4.2.5 or 4.2.8: " . ($representationContainsExtension ? "Yes" : "No"),
                );

                $mimeType = $representation->getAttribute("mimeType");
                if (strpos($mimeType, "video") !== false) {
                    $videoFound = true;
                }
                if (strpos($mimeType, "audio") !== false) {
                    $audioFound = true;
                }

                if (
                    $profileExists &&
                    ($adaptationSetProfiles == '' ||  $adaptationSetProfileExists) &&
                    ($representationProfiles == '' || $representationProfileExists)
                ) {
                    $subRepresentations = $representation->getElementsByTagName("SubRepresentation");
                    foreach ($subRepresentations as $subRep) {
                        $subRepProfiles = $subRep->getAttribute('profiles');
                        $subRepContainsDVBDash = DASHIF\Utility\profileListContainsProfile(
                            $subRepProfiles,
                            'urn:dvb:dash:profile:dvb-dash:2014'
                        );
                        $subRepContainsExtension = DASHIF\Utility\profileListContainsAtLeastOne(
                            $subRepProfiles,
                            array('urn:dvb:dash:profile:dvb-dash:isoff-ext-live:2014',
                            'urn:dvb:dash:profile:dvb-dash:isoff-ext-on-demand:2014')
                        );
                        $subRepProfileExists = ($subRepContainsDVBDash && $subRepContainsExtension);
                        $logger->test(
                            "HbbTV-DVB DASH Validation Requirements",
                            "DVB: Section 11.1",
                            "All Representations that are intended to be decoded and presented by a DVB " .
                            "conformant Player SHOULD be such that they will be inferred to have an @profiles " .
                            "attribute that includes the profile name defined in clause 4.1 as well as either " .
                            "the one defined in 4.2.5 or the one defined in 4.2.8'",
                            $subRepProfileExists,
                            "WARN",
                            "SubRepresentation Check succeeded",
                            "SubRepresentation Check failed: Contains clause 4.1: " .
                            ($subRepContainsDVBDash ? "Yes" : "No") .
                            ", contains either 4.2.5 or 4.2.8: " . ($subRepContainsExtension ? "Yes" : "No"),
                        );
                    }
                }
            }
        }

        //Continuation of adapationset-level checks
        $adaptationContentType = $adaptationSet->getAttribute("contentType");
        $adaptationMimeType = $adaptationSet->getAttribute("mimeType");

        if (
            $adaptationContentType == 'video' || $videoComponentFound ||
            $videoFound || strpos($adaptationMimeType, 'video') !== false
        ) {
            $hasVideoService = true;
            $this->dvbVideoChecks($adaptationSet, $representations, $i, $videoComponentFound);
            if ($audioComponentFound) {
                $this->dvbAudioChecks($adaptationSet, $representations, $i, $audioComponentFound);
            }
        } elseif (
            $adaptationContentType == 'audio' || $audioComponentFound ||
            $audioFound || strpos($adaptationMimeType, 'audio') !== false
        ) {
            $this->dvbAudioChecks($adaptationSet, $representations, $i, $audioComponentFound);
            if ($videoComponentFound) {
                $this->dvbVideoChecks($adaptationSet, $representations, $i, $videoComponentFound);
            }
            $audioAdaptations[] = $adaptationSet;
        } else {
            $this->dvbSubtitleChecks($adaptationSet, $representations, $i);
        }

        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "DVB: Section 4.2.2",
            "If a Period element contains multiple Adaptation Sets with @contentType=\"video\" then at least one " .
            "Adaptation Set SHALL contain a Role element with @schemeIdUri=\"urn:mpeg:dash:role:2011\" and " .
            "@value=\"main\"",
            $this->adaptationVideoCount <= 1 || $this->mainVideoFound,
            "FAIL",
            "$this->adaptationVideoCount adaptation(s) found with main label if needed for period $this->periodCount",
            "$this->adaptationVideoCount adaptations found, none labeled as main for period $this->periodCount"
        );

        $this->dvbContentProtection($adaptationSet, $representations, $i, $cenc);
    }

    if ($hasVideoService) {
        $this->streamBandwidthCheck();
    }

    if ($audioAdaptations->length > 1) {
        $this->fallbackOperationChecks($audioAdaptations);
    }

    if ($mainAudioFound && !empty($hoh_subtitle_lang)) {
        $mainLanguage = array();
        foreach ($main_audios as $main_audio) {
            if ($main_audio->getAttribute('lang') != '') {
                $mainLanguage[] = $main_audio->getAttribute('lang');
            }
        }

        foreach ($hoh_subtitle_lang as $hoh_lang) {
            if (!empty($mainLanguages)) {
                $logger->test(
                    "HbbTV-DVB DASH Validation Requirements",
                    "DVB: Section 7.1.2",
                    "According to Table 11, when hard of hearing subtitle type is signalled the " .
                    "@lang attribute of the subtitle representation SHALL be the same as the " .
                    "main audio for the programme",
                    in_array($hoh_lang, $main_lang),
                    "FAIL",
                    "Attributes match for period $this->periodCount",
                    "Attributes don't match for period $this->periodCount",
                );
            }
        }
    }
}

$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB: Section 4.5",
    "The MPD has a maximum of 64 periods after xlink resolution",
    $this->periodCount <= 64,
    "FAIL",
    "$this->periodCount periods found in MPD",
    "$this->periodCount periods found in MPD"
);


$this->dvbAssociatedAdaptationSetsCheck();

$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB: Section 6.1.2",
    "If there is more than one audio Adaptation Set in a DASH Presentation then at least one of them SHALL be " .
    "tagged with an @value set to \"main\"",
    $audioAdaptations->length <= 1 || $this->mainAudioFound,
    "FAIL",
    "$audioAdaptations->length adaptation(s) found with main label if needed in Presentation",
    "$audioAdaptations->length adaptations found but none of them are labeled as main in Presentation"
);
