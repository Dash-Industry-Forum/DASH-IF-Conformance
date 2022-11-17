<?php

global $session, $MediaProfDatabase;

$periodCount = sizeof($MediaProfDatabase);
$adaptationCount = sizeof($MediaProfDatabase[0]);
$errorMsg = "";
for ($i = 0; $i < ($periodCount - 1); $i++) {
    for ($adapt = 0; $adapt < $adaptationCount; $adapt++) {
        $dir1 = $session->getRepresentationDir($i, $adapt, 0);
        $xml1 = DASHIF\Utility\parseDOM($dir1 . '/atomInfo.xml', 'atomlist');
        if ($xml1) {
            $hdlr = $xml1->getElementsByTagName("hdlr")->item(0)->getAttribute("handler_type");
            if ($hdlr == "soun") {
                $decoderSpecInfo = $xml1->getElementsByTagName("DecoderSpecificInfo")->item(0);
                if ($decoderSpecInfo){
                  $channels_p1 = $decoderSpecInfo->getAttribute("channelConfig");
                }
            }
        }
        $dir2 = $session->getRepresentationDir($i + 1, $adapt, 0);
        $xml2 = DASHIF\Utility\parseDOM($dir2 . '/atomInfo.xml', 'atomlist');
        if ($xml2) {
            $hdlr = $xml2->getElementsByTagName("hdlr")->item(0)->getAttribute("handler_type");
            if ($hdlr == "soun") {
                $decoderSpecInfo = $xml2->getElementsByTagName("DecoderSpecificInfo")->item(0);
                if ($decoderSpecInfo){
                  $channels_p2 = $decoderSpecInfo->getAttribute("channelConfig");
                }

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
