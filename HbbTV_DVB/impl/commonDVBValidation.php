<?php

global $sizearray;

global $logger, $mpdHandler;

$adaptation = $mpdHandler->getFeatures()['Period'][$mpdHandler->getSelectedPeriod()]
                                        ['AdaptationSet'][$mpdHandler->getSelectedAdaptationSet()];
$representation = $adaptation['Representation'][$mpdHandler->getSelectedRepresentation()];

## Check on the support of the provided codec
// Segment part
$hdlrBoxes = $xmlRepresentation->getElementsByTagName('hdlr');
$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "Conformance-Internal",
    "Representation is expected to contain (at least one) `hdlr` box",
    count($hdlrBoxes),
    "WARN",
    "`hdlr` box found",
    "No `hdlr` box found, skipping further checks"
);
if (!count($hdlrBoxes)) {
    return;
}
$hdlrType = $hdlrBoxes->item(0)->getAttribute('handler_type');
$sdType = $xmlRepresentation->getElementsByTagName("$hdlrType" . '_sampledescription')->item(0)->getAttribute('sdType');

$originalFormat = '';
if (strpos($sdType, 'enc') !== false) {
    $sinfBoxes = $xmlRepresentation->getElementsByTagName('sinf');
    if ($sinfBoxes->length != 0) {
        $originalFormat = $sinfBoxes->item(0)->getElementsByTagName('frma')->item(0)->getAttribute('originalFormat');
    }
}

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
        $repDir = $session->getSelectedRepresentationDir();
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
        $type = $mpdHandler->getFeatures()['type'];
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
                            "Subtitle segments SHALL contain ISO-BMFF packaged EBU-TT-D" .
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
        if (hexdec($nalUnit->getAttribute('nal_type')) == 7) {
            $spsFound = true;
        }
        if (hexdec($nalUnit->getAttribute('nal_type')) == 8) {
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
