<?php

global $logger;

$logger->setModule("Schematron");
$logger->setHook("MPD");

$logger->message("MPDValidator output: " . $this->mpdValidatorOutput);
$logger->message("Schematron output: " . $this->schematronOutput);

$logger->test(
    "MPEG-DASH",
    "Commmon",
    "Schematron Validation",
    strpos($this->schematronOutput, 'XLink resolving successful') !== false,
    "FAIL",
    "XLink resolving succesful",
    "XLink resolving failed"
);

$logger->test(
    "MPEG-DASH",
    "Commmon",
    "Schematron Validation",
    strpos($this->schematronOutput, 'MPD validation successful') !== false,
    "FAIL",
    "MPD validation succesful",
    "MPD validation failed"
);

$logger->test(
    "MPEG-DASH",
    "Commmon",
    "Schematron Validation",
    strpos($this->schematronOutput, 'Schematron validation successful') !== false,
    "FAIL",
    "Schematron validation succesful",
    "Schematron validation failed"
);

if ($this->schematronOutput != '') {
    if (strpos($this->schematronOutput, 'Schematron validation successful') === false) {
        $this->schematronIssuesReport = analyzeSchematronIssues($this->mpdValidatorOutput);
    }
}
//createMpdFeatureList($this->dom, $schematronIssuesReport);
//convertToHtml();
