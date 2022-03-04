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
    $mup = time_parsing($mpd_dom->getAttribute('minimumUpdatePeriod'));
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
    "Check failed",
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
    "Check failed",
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

$mpdreport = "./placeholder.txt";
## Verifying the DVB Metric reporting mechanism according to Section 10.12.3
$this->dvbMetricReporting();

/*

    $cenc = $mpd_dom->getAttribute('xmlns:cenc');

    // Periods within MPD
    $period_count = 0;
    $video_service = false;
    $type = $mpd_dom->getAttribute('type');
    $AST = $mpd_dom->getAttribute('availabilityStartTime');

if ($type == 'dynamic' || $AST != '') {
    $UTCTimings = $mpd_dom->getElementsByTagName('UTCTiming');
    $acceptedTimingURIs = array('urn:mpeg:dash:utc:ntp:2014',
                                'urn:mpeg:dash:utc:http-head:2014',
                                'urn:mpeg:dash:utc:http-xsdate:2014',
                                'urn:mpeg:dash:utc:http-iso:2014',
                                'urn:mpeg:dash:utc:http-ntp:2014');
    $utc_info = '';

    if ($UTCTimings->length == 0) {
        fwrite($mpdreport, "Warning for DVB check: Section 4.7.2- 'If the MPD is dynamic or if the MPD@availabilityStartTime is present then the MPD SHOULD countain at least one UTCTiming element with the @schemeIdUri attribute set to one of the following: " . join(', ', $acceptedTimingURIs) . " ', UTCTiming element could not be found in the provided MPD.\n");
    } else {
        foreach ($UTCTimings as $UTCTiming) {
            if (!(in_array($UTCTiming->getAttribute('schemeIdUri'), $acceptedTimingURIs))) {
                $utc_info .= 'wrong ';
            }
        }

        if ($utc_info != '') {
            fwrite($mpdreport, "Warning for DVB check: Section 4.7.2- 'If the MPD is dynamic or if the MPD@availabilityStartTime is present then the MPD SHOULD countain at least one UTCTiming element with the @schemeIdUri attribute set to one of the following: " . join(', ', $acceptedTimingURIs) . " ', could not be found in the provided MPD.\n");
        }
    }
}

foreach ($mpd_dom->childNodes as $node) {
    if ($node->nodeName == 'Period') {
        $period_count++;
        $adapt_video_count = 0;
        $main_video_found = false;
        $main_audio_found = false;

        foreach ($node->childNodes as $child) {
            if ($child->nodeName == 'SegmentList') {
                fwrite($mpdreport, "###'DVB check violated: Section 4.2.2- The Period.SegmentList SHALL not be present', but found in Period $period_count.\n");
            }

            if ($child->nodeName == 'EventStream') {
                DVB_event_checks($child, $mpdreport);
            }
            if ($child->nodename == 'SegmentTemplate') {
                if (strpos($profiles, 'urn:dvb:dash:profile:dvb-dash:isoff-ext-on-demand:2014') === true) {
                    fwrite($mpdreport, "###'DVB check violated: Section 4.2.6- The Period.SegmentTemplate SHALL not be present for Period elements conforming to On Demand profile', but found in Period $period_count.\n");
                }
            }
        }

        // Adaptation Sets within each Period
        $adapts = $node->getElementsByTagName('AdaptationSet');
        $adapts_len = $adapts->length;

        if ($adapts_len > 16) {
            fwrite($mpdreport, "###'DVB check violated: Section 4.5- The MPD has a maximum of 16 adaptation sets per period', found $adapts_len in Period $period_count.\n");
        }

        $audio_adapts = array();
        for ($i = 0; $i < $adapts_len; $i++) {
            $adapt = $adapts->item($i);
            $video_found = false;
            $audio_found = false;

            $adapt_profile_exists = false;
            $adapt_profiles = $adapt->getAttribute('profiles');
            if ($profile_exists && $adapt_profiles != '') {
                if (strpos($adapt_profiles, 'urn:dvb:dash:profile:dvb-dash:2014') === false && (strpos($adapt_profiles, 'urn:dvb:dash:profile:dvb-dash:isoff-ext-live:2014') === false || strpos($adapt_profiles, 'urn:dvb:dash:profile:dvb-dash:isoff-ext-on-demand:2014') === false)) {
                    fwrite($mpdreport, "Warning for DVB check: Section 11.1- 'All Representations that are intended to be decoded and presented by a DVB conformant Player SHOULD be such that they will be inferred to have an @profiles attribute that includes the profile name defined in clause 4.1 as well as either the one defined in 4.2.5 or the one defined in 4.2.8', found profiles: $adapt_profiles.\n");
                } else {
                    $adapt_profile_exists = true;
                }
            }

            $reps = $adapt->getElementsByTagName('Representation');
            $reps_len = $reps->length;
            if ($reps_len > 16) {
                fwrite($mpdreport, "###'DVB check violated: Section 4.5- The MPD has a maximum of 16 representations per adaptation set', found $reps_len in Period $period_count Adaptation Set " . ($i + 1) . ".\n");
            }

            $contentTemp_vid_found = false;
            $contentTemp_aud_found = false;
            foreach ($adapt->childNodes as $ch) {
                if ($ch->nodeName == 'ContentComponent') {
                    if ($ch->getAttribute('contentType') == 'video') {
                        $contentTemp_vid_found = true;
                    }
                    if ($ch->getAttribute('contentType') == 'audio') {
                        $contentTemp_aud_found = true;
                    }
                }
                if ($ch->nodeName == 'Representation') {
                    if ($profile_exists && ($adapt_profiles == '' || $adapt_profile_exists)) {
                        $rep_profile_exists = false;
                        $rep_profiles = $ch->getAttribute('profiles');
                        if ($rep_profiles != '') {
                            if (strpos($rep_profiles, 'urn:dvb:dash:profile:dvb-dash:2014') === false && (strpos($rep_profiles, 'urn:dvb:dash:profile:dvb-dash:isoff-ext-live:2014') === false || strpos($rep_profiles, 'urn:dvb:dash:profile:dvb-dash:isoff-ext-on-demand:2014') === false)) {
                                fwrite($mpdreport, "Warning for DVB check: Section 11.1- 'All Representations that are intended to be decoded and presented by a DVB conformant Player SHOULD be such that they will be inferred to have an @profiles attribute that includes the profile name defined in clause 4.1 as well as either the one defined in 4.2.5 or the one defined in 4.2.8', found profiles: $rep_profiles.\n");
                            } else {
                                $rep_profile_exists = true;
                            }
                        }
                    }
                    if (strpos($ch->getAttribute('mimeType'), 'video') !== false) {
                        $video_found = true;
                    }
                    if (strpos($ch->getAttribute('mimeType'), 'audio') !== false) {
                        $audio_found = true;
                    }

                    if ($profile_exists && ($adapt_profiles == '' || $adapt_profile_exists) && ($rep_profiles == '' || $rep_profile_exists)) {
                        foreach ($ch->childNodes as $c) {
                            if ($c->nodeName == 'SubRepresentation') {
                                $subrep_profiles = $c->getAttribute('profiles');
                                if ($subrep_profiles != '') {
                                    if (strpos($subrep_profiles, 'urn:dvb:dash:profile:dvb-dash:2014') === false && (strpos($subrep_profiles, 'urn:dvb:dash:profile:dvb-dash:isoff-ext-live:2014') === false || strpos($subrep_profiles, 'urn:dvb:dash:profile:dvb-dash:isoff-ext-on-demand:2014') === false)) {
                                        fwrite($mpdreport, "Warning for DVB check: Section 11.1- 'All Representations that are intended to be decoded and presented by a DVB conformant Player SHOULD be such that they will be inferred to have an @profiles attribute that includes the profile name defined in clause 4.1 as well as either the one defined in 4.2.5 or the one defined in 4.2.8', found profiles: $subrep_profiles.\n");
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if ($adapt->getAttribute('contentType') == 'video' || $contentTemp_vid_found || $video_found || strpos($adapt->getAttribute('mimeType'), 'video') !== false) {
                $video_service = true;
                DVB_video_checks($adapt, $reps, $mpdreport, $i, $contentTemp_vid_found);

                if ($contentTemp_aud_found) {
                    DVB_audio_checks($adapt, $reps, $mpdreport, $i, $contentTemp_aud_found);
                }
            } elseif ($adapt->getAttribute('contentType') == 'audio' || $contentTemp_aud_found || $audio_found || strpos($adapt->getAttribute('mimeType'), 'audio') !== false) {
                DVB_audio_checks($adapt, $reps, $mpdreport, $i, $contentTemp_aud_found);

                if ($contentTemp_vid_found) {
                    DVB_video_checks($adapt, $reps, $mpdreport, $i, $contentTemp_vid_found);
                }

                $audio_adapts[] = $adapt;
            } else {
                DVB_subtitle_checks($adapt, $reps, $mpdreport, $i);
            }

            if ($adapt_video_count > 1 && $main_video_found == false) {
                fwrite($mpdreport, "###'DVB check violated: Section 4.2.2- If a Period element contains multiple Adaptation Sets with @contentType=\"video\" then at least one Adaptation Set SHALL contain a Role element with @schemeIdUri=\"urn:mpeg:dash:role:2011\" and @value=\"main\"', could not be found in Period $period_count.\n");
            }

            DVB_content_protection($adapt, $reps, $mpdreport, $i, $cenc);
        }

        if ($video_service) {
            StreamBandwidthCheck($mpdreport);
        }

        ## Section 6.6.3 - Check for Audio Fallback Operation
        if (!empty($audio_adapts) && sizeof($audio_adapts) > 1) {
            FallbackOperationCheck($audio_adapts, $mpdreport);
        }
        ##

        ## Section 7.1.2 Table 11 - First Row "Hard of Hearing"
        if ($main_audio_found) {
            if (!empty($hoh_subtitle_lang)) {
                $main_lang = array();
                foreach ($main_audios as $main_audio) {
                    if ($main_audio->getAttribute('lang') != '') {
                        $main_lang[] = $main_audio->getAttribute('lang');
                    }
                }

                foreach ($hoh_subtitle_lang as $hoh_lang) {
                    if (!empty($main_lang)) {
                        if (!in_array($hoh_lang, $main_lang)) {
                            fwrite($mpdreport, "###'DVB check violated: Section 7.1.2- According to Table 11, when hard of hearing subtitle type is signalled the @lang attribute of the subtitle representation SHALL be the same as the main audio for the programme', @lang attributes do not match in Period $period_count.\n");
                        }
                    }
                }
            }
        }
        ##
    }

    if ($period_count > 64) {
        fwrite($mpdreport, "###'DVB check violated: Section 4.5- The MPD has a maximum of 64 periods after xlink resolution', found $period_count.\n");
    }
}

    DVB_associated_adaptation_sets_check($mpdreport);

if ($adapt_audio_count > 1 && $main_audio_found == false) {
    fwrite($mpdreport, "###'DVB check violated: Section 6.1.2- If there is more than one audio Adaptation Set in a DASH Presentation then at least one of them SHALL be tagged with an @value set to \"main\"', could not be found in Period $period_count.\n");
}
 */
