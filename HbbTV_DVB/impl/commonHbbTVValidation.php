<?php

global $session, $mpdHandler, $logger;

$adaptation = $mpdHandler->getFeatures()['Period'][$mpdHandler->getSelectedPeriod()]['AdaptationSet'][$mpdHandler->getSelectedAdaptationSet()];
$representation = $adaptation['Representation'][$mpdHandler->getSelectedRepresentation()];

## Check on the support of the provided codec
// MPD part
$codecs = $adaptation['codecs'];
if ($codecs == '') {
    $codecs = $rep['codecs'];
}

if ($codecs != '') {
    $codecList = explode(',', $codecs);

    $unsupportedCodecsi = '';
    foreach ($codecList as $codec) {
        if (
            strpos($codec, 'avc') === false &&
            strpos($codec, 'mp4a') === false &&
            strpos($codec, 'ec-3') === false
        ) {
            $unsupportedCodecs .= "$codec ";
        }
    }

    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "HbbTV: Section 'Codec information'",
        "The codecs found in the MPD should be supported by the specification",
        $unsupportedCodecs == '',
        "FAIL",
        "All found codecs supported",
        "Codecs '" . $unsupportedCodecs . "' not supported"
    );
}

// Segment part
$hdlrType = $xmlRepresentation->getElementsByTagName('hdlr')->item(0)->getAttribute('handler_type');
$sdType = $xmlRepresentation->getElementsByTagName("$hdlrType" . '_sampledescription')->item(0)->getAttribute('sdType');

if ($hdlrType == 'vide' || $hdlrType == 'soun') {
    $segmentCodecUnsupported = (
        strpos($sdType, 'avc') === false &&
        strpos($sdType, 'mp4a') === false && strpos($sdType, 'ec-3') === false &&
        strpos($sdType, 'enc') === false
    );

    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "HbbTV: Section 'Codec information'",
        "The codecs found in the Segment should be supported by the specification",
        !$segmentCodecUnsupported,
        "FAIL",
        "All found codecs supported",
        "Segment codec '" . $sdType . "' not supported"
    );
}

$originalFormat = '';
if (strpos($sdType, 'enc') !== false) {
    $sinfBoxes = $xmlRepresentation->getElementsByTagName('sinf');
    if ($sinfBoxes->length != 0) {
        $originalFormat = $sinfBoxes->item(0)->getElementsByTagName('frma')->item(0)->getAttribute('originalFormat');
    }
}

if (strpos($sdType, 'avc') !== false || strpos($originalFormat, 'avc') !== false) {
    $width = $xmlRepresentation->getElementsByTagName("$hdlrType" . '_sampledescription')
                               ->item(0)->getAttribute('width');
    $height = $xmlRepresentation->getElementsByTagName("$hdlrType" . '_sampledescription')
                                ->item(0)->getAttribute('height');

    $nalUnits = $xmlRepresentation->getElementsByTagName('NALUnit');
    foreach ($nalUnits as $nalUnit) {
        if ($nalUnit->getAttribute('nal_type') != '0x07') {
            continue;
        }
        $profileIdc = $nalUnit->getAttribute('profile_idc');
        $levelIdc = $nalUnit->getElementsByTagName('comment')->item(0)->getAttribute('level_idc');
        if ((int)$width <= 720 && (int)$height <= 576) {
            $logger->test(
                "HbbTV-DVB DASH Validation Requirements",
                "HbbTV: Section 'Codec information'",
                "The codecs found in the Segment should be supported by the specification",
                $profileIdc == 77 || $profileIdc == 100,
                "FAIL",
                "Found profile '$profileIdc' is supported",
                "Found profile '$profileIdc' not supported"
            );
            $logger->test(
                "HbbTV-DVB DASH Validation Requirements",
                "HbbTV: Section 'Codec information'",
                "The codecs found in the Segment should be supported by the specification",
                $levelIdc == 30,
                "FAIL",
                "Found level '$levelIdc' is supported",
                "Found level '$levelIdc' not supported"
            );
        } elseif ((int)$width >= 720 && (int)$height >= 640) {
            $logger->test(
                "HbbTV-DVB DASH Validation Requirements",
                "HbbTV: Section 'Codec information'",
                "The codecs found in the Segment should be supported by the specification",
                $profileIdc == 100,
                "FAIL",
                "Found profile '$profileIdc' is supported",
                "Found profile '$profileIdc' not supported"
            );
            $logger->test(
                "HbbTV-DVB DASH Validation Requirements",
                "HbbTV: Section 'Codec information'",
                "The codecs found in the Segment should be supported by the specification",
                $levelIdc == 30 || $levelIdc == 31 || $levelIdc == 32 || $levelIdc == 40,
                "FAIL",
                "Found level '$levelIdc' is supported",
                "Found level '$levelIdc' not supported"
            );
        }
    }
}
##
##Segment checks.
$stsd = $xmlRepresentation->getElementsByTagName('stsd')->item(0);
$videoSampleDesciption = $stsd->getElementsByTagName('videoSampleDesciptiondescription');
$soundSampleDescription = $stsd->getElementsByTagName('soundSampleDescriptiondescription');

