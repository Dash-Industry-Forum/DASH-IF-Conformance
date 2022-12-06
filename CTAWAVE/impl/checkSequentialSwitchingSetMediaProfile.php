<?php

global $MediaProfDatabase, $logger;

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
      $logger->test(
          "WAVE Content Spec 2018Ed",
          "Section 7.2.2",
          "Sequential Switching Sets SHALL conform to the same CMAF Media Profile",
          $periodAdaptationSet[$i][$j] == $periodAdaptationSet[$i + 1][$j],
          "FAIL",
          "Corresponding for switching set $j between presentations $i and " . ($i + 1),
          "Violated for switching set $j between presentations $i and " . ($i + 1) . "with profiles " .
          $periodAdaptationSet[$i][$j] ."' and '" . $periodAdaptationSet[$i + 1][$j] . "' respectively"
      );
      $logger->test(
          "WAVE Content Spec 2018Ed",
          "Section 7.2.2",
          "Encoding parameters Shall be constrained such that CMAF Fragments of following Switching Set ".
          "can be decoded by a decoder configured for previous Switching Set without reinitialization",
          $periodAdaptationSet[$i][$j] == $periodAdaptationSet[$i + 1][$j],
          "FAIL",
          "Corresponding for switching set $j between presentations $i and " . ($i + 1),
          "Violated for switching set $j between presentations $i and " . ($i + 1) . "with profiles " .
          $periodAdaptationSet[$i][$j] ."' and '" . $periodAdaptationSet[$i + 1][$j] . "' respectively"
      );
    }
}
