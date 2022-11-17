<?php

global $logger, $session, $mpdHandler;

$adaptations = $mpdHandler->getFeatures()['Period'][$mpdHandler->getSelectedPeriod()]['AdaptationSet'];
$adaptationCount = sizeof($adaptations);

for ($adaptationIndex = 0; $adaptationIndex < $adaptationCount; $adaptationIndex++) {
    $location = $session->getAdaptationDir($mpdHandler->getSelectedPeriod(), $adaptationIndex);
    $fileCount = 0;
    $files = DASHIF\rglob("$location/*.xml");

    if (!$files) {
        continue;
    }

    $fileCount = count($files);
    for ($fileIndex = 0; $fileIndex < $fileCount; $fileIndex++) {
        $xml = DASHIF\Utility\parseDOM($files[$fileIndex], 'atomlist');
        if (!$xml) {
            continue;
        }
        $hdlrBox = $xml->getElementsByTagName("hdlrBox")->item(0);
        if ($hdlrBox == null || $hdlrBox->getAttribute("handler_type") != "vide") {
            continue;
        }

        $sampleDesc = $xml->getElementsByTagName("vide_sampledescription");
        $sampleDescriptions = "";
        for ($i = 0; $i < $sampleDesc->length; $i++) {
            $sampleDescriptions .= $sampleDesc->item($i)->getAttribute("sdType") . ", ";
        }

        $logger->test(
            "CTAWAVE",
            "WAVE Content Spec 2018Ed-Section 7.2.2",
            "Switching Set May conform to CMAF Single Initialization Constraints to indicate reinitialization " .
            "not req on Track switches",
            $sampleDesc->length == 1,
            "PASS",
            "Adherence to exactly one sample description: $sampleDescriptions",
            "Adherence to exactly more than one sample description: $sampleDescriptions"
        );
    }
}
