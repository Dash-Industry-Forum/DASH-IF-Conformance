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

$this->periodCount = 0;

$hasVideoService = false;

$cencAttribute = $mpdHandler->getDom()->getAttribute("xmlns:cenc");

foreach ($mpdHandler->getDom()->childNodes as $node) {
    if ($node->nodeName != 'Period') {
        continue;
    }
    $this->periodCount++;

    $this->adaptationAudioCount = 0;
    $this->mainAudios = array();

    // Adaptation Sets within each Period
    $adaptationSets = $node->getElementsByTagName('AdaptationSet');
    $adaptationSetCount = $adaptationSets->length;

    $audioAdaptations = array();
    for ($i = 0; $i < $adaptationSetCount; $i++) {
        $adaptationSet = $adaptationSets->item($i);
        $videoFound = false;
        $audioFound = false;

        /* Accidentally removed in previous commit, for each representation:
            $mimeType = $representation->getAttribute("mimeType");
                if (strpos($mimeType, "video") !== false) {
                    $videoFound = true;
                }
                if (strpos($mimeType, "audio") !== false) {
                    $audioFound = true;
                }
         */


        $representations = $adaptationSet->getElementsByTagName("Representation");
        $representationCount = $representations->length;



        //Continuation of adaptationset-level checks
        $adaptationContentType = $adaptationSet->getAttribute("contentType");
        $adaptationMimeType = $adaptationSet->getAttribute("mimeType");

        if (
            $adaptationContentType == 'video' || 
            $videoFound || strpos($adaptationMimeType, 'video') !== false
        ) {
            $hasVideoService = true;
            $this->dvbVideoChecks($adaptationSet, $representations, $i, false);
        } elseif (
            $adaptationContentType == 'audio' ||
            $audioFound || strpos($adaptationMimeType, 'audio') !== false
        ) {
            $this->dvbAudioChecks($adaptationSet, $representations, $i, false);
            $audioAdaptations[] = $adaptationSet;
        } else {
            $this->dvbSubtitleChecks($adaptationSet, $representations, $i);
        }

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
