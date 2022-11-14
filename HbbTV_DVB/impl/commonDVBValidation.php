<?php

global $mpd_features, $current_period, $current_adaptation_set, $current_representation, $profiles,
$sizearray;

global $logger;

$adaptation = $mpd_features['Period'][$current_period]['AdaptationSet'][$current_adaptation_set];
$representation = $adaptation['Representation'][$current_representation];

## Report on any resolutions used that are not in the tables of resoultions in 10.3 of the DVB DASH specification
$resolutionResult = $this->resolutionCheck($adaptation, $representation);

$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB: Section 'Codec information'",
    "The resolution should be in the table of resolutions in 10.3",
    $resolutionResult[0] != false,
    "INFO",
    "Resolution found",
    "Resolution " . $resolutionResult[1] . " and " . $resolutionResult[2] . " not in table"
);

## Check on the support of the provided codec
// MPD part
$codecs = $adaptation['codecs'];
if ($codecs == '') {
    $codecs = $representation['codecs'];
}

if ($codecs != '') {
    $codecs_arr = explode(',', $codecs);

    $unsupportedCodecs = '';
    foreach ($codecs_arr as $codec) {
        if (
            strpos($codec, 'avc') === false && strpos($codec, 'hev1') === false && strpos($codec, 'hvc1') === false &&
            strpos($codec, 'mp4a') === false && strpos($codec, 'ec-3') === false && strpos($codec, 'ac-4') === false &&
            strpos($codec, 'dtsc') === false && strpos($codec, 'dtsh') === false && strpos($codec, 'dtse') === false &&
            strpos($codec, 'dtsl') === false && strpos($codec, 'stpp') === false
        ) {
            $unsupportedCodecs .= "$codec ";
        }
    }

    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "DVB: Section 'Codec information'",
        "The codecs found in the MPD should be supported by the specification",
        $unsupportedCodecs == '',
        "WARN",
        "All found codecs supported",
        "Codecs '" . $unsupportedCodecs . "' not supported"
    );
}

// Segment part
$hdlrType = $xmlRepresentation->getElementsByTagName('hdlr')->item(0)->getAttribute('handler_type');
$sdType = $xmlRepresentation->getElementsByTagName("$hdlrType" . '_sampledescription')->item(0)->getAttribute('sdType');


$segmentCodecUnsupported = (
    strpos($sdType, 'avc') === false && strpos($sdType, 'hev1') === false && strpos($sdType, 'hvc1') === false &&
    strpos($sdType, 'mp4a') === false && strpos($sdType, 'ec-3') === false && strpos($sdType, 'ac-4') === false &&
    strpos($sdType, 'dtsc') === false && strpos($sdType, 'dtsh') === false && strpos($sdType, 'dtse') === false &&
    strpos($sdType, 'dtsl') === false && strpos($sdType, 'stpp') === false && strpos($sdType, 'enc') === false
);

$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB: Section 'Codec information'",
    "The codecs found in the Segment should be supported by the specification",
    !$segmentCodecUnsupported,
    "FAIL",
    "All found codecs supported",
    "Segment codec '" . $sdType . "' not supported"
);

$originalFormat = '';
if (strpos($sdType, 'enc') !== false) {
    $sinfBoxes = $xmlRepresentation->getElementsByTagName('sinf');
    if ($sinfBoxes->length != 0) {
        $originalFormat = $sinfBoxes->item(0)->getElementsByTagName('frma')->item(0)->getAttribute('originalFormat');
    }
}

