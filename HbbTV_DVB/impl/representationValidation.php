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
if (!($opfile = open_file($errorFilePath, 'a'))) {
    echo "Error opening/creating HbbTV/DVB codec validation file: $errorFilePath";
    return;
}

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
        //\TODO Reinstate
        //common_validation_DVB($opfile, $xmlRepresentation, $media_types);
    }
    if ($hbbtv_conformance) {
        //\TODO Reinstate
        //common_validation_HbbTV($opfile, $xmlRepresentation);
    }

    //\TODO Reinstate
    //seg_timing_common($opfile, $xmlRepresentation);
    //$bitrate_report_name = bitrate_report($xmlRepresentation);
    //$segment_duration_name = seg_duration_checks($opfile);
    if ($period_timing_info[1] !== '' && $period_timing_info[1] !== 0) {
        //\TODO Reinstate
        //$checks = segmentToPeriodDurationCheck($xmlRepresentation);
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

    ## For reporting
$search = file_get_contents($errorFilePath);
if (strpos($search, "###") === false) {
    if (strpos($search, "Warning") === false && strpos($search, "WARNING") === false) {
        $progress_xml->Results[0]->Period[$current_period]
                                 ->Adaptation[$current_adaptation_set]
                                 ->Representation[$current_representation] = "noerror";
        $file_location[] = "noerror";
    } else {
        $progress_xml->Results[0]->Period[$current_period]
                                 ->Adaptation[$current_adaptation_set]
                                 ->Representation[$current_representation] = "warning";
        $file_location[] = "warning";
    }
} else {
    $progress_xml->Results[0]->Period[$current_period]i
                             ->Adaptation[$current_adaptation_set]
                             ->Representation[$current_representation] = "error";

    $file_location[] = "error";
}
$progress_xml->asXml(trim($session_dir . '/' . $progress_report));

addOrRemoveImages('REMOVE');
$hbbtv_string_info = "<img id=\"segmentReport\" src=\"$segment_duration_name\" width=\"650\" height=\"350\">" .
                     "<img id=\"bitrateReport\" src=\"$bitrate_report_name\" width=\"650\" height=\"350\"/>\n";
addOrRemoveImages('ADD', $hbbtv_string_info);

return $file_location;
