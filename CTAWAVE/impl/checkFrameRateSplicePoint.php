<?php

global $session, $MediaProfDatabase, $logger;

$periodCount = sizeof($MediaProfDatabase);
$adaptationCount = sizeof($MediaProfDatabase[0]);
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

                
                $logger->test(
                    "WAVE Content Spec 2018Ed",
                    "Section 7.2.2",
                    "Frame rate Should be the same between Sequential Sw Sets at the Splice point",
                    $remainder == 0,
                    "WARN",
                    "Correct for Sw set $adapt between presentations $i and " . ($i+1),
                    "Invalid for Sw set $adapt between presentations $i and " . ($i+1),
                );
            }
        }
    }
}

