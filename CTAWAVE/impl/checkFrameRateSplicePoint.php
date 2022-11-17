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
            if ($hdlr == "vide") {
                $framerate_p1 = $this->getFrameRate($xml1);
            }
        }
        $dir2 = $session->getRepresentationDir($i + 1, $adapt, 0);
        $xml2 = DASHIF\Utility\parseDOM($dir2 . '/atomInfo.xml', 'atomlist');
        if ($xml2) {
            $hdlr = $xml2->getElementsByTagName("hdlr")->item(0)->getAttribute("handler_type");
            if ($hdlr == "vide") {
                $framerate_p2 = $this->getFrameRate($xml2);


                $remainder = ($framerate_p1 > $framerate_p2 ?
                  ($framerate_p1 % $framerate_p2) :
                  ($framerate_p2 % $framerate_p1));
                if ($remainder != 0) {
                    $errorMsg = "###Warning: WAVE Content Spec 2018Ed-Section 7.2.2: 'Frame rate Should " .
                    "be the same family of multiples between Sequential Sw Sets at the Splice point', " .
                    "violated for Sw set " . $adapt . " between CMAF Presentations " . $i . " and  " .
                    ($i + 1) . " with framerates of " . $framerate_p1 . " and " . $framerate_p2 .
                    " respectively.\n";
                }
            }
        }
    }
}

return $errorMsg;
