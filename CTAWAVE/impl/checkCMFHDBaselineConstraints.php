<?php

global $session, $logger;

$logger->test(
    "WAVE Content Spec 2018Ed",
    "Section 6.2",
    "WAVE CMFHD Baseline Program Shall contain a sequence of one or more CMAF Presentations conforming to CMAF " .
    "CMFHD profile",
    $this->presentationProfile == "CMFHD",
    "FAIL",
    "All CMAF Switching sets are CMFHD conformant",
    "Not all CMAF Switching sets are CMFHD conformant, found $this->presentationProfile"
);