$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "HbbTV: Section E.2.3",
    "Each Representation shall contain only one media component",
    $videoSampleDesciption->length == 0 || $soundSampleDescription->length == 0,
    "FAIL",
    "Found only video or audio sample descriptions",
    "Found both video and audio sample descriptions"
);

if ($hdlrType == 'vide') {
    $avcC = $xmlRepresentation->getElementsByTagName('avcC');
    if ($avcC->length > 0) {
        $nalUnits = $xmlRepresentation->getElementsByTagName('NALUnit');
        foreach ($nalUnits as $nalUnit) {
            if ($nalUnit->getAttribute('nal_type') == '0x07') {
                $spsFound = 1;
            }
            if ($nalUnit->getAttribute('nal_type') == '0x08') {
                $ppsFound = 1;
            }
        }
        if ($sdType != 'avc3') {
            $logger->test(
                "HbbTV-DVB DASH Validation Requirements",
                "HbbTV: Section E.2.3",
                "All info necessary to decode any Segment shall be provided in Initialization Segment",
                $spsFound == 1,
                "FAIL",
                "AVC video: SPS (Sequence parameter set) found",
                "AVC video: SPS (Sequence parameter set) not found"
            );
            $logger->test(
                "HbbTV-DVB DASH Validation Requirements",
                "HbbTV: Section E.2.3",
                "All info necessary to decode any Segment shall be provided in Initialization Segment",
                $ppsFound == 1,
                "FAIL",
                "AVC video: PPS (Picture parameter set) found",
                "AVC video: PPS (Picture parameter set) not found"
            );
        }
    } else {
        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "HbbTV: Section E.2.3",
            "All info necessary to decode any Segment shall be provided in Initialization Segment",
            $sdType == "avc3",
            "FAIL",
            "AVC video: decoder config record found",
            "AVC video: decoder config record not found"
        );
    }
} elseif ($hdlrType == 'soun') {
    $soundSampleDescription = $xmlRepresentation->getElementsByTagName('soundSampleDescriptiondescription');
    $sdType = null;
    $samplingRate = null;
    if ($soundSampleDescription->item(0)){
     $sdType = $soundSampleDescription->item(0)->getAttribute('sdType');
      $samplingRate = $soundSampleDescription->item(0)->getAttribute('sampleRate');
    }
    $audioDecoderSpecificInfo = $xmlRepresentation->getElementsByTagName('DecoderSpecificInfo');
    if ($audioDecoderSpecificInfo->length > 0) {
        $channelConfig = $audioDecoderSpecificInfo->item(0)->getAttribute('channelConfig');
    }
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "HbbTV: Section E.2.3",
        "All info necessary to decode any Segment shall be provided in Initialization Segment",
        $sdType != null,
        "FAIL",
        "Audio: Sample description type found",
        "Audio: Sample description type not found"
    );
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "HbbTV: Section E.2.3",
        "All info necessary to decode any Segment shall be provided in Initialization Segment",
        $samplingRate != null,
        "FAIL",
        "Audio: Sampling rate found",
        "Audio: Sampling rate not found"
    );
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "HbbTV: Section E.2.3",
        "All info necessary to decode any Segment shall be provided in Initialization Segment",
        $channelConfig != null,
        "FAIL",
        "Audio: Channel config found in decoder specific info",
        "Audio: Channel config not found in decoder specific"
    );
}

// Segment duration except the last one shall be at least one second
$sidxBoxes = $xmlRepresentation->getElementsByTagName('sidx');
$subsegmentSignaling = array();
if ($sidxBoxes->length != 0) {
    foreach ($sidxBoxes as $sidxBox) {
        $subsegmentSignaling[] = (int)($sidxBox->getAttribute('referenceCount'));
    }
}

