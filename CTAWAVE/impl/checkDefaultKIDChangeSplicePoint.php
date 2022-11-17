<?php

global $session, $MediaProfDatabase;

$errorMsg = "";
$periodCount = sizeof($MediaProfDatabase);
$adaptationCount = sizeof($MediaProfDatabase[0]);
$defaultKID1 = 0;
$defaultKID2 = 0;
$errorMsg = "";
for ($i = 0; $i < ($periodCount - 1); $i++) {
    for ($adapt = 0; $adapt < $adaptationCount; $adapt++) {
        $dir1 = $session->getRepresentationDir($i, $adapt, 0);
        $xml1 = DASHIF\Utility\parseDOM($dir1 . '/atomInfo.xml', 'atomlist');
        if ($xml1) {
            $tenc = $xml1->getElementsByTagName("tenc");
            if ($tenc->length > 0) {
                $defaultKID1 = $tenc->item(0)->getAttribute("default_KID");
            }
        }
        $dir2 = $session->getRepresentationDir($i + 1, $adapt, 0);
        $xml2 = DASHIF\Utility\parseDOM($dir2 . '/atomInfo.xml', 'atomlist');
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
