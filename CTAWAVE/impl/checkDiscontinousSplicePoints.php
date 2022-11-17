<?php

global $session, $MediaProfDatabase, $adaptation_set_template, $reprsentation_template;

$errorMsg = "";
$periodCount = sizeof($MediaProfDatabase);
$adaptationCount = sizeof($MediaProfDatabase[0]);
for ($i = 0; $i < ($periodCount - 1); $i++) {
    for ($adaptation = 0; $adaptation < $adaptationCount; $adaptation++) {
        $representationDirectory = $session->getRepresentationDir($i, $adaptation, 0);
        $xml1 = DASHIF\Utility\parseDOM($session->getRepresentationDir($i, $adaptation, 0) + "/atomInfo.xml", 'atomlist');

        if ($xml1) {
            $moofCount1 = $xml1->getElementsByTagName("moof")->length;
            if ($moofCount1 > 0) {
                $tfdt = $xml1->getElementsByTagName("tfdt")->item($moofCount1 - 1);
                $baseMediaDecodeTime1 = $tfdt->getAttribute("baseMediaDecodeTime");
                $trun = $xml1->getElementsByTagName("trun")->item($moofCount1 - 1);
                $cumulativeDuration1 = $trun->getAttribute("cummulatedSampleDuration");
                $mdhd = $xml1->getElementsByTagName("mdhd")->item(0);
                $timeScale1 = $mdhd->getAttribute("timescale");
            }
        }
        $xml2 = DASHIF\Utility\parseDOM($session->getRepresentationDir($i + 1, $adaptation, 0) + "/atomInfo.xml", 'atomlist');
        if ($xml2) {
            $moofCount2 = $xml2->getElementsByTagName("moof")->length;
            if ($moofCount2 > 0) {
                $tfdt = $xml2->getElementsByTagName("tfdt")->item(0);
                $baseMediaDecodeTime_p2 = $tfdt->getAttribute("baseMediaDecodeTime");
                $mdhd = $xml2->getElementsByTagName("mdhd")->item(0);
                $timeScale2 = $mdhd->getAttribute("timescale");
            }
            if (
                ($baseMediaDecodeTime_p2 / $timeScale2) !=
                (($baseMediaDecodeTime1 + $cumulativeDuration1) / $timeScale1)
            ) {
                $errorMsg = "Information: WAVE Content Spec 2018Ed-Section 7.2.2: Sequential Switching Sets can " .
                "be discontinuous, and it is observed for Sw set " . $adaptation . " between CMAF Presentations " .
                $i . " and  " . ($i + 1) . " with baseMediaDecodeTime- " .
                (($baseMediaDecodeTime1 + $cumulativeDuration1) / $timeScale1) . " and " .
                ($baseMediaDecodeTime_p2 / $timeScale2) . " respectively.\n";
            }
        }
    }
}
return $errorMsg;
