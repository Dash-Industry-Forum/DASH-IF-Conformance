<?php

global $mpd_dom, $logger;

$metrics = $mpd_dom->getElementsByTagName('Metrics');
foreach ($metrics as $metric) {
    $reportings = $metric->getElementsByTagName('Reporting');
    $reporting_idx = 0;
    ///\todo Fix this function
    foreach ($reportings as $reporting) {
        if (
            $reporting->getAttribute('schemeIdUri') != 'urn:dvb:dash:reporting:2014' ||
            $reporting->getAttribute('value') != 1
        ) {
            $reporting_idx++;
            continue;
        }
        $hasReportingUrl = ($reporting->getAttribute('reportingUrl') != '' ||
                            $reporting->getAttribute('dvb:reportingUrl') != '');


        if (
            $reporting->getAttribute('reportingUrl') == '' &&
            $reporting->getAttribute('dvb:reportingUrl') == ''
        ) {
            fwrite($mpdreport, "Information on DVB conformance: Section 10.12.3 - " .
            "Where DVB Metric reporting mechanism is indicated in a Reporting descriptor, " .
            "it SHALL have the @reportingUrl attribute.\n");
        } else {
            if (
                !isAbsoluteURL($reporting->getAttribute('reportingUrl')) &&
                !isAbsoluteURL($reporting->getAttribute('dvb:reportingUrl'))
            ) {
                fwrite($mpdreport, "Information on DVB conformance: Section 10.12.3 - " .
                "value of the @reportingUrl attribute in the Reporting descriptor " .
                "needs to be and absolute HTTP or HTTPS URL.\n");
            }
        }

        if ($reporting->getAttribute('probability') != '') {
            $probability = $reporting->getAttribute('probability');
            if (
                !(((string) (int) $probability === $probability) &&
                ($probability <= 1000) &&
                ($probability >= 1))
            ) {
                fwrite($mpdreport, "Information on DVB conformance: Section 10.12.3 -" .
                "value of the @probability attribute in the Reporting descriptor needs" .
                "to be a positive integer between 0 and 1000.\n");
            }
        }
        if ($reporting->getAttribute('dvb:probability') != '') {
            $probability = $reporting->getAttribute('dvb:probability');
            if (
                !(((string) (int) $probability === $probability) && i(
                    $probability <= 1000
                ) &&
                ($probability >= 1))
            ) {
                fwrite($mpdreport, "Information on DVB conformance: Section 10.12.3 - " .
                "value of the @probability attribute in the Reporting descriptor needs " .
                "to be a positive integer between 0 and 1000.\n");
            }
        }
        $reporting_idx++;
    }
}
