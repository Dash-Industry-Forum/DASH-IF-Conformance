<?php

global $MediaProfDatabase;

$errorMsg = "";
$periodCount = sizeof($MediaProfDatabase);
//Create an array of media profiles at Sw set level.
//If all reps doesnt have same MP, then "unknown" is assigned.
for ($i = 0; $i < $periodCount; $i++) {
    $adaptationCount = sizeof($MediaProfDatabase[$i]);
    for ($j = 0; $j < $adaptationCount; $j++) {
        $representationCount = sizeof($MediaProfDatabase[$i][$j]);
        $periodAdaptationSet[$i][$j] = $MediaProfDatabase[$i][$j][0];
        for ($k = 0; $k < ($representationCount - 1); $k++) {
            if ($MediaProfDatabase[$i][$j][$k] !== $MediaProfDatabase[$i][$j][$k + 1]) {
                $periodAdaptationSet[$i][$j] = "unknown";
                break;
            }
        }
    }
}

// Check the MP at the Sw Set level and raise conformance error .
$adaptationCount = sizeof($periodAdaptationSet[0]);
for ($i = 0; $i < ($periodCount - 1); $i++) {
    for ($j = 0; $j < $adaptationCount; $j++) {
        if ($periodAdaptationSet[$i][$j] !== $periodAdaptationSet[$i + 1][$j]) {
            $errorMsg = "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 7.2.2: Sequential Switching " .
            "Sets SHALL conform to the same CMAF Media Profile, voilated for Sw set " . $j . " between CMAF " .
            "Presentations " . $i . " and  " . ($i + 1) . " with media profiles- '" . $periodAdaptationSet[$i][$j] .
            "' and '" . $periodAdaptationSet[$i + 1][$j] . "' respectively.\n";
            $errorMsg .= "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 7.2.2: Encoding parameters " .
              "Shall be constrained such that CMAF Fragments of following Switching Set can be decoded by a decoder " .
              "configured for previous Switching Set without reinitialization, voilated for Sw set " . $j .
              " between CMAF Presentations " . $i . " and  " . ($i + 1) . " as Media Profile found are '" .
              $periodAdaptationSet[$i][$j] . "' and '" . $periodAdaptationSet[$i + 1][$j] . "' respectively.\n";
        }
    }
}
///\todo Make this work through a separate logger instance
return $errorMsg;
