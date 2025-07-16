<?php

global $main_audios, $hoh_subtitle_lang;
global $onRequest_array, $xlink_not_valid_array;

global $logger, $mpdHandler;


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

$this->checkDVBValidRelative();

## Verifying the DVB Metric reporting mechanism according to Section 10.12.3
$this->dvbMetricReporting();

$type = $mpdHandler->getDom()->getAttribute('type');
$AST = $mpdHandler->getDom()->getAttribute('availabilityStartTime');

if ($type == 'dynamic' || $AST != '') {
    $UTCTimings = $mpdHandler->getDom()->getElementsByTagName('UTCTiming');
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
        "'If the MPD is dynamic or if the MPD@availabilityStartTime is present then the MPD SHOULD contain at least " .
        "one UTCTiming element with the @schemeIdUri attribute set to one of the following: " .
        join(', ', $acceptedURIs),
        $utcTimingValid,
        "WARN",
        "At least one valid UTCTiming element found",
        "None of the valid UTCTiming elements found"
    );
}

$this->periodCount = 0;

$hasVideoService = false;

$cencAttribute = $mpdHandler->getDom()->getAttribute("xmlns:cenc");

foreach ($mpdHandler->getDom()->childNodes as $node) {
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
        if ($child->nodeName == 'SegmentTemplate') {
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

    $audioAdaptations = array();
    for ($i = 0; $i < $adaptationSetCount; $i++) {
        $adaptationSet = $adaptationSets->item($i);
        $videoFound = false;
        $audioFound = false;


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

        if (isset($ch) && $ch) {
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


        //Continuation of adaptationset-level checks
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

        $this->dvbContentProtection($adaptationSet, $representations, $i, $cencAttribute);
    }

    if ($hasVideoService) {
        $this->streamBandwidthCheck();
    }

    if (count($audioAdaptations) > 1) {
        $this->fallbackOperationChecks($audioAdaptations);
    }

    if ($this->mainAudioFound && !empty($hoh_subtitle_lang)) {
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

$this->dvbAssociatedAdaptationSetsCheck();

$audioCount = count($audioAdaptations);
$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB: Section 6.1.2",
    "If there is more than one audio Adaptation Set in a DASH Presentation then at least one of them SHALL be " .
    "tagged with an @value set to \"main\"",
    $audioCount <= 1 || $this->mainAudioFound,
    "FAIL",
    "$audioCount adaptation(s) found with main label if needed in Presentation",
    "$audioCount adaptations found but none of them are labeled as main in Presentation"
);
