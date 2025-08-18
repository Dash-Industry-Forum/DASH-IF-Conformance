<?php

global $mpdHandler, $logger;

$metrics = $mpdHandler->getDom()->getElementsByTagName('Metrics');
foreach ($metrics as $metric) {
    $reportings = $metric->getElementsByTagName('Reporting');
    $reporting_idx = 0;
    foreach ($reportings as $reporting) {
        if (
            $reporting->getAttribute('schemeIdUri') != 'urn:dvb:dash:reporting:2014' ||
            $reporting->getAttribute('value') != 1
        ) {
            $reporting_idx++;
            continue;
        }

        ///\Correctness Check if this is the right validation (e.g. is empty valid)
        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "DVB: Section 10.12.3",
            "value of the @probability attribute in the Reporting descriptor needs" .
            "to be a positive integer between 0 and 1000.",
            $this->checkValidProbability($reporting->getAttribute('probability')),
            "FAIL",
            "probability is either not given, or a valid integer for reporting schema $reporting_idx",
            "probability is given, but not a valid integer for reporting schema $reporting_idx",
        );

        ///\Correctness Check if this is the right validation (e.g. is empty valid)
        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "DVB: Section 10.12.3",
            "value of the @probability attribute in the Reporting descriptor needs" .
            "to be a positive integer between 0 and 1000.",
            $this->checkValidProbability($reporting->getAttribute('dvb:probability')),
            "FAIL",
            "dvb:probability is either not given, or a valid integer for reporting schema $reporting_idx",
            "dvb:probability is given, but not a valid integer for reporting schema $reporting_idx",
        );

        $reporting_idx++;
    }
}
