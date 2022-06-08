<?php

$contentType = $adaptation->getAttribute("contentType");

## Information from this part is used for Section 4.2.2 check about multiple Adaptation Sets with video as contentType
if ($contentType == 'video') {
    $this->adaptationVideoCount++;

    $roles = $adaptation->getElementsByTagName("Role");
    foreach ($roles as $role) {
        if ($role->getAttribute('schemeIdUri') == 'urn:mpeg:dash:role:2011' && $role->getAttribute('value') == 'main') {
            $this->mainVideoFound = true;
        }
    }
}

$ids = array();
$contentComponents = $adaptation->getElementsByTagName("ContentComponent");
foreach ($contentComponents as $component) {
    if ($component->getAttribute('contentType') == "video") {
        $ids[] = $component->getAttribute('id');
    }
}

$validSupplemental = true;
$supplementalProperties = $adaptation->getElementsByTagName("SupplementalProperty");
foreach ($supplementalProperties as $property) {
    if (
        $property->getAttribute('schemeIdUri') == 'urn:dvb:dash:fontdownload:2014' &&
        $property->getAttribute('value') == '1'
    ) {
      ///\todo shouldn't this be dvb:url?
        $url = ($property->getAttribute('url') != '' || $property->getAttribute('dvburl') != '');
        $fontFamily = ($property->getAttribute('fontFamily') != '' || $property->getAttribute('dvb:fontFamily') != '');
        $mimeType = ($property->getAttribute('mimeType') != '' || $property->getAttribute('dvb:mimeType') != '');

        if ($url && $fontFamily && $mimeType) {
            $validSupplemental = false;
        }
    }
}

$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB: Section 7.2.1.1",
    "For DVB font download for subtitles, a descriptor with these properties SHALL only be placed within an " .
    "Adaptation Set containing subtitle Representations",
    $validSupplemental,
    "FAIL",
    "No downloadable fonts found in Supplemental Properties for Period $this->periodCount",
    "Downloadable fonts found in Supplemental Properties for Period $this->periodCount"
);

$validEssential = true;
$essentialProperties = $adaptation->getElementsByTagName("EssentialProperty");
foreach ($essentialProperties as $property) {
    if (
        $property->getAttribute('schemeIdUri') == 'urn:dvb:dash:fontdownload:2014' &&
        $property->getAttribute('value') == '1'
    ) {
      ///\todo shouldn't this be dvb:url?
        $url = ($property->getAttribute('url') != '' || $property->getAttribute('dvburl') != '');
        $fontFamily = ($property->getAttribute('fontFamily') != '' || $property->getAttribute('dvb:fontFamily') != '');
        $mimeType = ($property->getAttribute('mimeType') != '' || $property->getAttribute('dvb:mimeType') != '');

        if ($url && $fontFamily && $mimeType) {
            $validEssential = false;
        }
    }
}

$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB: Section 7.2.1.1",
    "For DVB font download for subtitles, a descriptor with these properties SHALL only be placed within an " .
    "Adaptation  Set containing subtitle Representations",
    $validEssential,
    "FAIL",
    "No downloadable fonts found in Essential Properties for Period $this->periodCount",
    "Downloadable fonts found in Essential Properties for Period $this->periodCount"
);


$adaptationWidthPresent = ($adaptation->getAttribute("width") != '');
$adaptationHeightPresent = ($adaptation->getAttribute("height") != '');
$adaptationFrameRatePresent = ($adaptation->getAttribute("frameRate") != '');
$adaptationScanTypePresent = ($adaptation->getAttribute("scanType") != '');

$adaptationSetCodecs = $adaptation->getAttribute('codecs');
$representationCodecs = array();
$subRepresentationCodecs = array();
$representationScanTypes = array();
$representationFrameRate = array();


$j = 0;
foreach ($representations as $respresentation) {
    $j++;
    $representationWidthPresent = ($representation->getAttribute("width") != '');
    $representationHeightPresent  = ($representation->getAttribute("height") != '');
    $representationFrameRatePresent  = ($representation->getAttribute("frameRate") != '');
    $representationFrameRates[]  = $representation->getAttribute("frameRate");
    $representationScanTypePresent  = ($representation->getAttribute("scanType") != '');
    $representationScanTypes[]  = $representation->getAttribute("scanType");

    if ($contentType == "video") {
        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "DVB: Section 4.4",
            "For any Representation within an Adaptation Set with @contentType=\"video\" @width " .
            "attribute SHALL be present if not in the AdaptationSet element'",
            $adaptationWidthPresent || $representationWidthPresent,
            "FAIL",
            "Width attribute found in adaptation set and/or representation $j",
            "Width attribute not found in adaptation set and/or representation $j"
        );
        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "DVB: Section 4.4",
            "For any Representation within an Adaptation Set with @contentType=\"video\" @height " .
            "attribute SHALL be present if not in the AdaptationSet element'",
            $adaptationHeightPresent || $representationHeightPresent,
            "FAIL",
            "Height attribute found in adaptation set and/or representation $j",
            "Height attribute not found in adaptation set and/or representation $j"
        );
        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "DVB: Section 4.4",
            "For any Representation within an Adaptation Set with @contentType=\"video\" @frameRate " .
            "attribute SHALL be present if not in the AdaptationSet element'",
            $adaptationFrameRatePresent || $representationFrameRatePresent,
            "FAIL",
            "FrameRate attribute found in adaptation set and/or representation $j",
            "FrameRate attribute not found in adaptation set and/or representation $j"
        );
        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "DVB: Section 4.4",
            "For any Representation within an Adaptation Set with @contentType=\"video\" @sar " .
            "attribute SHALL be present if not in the AdaptationSet element'",
            $adaptationScanTypePresent || $representationScanTypePresent,
            "FAIL",
            "SAR attribute found in adaptation set and/or representation $j",
            "SAR attribute not found in adaptation set and/or representation $j"
        );
    }

    $codecs[] = $representation->getAttribute('codecs');
    $subRepresentations = $rep->getElementsByTagName('SubRepresentation');
    foreach ($subRepresentations as $subRepresentation) {
        $subRepresentationCodecs[] = $subRepresentation->getAttribute('codecs');

        if ($videoComponentFound) {
            if (in_array($subRepresentation->getAttribute('contentComponent'), $ids)) {
                $this->videoBandwidth[] = (
                  $representation->getAttribute('bandwidth') != '') ?
                  (float)($representation->getAttribute('bandwidth')) :
                  (float)($representation->getAttribute('bandwidth')
                );
            }
        }
    }
    if (!$videoComponentFound) {
        $this->videoBandwidth[] = (float)($representation->getAttribute('bandwidth'));
    }
}

