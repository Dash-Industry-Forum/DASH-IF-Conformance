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

class LeapSecond
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
            test: 'LeapSecondInformation SHOULD be provided',
            skipReason: "",
        );
    }

    //Public validation functions
    public function validateLeapSecond(): void
    {
        $mpdCache = app(MPDCache::class);


        $this->presentCase->add(
            result: count($mpdCache->getDOMElements('LeapSecondInformat')) > 0,
            severity: "WARN",
            pass_message: "LeapSecondInformation available",
            fail_message: "LeapSecondInformation not available",
        );
    }

    //Private helper functions
}
