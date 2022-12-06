<?php

global $session, $MediaProfDatabase, $logger;

$periodCount = sizeof($MediaProfDatabase);
$adaptationCount = sizeof($MediaProfDatabase[0]);
for ($i = 0; $i < ($periodCount - 1); $i++) {
    for ($adaptation = 0; $adaptation < $adaptationCount; $adaptation++) {
        $dir1 = $session->getRepresentationDir($i, $adapt, 0);
        $xml1 = DASHIF\Utility\parseDOM($dir1 . '/atomInfo.xml', 'atomlist');
        if ($xml1) {
            $sdType1 = $this->getSdType($xml1);
        }
        $dir2 = $session->getRepresentationDir($i + 1, $adapt, 0);
        $xml2 = DASHIF\Utility\parseDOM($dir2 . '/atomInfo.xml', 'atomlist');
        if ($xml2) {
            $sdType2 = $this->getSdType($xml2);
        }
                $logger->test(
                    "WAVE Content Spec 2018Ed",
                    "Section 7.2.2",
                    "Sample entries in Sequential Switching Sets Shall not change sample type at Splice points",
                    $sdType1 == $sdType2,
                    "FAIL",
                    "Correct for Sw set $adapt between presentations $i and " . ($i+1),
                    "Invalid for Sw set $adapt between presentations $i and " . ($i+1),
                );
    }
}

