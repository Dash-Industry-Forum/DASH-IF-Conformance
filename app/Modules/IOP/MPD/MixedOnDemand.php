<?php

namespace App\Modules\IOP\MPD;

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

class MixedOnDemand
{
    //Private subreporters
    private SubReporter $legacyReporter;

    private TestCase $profilesPresentCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->legacyReporter = &$reporter->context(new ReporterContext(
            "MPD",
            "DASH-IF IOP",
            "4.3",
            []
        ));

        $this->profilesPresentCase = $this->legacyReporter->add(
            section: '3.10.4',
            test: "@profiles signalling SHALL be present in each Period",
            skipReason: "Corresponding profile(s) not signalled",
        );
    }

    //Public validation functions
    public function validateMixed(): void
    {
        $mpdCache = app(MPDCache::class);

        if (!$mpdCache->hasProfile('http://dashif.org/guidelines/dash-if-mixed')) {
            return;
        }

        foreach ($mpdCache->allPeriods() as $period) {
            $this->profilesPresentCase->pathAdd(
                path: $period->path(),
                result: $period->getAttribute('profiles') != '',
                severity: "FAIL",
                pass_message: "Profiles present",
                fail_message: "Profiles not present",
            );
        }
    }

    //Private helper functions
}
