<?php

global $session, $MediaProfDatabase;

$errorMsg = "";
$periodCount = sizeof($MediaProfDatabase);
$adaptationCount = sizeof($MediaProfDatabase[0]);
for ($i = 0; $i < ($periodCount - 1); $i++) {
    for ($adaptation = 0; $adaptation < $adaptationCount; $adaptation++) {
        $dir1 = $session->getRepresentationDir($i, $adapt, 0);
        $xml1 = get_DOM($dir1 . '/atomInfo.xml', 'atomlist');
        if ($xml1) {
            $sdType1 = $this->getSdType($xml1);
        }
        $dir2 = $session->getRepresentationDir($i + 1, $adapt, 0);
        $xml2 = get_DOM($dir2 . '/atomInfo.xml', 'atomlist');
        if ($xml2) {
            $sdType2 = $this->getSdType($xml2);
        }
        if ($sdType1 != $sdType2) {
            $errorMsg = "###CTA WAVE check violated: WAVE Content Spec 2018Ed-Section 7.2.2: 'Sample entries in " .
            "Sequential Switching Sets Shall not change sample type at Splice points', but different sample types " .
            $sdType1 . " and " . $sdType2 . "observed for Sw set " . $adaptation . " between CMAF Presentations " .
             $i . " and  " . ($i + 1) . ".\n";
        }
    }
}

return $errorMsg;
