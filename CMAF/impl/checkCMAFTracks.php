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
