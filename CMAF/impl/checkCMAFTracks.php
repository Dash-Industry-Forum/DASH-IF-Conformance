<?php

global $session, $logger, $mpdHandler;

$representationDirectory = $session->getSelectedRepresentationDir();

$xmlRepresentation = "$representationDirectory/atomInfo.xml";

if (!file_exists($xmlRepresentation)) {
    fwrite(STDERR, "Can't open $xmlRepresentation\n");
    return;
}
$xml = DASHIF\Utility\parseDOM($xmlRepresentation, 'atomlist');

if (!$xml) {
    fwrite(STDERR, "Invalid xml in $xmlRepresentation\n");
    return;
}


$return_array = $this->checkCMAFMessages($representationDirectory);

$cmaf_cmfc = $return_array[0];
$cmaf_cmf2 = $return_array[1];

# Store media type for selection set checks later
if ($xml->getElementsByTagName('hdlr')->item(0)) {
    $mediaType = $xml->getElementsByTagName('hdlr')->item(0)->getAttribute('handler_type');
    $this->mediaTypes[$mpdHandler->getSelectedPeriod()]
                     [$mpdHandler->getSelectedAdaptationSet()]
                     [$mpdHandler->getSelectedRepresentation()] = $mediaType;
}

$adaptationSet = $mpdHandler->getFeatures()['Period'][$mpdHandler->getSelectedPeriod()]
                                           ['AdaptationSet'][$mpdHandler->getSelectedAdaptationSet()];

$noErrorInTrack = true;
$id = $adaptationSet['Representation'][$mpdHandler->getSelectedAdaptationSet()]['id'];
$moofBoxes = $xml->getElementsByTagName('moof');
$moofBoxesCount = $moofBoxes->length;
$trunBoxes = $xml->getElementsByTagName('trun');
$tfdtBoxes = $xml->getElementsByTagName('tfdt');

// 'subs' presence check for TTML image subtitle track with media profile 'im1i'
$representationCodecs = $adaptationSet['Representation'][$mpdHandler->getSelectedRepresentation()]['codec'];
if (strpos($representationCodecs, 'im1i') !== false) {
    for ($j = 0; $j < $moofBoxesCount; $j++) {
        $moofBox = $moofBoxes[$j];
        $subsBoxes = $moofBox->getElementsByTagName('subs');
        $logger->test(
            "CMAF",
            "Section 7.5.20",
            "Each CMAF fragment in a TTML image subtitle track of CMAF media profile 'im1i' SHALL " .
            "contain a SubSampleInformationBox in the TrackFragmentBox",
            $subsBoxes->length,
            "FAIL",
            "Representation $id Fragment $j valid",
            "Representation $id Fragment $j has no SubSampleInformation"
        );
    }
}

for ($j = 1; $j < $moofBoxesCount; $j++) {
    $previousFragmentSampleDuration = $trunBoxes->item($j - 1)->getAttribute('cummulatedSampleDuration');
    $previousFragmentDecodeTime = $tfdtBoxes->item($j - 1)->getAttribute('baseMediaDecodeTime');
    $currentFragmentDecodeTime = $tfdtBoxes->item($j)->getAttribute('baseMediaDecodeTime');

    $noErrorInTrack &= $logger->test(
        "CMAF",
        "Section 7.3.2.2",
        "Each CMAF Fragment in a CMAF Track SHALL have baseMediaDecodeTime equal to the sum of all prior " .
            "Fragment durations added to the first Fragment's baseMediaDecodeTime",
        $currentFragmentDecodeTime == $previousFragmentDecodeTime + $previousFragmentSampleDuration,
        "FAIL",
        "Representation $id Fragment $j valid",
        "Representation $id Fragment $j does not have a valid baseMediaDecodeTime"
    );
    $noErrorInTrack &= $logger->test(
        "CMAF",
        "Section 7.3.2.3",
        "CMAF Chunks in a CMAF Track SHALL NOT overlap or have gaps in decode time",
        $currentFragmentDecodeTime == $previousFragmentDecodeTime + $previousFragmentSampleDuration,
        "FAIL",
        "Representation $id Fragment $j valid",
        "Representation $id Fragment $j has an overlap or gap"
    );
}

