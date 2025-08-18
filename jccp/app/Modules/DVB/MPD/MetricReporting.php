<?php

namespace App\Modules\DVB\MPD;

use App\Services\MPDCache;
use App\Services\Manifest\Period;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MetricReporting
{
    //Private subreporters
    private SubReporter $v141reporter;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->v141reporter = &$reporter->context(new ReporterContext(
            "MPD",
            "DVB",
            "v1.4.1",
            ["document" => "ETSI TS 103 285"]
        ));
    }

    //Public validation functions
    public function validateMetricReporting(): void
    {
        $mpdCache = app(MPDCache::class);
        $metricElements = $mpdCache->getDOMElements('Metrics');
        if (!$metricElements) {
            return;
        }
        foreach ($metricElements as $metricElement) {
            $this->validateSingleMetric($metricElement);
        }
    }

    //Private helper functions
    private function validateSingleMetric(\DOMElement $metricElement): void
    {
        $reportingElements = $metricElement->getElementsByTagName('Reporting');

        foreach ($reportingElements as $reportingIndex => $reportingElement) {
            $this->validateReportingURL($reportingIndex, $reportingElement);
        }
    }

    private function validateReportingURL(int $reportingIndex, \DOMElement $reportingElement): void
    {
        if (
            $reportingElement->getAttribute('schemeIdUri') != 'urn:dvb:dash:reporting:2014' ||
            $reportingElement->getAttribute('value') != '1'
        ) {
            return;
        }

        $reportingUrl = $reportingElement->getAttribute('reportingUrl');

        $this->v141reporter->test(
            section: "Section 10.12.3.3",
            test: "Reporting URL is required to be an absolute HTTP(S) url",
            result: $reportingUrl != '',
            severity: "FAIL",
            pass_message: "URL Field present for reporting at index $reportingIndex",
            fail_message: "No or empty URL Field for reporting at index $reportingIndex",
        );

        if ($reportingUrl == '') {
            return;
        }


        $this->v141reporter->test(
            section: "Section 10.12.3.3",
            test: "Reporting URL is required to be an absolute HTTP(S) isAbsoluteURL",
            result: isAbsoluteURL($reportingUrl),
            severity: "FAIL",
            pass_message: "Absolute HTTP(S) URL detected for reporting at index $reportingIndex",
            fail_message: "Relative HTTP(S) URL detected for reporting at index $reportingIndex",
        );
    }
}
