<?php

global $session, $MediaProfDatabase;

$errorMsg = "";
$periodCount = sizeof($MediaProfDatabase);
$adaptationCount = sizeof($MediaProfDatabase[0]);

$encryptionSchemePeriod = array();
for ($i = 0; $i < ($periodCount - 1); $i++) {
    $encryptionSchemeAdaptation = array();
    for ($adaptation = 0; $adaptation < $adaptationCount; $adaptation++) {
        $dir1 = $session->getRepresentationDir($i, $adapt, 0);
        $xml1 = get_DOM($dir1 . '/atomInfo.xml', 'atomlist');
        if ($xml1) {
            $encryptionScheme1 = getEncrytionScheme($xml1);
            if ($encryptionScheme1 !== 0) {
                array_push($encryptionSchemeAdaptation, $encryptionScheme1);
            }
        }
        $dir2 = $session->getRepresentationDir($i + 1, $adapt, 0);
        $xml2 = get_DOM($dir2 . '/atomInfo.xml', 'atomlist');
        if ($xml2) {
            $encryptionScheme2 = getEncrytionScheme($xml2);
        }
        if ($encryptionScheme1 != $encryptionScheme2 && ($encryptionScheme1 === 0 || $encryptionScheme2 === 0)) {
            $errorMsg = "Information: WAVE Content Spec 2018Ed-Section 7.2.2: Sequential Switching Sets can change " .
              "between unencrypted/encrypted at Splice points, it is observed for Sw set " . $adaptation . " between " .
              "CMAF Presentations " . $i . " and  " . ($i + 1) . " with enc scheme " . $encryptionScheme1 . " and " .
              $encryptionScheme2 . " respectively.\n";
        }
    }
    if (count($encryptionSchemeAdaptation) == 0) {
        array_push($encryptionSchemePeriod, 0);
    } else {
        array_push($encryptionSchemePeriod, array_unique($encryptionSchemeAdaptation)[0]);
    }
}

for ($i = 0; $i < ($periodCount - 1); $i++) {
    if (
        $encryptionSchemePeriod[$i] !== $encryptionSchemePeriod[$i + 1] &&
        $encryptionSchemePeriod[$i] !== 0 && $encryptionSchemePeriod[$i + 1] !== 0
    ) {
        $errorMsg .= "###CTA WAVE check violated: WAVE Content Spec 2018Ed-Section 7.2.2: 'WAVE content SHALL " .
        "contain one CENC Scheme per program', violated between CMAF Presentations " . $i . " and  " . ($i + 1) .
        " contains " . $encryptionSchemePeriod[$i] . " and " . $encryptionSchemePeriod[$i + 1] . " respectively.\n";
    }
}

return $errorMsg;