if (strpos($sdType, 'avc') !== false || strpos($originalFormat, 'avc') !== false) {
    $nalUnits = $xmlRepresentation->getElementsByTagName('NALUnit');
    foreach ($nalUnits as $nalUnit) {
        if ($nalUnit->getAttribute('nal_type') != '0x07') {
            continue;
        }
        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "DVB: Section 'Codec information'",
            "Profile used for AVC codec in Segment must be supported by the specification",
            $nalUnit->getAttribute('profile_idc') == 100,
            "FAIL",
            "Valid profile used",
            "Invalid profile " . $nalUnit->getAttribute('profile_idc') . " used"
        );
        $level_idc = $nalUnit->getElementsByTagName('comment')->item(0)->getAttribute('level_idc');
        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "DVB: Section 'Codec information'",
            "Level used for AVC codec in Segment must be supported by the specification",
            $level_idc == 30 || $level_idc == 41 || $level_idc == 32 || $level_idc == 40,
            "FAIL",
            "Valid level used",
            "Invalid level $level_idc used"
        );
    }
} elseif (
    strpos($sdType, 'hev1') !== false ||
          strpos($sdType, 'hvc1') !== false ||
          strpos($originalFormat, 'hev1') !== false ||
          strpos($originalFormat, 'hvc1') !== false
) {
    $width = (int)$xmlRepresentation->getElementsByTagName(
        "$hdlrType" . '_sampledescription'
    )->item(0)->getAttribute('width');
    $height = (int)$xmlRepresentation->getElementsByTagName(
        "$hdlrType" . '_sampledescription'
    )->item(0)->getAttribute('height');

    $nalUnits = $xmlRepresentation->getElementsByTagName('NALUnit');
    foreach ($nalUnits as $nalUnit) {
        $nalUnitType = $nalUnit->parentNode->getAttribute('nalUnitType');
        if ($nalUnitType != '33') {
            continue;
        }
        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "DVB: Section 'Codec information'",
            "Tier used for HEVC codec in Segment must be supported by the specification Section 5.2.3",
            $nalUnit->getAttribute('gen_tier_flag') == '0',
            "FAIL",
            "Valid tier used",
            "Invalid level used: " . $nalUnit->getAttribute('gen_tier_flag')
        );
        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "DVB: Section 'Codec information'",
            "Bit depth used for HEVC codec in Segment must be supported by the specification Section 5.2.3",
            $nalUnit->getAttribute('bit_depth_luma_minus8') == 0 ||
            $nalUnit->getAttribute('bit_depth_luma_minus8') == 2,
            "FAIL",
            "Valid bit depth used",
            "Invalid bit depth used: " . $nalUnit->getAttribute('bit_depth_luma_minus8')
        );

        if ($width <= 1920 && $height <= 1080) {
            $logger->test(
                "HbbTV-DVB DASH Validation Requirements",
                "DVB: Section 'Codec information'",
                "Profile used for HEVC codec in Segment must be supported by the specification Section 5.2.3",
                $nalUnit->getAttribute('gen_profile_idc') == '1' || $nalUnit->getAttribute('gen_profile_idc') == '2',
                "FAIL",
                "Valid profile used",
                "Invalid profile used: " . $nalUnit->getAttribute('gen_profile_idc')
            );
            $logger->test(
                "HbbTV-DVB DASH Validation Requirements",
                "DVB: Section 'Codec information'",
                "Level used for HEVC codec in Segment must be supported by the specification Section 5.2.3",
                (int)$nalUnit->getAttribute('sps_max_sub_layers_minus1') != 0 ||
                (int)$nalUnit->getAttribute('gen_level_idc') <= 123,
                "FAIL",
                "Valid level used",
                "Invalid level used: " . $nalUnit->getAttribute('gen_level_idc')
            );
        } elseif ($width > 1920 && $height > 1080) {
            $logger->test(
                "HbbTV-DVB DASH Validation Requirements",
                "DVB: Section 'Codec information'",
                "Profile used for HEVC codec in Segment must be supported by the specification Section 5.2.3",
                $nalUnit->getAttribute('gen_profile_idc') == '2',
                "FAIL",
                "Valid profile used",
                "Invalid profile used: " . $nalUnit->getAttribute('gen_profile_idc')
            );
            $logger->test(
                "HbbTV-DVB DASH Validation Requirements",
                "DVB: Section 'Codec information'",
                "Level used for HEVC codec in Segment must be supported by the specification Section 5.2.3",
                (int)$nalUnit->getAttribute('sps_max_sub_layers_minus1') != 0 ||
                (int)$nalUnit->getAttribute('gen_level_idc') <= 153,
                "FAIL",
                "Valid level used",
                "Invalid level used: " . $nalUnit->getAttribute('gen_level_idc')
            );
        }
    }
}
##

