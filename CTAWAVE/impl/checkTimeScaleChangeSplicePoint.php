<?php

global $session, $MediaProfDatabase, $message;

$periodCount = sizeof($MediaProfDatabase);
$adaptationCount = sizeof($MediaProfDatabase[0]);
for ($i = 0; $i < ($periodCount - 1); $i++) {
    for ($adapt = 0; $adapt < $adaptationCount; $adapt++) {
        $dir1 = $session->getRepresentationDir($i, $adapt, 0);
        $xml1 = DASHIF\Utility\parseDOM($dir1 . '/atomInfo.xml', 'atomlist');

        $dir2 = $session->getRepresentationDir($i + 1, $adapt, 0);
        $xml2 = DASHIF\Utility\parseDOM($dir2 . '/atomInfo.xml', 'atomlist');

        if ($xml1) {
            $mvhd = $xml1->getElementsByTagName("mvhd");
            if ($mvhd->length > 0) {
                $timeScale1 = $mvhd->item(0)->getAttribute("timeScale");
            }
        }

        if ($xml2) {
            $mvhd = $xml2->getElementsByTagName("mvhd");
            if ($mvhd->length > 0) {
                $timeScale2 = $mvhd->item(0)->getAttribute("timeScale");
            }
        }
        if ($timeScale1 != $timeScale2) {
            $logger->messasges("Information: WAVE Content Spec 2018Ed-Section 7.2.2: 'Timescale can change " .
            "at Splice points', change is observed for Sw set " . $adapt . " between CMAF Presentations " .
            $i . " and  " . ($i + 1) . " with timescale " . $timeScale1 . " and " . $timeScale1 . " respectively");
        }
    }
}

