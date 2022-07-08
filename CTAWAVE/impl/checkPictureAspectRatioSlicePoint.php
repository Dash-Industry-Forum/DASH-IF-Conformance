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
            $tkhd = $xml1->getElementsByTagName("tkhd")->item(0);
            $hdlr = $xml1->getElementsByTagName("hdlr")->item(0)->getAttribute("handler_type");
            if ($hdlr == "vide") {
                $par_p1 = $tkhd->getAttribute("width") / ($tkhd->getAttribute("height"));
            }
        }
        $xml2 = get_DOM($session_dir . '/Period' . ($i + 1) . '/' . $adaptationDirectory . '/' .
          $representationDirectory . '.xml', 'atomlist');
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

///\todo Make this work through a separate logger instance
return $errorMsg;