## Subtitle checks
if ($adaptation['mimeType'] == 'application/mp4' || $representation['mimeType'] == 'application/mp4') {
    if ($adaptation['codecs'] == 'stpp' || $representation['codecs'] == 'stpp') {
        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "DVB: Section 'Subtitles'",
            "For subtitle media, handler type in the Initialization Segment SHALL be \"subt\"",
            $hdlrType == "subt",
            "FAIL",
            "Valid handler type found",
            "Invalid handler type \"$hdlrType\" found"
        );


        $stpp = $xmlRepresentation->getElementsByTagName('stpp');
        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "DVB: Section 'Subtitles'",
            "For subtitle media, sample entry type SHALL be \"stpp (XMLSubtitleSampleEntry)\"",
            $stpp->length > 0,
            "FAIL",
            "stpp found",
            "stpp not found"
        );

        if ($stpp->length > 0) {
            $logger->test(
                "HbbTV-DVB DASH Validation Requirements",
                "DVB: Section 'Subtitles'",
                "For subtitle media, namespaces SHALL be listed in the sample entry",
                $stpp->item(0)->getAttribute('namespace') != '',
                "FAIL",
                "Namespace element listed in first stpp item",
                "Namespace element not listed in first stpp item"
            );
        }

        ## EBU TECH 3381 - Section 5 - Layout check
        if (in_array('video', $mediaTypes)) {
            $tkhd = $xmlRepresentation->getElementsByTagName('tkhd')->item(0);
            $logger->test(
                "HbbTV-DVB DASH Validation Requirements",
                "DVB: Section 'Subtitles'",
                "EBU TECH 3381 Section 5- When the subtitle track is associated with a video object the width and " .
                "height of the subtitle track SHOULD NOT be set",
                "For subtitle media, sample entry type SHALL be \"stpp (XMLSubtitleSampleEntry)\"",
                (int)($tkhd->getAttribute('width')) == 0 && (int)($tkhd->getAttribute('height')) == 0,
                "WARN",
                "Width and height not set",
                "Width and/or height set"
            );
        }
        ##

        ## Check the timing of segments and the EBU-TT-D files
        // EBU-TT-D
        $validEBUTTD = true;
        $subtitleTimings = array();
        $repDir = $session->getRepresentationDir($current_period, $current_adaptation_set, $current_representation);
        ///\RefactorTodo Make this reflect the correct location
        $files = glob("$repDir/Subtitles/*");
        natsort($files);

        foreach ($files as $file) {
            $fileLoaded = simplexml_load_file($file);
            if (!$fileLoaded) {
                continue;
            }

            $domAbs = dom_import_simplexml($fileLoaded);
            $abs = new DOMDocument('1.0');
            $domAbs = $abs->importNode($domAbs, true);
            $domAbs = $abs->appendChild($domAbs);
            $abs = $abs->getElementsByTagName('subtitle')->item(0);
            $tts = $abs = $abs->getElementsByTagName('tt');

            $begin = '';

            foreach ($tts as $tt) {
                ##Check if metadata present; if yes, check if the profile is other than EBU-TT-D
                if ($tt->getElementsByTagName('metadata')->length != 0) {
                    $metadataElements = $tt->getElementsByTagName('metadata')->item(0)->childNodes;
                    foreach ($metadataElements as $metadataElement) {
                        if ($metadataElement->nodeType == XML_ELEMENT_NODE) {
                            if (strpos($metadataElement->nodeName, 'ebutt') === false) {
                                $validEBUTTD = false;
                            }
                        }
                    }
                }
                ##

                $body = $tt->getElementsByTagName('body')->item(0);
                $divs = $body->getElementsByTagName('div');
                foreach ($divs as $div) {
                    $paragraphs = $div->getElementsByTagName('p');
                    foreach ($paragraphs as $paragraph) {
                        $hms = explode(':', $paragraph->getAttribute('begin'));
                        $begin .= ' ' . (string)($hms[0] * 360 + $hms[1] * 60 + $hms[2]);
                    }
                }
            }

            $subtitleTimings[] = $begin;
        }

        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "DVB: Section 'Subtitles'",
            "Subtitle segments SHALL contain ISO-BMFF packaged EBU-TT-D",
            "For subtitle media, sample entry type SHALL be \"stpp (XMLSubtitleSampleEntry)\"",
            $validEBUTTD,
            "FAIL",
            "Only valid segments found",
            "At least one other profile found"
        );

        // Segments
        $type = $mpd_features['type'];
        $moofBoxCount = $xmlRepresentation->getElementsByTagName('moof')->length;
        $trunBoxes = $xmlRepresentation->getElementsByTagName('trun');
        $tfdtBoxes = $xmlRepresentation->getElementsByTagName('tfdt');

        $sidxBoxes = $xmlRepresentation->getElementsByTagName('sidx');
        $subsegmentSignaling = array();

        if ($sidxBoxes->length > 0) {
            foreach ($sidxBoxes as $sidxBox) {
                $subsegmentSignaling[] = (int)($sidxBox->getAttribute('referenceCount'));
            }
        }

        $mediaTime = 0;

        $elstBoxes = $xmlRepresentation->getElementsByTagName('elst');
        if ($elstBoxes->length > 0) {
            $mediaTime = (int)($elstBoxes->item(0)->getElementsByTagName('elstEntry')
                                         ->item(0)->getAttribute('mediaTime'));
        }

        if ($type != 'dynamic') {
            $timescale = $xmlRepresentation->getElementsByTagName('mdhd')->item(0)->getAttribute('timescale');
            $sidxIndex = 0;
            $cumulativeSubsegmentDuration = 0;
            $s = 0;
            for ($j = 0; $j < $moofBoxCount; $j++) {
                if (empty($subsegmentSignaling)) {
                    $cumulativeSubsegmentDuration +=
                        (($trunBoxes->item($j)->getAttribute('cummulatedSampleDuration')) / $timescale);

                    $subtitleBegin = explode(' ', $subtitleTimings[$j]);
                    for ($be = 1; $be < sizeof($subtitleBegin); $be++) {
                        $logger->test(
                            "HbbTV-DVB DASH Validation Requirements",
                            "DVB: Section 'Subtitles'",
                            "Subtitle segments SHALL contain ISO-BMFF packaged EBU-TT-D".
                            "For subtitle media, timing of all subtitles should conform to the segment time period",
                            $subtitleBegin[$be] <= $cumulativeSubsegmentDuration,
                            "WARN",
                            "Subtitle $be within segment boundaries",
                            "Subtitle $be starting at " . $subtitleBegin[$be] . " not within segment boundaries",
                        );
                    }
                } else {
                    $referenceCount = 1;
                    if ($sidxIndex < sizeof($subsegmentSignaling)) {
                        $referenceCount = $subsegmentSignaling[$sidxIndex];
                    }

                    $cumulativeSubsegmentDuration +=
                        (($trunBoxes->item($j)->getAttribute('cummulatedSampleDuration')) / $timescale);
                    $subsegmentSignaling[$sidxIndex] = $referenceCount - 1;

                    if ($subsegmentSignaling[$sidxIndex] == 0) {
                        while ($s <= $j) {
                            $subtitleBegin = explode(' ', $subtitleTimings[$s]);
                            for ($be = 1; $be < sizeof($subtitleBegin); $be++) {
                                $logger->test(
                                    "HbbTV-DVB DASH Validation Requirements",
                                    "DVB: Section 'Subtitles'",
                                    "Subtitle segments SHALL contain ISO-BMFF packaged EBU-TT-D",
                                    "For subtitle media, timing of all subtitles should conform to the segment " .
                                    "time period",
                                    $subtitleBegin[$be] <= $cumulativeSubsegmentDuration,
                                    "WARN",
                                    "Subtitle $be within segment boundaries",
                                    "Subtitle $be starting at " . $subtitleBegin[$be] . " not within segment boundaries"
                                );
                            }
                            $s++;
                        }

                        $sidxIndex++;
                    }
                }
            }
        }
    }
}

