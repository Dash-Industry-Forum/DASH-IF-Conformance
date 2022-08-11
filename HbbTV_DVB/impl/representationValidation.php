<?php

global $hbbtv_conformance, $dvb_conformance, $session_dir, $mpd_dom,
        $current_period, $current_adaptation_set, $current_representation,
        $period_timing_info, $adaptation_set_template, $reprsentation_template,$subtitle_segments_location,
        $reprsentation_error_log_template, $string_info, $progress_report, $progress_xml;

global $logger;

$representationErrorFile = str_replace(
    array('$AS$', '$R$'),
    array($current_adaptation_set, $current_representation),
    $reprsentation_error_log_template
);
$errorFilePath = $session_dir . "/Period" . $current_period . "/" . $representationErrorFile . "txt";

## Representation checks
$adaptationDirectory = str_replace('$AS$', $current_adaptation_set, $adaptation_set_template);
$representationDirectory = str_replace(
    array('$AS$', '$R$'),
    array($current_adaptation_set, $current_representation),
    $reprsentation_template
);
$xmlRepresentation = get_DOM($session_dir . '/Period' . $current_period . '/' . $adaptationDirectory . '/' .
  $representationDirectory . '.xml', 'atomlist');
if ($xmlRepresentation) {
    if ($dvb_conformance) {
        $media_types = media_types($mpd_dom->getElementsByTagName('Period')->item($current_period));
        $this->commonValidationDVB($xmlRepresentation, $media_types);
    }
    if ($hbbtv_conformance) {
        $this->commonValidationHbbTV($xmlRepresentation);
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
