<?php

global $MediaProfDatabase, $session_dir,$adaptation_set_template,$CTAspliceConstraitsLog;

//Check for CMFHD presentation profile for all periods/presentations
//and then check WAVE Baseline constraints . If both are satisfied, then CMFHD Baseline Constraints are satisfied.
$errorMsg = "";
$periodCount = sizeof($MediaProfDatabase);
$presentationProfileArray = array();
for ($i = 0; $i < $periodCount; $i++) {
    $adaptationCount = sizeof($MediaProfDatabase[$i]);
    $presentationProfile = $this->CTACheckPresentation($adaptationCount, $i);
    array_push($presentationProfileArray, $presentationProfile);
}

///\todo Fix this
/*
  if (!(count(array_unique($presentationProfileArray)) === 1 &&
    array_unique($presentationProfileArray)[0] == "CMFHD")) {
    $errorMsg .= "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 6.2: 'WAVE CMFHD Baseline Program Shall
      contain a sequence of one or more CMAF Presentations conforming to CMAF CMFHD profile', violated as not all CMAF
      presentations conforms to CMFHD. \n";
}

//WAVE Baseline constraints are already checked, open the log file and check if contains errors and print related
//error message.
$searchfiles = file_get_contents($session_dir . '/' . $CTAspliceConstraitsLog . '.txt');
if (strpos($searchfiles, "###CTAWAVE check violated") !== false) {
  $errorMsg .= "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 6.2: 'WAVE CMFHD Baseline Program's
    Sequential Sw Sets Shall only contain splices conforming to WAVE Baseline Splice profile (section 7.2)', but
    violation observed in WAVE Baseline Splice constraints. \n";
}
 */


return $errorMsg;
