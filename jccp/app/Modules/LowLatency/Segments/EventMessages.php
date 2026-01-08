<?php

namespace App\Modules\LowLatency\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Services\Manifest\AdaptationSet;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes\DescriptionType;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class EventMessages
{
    //Private subreporters
    private SubReporter $legacyReporter;

    private TestCase $eventDependencyCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->legacyReporter = &$reporter->context(new ReporterContext(
            "Segments",
            "LEGACY",
            "Low Latency",
            []
        ));

        //TODO: Make this a dependency on the 'correct' spec
        $this->eventDependencyCase = $this->legacyReporter->dependencyAdd(
            section: "9.X.4.5",
            test: "All 'emsg' boxes inserted [..] after the start of the first CMAF chunk " .
                  "SHALL be repeated before the first chunk of the next segment",
            dependentModule: "Wave HLS Interop Segments Module",
            dependentSpec: "CTA-5005-A - Final",
            dependentSection: "4.5.2 - Carriage of Timed Event Data"
        );
    }

    //Public validation functions
    public function validateEventMessages(Representation $representation): void
    {
        $this->eventDependencyCase->pathAdd(
            path: $representation->path(),
            result: true,
            severity: "DEPENDENCY",
            pass_message: "Dependent check",
            fail_message: ""
        );
    }
}