$mdatFile = fopen("$representationDirectory/mdatoffset.txt", 'r');
for ($j = 0; $j < $moofBoxesCount; $j++) {
    $currentTrunBox = $trunBoxes->item($j);
    if ($currentTrunBox->getAttribute('version') == 1) {
        $firstSampleCompositionTime = $currentTrunBox->getAttribute('earliestCompositionTime');
        $firstSampleDecodeTime = $tfdtBoxes->item(0)->getAttribute('baseMediaDecodeTime');
        $logger->test(
            "CMAF",
            "Section 7.5.17",
            "For 'trun' version 1, the composition time of 1st presented sample in a CMAF Segment SHALL be same " .
            "as first Sample decode time",
            $firstSampleCompositionTime == $firstSampleDecodeTime,
            "FAIL",
            "Representation $id valid",
            "Representation $id not valid"
        );
    }

    if ($mdatFile === false) {
        continue;
    }

    $mdatInfo = array();
    if (!feof($mdatFile)) {
        $mdatInfo = explode(" ", fgets($mdatFile));
    }

    if (empty($mdatInfo)) {
        continue;
    }
    $moofOffset = $moofBoxes->item($j)->getAttribute('offset');
    $dataOffset = $currentTrunBox->getAttribute('data_offset');
    $trunSampleSize = $currentTrunBox->getAttribute('sampleSizeTotal');
    // Check that $dataOffset leads to a position within the mdat and that the total length of content in the trun
    // doesn't, when added to this value, go beyond the end of the mdat.
    $offsetWithinInMdat = ($moofOffset + $dataOffset) >= $mdatInfo[0];
    $offsetBeforeNextMoof = ($moofOffset + $dataOffset + $trunSampleSize) <= ($mdatInfo[0] + $mdatInfo[1]);
    $logger->test(
        "CMAF",
        "Section 7.3.2.3",
        "All media samples in a CMAF Chunk SHALL be addressed by byte offsets in the TrackRunBox relative to " .
        "first byte of the MovieFragmentBox",
        $offsetWithinInMdat && $offsetBeforeNextMoof,
        "FAIL",
        "Representation $id, chunk $j valid",
        "Representation $id, chunk $j not valid"
    );
}
if ($mdatFile !== false) {
    fclose($mdatFile);
}

$logger->test(
    "CMAF",
    "Section 7.3.2.2",
    "The concatenation of a CMAF Header and all CMAF Fragments in the CMAF Track in consecutive decode order " .
    "SHALL be a valid fragmented ISOBMFF file",
    $noErrorInTrack,
    "FAIL",
    "Representation $id valid",
    "Representation $id not valid"
);

$hdlrBox = $xml->getElementsByTagName('hdlr')->item(0);
$hdlrType = ($hdlrBox == null ? '' : $hdlrBox->getAttribute('handler_type'));

$elstBoxes = $xml->getElementsByTagName('elstEntry');
if ($elstBoxes->length > 0 && $hdlrType == 'vide') {
    $firstSampleCompositionTime = $trunBoxes->item(0)->getAttribute('earliestCompositionTime');
    $mediaTime = $elstBoxes->item(0)->getAttribute('mediaTime');
    $logger->test(
        "CMAF",
        "Section 7.5.13",
        "In video CMAF track, an EditListBox SHALL be used to adjust the earliest video sample to movie " .
        "presentation time zero, i.e., media-time equal to composition-time of earliest presented sample in " . "
        the first Fragment",
        $mediaTime == $firstSampleCompositionTime,
        "FAIL",
        "Representation $id valid",
        "Representation $id not valid"
    );
}

