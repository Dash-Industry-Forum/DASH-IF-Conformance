<?php

global $logger;

$subtitle = false;

$adaptationCodecs = $adaptation->getAttribute('codecs');
$adaptationMimeType = $adaptation->getAttribute('mimeType');
$adaptationContentType = $adaptation->getAttribute('contentType');
$codecInfoString = '';

if (strpos($adaptationCodecs, 'stpp') !== false) {
    $codecInfoString .= 'y ';
}

$ids = array();
$hohAccessibility = false;
$accesiblities = $adaptation->getElementsByTagName("Accessibility");
foreach ($accesiblities as $accessibility) {
    if (
        $accessibility->getAttribute('schemeIdUri') == 'urn:tva:metadata:cs:AudioPurposeCS:2007' &&
        $accessibility->getAttribute('value') == '2'
    ) {
        $hohAccessibility = true;
    }
}

$hohRole = false;

$roles = $adaptation->getElementsByTagName("Role");
foreach ($roles as $role) {
    if ($role->getAttribute('schemeIdUri') == 'urn:mpeg:dash:role:2011' && $role->getAttribute('value') == 'main') {
        $hohRole = true;
    }
}

$contentComponents = $adaptation->getElementsByTagName("ContentComponent");
$hasContentComponent = !empty($contentComponents);

$contentComponentTypes = array();

foreach ($contentComponents as $component) {
    $type = $component->getAttribute('contentType');
    $contentComponentTypes[] = $type;
    if ($type == 'text') {
        $ids[] = $type;
    }
}

$supplementalProperties = $adaptation->getElementsByTagName("SupplementalProperty");
$hasSupplementalProperties = !empty($supplementalProperties);

$supplementalSchemes = array();
$supplementalValues = array();
$supplementalUrls = array();
$supplementalFontFamilies = array();
$supplementalMimeTypes = array();

foreach ($supplementalProperties as $property) {
    $supplementalSchemes[] = $property->getAttribute('schemeIdUri');
    $supplementalValues[] = $property->getAttribute('value');
    $supplementalUrls[] = (($property->getAttribute('dvb:url') != '') ?
      $property->getAttribute('dvb:url') :
      $property->getAttribute('url'));
    $supplementalFontFamilies[] = (($property->getAttribute('dvb:fontFamily') != '') ?
      $property->getAttribute('dvb:fontFamily') :
      $property->getAttribute('fontFamily'));
    $supplementalMimeTypes[] = (($property->getAttribute('dvb:mimeType') != '') ?
      $property->getAttribute('dvb:mimeType') :
      $property->getAttribute('mimeType'));
}

$essentialProperties = $adaptation->getElementsByTagName("EssentialProperty");
$hasEssentialProperties = !empty($essentialProperties);

$essentialSchemes = array();
$essentialValues = array();
$essentialUrls = array();
$essentialFontFamilies = array();
$essentialMimeTypes = array();

foreach ($essentialProperties as $property) {
    $essentialSchemes[] = $property->getAttribute('schemeIdUri');
    $essentialValues[] = $property->getAttribute('value');
    $essentialUrls[] = (($property->getAttribute('dvb:url') != '') ?
      $property->getAttribute('dvb:url') :
      $property->getAttribute('url'));
    $essentialFontFamilies[] = (($property->getAttribute('dvb:fontFamily') != '') ?
      $property->getAttribute('dvb:fontFamily') :
      $property->getAttribute('fontFamily'));
    $essentialMimeTypes[] = (($property->getAttribute('dvb:mimeType') != '') ?
      $property->getAttribute('dvb:mimeType') :
      $property->getAttribute('mimeType'));
}

if ($hohAccessibility && $hohRole) {
    if ($adaptataion->getAttribute('lang') != '') {
        $this->hohSubtitleLanguages[] = $adaptation->getAttribute('lang');
    }
}