$timescale = $xmlRepresentation->getElementsByTagName('mdhd')->item(0)->getAttribute('timescale');
$moofBoxCount = $xmlRepresentation->getElementsByTagName('moof')->length;
$sidxIndex = 0;
$cumulativeSubsegmentDuration = 0;
for ($j = 0; $j < $moofBoxCount - 1; $j++) {
    $cummulatedSampleDuration = $xmlRepresentation->getElementsByTagName('trun')
                                                  ->item($j)->getAttribute('cummulatedSampleDuration');
    $segmentDuration = $cummulatedSampleDuration / $timescale;

    if (
        empty($subsegmentSignaling) ||
        (!empty($subsegmentSignaling) &&
         sizeof(array_unique($subsegmentSignaling)) == 1 &&
         in_array(0, $subsegmentSignaling)
        )
    ) {
        if ($hdlrType == 'vide' || $hdlrType == 'soun') {
            $logger->test(
                "HbbTV-DVB DASH Validation Requirements",
                "HbbTV: Section E.2.3",
                "Where subsegments are not signalled, each video and each audio segment SHALL have a duration " .
                "of not more than 15 seconds",
                $segmentDuration <= 15,
                "FAIL",
                "Duration of segment " . $j + 1 . " in bounds",
                "Duration of segment " . $j + 1 . " not in bounds (is $segmentDuration)",
            );
        }

        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "HbbTV: Section E.2.3",
            "Segment duration SHALL be at least 1 second except for the last segment of a Period",
            $segmentDuration >= 1,
            "FAIL",
            "Duration of segment " . $j + 1 . " is at least 1 second",
            "Duration of segment " . $j + 1 . " is less than 1 second",
        );
    } elseif (!empty($subsegmentSignaling) && !in_array(0, $subsegmentSignaling)) {
        $referenceCount = $subsegmentSignaling[$sidxIndex];
        $cumulativeSubsegmentDuration += $segmentDuration;
        if ($hdlrType == 'vide' || $hdlrType == 'soun') {
            $logger->test(
                "HbbTV-DVB DASH Validation Requirements",
                "HbbTV: Section E.2.3",
                "Where subsegments are not signalled, each video and each audio segment SHALL have a duration " .
                "of not more than 15 seconds",
                $segmentDuration <= 15,
                "FAIL",
                "Duration of segment " . $j + 1 . " in bounds",
                "Duration of segment " . $j + 1 . " not in bounds (is $segmentDuration)",
            );
        }

        $subsegmentSignaling[$sidxIndex] = $referenceCount - 1;
        if ($subsegmentSignaling[$sidxIndex] == 0) {
            $logger->test(
                "HbbTV-DVB DASH Validation Requirements",
                "HbbTV: Section E.2.3",
                "Segment duration SHALL be at least 1 second except for the last segment of a Period",
                $cumulativeSubsegmentDuration >= 1,
                "FAIL",
                "Duration of segment " . $j + 1 . " is at least 1 second",
                "Duration of segment " . $j + 1 . " is less than 1 second",
            );

            $sidxIndex++;
            $cumulativeSubsegmentDuration = 0;
        }
    } else {
        $referenceCount = $subsegmentSignaling[$sidxIndex];
        if ($referenceCount == 0) {
            if ($hdlrType == 'vide' || $hdlrType == 'soun') {
                $logger->test(
                    "HbbTV-DVB DASH Validation Requirements",
                    "HbbTV: Section E.2.3",
                    "Where subsegments are not signalled, each video and each audio segment SHALL have a duration " .
                    "of not more than 15 seconds",
                    $segmentDuration <= 15,
                    "FAIL",
                    "Duration of segment " . $j + 1 . " in bounds",
                    "Duration of segment " . $j + 1 . " not in bounds (is $segmentDuration)",
                );
            }

            $logger->test(
                "HbbTV-DVB DASH Validation Requirements",
                "HbbTV: Section E.2.3",
                "Segment duration SHALL be at least 1 second except for the last segment of a Period",
                $segmentDuration >= 1,
                "FAIL",
                "Duration of segment " . $j + 1 . " is at least 1 second",
                "Duration of segment " . $j + 1 . " is less than 1 second",
            );

            $sidxIndex++;
        } else {
            $subsegmentSignaling[$sidxIndex] = $referenceCount - 1;
            $cumulativeSubsegmentDuration += $segmentDuration;
            if ($hdlrType == 'vide' || $hdlrType == 'soun') {
                $logger->test(
                    "HbbTV-DVB DASH Validation Requirements",
                    "HbbTV: Section E.2.3",
                    "Where subsegments are not signalled, each video and each audio segment SHALL have a duration " .
                    "of not more than 15 seconds",
                    $segmentDuration <= 15,
                    "FAIL",
                    "Duration of segment " . $j + 1 . " in bounds",
                    "Duration of segment " . $j + 1 . " not in bounds (is $segmentDuration)",
                );
            }

            if ($subsegmentSignaling[$sidxIndex] == 0) {
                $sidxIndex++;
                $logger->test(
                    "HbbTV-DVB DASH Validation Requirements",
                    "HbbTV: Section E.2.3",
                    "Segment duration SHALL be at least 1 second except for the last segment of a Period",
                    $cumulativeSubsegmentDuration >= 1,
                    "FAIL",
                    "Duration of segment " . $j + 1 . " is at least 1 second",
                    "Duration of segment " . $j + 1 . " is less than 1 second",
                );

                $cumulativeSubsegmentDuration = 0;
            }
        }
    }
}


$repDir = $session->getSelectedRepresentationDir();
if ($mpdHandler->getFeatures()['type'] == 'dynamic') {
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "HbbTV: Section 'segments'",
        "Segment includes features that are not required by the profile being validated against",
        count(glob("$repDir/*")) != 1,
        "FAIL",
        "Check succesful",
        "found only segment in the representation while MPD@type is dynamic."
    );
}
