<?php

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


$this->periodCount = 0;


$cencAttribute = $mpdHandler->getDom()->getAttribute("xmlns:cenc");

foreach ($mpdHandler->getDom()->childNodes as $node) {
    if ($node->nodeName != 'Period') {
        continue;
    }
    $this->periodCount++;

    // Adaptation Sets within each Period
    $adaptationSets = $node->getElementsByTagName('AdaptationSet');
    $adaptationSetCount = $adaptationSets->length;

    for ($i = 0; $i < $adaptationSetCount; $i++) {
        $adaptationSet = $adaptationSets->item($i);


        $representations = $adaptationSet->getElementsByTagName("Representation");



        $this->dvbSubtitleChecks($adaptationSet, $representations, $i);

        $this->dvbContentProtection($adaptationSet, $representations, $i, $cencAttribute);
    }

    //NOTE: Only if audio
    $this->fallbackOperationChecks(array());
}

$this->dvbAssociatedAdaptationSetsCheck();
