<?php

global $session_dir, $MediaProfDatabase, $adaptation_set_template, $reprsentation_template;

$periodCount = sizeof($MediaProfDatabase);
$adaptationCount = sizeof($MediaProfDatabase[0]);
$errorMsg = "";
for ($i = 0; $i < ($periodCount - 1); $i++) {
    for ($adapt = 0; $adapt < $adaptationCount; $adapt++) {
        $adaptationDirectory = str_replace('$AS$', $adapt, $adaptation_set_template);
        $representationDirectory = str_replace(array('$AS$', '$R$'), array($adapt, 0), $reprsentation_template);
        $xml1 = get_DOM($session_dir . '/Period' . $i . '/' . $adaptationDirectory . '/' .
          $representationDirectory . '.xml', 'atomlist');
        if ($xml1) {
            $hdlr = $xml1->getElementsByTagName("hdlr")->item(0)->getAttribute("handler_type");
            if ($hdlr == "soun") {
                $decoderSpecInfo = $xml1->getElementsByTagName("DecoderSpecificInfo")->item(0);
                $channels_p1 = $decoderSpecInfo->getAttribute("channelConfig");
            }
        }
        $xml2 = get_DOM($session_dir . '/Period' . ($i + 1) . '/' . $adaptationDirectory . '/' .
          $representationDirectory . '.xml', 'atomlist');
        if ($xml2) {
            $hdlr = $xml2->getElementsByTagName("hdlr")->item(0)->getAttribute("handler_type");
            if ($hdlr == "soun") {
                $decoderSpecInfo = $xml2->getElementsByTagName("DecoderSpecificInfo")->item(0);
                $channels_p2 = $decoderSpecInfo->getAttribute("channelConfig");

                if ($channels_p1 != $channels_p2) {
                    $errorMsg = "###Warning: WAVE Content Spec 2018Ed-Section 7.2.2: 'Audio channel configuration " .
                    "Should allow the same stereo or multichannel config between Sequential Sw Sets at the Splice " .
                    "point', violated for Sw set " . $adapt . " between CMAF Presentations " . $i . " and  " .
                    ($i + 1) . " with channels " . $channels_p1 . " and " . $channels_p2 . " respectively.\n";
                }
            }
        }
    }
}
return $errorMsg;
