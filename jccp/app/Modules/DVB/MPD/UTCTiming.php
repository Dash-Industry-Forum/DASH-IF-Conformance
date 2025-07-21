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

class UTCTiming
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
    public function validateUTCTimingElement(): void
    {
        $mpdCache = app(MPDCache::class);
        if ($mpdCache->getAttribute('type') != "dynamic" && $mpdCache->getAttribute('availabilityStartTime') == '') {
            return;
        }

        $acceptedURIs = ['urn:mpeg:dash:utc:ntp:2014',
                          'urn:mpeg:dash:utc:http-head:2014',
                          'urn:mpeg:dash:utc:http-xsdate:2014',
                          'urn:mpeg:dash:utc:http-iso:2014',
                          'urn:mpeg:dash:utc:http-ntp:2014'];

        $utcTimingValid = false;
        $utcTimingElements = $mpdCache->getDOMElements('UTCTiming');

        if ($utcTimingElements) {
            foreach ($utcTimingElements as $utcTimingElement) {
                if (in_array($utcTimingElement->getAttribute('schemeIdUri'), $acceptedURIs)) {
                    $utcTimingValid = true;
                    break;
                }
            }
        }

        $this->v141reporter->test(
            section: "Section 4.7.2",
            test: "'If the MPD is dynamic or if the MPD@availabilityStartTime is present theni [..] " .
                  "the MPD SHOULD contain at least one UTCTiming element with the @schemeIdUri attribute set " .
                  "to one of the following: " . join(', ', $acceptedURIs),
            result: $utcTimingValid,
            severity: "WARN",
            pass_message: "At least one valid UTCTiming element found",
            fail_message: "None of the valid UTCTiming elements found"
        );
    }

    //Private helper functions
}
