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
            $tkhd = $xml1->getElementsByTagName("tkhd")->item(0);
            $hdlr = $xml1->getElementsByTagName("hdlr")->item(0)->getAttribute("handler_type");
            if ($hdlr == "vide") {
                $par_p1 = $tkhd->getAttribute("width") / ($tkhd->getAttribute("height"));
            }
        }
        $dir2 = $session->getRepresentationDir($i + 1, $adapt, 0);
        $xml2 = DASHIF\Utility\parseDOM($dir2 . '/atomInfo.xml', 'atomlist');
        if ($xml2) {
            $tkhd = $xml2->getElementsByTagName("tkhd")->item(0);
            $hdlr = $xml2->getElementsByTagName("hdlr")->item(0)->getAttribute("handler_type");
            if ($hdlr == "vide") {
                $par_p2 = $tkhd->getAttribute("width") / ($tkhd->getAttribute("height"));


                if ($par_p1 != $par_p2) {
                    $errorMsg = "###Warning: WAVE Content Spec 2018Ed-Section 7.2.2: 'Picture Aspect Ratio (PAR) " .
                    "Should be the same between Sequential Sw Sets at the Splice point', violated for Sw set " .
                    $adapt . " between CMAF Presentations " . $i . " and  " . ($i + 1) . " with - PAR " .
                    $par_p1 . " and " . $par_p2 . ".\n";
                }
            }
        }
    }
}

return $errorMsg;
