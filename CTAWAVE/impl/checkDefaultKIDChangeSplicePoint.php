<?php

global $session_dir, $MediaProfDatabase, $adaptation_set_template, $reprsentation_template;

$errorMsg = "";
$periodCount = sizeof($MediaProfDatabase);
$adaptationCount = sizeof($MediaProfDatabase[0]);
$defaultKID1 = 0;
$defaultKID2 = 0;
$errorMsg = "";
for ($i = 0; $i < ($periodCount - 1); $i++) {
    for ($adapt = 0; $adapt < $adaptationCount; $adapt++) {
        $adaptationDirectory = str_replace('$AS$', $adapt, $adaptation_set_template);
        $representationDirectory = str_replace(array('$AS$', '$R$'), array($adapt, 0), $reprsentation_template);
        $xml1 = get_DOM($session_dir . '/Period' . $i . '/' . $adaptationDirectory . '/' .
          $representationDirectory . '.xml', 'atomlist');
        if ($xml1) {
            $tenc = $xml1->getElementsByTagName("tenc");
            if ($tenc->length > 0) {
                $defaultKID1 = $tenc->item(0)->getAttribute("default_KID");
            }
        }
        $xml2 = get_DOM($session_dir . '/Period' . ($i + 1) . '/' . $adaptationDirectory . '/' .
          $representationDirectory . '.xml', 'atomlist');
        if ($xml2) {
            $tenc = $xml2->getElementsByTagName("tenc");
            if ($tenc->length > 0) {
                $defaultKID2 = $tenc->item(0)->getAttribute("default_KID");
            }
        }
        if ($defaultKID1 != $defaultKID2) {
            $errorMsg = "Information: WAVE Content Spec 2018Ed-Section 7.2.2: 'Default KID can change at Splice " .
            "points', change is observed for Sw set " . $adapt . " between CMAF Presentations " . $i . " and  " .
            ($i + 1) . " with values -" . $defaultKID1 . " and " . $defaultKID2 . " respectively.\n";
        }
    }
}
return $errorMsg;
