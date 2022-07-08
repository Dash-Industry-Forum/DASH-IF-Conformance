<?php

global $session_dir, $MediaProfDatabase, $adaptation_set_template, $reprsentation_template;

$errorMsg = "";
$periodCount = sizeof($MediaProfDatabase);
$adaptationCount = sizeof($MediaProfDatabase[0]);
for ($i = 0; $i < ($periodCount - 1); $i++) {
    for ($adapt = 0; $adapt < $adaptationCount; $adapt++) {
        $adaptationDirectory = str_replace('$AS$', $adapt, $adaptation_set_template);
        $representationDirectory = str_replace(array('$AS$', '$R$'), array($adapt, 0), $reprsentation_template);
        $xml1 = get_DOM($session_dir . '/Period' . $i . '/' . $adaptationDirectory . '/' .
          $representationDirectory . '.xml', 'atomlist');
        if ($xml1) {
            $tkhd = $xml1->getElementsByTagName("tkhd");
            if ($tkhd->length > 0) {
                $trackID1 = $tkhd->item(0)->getAttribute("trackID");
            }
        }
        $xml2 = get_DOM($session_dir . '/Period' . ($i + 1) . '/' . $adaptationDirectory . '/' .
          $representationDirectory . '.xml', 'atomlist');
        if ($xml2) {
            $tkhd = $xml2->getElementsByTagName("tkhd");
            if ($tkhd->length > 0) {
                $trackID2 = $tkhd->item(0)->getAttribute("trackID");
            }
        }
        if ($trackID1 != $trackID2) {
            $errorMsg = "Information: WAVE Content Spec 2018Ed-Section 7.2.2: 'Track_ID can change at Splice " .
            "points', change is observed for Sw set " . $adapt . " between CMAF Presentations " . $i . " and  " .
            ($i + 1) . " with TrackID -" . $trackID1 . " and " . $trackID2 . " respectively.\n";
        }
    }
}
///\todo Make this work through a separate logger instance
return $errorMsg;
