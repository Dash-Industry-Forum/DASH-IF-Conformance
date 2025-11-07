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

# Store media type for selection set checks later
if ($xml->getElementsByTagName('hdlr')->item(0)) {
    $mediaType = $xml->getElementsByTagName('hdlr')->item(0)->getAttribute('handler_type');
    $this->mediaTypes[$mpdHandler->getSelectedPeriod()]
                     [$mpdHandler->getSelectedAdaptationSet()]
                     [$mpdHandler->getSelectedRepresentation()] = $mediaType;
}

$adaptationSet = $mpdHandler->getFeatures()['Period'][$mpdHandler->getSelectedPeriod()]
                                           ['AdaptationSet'][$mpdHandler->getSelectedAdaptationSet()];

$id = $adaptationSet['Representation'][$mpdHandler->getSelectedAdaptationSet()]['id'];
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

