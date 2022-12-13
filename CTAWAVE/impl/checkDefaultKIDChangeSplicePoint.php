<?php

global $session, $MediaProfDatabase;

global $validatorAdapters;

$errorMsg = "";
$periodCount = sizeof($MediaProfDatabase);
$adaptationCount = sizeof($MediaProfDatabase[0]);
$defaultKID1 = 0;
$defaultKID2 = 0;
$errorMsg = "";
for ($i = 0; $i < ($periodCount - 1); $i++) {
    for ($adapt = 0; $adapt < $adaptationCount; $adapt++) {
        $dir1 = $session->getRepresentationDir($i, $adapt, 0);

        foreach($validatorAdapters as &$adapter){
          $adapter->loadNew($dir1);
          if ($adapter->isLoaded()){
            $defaultKID1 = $adapter->getNamedBoxProperty("tenc", "default_KID", 0);
            break;
          }
        }

        $dir2 = $session->getRepresentationDir($i + 1, $adapt, 0);
        foreach($validatorAdapters as &$adapter){
          $adapter->loadNew($dir2);
          if ($adapter->isLoaded()){
            $defaultKID2 = $adapter->getNamedBoxProperty("tenc", "default_KID", 0);
            break;
          }
        }

        if ($defaultKID1 != $defaultKID2) {
            $errorMsg = "Information: WAVE Content Spec 2018Ed-Section 7.2.2: 'Default KID can change at Splice " .
            "points', change is observed for Sw set " . $adapt . " between CMAF Presentations " . $i . " and  " .
            ($i + 1) . " with values -" . $defaultKID1 . " and " . $defaultKID2 . " respectively.\n";
        }
    }
}
return $errorMsg;
