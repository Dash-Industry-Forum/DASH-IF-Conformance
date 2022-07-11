<?php

global $session_dir, $MediaProfDatabase, $adaptation_set_template, $reprsentation_template;

$errorMsg = "";
$periodCount = sizeof($MediaProfDatabase);
$adaptationCount = sizeof($MediaProfDatabase[0]);
for ($i = 0; $i < ($periodCount - 1); $i++) {
    for ($adaptation = 0; $adaptation < $adaptationCount; $adaptation++) {
        $adaptationDirectory = str_replace('$AS$', $adaptation, $adaptation_set_template);
        $representationDirectory = str_replace(array('$AS$', '$R$'), array($adaptation, 0), $reprsentation_template);
        $xml1 = get_DOM($session_dir . '/Period' . $i . '/' . $adaptationDirectory . '/' .
                              $representationDirectory . '.xml', 'atomlist');
        if ($xml1) {
            $sdType1 = $this->getSdType($xml1);
        }
        $xml2 = get_DOM($session_dir . '/Period' . ($i + 1) . '/' . $adaptationDirectory . '/' .
                              $representationDirectory . '.xml', 'atomlist');
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
