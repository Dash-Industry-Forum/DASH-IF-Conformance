<?php

global $mpdHandler, $logger;

$selectedPeriod = $mpdHandler->getSelectedPeriod();
$selectedAdaptation = $mpdHandler->getSelectedAdaptationSet();
$selectedRepresentation = $mpdHandler->getSelectedRepresentation();

$period = $mpdHandler->getFeatures()['Period'][$selectedPeriod];
$adaptation_set = $period['AdaptationSet'][$selectedAdaptationSet];
$representation = $adaptation_set['Representation'][$selectedRepresentation];
$codecs = ($representation['codecs']) ? $representation['codecs'] : $adaptation_set['codecs'];
$mimeType = ($representation['mimeType']) ? $representation['mimeType'] : $adaptation_set['mimeType'];
$bitstreamSwitching = ($adaptation_set['bitstreamSwitching']) ?
  $adaptation_set['bitstreamSwitching'] : $period['bitstreamSwitching'];

if (!$bitstreamSwitching) {
    return;
}
if (strpos($mimeType, 'video') === false) {
    return;
}


$isAvc = strpos($codecs, 'avc') !== false;
$isHevc = strpos($codecs, 'hev') !== false || strpos($codecs, 'hvc') !== false;
if ($isAvc) {
    $logger->test(
        "DASH-IF IOP 4.3",
        "Section 6.2.5.2",
        "For AVC video data, if the @bitstreamswitching flag is set to true, all Representations " .
        "SHALL be encoded using avc3",
        strpos($codecs, 'avc3') !== false,
        "FAIL",
        "Valid encoding found  for Period $selectedPeriod Adaptation Set $selectedAdaptation " .
        "Representation $selectedRepresentation.",
        "Invalid encoding found for Period $selectedPeriod Adaptation Set $selectedAdaptation " .
        "Representation $selectedRepresentation (codecs $codecs)."
    );

    $codecBoxes = $xml->getElementsByTagName('avcC');
    $logger->test(
        "DASH-IF IOP 4.3",
        "Section 6.2.5.2",
        "For AVC video data, if the @bitstreamswitching flag is set to true, all Representations SHALL include " .
        "Initialitization Segment containing 'avcC' box",
        $codecBoxes->length > 0,
        "FAIL",
        $codecBoxes->length . " 'avcC' boxes found for Period $selectedPeriod Adaptation Set $selectedAdaptation " .
        "Representation $selectedRepresentation.",
        "No 'avcC' boxes found for Period $selectedPeriod Adaptation Set $selectedAdaptation " .
        "Representation $selectedRepresentation."
    );
    if ($codecBoxes->length > 0) {
        $spsFound = false;
        $ppsFound = false;
        $nalBoxes = $codec_box->item(0)->getElementsByTagName('NALUnit');
        foreach ($nalBoxes as $nalBox) {
            $unitType = $nalBox->getAttribute('nal_unit_type');
            if ($unitType == '0x07') {
                $spsFound = true;
            }
            if ($unitType == '0x08') {
                $ppsFound = true;
            }
        }
        $logger->test(
            "DASH-IF IOP 4.3",
            "Section 6.2.5.2",
            "For AVC video data, if the @bitstreamswitching flag is set to true, all Representations " .
            "SHALL include Initialitization Segment containing 'avcC' box containing Decoder Configuration " .
            "Record containing SPS and PPS NALs",
            $spsFound && $ppsFound,
            "FAIL",
            "All units found for Period $selectedPeriod Adaptation Set $selectedAdaptation " .
            "Representation $selectedRepresentation.",
            "Not all units found for Period $selectedPeriod Adaptation Set $selectedAdaptation " .
            "Representation $selectedRepresentation."
        );
    }
}
if ($isHevc) {
    $logger->test(
        "DASH-IF IOP 4.3",
        "Section 6.2.5.2",
        "For HEVC video data, if the @bitstreamswitching flag is set to true, all Representations " .
        "SHALL be encoded using hev1",
        strpos($codecs, 'hev1') !== false,
        "FAIL",
        "Valid encoding found  for Period $selectedPeriod Adaptation Set $selectedAdaptation " .
        "Representation $selectedRepresentation.",
        "Invalid encoding found for Period $selectedPeriod Adaptation Set $selectedAdaptation " .
        "Representation $selectedRepresentation (codecs $codecs)."
    );

    $codecBoxes = $xml->getElementsByTagName('hvcC');
    $logger->test(
        "DASH-IF IOP 4.3",
        "Section 6.2.5.2",
        "For HEVC video data, if the @bitstreamswitching flag is set to true, all Representations SHALL include " .
        "Initialitization Segment containing 'hvcC' box",
        $codecBoxes->length > 0,
        "FAIL",
        $codecBoxes->length . " 'hvcC' boxes found for Period $selectedPeriod Adaptation Set $selectedAdaptation " .
        "Representation $selectedRepresentation.",
        "No 'hvcC' boxes found for Period $selectedPeriod Adaptation Set $selectedAdaptation " .
        "Representation $selectedRepresentation."
    );
    if ($codecBoxes->length > 0) {
        $vps_found = false;
        $sps_found = false;
        $pps_found = false;
        $nalBoxes = $codecBoxes->item(0)->getElementsByTagName('NALUnit');
        foreach ($nalBoxes as $nalBox) {
            $unitType = $nalBox->getAttribute('nal_unit_type');
            if ($unitType == 32) {
                $vpsFound = true;
            }
            if ($unitType == 33) {
                $spsFound = true;
            }
            if ($unitType == 34) {
                $ppsFound = true;
            }
        }
        $logger->test(
            "DASH-IF IOP 4.3",
            "Section 6.2.5.2",
            "For HEVC video data, if the @bitstreamswitching flag is set to true, all Representations " .
            "SHALL include Initialitization Segment containing 'hvcC' box containing Decoder Configuration " .
            "Record containing SPS, PPS and VPS NALs",
            $spsFound && $ppsFound && $vpsFound,
            "FAIL",
            "All units found for Period $selectedPeriod Adaptation Set $selectedAdaptation " .
            "Representation $selectedRepresentation.",
            "Not all units found for Period $selectedPeriod Adaptation Set $selectedAdaptation " .
            "Representation $selectedRepresentation."
        );
    }
}

