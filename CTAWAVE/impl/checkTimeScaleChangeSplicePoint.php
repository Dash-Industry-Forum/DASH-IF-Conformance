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
            $mvhd = $xml1->getElementsByTagName("mvhd");
            if ($mvhd->length > 0) {
                $timeScale1 = $mvhd->item(0)->getAttribute("timeScale");
            }
        }
        $xml2 = get_DOM($session_dir . '/Period' . ($i + 1) . '/' . $adaptationDirectory . '/' .
          $representationDirectory . '.xml', 'atomlist');
        if ($xml2) {
            $mvhd = $xml2->getElementsByTagName("mvhd");
            if ($mvhd->length > 0) {
                $timeScale2 = $mvhd->item(0)->getAttribute("timeScale");
            }
        }
        if ($timeScale1 != $timeScale2) {
            $errorMsg = "Information: WAVE Content Spec 2018Ed-Section 7.2.2: 'Timescale can change " .
            "at Splice points', change is observed for Sw set " . $adapt . " between CMAF Presentations " .
            $i . " and  " . ($i + 1) . " with timescale " . $timeScale1 . " and " . $timeScale1 . " respectively.\n";
        }
    }
}

return $errorMsg;