## Information from this part is used for Section 5.1 AVC codecs
foreach (explode(',', $adaptationSetCodecs) as $codec) {
    if (strpos($codec, 'avc') !== false) {
        $this->avcCodecValidForDVB($codec);
    }
}

foreach ($representationCodecs as $codecs) {
    foreach (explode(',', $codecs) as $codec) {
        if (strpos($codec, 'avc') !== false) {
            $this->avcCodecValidForDVB($codec);
        }
    }
}
foreach ($subRepresentationCodecs as $codecs) {
    foreach (explode(',', $codecs) as $codec) {
        if (strpos($codec, 'avc') !== false) {
            $this->avcCodecValidForDVB($codec);
        }
    }
}

if ($contentType != 'video') {
    return;
}



$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB: Section 4.4",
    "For any Adaptation Sets with @contentType=\"video\" @maxWidth attribute (or @width if all Representations " .
    "have the same width) SHOULD be present",
    $adaptation->getAttribute('maxWidth') != '' || $adaptationWidthPresent,
    "WARN",
    "Attribute found in adaptation set for period $this->periodCount",
    "Attribute not found in adaptation set for period $this->periodCount"
);
$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB: Section 4.4",
    "For any Adaptation Sets with @contentType=\"video\" @maxHeight attribute (or @height if all Representations " .
    "have the same height) SHOULD be present",
    $adaptation->getAttribute('maxHeight') != '' || $adaptationHeightPresent,
    "WARN",
    "Attribute found in adaptation set for period $this->periodCount",
    "Attribute not found in adaptation set for period $this->periodCount"
);
$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB: Section 4.4",
    "For any Adaptation Sets with @contentType=\"video\" @maxFrameRate attribute (or @frameRate if all " .
    "Representations have the same height) SHOULD be present",
    $adaptation->getAttribute('maxFrameRate') != '' || $adaptationFrameRatePresent,
    "WARN",
    "Attribute found in adaptation set for period $this->periodCount",
    "Attribute not found in adaptation set for period $this->periodCount"
);
$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB: Section 4.4",
    "For any Adaptation Sets with @contentType=\"video\" @par attribute SHOULD be present",
    $adaptation->getAttribute('par') != '',
    "WARN",
    "Attribute found in adaptation set for period $this->periodCount",
    "Attribute not found in adaptation set for period $this->periodCount"
);

if (in_array('interlaced', $representationScanTypes)) {
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "DVB: Section 4.4",
        "For any Representation within an Adaptation Set with @contentType=\"video\" @scanType attribute " .
        "SHALL be present if interlaced pictures are used within any Representation in the Adaptation Set",
        !in_array('', $representationScanTypes),
        "FAIL",
        "All scanTypes are accounted for in period $this->periodCount",
        "Missing at least one scanType for period $this->periodCount"
    );
}

## Information from this part is used for Section 11.2.2 frame rate check
$frameRateLen = sizeof($representationFrameRates);
for ($f1 = 0; $f1 < $frameRateLen; $f1++) {
    $frameRate1 = $representationFrameRates[$f1];
    if ($frameRate1 != '') {
        for ($f2 = $f1 + 1; $f2 < $frameRateLen; $f2++) {
            $frameRate2 = $representationFrameRates[$f2];
            if ($frameRate2 != '') {
                $modulo = (
                  $frameRate1 > $frameRate2 ?
                  ($frameRate1 % $frameRate2) :
                  ($frameRate2 % $frameRate1)
                );

                $logger->test(
                    "HbbTV-DVB DASH Validation Requirements",
                    "DVB: Section 11.2.2",
                    "The frame rates used SHOULD be multiple integers of each other to enable seamless switching",
                    $modulo == 0,
                    "WARN",
                    "$f1 and $f2 are exact multiples",
                    "$f1 and $f2 are not exact multiples"
                );
            }
        }
    }
}