if ($isAvc || $isHevc) {
    $elstBoxes = $xml->getElementsByTagName('elst');
    $representationProfiles = $mpdHandler->getProfiles[$selectedPeriod][$selectedAdaptation][$selectedRepresentation];
    if (
        !(strpos($representationProfiles, 'http://dashif.org/guidelines/dash-if-ondemand') !== false ||
        (strpos($representationProfiles, 'http://dashif.org/guidelines/dash') !== false &&
        strpos($representationProfiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011') !== false))
    ) {
        $logger->test(
            "DASH-IF IOP 4.3",
            "Section 6.2.5.2",
            "Edit lists SHALL NOT be present in video Adaptation Sets unless they are offered in On-Demand profile",
            $elstBoxes->length == 0,
            "FAIL",
            "No edit lists found for Period $selectedPeriod Adaptation Set $selectedAdaptation " .
            "Representation $selectedRepresentation",
            "Edit lists found for Period $selectedPeriod Adaptation Set $selectedAdaptation " .
            "Representation $selectedRepresentation"
        );
    }

    $trunElements = $xml->getElementsByTagName('trun');
    $tfdtElements = $xml->getElementsByTagName('tfdt');
    $firstSampleCompTime = '';
    $firstSampleDecTime = '';
    if ($trunElements->length > 0) {
        $firstSampleCompTime = $xml_trun[0]->getAttribute('earliestCompositionTime');
    }
    if ($tfdtElements->length > 0) {
        $firstSampleDecTime = $tfdt[0]->getAttribute('baseMediaDecodeTime');
    }

    $logger->test(
        "DASH-IF IOP 4.3",
        "Section 6.2.5.2",
        "Video media Segments SHALL set the first presented sample's composition " .
        "time equal to the first decoded sample's decode time",
        $firstSampleCompTime != '' && $firstSampleCompTime == $firstSampleDecTime,
        "FAIL",
        "Composition and decoded times equal for Period $selectedPeriod Adaptation Set " .
        "$selectedAdaptation Representation $selectedRepresentation",
        "Composition and decoded times missing or different for Period $selectedPeriod " .
        "Adaptation Set $selectedAdaptation Representation $selectedRepresentation",
    );
}
