<?php

namespace App\Modules\HbbTV;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\ModuleLogger;
use App\Services\MPDCache;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Interfaces\Module;
use App\Services\Manifest\Representation;
use App\Services\Segment;

//Module checks

class Segments extends Module
{
    private SubReporter $legacyreporter;

    private TestCase $dependentDurationCase;
    private TestCase $dependentVideoInitCase;

    public function __construct()
    {
        parent::__construct();
        $this->name = "HbbTV Segments Module";

        $reporter = app(ModuleReporter::class);
        $this->legacyreporter = $reporter->context(new ReporterContext(
            "Segments",
            "LEGACY",
            "HbbTV",
            []
        ));

        $this->dependentDurationCase = $this->legacyreporter->dependencyAdd(
            section: "Unknown",
            test: "Inherit DVB legacy checks",
            dependentModule: "DVB Segments Module",
            dependentSpec: "DVB - v1.4.1",
            dependentSection: "4.5"
        );
        $this->dependentVideoInitCase = $this->legacyreporter->dependencyAdd(
            section: "Unknown",
            test: "Inherit DVB legacy checks",
            dependentModule: "DVB Segments Module",
            dependentSpec: "DVB - v1.4.1",
            dependentSection: "5.1.2"
        );
    }

    public function validateMPD(): void
    {
        parent::validateMPD();
    }

    /**
     * @param array<Segment> $segments
     **/
    public function validateSegments(Representation $representation, array $segments): void
    {

        $this->dependentDurationCase->pathAdd(
            path: $representation->path(),
            result: true,
            severity: "DEPENDENCY",
            pass_message: "Timing needs to adhere to DVB",
            fail_message: ""
        );
        $this->dependentVideoInitCase->pathAdd(
            path: $representation->path(),
            result: true,
            severity: "DEPENDENCY",
            pass_message: "Segment initialization needs to adhere to DVB",
            fail_message: ""
        );
        foreach ($segments as $segmentIndex => $segment) {
            if ($segmentIndex == 0) {
                $this->validateInitialization($representation, $segment);
            }
            $this->validateSegment($representation, $segment, $segmentIndex);
        }
    }

    private function validateInitialization(Representation $representation, Segment $segment): void
    {
    }

    private function validateSegment(Representation $representation, Segment $segment, int $segmentIndex): void
    {
    }
}
