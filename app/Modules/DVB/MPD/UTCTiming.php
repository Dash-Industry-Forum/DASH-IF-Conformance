<?php

namespace App\Modules\DVB\MPD;

use App\Services\MPDCache;
use App\Services\Manifest\Period;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Reporter\TestCase;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class UTCTiming
{
    //Private subreporters
    private SubReporter $v141reporter;

    private TestCase $availabilityStartTimeCase;

    /**
     * @var array<string> $acceptedURIs
     **/
    private array $acceptedURIs;

    public function __construct()
    {
        $this->acceptedURIs = ['urn:mpeg:dash:utc:ntp:2014',
                          'urn:mpeg:dash:utc:http-head:2014',
                          'urn:mpeg:dash:utc:http-xsdate:2014',
                          'urn:mpeg:dash:utc:http-iso:2014',
                          'urn:mpeg:dash:utc:http-ntp:2014'];

        $this->registerChecks();
    }

    private function registerChecks(): void
    {
        $reporter = app(ModuleReporter::class);
        $this->v141reporter = &$reporter->context(new ReporterContext(
            "MPD",
            "DVB",
            "v1.4.1",
            ["document" => "ETSI TS 103 285"]
        ));

        $this->availabilityStartTimeCase = $this->v141reporter->add(
            section: "Section 4.7.2",
            test: "The MPD SHOULD contain at least one UTCTiming element with the @schemeIdUri attribute set to [...]",
            skipReason: 'MPD is not dynamic, or no MPD@availabilityStartTime present'
        );
    }

    //Public validation functions
    public function validateUTCTimingElement(): void
    {
        $mpdCache = app(MPDCache::class);
        if ($mpdCache->getAttribute('type') != "dynamic" && $mpdCache->getAttribute('availabilityStartTime') == '') {
            return;
        }


        $utcTimingValid = false;
        $utcTimingElements = $mpdCache->getDOMElements('UTCTiming');

        if ($utcTimingElements) {
            foreach ($utcTimingElements as $utcTimingElement) {
                if (in_array($utcTimingElement->getAttribute('schemeIdUri'), $this->acceptedURIs)) {
                    $utcTimingValid = true;
                    break;
                }
            }
        }

        $this->availabilityStartTimeCase->add(
            result: $utcTimingValid,
            severity: "WARN",
            pass_message: "At least one valid UTCTiming element found",
            fail_message: "Allowed values: " . implode(',', $this->acceptedURIs),
        );
    }

    //Private helper functions
}