## Segment checks
// Section 4.3 on on-demand profile periods containing sidx boxes
if (
    strpos(
        $profiles[$current_period][$current_adaptation_set][$current_representation],
        'urn:mpeg:dash:profile:isoff-on-demand:2011'
    ) !== false
    ||
    strpos(
        $profiles[$current_period][$current_adaptation_set][$current_representation],
        'urn:dvb:dash:profile:dvb-dash:isoff-ext-on-demand:2014'
    ) !== false
) {
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "DVB: Section 'Segments'",
        "The segment for an \"On Demand Profile\" shall contain only 1 sidx box (Section 4.3)",
        $xmlRepresentation->getElementsByTagName('sidx')->length == 1,
        "FAIL",
        "1 sidx bound found",
        "" . $xmlRepresentation->getElementsByTagName('sidx')->length . " sidx boxes found"
    );

    $segmentLocation = str_replace(
        array('$AS$', '$R$'),
        array($current_adaptation_set, $current_representation),
        $representationrsentation_template
    );
    $segmentCount = count(glob("$repDir/*")) - count(glob("$repDir/*", GLOB_ONLYDIR));
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "DVB: Section 4.3 (For On Demand Profile)",
        "Each representation SHALL have only 1 Segment",
        $segmentCount == 1,
        "FAIL",
        "1 segment found",
        "$segmentCount segments found"
    );
}

