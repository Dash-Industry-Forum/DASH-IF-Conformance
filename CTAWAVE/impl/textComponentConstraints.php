<?php

global $logger;

$sampleDescription = $representation->getSampleDescription();

if (!$sampleDescription) {
    return;
}

$spec = "CTA-5005-A";
$section = "4.1.2 - Constraints on CMAF Authoring for Manifest Interoperability";
$explanation = "Text components SHALL be packaged in ISMC1, ISMC1.1 or WebVTT Tracks";

if ($sampleDescription->type == DASHIF\Boxes\DescriptionType::Subtitle) {
  //Has to be ISMC in this case
    $ismcNamespace = str_contains($sampleDescription->namespace, 'http://www.w3.org/ns/ttml');
    $logger->test(
        $spec,
        $section,
        $explanation,
        $ismcNamespace,
        "FAIL",
        "Subtitle track detected, with correct namespace for " . $representation->getPrintable(),
        "Subtitle track detected, with incorrect namespace $sampleDescription->namespace for " .
        $representation->getPrintable()
    );
    $ismc1Location = str_contains(
        $sampleDescription->schemaLocation,
        'http://www.w3.org/ns/ttml/profile/imsc1/text'
    );
    $ismc11Location = str_contains(
        $sampleDescription->schemaLocation,
        'http://www.w3.org/ns/ttml/profile/imsc1.1/text'
    );
    $logger->test(
        $spec,
        $section,
        $explanation,
        $ismc1Location || $ismc11Location,
        "FAIL",
        "Subtitle track detected, with correct schema_location for " . $representation->getPrintable(),
        "Subtitle track detected, with incorrect schema_location $sampleDescription->schemaLocation for " .
        $representation->getPrintable()
    );

    $mimeTypeTTML = str_contains($sampleDescription->auxiliaryMimeTypes, 'application/ttml+xml');
    $hasCodecs = str_contains($sampleDescription->auxiliaryMimeTypes, ';codecs=');
    $isIm1t = $ismc1Location &&
              $hasCodecs &&
              str_contains($sampleDescription->auxiliaryMimeTypes, 'im1t');
    $isIm2t = $ismc11Location &&
              $hasCodecs &&
              str_contains($sampleDescription->auxiliaryMimeTypes, 'im2t');
    $logger->test(
        $spec,
        $section,
        $explanation,
        $mimeTypeTTML && ($isIm1t || $isIm2t),
        "FAIL",
        "Subtitle track detected, with correct mimetype for " . $representation->getPrintable(),
        "Subtitle track detected, with incorrect mimetype $sampleDescription->auxiliaryMimeTypes for " .
        $representation->getPrintable()
    );
}

if ($sampleDescription->type == DASHIF\Boxes\DescriptionType::Text) {
    $logger->test(
        $spec,
        $section,
        $explanation,
        $sampleDescription->codingname == 'wvtt',
        "FAIL",
        "Text track detected, and signaled as WebVTT " . $representation->getPrintable(),
        "Text track detected, but signaled as $sampleDescription->codingname for " . $representation->getPrintable()
    );
}
