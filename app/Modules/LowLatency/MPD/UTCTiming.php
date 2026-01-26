<?php

namespace App\Modules\LowLatency\MPD;

use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes\DescriptionType;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class UTCTiming
{
    //Private subreporters
    private SubReporter $legacyReporter;

    private TestCase $presentCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->legacyReporter = &$reporter->context(new ReporterContext(
            "MPD",
            "LEGACY",
            "Low Latency",
            []
        ));

        $this->presentCase = $this->legacyReporter->add(
            section: '9.X.4.2',
            test: 'At least one UTC timing description element SHALL be present with a correct @schemIdUri',
            skipReason: "",
        );
    }

    //Public validation functions
    public function validateUTCTiming(): void
    {
        $mpdCache = app(MPDCache::class);

        $utcTimings = $mpdCache->getDOMElements('UTCTiming');

        $atLeastOneValid = false;
        foreach ($utcTimings as $utcTiming) {
            $scheme = $utcTiming->getAttribute('schemeIdUri');
            if (
                $scheme == 'urn:mpeg:dash:utc:http-xsdate:2014' ||
                $scheme == 'urn:mpeg:dash:utc:http-iso:2014' ||
                $scheme == 'urn:mpeg:dash:utc:http-ntp:2014'
            ) {
                $atLeastOneValid = true;
            }
        }
        $this->presentCase->add(
            result: $atLeastOneValid,
            severity: "FAIL",
            pass_message: "At least one valid UTCTiming element found",
            fail_message: "No valid UTCTiming elements found"
        );
    }

    //Private helper functions
}