// Section 4.3 on traf box count in moof boxes
$moofBoxes = $xmlRepresentation->getElementsByTagName('moof');
foreach ($moofBoxes as $moofBox) {
    $trafCount = $moofBox->getElementsByTagName('traf')->length;
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "DVB: Section 4.3",
        "The 'moof' box SHALL contain only one 'traf' box",
        $trafCount == 1,
        "FAIL",
        "1 'traf' box found",
        "$trafCount 'traf' boxes found"
    );
}

// Section 4.5 on segment and subsegment durations
$sidxBoxes = $xmlRepresentation->getElementsByTagName('sidx');
$subsegmentSignaling = array();
if ($sidxBoxes->length != 0) {
    foreach ($sidxBoxes as $sidx_box) {
        $subsegmentSignaling[] = (int)($sidx_box->getAttribute('referenceCount'));
    }
}

$timescale = $xmlRepresentation->getElementsByTagName('mdhd')->item(0)->getAttribute('timescale');
$moofBoxCount = $moofBoxes->length;
$sidxIndex = 0;
$cumulativeSubsegmentDuration = 0;
for ($j = 0; $j < $moofBoxCount - 1; $j++) {
    //\Note Is this correct???
    $cummulatedSampleDuration =
        $xmlRepresentation->getElementsByTagName('trun')->item($j)->getAttribute('cummulatedSampleDuration');
    $segmentDuration = $cummulatedSampleDuration / $timescale;

    if (
        empty($subsegmentSignaling) ||
        (!empty($subsegmentSignaling) &&
         sizeof(array_unique($subsegmentSignaling)) == 1 &&
         in_array(0, $subsegmentSignaling))
    ) {
        if ($hdlrType == 'vide' || $hdlrType == 'soun') {
            $logger->test(
                "HbbTV-DVB DASH Validation Requirements",
                "DVB: Section 4.5",
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
            "DVB: Section 4.5",
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
                "DVB: Section 4.5",
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
                "DVB: Section 4.5",
                "Segment duration SHALL be at least 1 second except for the last segment of a Period",
                $cumulativeSubsegmentDuration >= 1,
                "FAIL",
                "Duration of segment " . $j + 1 . " is at least 1 second",
                "Duration of segment " . $j + 1 . " is less than 1 second",
            );

            $sidxIndex++;
            $cumulativeSubsegmentDuration = 0;
        }

        // Section 5.1.2 on AVC content's SAP type
        if ($hdlrType == 'vide' && strpos($sdType, 'avc') !== false) {
            if ($sidxBoxes->length != 0) {
                $subsegment = $sidxBoxes->item($sidxIndex)->getElementsByTagName('subsegment')->item(0);
                if ($subsegment != null && $subsegment->getAttribute('starts_with_SAP') == '1') {
                    $sapType = $subsegment->getAttribute('SAP_type');
                    $logger->test(
                        "HbbTV-DVB DASH Validation Requirements",
                        "DVB: Section 5.1.2",
                        "Segments SHALL start with SAP types of 1 or 2",
                        $sapType == '1' || $sapType == '2',
                        "FAIL",
                        "Valid SAP type found",
                        "Invalid SAP type $sapType found"
                    );
                }
            }
        }
        //
    } else {
        $referenceCount = $subsegmentSignaling[$sidxIndex];
        if ($referenceCount == 0) {
            if ($hdlrType == 'vide' || $hdlrType == 'soun') {
                $logger->test(
                    "HbbTV-DVB DASH Validation Requirements",
                    "DVB: Section 4.5",
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
                "DVB: Section 4.5",
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
                    "DVB: Section 4.5",
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
                    "DVB: Section 4.5",
                    "Segment duration SHALL be at least 1 second except for the last segment of a Period",
                    $cumulativeSubsegmentDuration >= 1,
                    "FAIL",
                    "Duration of segment " . $j + 1 . " is at least 1 second",
                    "Duration of segment " . $j + 1 . " is less than 1 second",
                );

                $cumulativeSubsegmentDuration = 0;
            }

            // Section 5.1.2 on AVC content's SAP type
            if ($hdlrType == 'vide' && strpos($sdType, 'avc') !== false) {
                if ($sidxBoxes->length != 0) {
                    $subsegment = $sidxBoxes->item($sidxIndex)->getElementsByTagName('subsegment')->item(0);
                    if ($subsegment != null && $subsegment->getAttribute('starts_with_SAP') == '1') {
                        $sapType = $subsegment->getAttribute('SAP_type');
                        $logger->test(
                            "HbbTV-DVB DASH Validation Requirements",
                            "DVB: Section 5.1.2",
                            "Segments SHALL start with SAP types of 1 or 2",
                            $sapType == '1' || $sapType == '2',
                            "FAIL",
                            "Valid SAP type found",
                            "Invalid SAP type $sapType found"
                        );
                    }
                }
            }
            //
        }
    }

    // Section 6.2 on HE_AACv2 and 6.5 on MPEG Surround audio content's SAP type
    if ($hdlrType == 'soun' && strpos($sdType, 'mp4a') !== false) {
        if ($sidxBoxes->length != 0) {
            $subsegments = $sidxBoxes->item($sidxIndex)->getElementsByTagName('subsegment');
            if ($subsegments->length != 0) {
                foreach ($subsegments as $subsegment) {
                    if ($subsegment->getAttribute('starts_with_SAP') == '1') {
                        $sapType = $subsegment->getAttribute('SAP_type');
                        $logger->test(
                            "HbbTV-DVB DASH Validation Requirements",
                            "DVB: Section 5.1.2",
                            "Segments SHALL start with SAP types of 1 or 2",
                            $sapType == '1',
                            "FAIL",
                            "Valid SAP type found",
                            "Invalid SAP type $sapType found"
                        );
                    }
                }
            }
        }
    }
    //
}
##