$videoSampleDescription = $xml->getElementsByTagName('vide_sampledescription');
if ($videoSampleDescription->length > 0) {
    $sdType = $videoSampleDescription->item(0)->getAttribute('sdType');
    if ($sdType == "hvc1" || $sdType == "hev1") {
        $hvccBoxes = $videoSampleDescription->item(0)->getElementsByTagName('hvcC');
        if ($hvccBoxes->length != 1) {
            $logger->test(
                "CMAF",
                "Section B.2.3",
                "The HEVCSampleEntry SHALL contain an HEVCConfigurationBox (hvcC) containing an " .
                "HEVCDecoderConfigurationRecord",
                $hvccBoxes->length == 1,
                "FAIL",
                "Representation/Track $id valid (found exactly 1 hvcc box)",
                "Representation/Track $id not valid (found $hvccBoxes->length hvcc boxes)"
            );
        }
    }
    if ($sdType == "hev1") {
        $vuiFlag = 0;
        $nalUnits = $xml->getElementsByTagName('NALUnit');
        for ($k = 0; $k < $nalUnits->length; $k++) {
            $nalUnitType = $nalUnits->item($k)->getAttribute('nal_unit_type');
            if ($nalUnitType == 33) {
                $vuiFlag = $nalUnits->item($k)->getAttribute('vui_parameters_present_flag');
            }
        }
        if ($vuiFlag == 0) {
            $colorInformationBoxes = $videoSampleDescription->item(0)->getElementsByTagName('colr');
            $pixelAspectRatioBoxes = $videoSampleDescription->item(0)->getElementsByTagName('pasp');
            $logger->test(
                "CMAF",
                "Section B.2.3",
                "The HEVCSampleEntry SHALL contain PixelAspectRatioBox",
                $pixelAspectRatioBoxes->length,
                "FAIL",
                "Box found in Representation/Track $id",
                "Box not found in Representation/Track $id"
            );
            $logger->test(
                "CMAF",
                "Section B.2.3",
                "The HEVCSampleEntry SHALL contain ColorInformationBox with colour_type 'nclx'",
                $colorInformationBoxes->length,
                "FAIL",
                "Box found in Representation/Track $id",
                "Box not found in Representation/Track $id"
            );

            if ($colorInformationBoxes->length) {
                $colourType = $colorInformationBoxes->item(0)->getAttribute('colrtype');
                $logger->test(
                    "CMAF",
                    "Section B.2.3",
                    "The HEVCSampleEntry SHALL contain ColorInformationBox with colour_type 'nclx'",
                    $colourType == 'nclx',
                    "FAIL",
                    "Box found with correct 'colour_type' in Representation/Track $id",
                    "Box found with incorrect 'colour_type' '$colourType' in Representation/Track $id"
                );
            }
        }
    }
}

//Check for metadata required to decode, decrypt, display in CMAF Header.
if ($hdlrType == 'vide' && ($sdType == 'avc1' || $sdType == 'avc3')) {
    $width = $videoSampleDescription->item(0)->getAttribute('width');
    $height = $videoSampleDescription->item(0)->getAttribute('height');
    $nalUnits = $xml->getElementsByTagName('NALUnit');
    if ($nalUnits->length > 0) {
        $nalComment = $nalUnits->item(0)->getElementsByTagName('comment');
        $profileIdc = $nalUnits->item(0)->getAttribute('profile_idc');
        $levelIdc = $nalComment->item(0)->getAttribute('level_idc');
    }
    $logger->test(
        "CMAF",
        "Section 7.3.2.4",
        "Each CMAF Fragment in combination with its associated Header SHALL contain sufficient metadata to be " .
        "decoded and displayed when independently accessed",
        $width != null,
        "FAIL",
        "Width found for representation / track $id",
        "Width not found for representation / track $id",
    );
    $logger->test(
        "CMAF",
        "Section 7.3.2.4",
        "Each CMAF Fragment in combination with its associated Header SHALL contain sufficient metadata to be " .
        "decoded and displayed when independently accessed",
        $height != null,
        "FAIL",
        "Height found for representation / track $id",
        "Height not found for representation / track $id",
    );
    if ($sdType == 'avc1') {
        $logger->test(
            "CMAF",
            "Section 7.3.2.4",
            "Each CMAF Fragment in combination with its associated Header SHALL contain sufficient metadata to be " .
            "decoded and displayed when independently accessed",
            $profileIdc != null,
            "FAIL",
            "'profile_idc' found for representation / track $id",
            "'profile_idc' not found for representation / track $id",
        );
        $logger->test(
            "CMAF",
            "Section 7.3.2.4",
            "Each CMAF Fragment in combination with its associated Header SHALL contain sufficient metadata to be " .
            "decoded and displayed when independently accessed",
            $levelIdc != null,
            "FAIL",
            "'level_idc' found for representation / track $id",
            "'level_idc' not found for representation / track $id",
        );
        $logger->test(
            "CMAF",
            "Section 7.3.2.4",
            "Each CMAF Fragment in combination with its associated Header SHALL contain sufficient metadata to be " .
            "decoded and displayed when independently accessed",
            $mpdHandler->getFrameRate() != null,
            "FAIL",
            "FPS info found in MPD for representation / track $id",
            "FPS info not found in MPD for representation / track $id",
        );
    }
}
if ($hdlrType == 'soun') {
    $audioSampleDescription = $xml->getElementsByTagName('soun_sampledescription');
    $sdType = $audioSampleDescription->item(0)->getAttribute('sdType');
    $samplingRate = $audioSampleDescription->item(0)->getAttribute('sampleRate');
    $audioDecoderSpecificInfo = $xml->getElementsByTagName('DecoderSpecificInfo');
    if ($audioDecoderSpecificInfo->length > 0) {
        $channelConfig = $audioDecoderSpecificInfo->item(0)->getAttribute('channelConfig');
    }
    $logger->test(
        "CMAF",
        "Section 7.3.2.4",
        "Each CMAF Fragment in combination with its associated Header SHALL contain sufficient metadata to be " .
        "decoded and displayed when independently accessed",
        $sdType != null,
        "FAIL",
        "'sdType' found for representation / track $id",
        "'sdType' not found for representation / track $id",
    );
    $logger->test(
        "CMAF",
        "Section 7.3.2.4",
        "Each CMAF Fragment in combination with its associated Header SHALL contain sufficient metadata to be " .
        "decoded and displayed when independently accessed",
        $samplingRate != null,
        "FAIL",
        "'samplingRate' found for representation / track $id",
        "'samplingRate' not found for representation / track $id",
    );
    $logger->test(
        "CMAF",
        "Section 7.3.2.4",
        "Each CMAF Fragment in combination with its associated Header SHALL contain sufficient metadata to be " .
        "decoded and displayed when independently accessed",
        $channelConfig != null,
        "FAIL",
        "'channelConfig' found for representation / track $id",
        "'channelConfig' not found for representation / track $id",
    );
}

