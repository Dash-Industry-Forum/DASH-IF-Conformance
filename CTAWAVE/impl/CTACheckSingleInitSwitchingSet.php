<?php

global $mpd_features, $current_period, $session;

global $logger;

$adaptations = $mpd_features['Period'][$current_period]['AdaptationSet'];
$adaptationCount = sizeof($adaptations);

for ($adaptationIndex = 0; $adaptationIndex < $adaptationCount; $adaptationIndex++) {
    $location = $session->getAdaptationDir($current_period, $adaptationIndex);
    $fileCount = 0;
    ///\RefactorTodo This pattern is used in multiple locations, but won't work anymore
    $files = glob($location . "/*.xml");
    if (!$files) {
        continue;
    }

    $fileCount = count($files);
    for ($fileIndex = 0; $fileIndex < $fileCount; $fileIndex++) {
        $xml = get_DOM($files[$fileIndex], 'atomlist');
        if (!$xml) {
            continue;
        }
        $hdlrBox = $xml->getElementsByTagName("hdlrBox")->item(0);
        if ($hdlrBox->getAttribute("hdlrType") != "vide") {
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