// Section 5.1.2 on AVC content's sample entry type
if ($hdlrType == 'vide' && strpos($sdType, 'avc') !== false) {
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "DVB: Section 5.1.2",
        "Content SHOULD be offered using Inband storage for SPS/PPS i.e. sample entries 'avc3' and 'avc4'",
        $sdType == 'avc3' || $sdType == 'avc4',
        "WARN",
        "Valid sample description '$sdType' found",
        "Invalid sample description '$sdType' found"
    );

    $sampleDescription = $xmlRepresentation->getElementsByTagName("$hdlrType" . '_sampledescription')->item(0);
    $nalUnits = $sampleDescription->getElementsByTagName('NALUnit');
    $spsFound = false;
    $ppsFound = false;
    foreach ($nalUnits as $nalUnit) {
        if ($nalUnit->getAttribute('nal_type') == '0x07') {
            $spsFound = true;
        }
        if ($nalUnit->getAttribute('nal_type') == '0x08') {
            $ppsFound = true;
        }
    }
    // in AVC3 this data goes in the first sample of every fragment (i.e. the first sample in each mdat box).
    if ($sdType != 'avc3') {
        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "DVB: Section 5.1.2",
            "All information necessary to decode any Segment chosen from the Representation SHALL " .
            "be provided in the initialization Segment",
            $spsFound,
            "FAIL",
            "SPS found",
            "SPS not found"
        );
        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "DVB: Section 5.1.2",
            "All information necessary to decode any Segment chosen from the Representation SHALL " .
            "be provided in the initialization Segment",
            $ppsFound,
            "FAIL",
            "PPS found",
            "PPS not found"
        );
    }
}

// Section 4.5 on subtitle segment sizes
if ($hdlrType == 'subt') {
    $validSegmentSizes = true;
    foreach ($sizearray as $segsize) {
        if ($segsize > 512 * 1024) {
            $validSegmentSizes = false;
            break;
        }
    }
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "DVB: Section 4.5",
        "Subtitle segments SHALL have a maximum segment size of 512KB",
        $validSegmentSizes,
        "FAIL",
        "All valid",
        "At least one segment is too large"
    );
}
