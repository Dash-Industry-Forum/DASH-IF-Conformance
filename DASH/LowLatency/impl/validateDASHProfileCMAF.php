<?php

global $session, $current_period, $logger;

$dashConformsToCmafFrag = array();
$representations = $adaptationSet['Representation'];

foreach ($representations as $representationId => $representation) {
    $rep_xml = $session->getRepresentationDir($current_period, $adaptationSetId, $representationId) . '/atomInfo.xml';

    if (!file_exists($rep_xml)) {
        continue;
    }

    $xml = get_DOM($rep_xml, 'atomlist');
    if (!$xml) {
        continue;
    }

    $contentType = $adaptationSet['contentType'];
    $mimeType = ($representation['mimeType'] != null) ? $representation['mimeType'] : $adaptationSet['mimeType'];
    $hdlrType = $xml->getElementsByTagName('hdlr')->item(0)->getAttribute('handler_type');
    if ($contentType != null) {
        $validContentType = true;
        if ($hdlrType == 'vide' && strpos($contentType, 'video') === false) {
            $validContentType = false;
        }
        if ($hdlrType == 'soun' && strpos($contentType, 'audio') === false) {
            $validContentType = false;
        }
        if (($hdlrType == 'text' || $hdlrType == 'subt') && strpos($contentType, 'text') === false) {
            $validContentType = false;
        }
        $logger->test(
            "DASH-IF IOP CR Low Latency Live",
            "Section 9.X.4.5 (As part of MPEG-DASH 8.X.4)",
            "The @contentType SHALL be set to the hdlr type of the " .
            "CMAF Master Header of the Switching Set",
            $validContentType,
            "FAIL",
            "ContentType and Hdlr type match in Period " . ($current_period + 1) . ' Adaptation ' .
            ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
            "ContentType and Hdlr type do not match in Period " . ($current_period + 1) . ' Adaptation ' .
            ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1)
        );

        $validMimeType = true;
        if (
            strpos($contentType, 'video') !== false &&
            !($mimeType === "video/mp4" || $mimeType === "video/mp4, profiles='cmfc'")
        ) {
            $validMimeType = false;
        }
        if (
            strpos($contentType, 'audio') !== false &&
            !($mimeType === "audio/mp4" || $mimeType === "audio/mp4, profiles='cmfc'")
        ) {
            $validMimeType = false;
        }
        if (
            strpos($contentType, 'text') !== false &&
            !($mimeType === "text/mp4" || $mimeType === "text/mp4, profiles='cmfc'")
        ) {
            $validMimeType = false;
        }
        $logger->test(
            "DASH-IF IOP CR Low Latency Live",
            "Section 9.X.4.5 (As part of MPEG-DASH 8.X.4)",
            "The @mimeType SHALL be compatible \"<@contentType>/mp4\" or \"<@contentType>/mp4, profiles='cmfc'\"",
            $validContentType,
            "FAIL",
            "Mimetype matches in Period " . ($current_period + 1) . ' Adaptation ' .
            ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
            "Mimetype does not match in Period " . ($current_period + 1) . ' Adaptation ' .
            ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1)
        );

        if (strpos($contentType, 'video') !== false) {
            $maxWidth = ($adaptationSet['maxWidth'] != null) ? ((int) ($adaptationSet['maxWidth'])) : 0;
            $maxHeight = ($adaptationSet['maxHeight'] != null) ? ((int) ($adaptationSet['maxWidth'])) : 0;
            $tkhd = $xml->getElementsByTagName('tkhd')->item(0);
            $width = (int) ($tkhd->getAttribute('width'));
            $height = (int) ($tkhd->getAttribute('height'));
            $logger->test(
                "DASH-IF IOP CR Low Latency Live",
                "Section 9.X.4.5 (As part of MPEG-DASH 8.X.4)",
                "If the @contentType is video, then @maxWidth SHOULD be set to the width in CMAF TrackHeaderBox " .
                "of the CMAF Master Header",
                $maxWidth == $width,
                "WARN",
                "Width matches in Period " . ($current_period + 1) . ' Adaptation ' .
                ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
                "Width does not match in Period " . ($current_period + 1) . ' Adaptation ' .
                ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1)
            );
            $logger->test(
                "DASH-IF IOP CR Low Latency Live",
                "Section 9.X.4.5 (As part of MPEG-DASH 8.X.4)",
                "If the @contentType is video, then @maxHeight SHOULD be set to the width in CMAF TrackHeaderBox " .
                "of the CMAF Master Header",
                $maxHeight == $height,
                "WARN",
                "Height matches in Period " . ($current_period + 1) . ' Adaptation ' .
                ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
                "Height does not match in Period " . ($current_period + 1) . ' Adaptation ' .
                ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1)
            );
        }
    }

    ///\Correctness Changed this check
    $codecs = ($representation['codecs'] != null) ? $representation['codecs'] : $adaptationSet['codecs'];
    $validCodecs = $codecs != null;
    if ($validCodecs) {
        $sample_description = $hdlr_type . '_sampledescription';
        $stsdBoxes = $xml->getElementsByTagName('stsd');
        $sdType = $stsdBoxes->item(0)->getElementsByTagName($sample_description)->item(0)->getAttribute('sdType');
        if (strpos($codecs, $sdType) === false) {
            $validCodecs = false;
        }
    }
    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.5 (As part of MPEG-DASH 8.X.4)",
        "The @codecs parameter SHALL be set to the sample entry of the CMAF Master Header",
        "of the CMAF Master Header",
        $validCodecs,
        "FAIL",
        "Codecs set to valid value in Period " . ($current_period + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
        "Codecs either not set, or invalid in Period " . ($current_period + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1)
    );

    $tencs = $xml->getElementsByTagName('tenc');
    if ($tencs->length > 0) {
        $tenc = $tencs->item(0);
        $contentProtections = ($representation['ContentProtection'] != null) ?
          $representation['ContentProtection'] : $adaptationSet['ContentProtection'];

        $validContentProtection = ($contentProtections != null);
        if ($validContentProtection) {
            $validContentProtection = false;
            foreach ($contentProtections as $contentProtection) {
                if ($contentProtection['schemeIdUri'] == 'urn:mpeg:dash:mp4protection:2011') {
                    if ($contentProtection['value'] != 'cenc' && $contentProtection['value'] != 'cbcs') {
                        continue;
                    }
                    if ($contentProtection['cenc:default_KID'] == null) {
                        $validContentProtection = true;
                    } elseif ($contentProtection['cenc:default_KID'] == $tenc->getAttribute('default_KID')) {
                        $validContentProtectionFound = true;
                    }
                } else {
                    $cenc_default_KID = $contentProtection['cenc:default_KID'];
                    $cenc_pssh = $contentProtection['cenc:pssh'];
                    $psshs = $xml->getElementsByTagName('pssh');

                    $checkDefaultKID = ($cenc_default_KID == null ||
                      $cenc_default_KID == $tenc->getAttribute('default_KID'));
                    $checkPSSH = ($cenc_pssh == null || ($psshs->length > 0 &&
                      $cenc_pssh == $psshs->item(0)->getAttribute('systemID')));

                    if ($checkDefaultKID && $checkPSSH) {
                        $validContentProtection = true;
                    }
                }

                if ($validContentProtection) {
                    break;
                }
            }
        }
        $logger->test(
            "DASH-IF IOP CR Low Latency Live",
            "Section 9.X.4.5 (As part of MPEG-DASH 8.X.4)",
            "If the content is protected a ContentProtection element SHALL be present and set appropriately",
            $validContentProtection,
            "FAIL",
            "Content protection set and valid in Period " . ($current_period + 1) . ' Adaptation ' .
            ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
            "Content protection either not set or not valid in Period " . ($current_period + 1) . ' Adaptation ' .
            ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1)
        );
    }

    $segmentAccessRepresentation = $segmentAccessInfo[$representationId][0];

    $timescaleMPD = $segmentAccessRepresentation['timescale'];
    $timescaleHeader = $xml->getElementsByTagName('mdhd')->item(0)->getAttribute('timescale');
    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.5 (As part of MPEG-DASH 8.X.4)",
        "The @timescale in Representation SHALL be set to the timescale of Media Header Box ('mdhd') of the CMAF Track",
        $timescaleMPD == null || $timescaleMPD == $timescaleHeader,
        "FAIL",
        "MPD and Header timescales are equal in Period " . ($current_period + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
        "MPD and Header timescales are not equal in Period " . ($current_period + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1)
    );

    if ($segmentAccessRepresentation['SegmentTimeline'] != null) {
        $this->validateSegmentTimeline(
            $adaptationSet,
            $adaptationSetId,
            $representation,
            $representationId,
            $segmentAccessRepresentation,
            $infoFileAdapt
        );
    } else {
        $this->validateSegmentTemplate(
            $adaptationSetId,
            $representationId,
            $segmentAccessRepresentation,
            $infoFileAdapt
        );
    }

    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
        "Event Message Streams MAY be signaled with InbandEventStream elements",
        $representation['InbandEventStream'] != null || $adaptationSet['InbandEventStream'] != null,
        "PASS",
        "InbandEventSteam found for Period " . ($current_period + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
        "InbandEventSteam not found for Period " . ($current_period + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1)
    );

    $validSelfInitializingSegment = $this->validateSelfInitializingSegment(
        $adaptationSet,
        $adaptationSetId,
        $representation,
        $representationId,
        $segmentAccessRepresentation,
        $infoFileAdapt,
        $xml
    );

    $validCMAFSegment = true;
    $tfdtBoxes = $xml->getElementsByTagName('tfdt');
    for ($i = 1; $i < $tfdtBoxes->length; $i++) {
        $tfdtPreviousDecodeTime = $tfdtBoxes->item($i - 1)->getAttribute('baseMediaDecodeTime');
        $tfdtDecodeTime = $tfdtBoxes->item($i)->getAttribute('baseMediaDecodeTime');
        if ($tfdtPreviousDecodeTime > $tfdtDecodeTime) {
            $validCMAFSegment = false;
            break;
        }
    }
    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
        "Each CMAF Segment SHALL contain one or more complete and consecutive CMAF Fragments in decode order",
        $validCMAFSegment,
        "INFO",
        "All fragments in decode order for Period " . ($current_period + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
        "CMAF Fragments out of decode order found for Period " . ($current_period + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1)
    );

    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
        "Each Media Segment SHALL conform to a CMAF Addressable Media Object as defined in CMAF 7.3.3",
        $validCMAFSegment || $validSelfInitializingSegment,
        "FAIL",
        "All segments valid for Period " . ($current_period + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
        "Not all segments valid for Period " . ($current_period + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1)
    );


    $errors = file_get_contents(
        $session->getRepresentationDir($current_period, $adaptationSetId, $representationId) . '/stderr.txt'
    );

    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
        "Each Media Segment SHALL conform to a Delivery Unit Media Segment as defined in 6.3.4.2",
        strpos($errors, 'ISO/IEC 23009-1:2012(E), 6.3.4.2') === false,
        "FAIL",
        "All segments conform to Delivery Unit Media Segment found in Period " . ($current_period + 1) .
        ' Adaptation ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
        "Segment non-conforming to Delivery Unit Media Segment found for Period " . ($current_period + 1) .
        ' Adaptation ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1)
    );
    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
        "Each Initialization Segment, if present, SHALL conform to a CMAF Header as defined in CMAF 7.3.2.1",
        strpos($errors, 'CMAF checks violated: Section 7.3.2.1') === false,
        "FAIL",
        "All segments conform to CMAF Header in Period " . ($current_period + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
        "Segment non-conforming to CMAF Header found for Period " . ($current_period + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1)
    );
    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
        "Each Initialization Segment, if present, SHALL conform to an Initialization Segment as defined in 6.3.3",
        strpos($errors, 'ISO/IEC 23009-1:2012(E), 6.3.3') === false,
        "FAIL",
        "All segments conform to CMAF Initialization in Period " . ($current_period + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
        "Segment non-conforming to CMAF Initialization found for Period " . ($current_period + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1)
    );

    $moofBoxes = $xml->getElementsByTagName('moof');
    $trunBoxes = $xml->getElementsByTagName('trun');
    $errorInTrack = false;
    for ($j = 1; $j < $moofBoxes->length; $j++) {
        $cummulatedSampleDurFragPrev = $trunBoxes->item($j - 1)->getAttribute('cummulatedSampleDuration');
        $decodeTimeFragPrev = $tfdtBoxes->item($j - 1)->getAttribute('baseMediaDecodeTime');
        $decodeTimeFragCurr = $tfdtBoxes->item($j)->getAttribute('baseMediaDecodeTime');

        if ($decodeTimeFragCurr != $decodeTimeFragPrev + $cummulatedSampleDurFragPrev) {
            $errorInTrack = true;
        }
    }
    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
        "The Representation SHALL conform to a CMAF Track as defined in CMAF 7.3.2.2",
        !$errorInTrack,
        "FAIL",
        "Valid representation: Period " . ($current_period + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
        "Invalid representation: Period " . ($current_period + 1) . ' Adaptation ' .
        ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1)
    );

    $startWithSAP = $adaptationSet['startWithSAP'];
    if ($representation['startWithSAP'] != null) {
        $startWithSAP = $representation['startWithSAP'];
    }
    if (strpos($errors, 'CMAF checks violated: Section 7.3.2.4') === false) {
        $dashConformsToCmafFrag[$representationId] = true;
        $logger->test(
            "DASH-IF IOP CR Low Latency Live",
            "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
            "If every DASH segment conforms to CMAF Fragment constraints and @startWithSAP is present, " .
            "it SHALL be set to value 1 or 2",
            $startWithSAP == null || $startWithSAP == '1' || $startWithSAP == '2',
            "FAIL",
            "Start sap has a valid value of $startWithSAP in Period " . ($current_period + 1) . ' Adaptation ' .
            ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1),
            "Start sap has an invalid value of $startWithSAP in Period " . ($current_period + 1) . ' Adaptation ' .
            ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1)
        );
    }
}

$logger->test(
    "DASH-IF IOP CR Low Latency Live",
    "Section 9.X.4.5 (As part of MPEG-DASH 8.X.4)",
    "Either segmentAlignment or subsegmentAlignment SHALL be set",
    $adaptationSet['segmentAlignment'] != null || $adaptationSet['subsegmentAlignment'] != null,
    "FAIL",
    "Valid for Period " . ($current_period + 1) . ' Adaptation ' .
    ($adaptationSetId + 1),
    "Neither found for Period " . ($current_period + 1) . ' Adaptation ' .
    ($adaptationSetId + 1)
);

return $dashConformsToCmafFrag;
