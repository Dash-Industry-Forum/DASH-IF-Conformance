<?php

global $session, $MediaProfDatabase, $logger;

$periodCount = sizeof($MediaProfDatabase);
$adaptationCount = sizeof($MediaProfDatabase[0]);

$encryptionSchemePeriod = array();

for ($i = 0; $i < ($periodCount - 1); $i++) {
    $logger->test(
        "WAVE Content Spec 2018Ed",
        "Section 7.2.2",
        "WAVE content SHALL contain one CENC Scheme per program",
        $encryptionSchemePeriod[$i] == $encryptionSchemePeriod[$i + 1] ||
        $encryptionSchemePeriod[$i] == 0 || $encryptionSchemePeriod[$i + 1] == 0,
        "FAIL",
        "One CENC Scheme found",
        "Violated between CMAF Presentations " . $i . " and  " . ($i + 1) .
        " contains " . $encryptionSchemePeriod[$i] . " and " . $encryptionSchemePeriod[$i + 1] . " respectively."
    );
}