$dash264 = strpos(
    $mpdHandler->getProfiles()[$mpdHandler->getSelectedPeriod()]
                              [$mpdHandler->getSelectedAdaptationSet()]
                              [$mpdHandler->getSelectedRepresentation()],
    "http://dashif.org/guidelines/dash264"
) !== false;

$contentProtectionLength = 0;
if ($adaptationSet['ContentProtection']) {
    $contentProtectionLength = sizeof($adaptationSet['ContentProtection']);
} elseif (
    $adaptationSet['Representation'] &&
    $adaptationSet['Representation'][$mpdHandler->getSelectedRepresentation()] &&
    $adaptationSet['Representation'][$mpdHandler->getSelectedRepresentation()]['ContentProtection']
) {
    $contentProtectionLength = sizeof(
        $adaptationSet['Representation'][$mpdHandler->getSelectedRepresentation()]['ContentProtection']
    );
}


if ($contentProtectionLength > 0 && $dash264 == true) {
    $decryptionPossible = true;
    if ($xml->getElementsByTagName('tenc')->length == 0) {
        $decryptionPossible = false;
    } else {
        $tencBoxes = $xml->getElementsByTagName('tenc');
        $auxiliaryInformationPresent = ($tencBoxes->item(0)->getAttribute('default_IV_size') != 0);
        if ($auxiliaryInformationPresent) {
            for ($j = 0; $j < $moofBoxesCount; $j++) {
                $trafBoxes = $moofBoxes->item($j)->getElementsByTagName('traf');
                $sencBoxes = $trafBoxes->item(0)->getElementsByTagName('senc');
                $decryptionPossible &= $logger->test(
                    "CMAF",
                    "Section 7.4.2",
                    "When Sample Encryption Sample Auxiliary Info is used, 'senc' SHALL be present in each " .
                    "CMAF Fragment",
                    $sencBoxes->length,
                    "FAIL",
                    "Valid for representation / track $id, Fragment $j",
                    "Not valid for representation / track $id, Fragment $j",
                );
            }
        }
    }

    $logger->test(
        "CMAF",
        "Section 7.3.2.4",
        "Each CMAF Fragment in combination with its associated Header SHALL contain sufficient metadata to be " .
        "decrypted when independently accessed",
        $decryptionPossible,
        "FAIL",
        "Valid for representation / track $id",
        "Not valid for representation / track $id",
    );
}

$logger->test(
    "CMAF",
    "Section 7.3.2.2",
    "A CMAF Track SHALL conform to at least one structural brand",
    $cmaf_cmfc || $cmaf_cmf2,
    "FAIL",
    "Valid for representation / track $id, segment $z",
    "Not valid for representation / track $id, segment $z",
);
