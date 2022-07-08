<?php

global $MediaProfDatabase, $session_dir,$adaptation_set_template,$CTAspliceConstraitsLog,$reprsentation_template;

$error = $this->checkSequentialSwitchingSetAV();

//Call the CMFHD Baseline constraints.
$error = $this->checkCMFHDBaselineConstraints();

///\todo Fix this
/*
//Using the error messages, check other MAY/Need not conditions and print respective informations.
if (strpos($error, "###CTAWAVE check violated") !== false) {
  fwrite($opfile, "Information:WAVE Content Spec 2018Ed-Section 6.1: 'WAVE Programs that contain more than one
    CMAF Presentation MAY conform to constraints of a WAVE Splice Constraints Profile (section 6.2)', however
    non-conformance to CMFHD Baseline observed in this WAVE Program. \n ");
} else {
  fwrite($opfile, "Information:WAVE Content Spec 2018Ed-Section 6.1/6.2: 'WAVE Programs that contain more than one
    CMAF Presentation MAY conform to constraints of a WAVE Splice Constraints Profile (section 6.2)', however
    conformance to CMFHD Baseline observed in this WAVE Program. \n ");
}

if (strpos($error, "violation observed in WAVE Baseline Splice") !== false) {
  fwrite($opfile, "Information:WAVE Content Spec 2018Ed-Section 6.1: 'CMAF Presentation in a WAVE Program need
    not conform to any Splice Constraint Profile', however non-conformance to WAVE Baseline Splice constraints
    found. \n ");
} elseif (strpos($error, "violated as not all CMAF presentations conforms to CMFHD") !== false) {
  fwrite($opfile, "Information:WAVE Content Spec 2018Ed-Section 6.1: 'CMAF Presentation in a WAVE Program need
    not conform to any Splice Constraint Profile', however non-conformance to CMFHD Baseline constraints found.
    \n ");
}
 */
