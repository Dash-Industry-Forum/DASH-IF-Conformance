<?php

global $mpdHandler,
        $current_adaptation_set, $current_representation,
        $period_timing_info, $logger, $session;


$repDir = $session->getRepresentationDir($current_representation, $current_adaptation_set, $current_representation);
$errorFilePath = "$repDir/stderr.txt";

///\RefactorTodo Wrong directory, again..?
$xmlRepresentation = DASHIF\Utility\parseDOM("$repDir/atomInfo.xml", 'atomlist');
if ($xmlRepresentation) {
    if ($this->DVBEnabled) {
        $mediaTypes = DASHIF\Utility\mediaTypes($mpdHandler->getDom()->getElementsByTagName('Period')->item($mpdHandler->getSelectedPeriod()));
        $this->commonDVBValidation($xmlRepresentation, $mediaTypes);
    }
    if ($this->HbbTvEnabled){
        $this->commonHbbTVValidation($xmlRepresentation);
    }

    $this->segmentTimingCommon($xmlRepresentation);
    $this->bitrateReport($xmlRepresentation);
    $segmentDurationName = $this->segmentDurationChecks();
    if ($period_timing_info[1] !== '' && $period_timing_info[1] !== 0) {
        $checks = $this->segmentToPeriodDurationCheck($xmlRepresentation);
        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "Common section 'periods'",
            "The accumulated duration of the segments MUST match the period duration",
            $checks[0],
            "FAIL",
            "Durations match",
            "Durations " . $checks[1] . " and " . $checks[2] . "do not match"
        );
    }
}


$this->addOrRemoveImages('REMOVE');
$hbbtv_string_info = "<img id=\"segmentReport\" src=\"$segmentDurationName\" width=\"650\" height=\"350\">" .
                     "<img id=\"bitrateReport\" src=\"$bitrate_report_name\" width=\"650\" height=\"350\"/>\n";
$this->addOrRemoveImages('ADD', $hbbtv_string_info);
