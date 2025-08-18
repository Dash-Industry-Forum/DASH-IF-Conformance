<?php

global $logger;

$contentType = $adaptation->getAttribute("contentType");

$ids = array();
$contentComponents = $adaptation->getElementsByTagName("ContentComponent");
foreach ($contentComponents as $component) {
    if ($component->getAttribute('contentType') == "video") {
        $ids[] = $component->getAttribute('id');
    }
}

$adaptationSetCodecs = $adaptation->getAttribute('codecs');
$representationCodecs = array();
$subRepresentationCodecs = array();
$representationFrameRate = array();


$j = 0;
foreach ($representations as $representation) {
    $j++;
    $representationFrameRates[]  = $representation->getAttribute("frameRate");

    $codecs[] = $representation->getAttribute('codecs');

    $subRepresentations = $representation->getElementsByTagName('SubRepresentation');
    foreach ($subRepresentations as $subRepresentation) {
        $subRepresentationCodecs[] = $subRepresentation->getAttribute('codecs');
    }
    $this->videoBandwidth[] = (float)($representation->getAttribute('bandwidth'));
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
