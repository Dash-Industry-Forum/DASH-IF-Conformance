<?php

global $onRequest_array, $xlink_not_valid_array, $mpd_dom, $mpd_url;

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
    "HbbTV-DVB DASH Validation Requirements",
    "HbbTV: Section E.2.1",
    "The MPD size after xlink resolution SHALL NOT exceed 100 Kbytes",
    $mpd_bytes <= 1024 * 100,
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
    "HbbTV: Section 'MPD'",
    "MPD@minimumUpdatePeriod should have a value of 1 second or higher",
    $minimumUpdateWarning == false,
    "WARN",
    "Check succeeded",
    "Check failed",
);

$docType = $mpd_dom->doctype;
$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "HbbTV: Section 'MPD'",
    "The MPD must not contain an XML Document Type Definition(<!DOCTYPE>)",
    $docType === null,
    "FAIL",
    "No Doctype found",
    "Doctype found",
);


$this->periodCount = 0;
foreach ($mpd_dom->childNodes as $node) {
    if ($node - nodeName != 'Period') {
        continue;
    }
    $this->periodCount++;


    foreach ($mpd_dom->childNodes as $node) {
        if ($node->nodeName == 'Period') {
            $this->periodCount++;

            $adaptationSets = $node->getElementsByTagName('AdaptationSet');
            $adaptationCount = 0;

            $this->adaptationVideoCount = 0;
            $this->adaptationAudioCount = 0;
            $this->mainVideoFound = 0;
            $this->mainAudioFound = 0;

            foreach ($adapationSets as $adaptationSet) {
                $adaptationCount++;
                $roles = $adaptation->getElementsByTagName('Role');

                $schemeIdUri = '';
                $roleValue = '';

                if ($roles->length > 0) {
                     $schemeIdUri = $role->item(0)->getAttribute('schemeIdUri');
                     $roleValue = $role->item(0)->getAttribute('value');
                }

                $representations = $adapationSet->getElementsByTagName("Representation");
                $representationCount = $representations->length;
                if (
                    $adaptationSet->getAttribute('contentType') == 'video' ||
                    $adaptationSet->getAttribute('mimeType') == 'video/mp4' ||
                    (
                    $representation->length > 0 &&
                    $representations->item(0)->getAttribute('mimeType') == 'video/mp4'
                    )
                ) {
                    $this->$adaptationVideoCount++;
                    if (
                        $role->length > 0 && (strpos($schemeIdUri, "urn:mpeg:dash:role:2011") !== false &&
                        $roleValue == "main")
                    ) {
                        $this->mainVideoFound++;
                    }
                    $this->hbbVideoRepresentationChecks($adaptation, $adaptationCount, $this->periodCount);
                }
                if (
                    $adaptationSet->getAttribute('contentType') == 'audio' ||
                    $adaptationSet->getAttribute('mimeType') == 'audio/mp4' ||
                    (
                    $representation->length > 0 &&
                    $representations->item(0)->getAttribute('mimeType') == 'audio/mp4'
                    )
                ) {
                    $this->$adaptationAudioCount++;
                    if (
                        $role->length > 0 && (strpos($schemeIdUri, "urn:mpeg:dash:role:2011") !== false &&
                        $roleValue == "main")
                    ) {
                        $this->mainAudioFound++;
                    }
                    $this->hbbAudioRepresentationChecks($adaptation, $adaptationCount, $this->periodCount);
                }
            }

            ///\RefactorTodo Reimplement at correct spot
            /*
            //Following has error reporting code if MPD element is not part of validating profile.
            if ($rep_count > 16) {
              fwrite($mpdreport, "###'HbbTV check violated: Section E.2.2 - There shall be no more than 16 ".
                "Representations per Adaptatation Set  in an MPD', but found " . $rep_count .
                " Represenations in Adaptation Set " . $adapt_count .
                " in Period " . $period_count . " \n");
            }
             */
        }

        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "HbbTV: Section E.2.2",
            "The MPD has a maximum of 64 periods after xlink resolution",
            $this->adaptationSetCount <= 64,
            "FAIL",
            "$this->adaptationSetCount adaptation sets found in period $this->periodCount",
            "$this->adaptationSetCount adapation sets found in period $this->periodCount"
        );

        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "HbbTV: Section E.2.2",
            "There shall be at least one video Adaptation Set per Period in an MPD",
            $this->adaptationVideoCount,
            "FAIL",
            "$this->adaptationVideoCount video adaptation sets found in period $this->periodCount",
            "No video adaptation sets found in period $this->periodCount"
        );

        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "HbbTV: Section E.2.2",
            "If there is more than one video AdaptationSet, exactly one shall be labelled with Role@value 'main'",
            $this->adaptationVideoCount <= 1 || $mainVideoFound == 1,
            "FAIL",
            "1 or less video adaptations found in period $this->periodCount, or exactly one is labeled 'main'",
            "Invalid video adapatationset configruation found found in period $this->periodCount"
        );

        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "HbbTV: Section E.2.2",
            "If there is more than one audio AdaptationSet, exactly one shall be labelled with Role@value 'main'",
            $this->adaptationAudioCount <= 1 || $mainAudioFound == 1,
            "FAIL",
            "1 or less audio adaptations found in period $this->periodCount, or exactly one is labeled 'main'",
            "Invalid audio adapatationset configruation found found in period $this->periodCount"
        );
    }
}
