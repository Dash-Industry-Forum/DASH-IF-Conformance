<?php

global $session, $MediaProfDatabase, $logger;

$periodCount = sizeof($MediaProfDatabase);
$adaptationCount = sizeof($MediaProfDatabase[0]);
for ($i = 0; $i < ($periodCount - 1); $i++) {
    for ($adapt = 0; $adapt < $adaptationCount; $adapt++) {
        $dir1 = $session->getRepresentationDir($i, $adapt, 0);
        $xml1 = DASHIF\Utility\parseDOM($dir1 . '/atomInfo.xml', 'atomlist');

        $dir2 = $session->getRepresentationDir($i + 1, $adapt, 0);
        $xml2 = DASHIF\Utility\parseDOM($dir2 . '/atomInfo.xml', 'atomlist');

        if ($xml1) {
            $tkhd = $xml1->getElementsByTagName("tkhd");
            if ($tkhd->length > 0) {
                $trackID1 = $tkhd->item(0)->getAttribute("trackID");
            }
        }

        if ($xml2) {
            $tkhd = $xml2->getElementsByTagName("tkhd");
            if ($tkhd->length > 0) {
                $trackID2 = $tkhd->item(0)->getAttribute("trackID");
            }
        }
        if ($trackID1 != $trackID2) {
            $logger->message(
                "WAVE Content Spec 2018Ed-Section 7.2.2: 'Track_ID can change at Splice " .
                "points', change is observed for Sw set " . $adapt . " between CMAF Presentations " . $i . " and  " .
                ($i + 1) . " with TrackID -" . $trackID1 . " and " . $trackID2 . " respectively."
            );
        }
    }
}