$representationIndex = 0;
foreach ($representations as $representation) {
    $representationIndex++;

    if (strpos($representation->getAttribute('codecs'), 'stpp') !== false) {
        $codecInfoString .= 'y ';
    }

    $subRepresentationCodecs = array();
    $subRepresentations = $representation->getElementsByTagName("SubRepresentation");
    foreach ($subRepresentations as $subRepresentation) {
        $subRepresentationCodec = $subRepresentation->getAttribute('codec');
        $subRepresentationCodecs[] = $subRepresentationCodec;
        if (strpos($subRepresentationCodec, 'stpp') !== false) {
            $codecInfoString .= 'y ';
        }
        if (in_array($subRepresentation->getAttribute('contentComponent'), $ids)) {
            $this->subtitleBandwidth[] = (float)($representation->getAttribute('bandwidth') != '' ?
            $representation->getAttribute('bandwidth') :
            $subRepresentation->getAttribute('bandwidth'));
        }
    }

    if ($adaptationMimeType == 'application/mp4' || $representation->getAttribute('mimeType') == 'application/mp4') {
        if (
            strpos($adaptationCodecs, 'stpp') !== false ||
            strpos($representation->getAttribute('codecs'), 'stpp') !== false ||
            in_array('stpp', $subRepresentationCodecs) !== false
        ) {
            $subtitle = true;


            $logger->test(
                "HbbTV-DVB DASH Validation Requirements",
                "DVB: Section 7.1.1",
                "The @contentType attribute indicated for subtitles SHALL be \"text\"",
                $adaptationContentType == "text" || in_array("text", $contentComponentTypes),
                "FAIL",
                "Valid contentType found in $this->periodCount, Representation $representationIndex",
                "Invalid contentType found in $this->periodCount, Representation $representationIndex"
            );
            $logger->test(
                "HbbTV-DVB DASH Validation Requirements",
                "DVB: Section 7.1.2",
                "In order to allow a Player to identify the primary purpose of a subtitle track, " .
                "the language attribute SHALL be set on the Adaptation Set",
                $adaptation->getAttribute('lang') != '',
                "FAIL",
                "language attribute found in $this->periodCount, Representation $representationIndex",
                "language attribute not found in $this->periodCount, Representation $representationIndex"
            );
        }
        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "DVB: Section 7.1.1",
            "The @codecs attribute indicated for subtitles SHALL be \"stpp\"",
            $codecInfoString != '',
            "FAIL",
            "\"stpp\" found for $this->periodCount, Representation $representationIndex",
            "\"stpp\" not found for $this->periodCount, Representation $representationIndex"
        );

        if (!$hasContentComponent) {
            $this->subtitleBandwidth[] = (float)($rep->getAttribute('bandwidth'));
        }
    }
}

    ## Information from this part is for Section 7.2: downloadable fonts and descriptors needed for them
if ($subtitle) {
    ///\Correctness Are supplemental and esssential properties exclusive?
    if ($hasSupplementalProperties) {
        $x = 0;
        foreach ($supplementalSchemes as $scheme) {
            if ($scheme == 'urn:dvb:dash:fontdownload:2014') {
                $logger->test(
                    "HbbTV-DVB DASH Validation Requirements",
                    "DVB: Section 7.2.1.1",
                    "This descriptor (SupplementalProperty for downloadable fonts) SHALL use the values for " .
                    "@schemeIdUri and @value specified in clause 7.2.1.2",
                    $supplementalValues[$x] == '1',
                    "FAIL",
                    "Valid configuration found in $this->periodCount, supplemental property " . $x + 1,
                    "Invalid configuration found in $this->periodCount, supplemental property " . $x + 1
                );
                $logger->test(
                    "HbbTV-DVB DASH Validation Requirements",
                    "DVB: Section 7.2.1.1",
                    "The descriptor (SupplementalProperty for downloadable fonts) SHALL carry all the mandatory " .
                    "additional attributes defined in clause 7.2.1.3",
                    $supplementalUrls[$x] != '' && $supplementalFontFamilies[$x] != '' &&
                    ($supplementalMimeTypes[$x] == 'application/font-sfnt' ||
                     $supplementalMimeTypes[$x] == 'application/font-woff'),
                    "FAIL",
                    "Valid configuration found in $this->periodCount, supplemental property " . $x + 1,
                    "Invalid configuration found in $this->periodCount, supplemental property " . $x + 1
                );
            }
            $x++;
        }
    } elseif ($hasEssentialProperties) {
        $x = 0;
        foreach ($essentialSchemes as $scheme) {
            if ($scheme == 'urn:dvb:dash:fontdownload:2014') {
                $logger->test(
                    "HbbTV-DVB DASH Validation Requirements",
                    "DVB: Section 7.2.1.1",
                    "This descriptor (EssentialProperty for downloadable fonts) SHALL use the values for " .
                    "@schemeIdUri and @value specified in clause 7.2.1.2",
                    $essentialValues[$x] == '1',
                    "FAIL",
                    "Valid configuration found in $this->periodCount, essential property " . $x + 1,
                    "Invalid configuration found in $this->periodCount, essential property " . $x + 1
                );
                $logger->test(
                    "HbbTV-DVB DASH Validation Requirements",
                    "DVB: Section 7.2.1.1",
                    "The descriptor (EssentialProperty for downloadable fonts) SHALL carry all the mandatory " .
                    "additional attributes defined in clause 7.2.1.3",
                    $essentialUrls[$x] != '' && $essentialFontFamilies[$x] != '' &&
                    ($essentialMimeTypes[$x] == 'application/font-sfnt' ||
                     $essentialMimeTypes[$x] == 'application/font-woff'),
                    "FAIL",
                    "Valid configuration found in $this->periodCount, essential property " . $x + 1,
                    "Invalid configuration found in $this->periodCount, essential property " . $x + 1
                );
            }
            $x++;
        }
    }
}
