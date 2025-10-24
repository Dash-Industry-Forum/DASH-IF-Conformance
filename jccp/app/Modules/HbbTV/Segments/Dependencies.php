<?php

namespace App\Modules\HbbTV\Segments;

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
use App\Services\SpecManager;

class Dependencies
{
    //Private subreporters
    private SubReporter $legacyReporter;
    private SubReporter $crosslegacyReporter;
    private TestCase $dependentDurationCase;
    private TestCase $dependentVideoInitCase;
    private TestCase $dependentProtectionCase;

    public function __construct()
    {
        $specManager = app(SpecManager::class);
        $specManager->activateDependency("DVB Segments Module");
        $reporter = app(ModuleReporter::class);
        $this->legacyReporter = &$reporter->context(new ReporterContext(
            "Segments",
            "Legacy",
            "HbbTV",
            []
        ));
        $this->dependentDurationCase = $this->legacyReporter->dependencyAdd(
            section: "Unknown",
            test: "Inherit DVB legacy checks",
            dependentModule: "DVB Segments Module",
            dependentSpec: "DVB - v1.4.1",
            dependentSection: "4.5"
        );
        $this->dependentVideoInitCase = $this->legacyReporter->dependencyAdd(
            section: "Unknown",
            test: "Inherit DVB legacy checks",
            dependentModule: "DVB Segments Module",
            dependentSpec: "DVB - v1.4.1",
            dependentSection: "5.1.2"
        );
        $this->crosslegacyReporter = &$reporter->context(new ReporterContext(
            "CrossValidation",
            "Legacy",
            "HbbTV",
            []
        ));
        $this->dependentProtectionCase = $this->crosslegacyReporter->dependencyAdd(
            section: "DRM",
            test: "Inherit DVB legacy checks",
            dependentModule: "DVB Segments Module",
            dependentSpec: "Legacy - DVB",
            dependentSection: "DRM"
        );
    }

    //Public validation functions
    public function validateDependencies(Representation $representation, Segment $segment): void
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
        $this->dependentProtectionCase->pathAdd(
            path: $representation->path(),
            result: true,
            severity: "DEPENDENCY",
            pass_message: "Segment protection needs to adhere to DVB",
            fail_message: ""
        );
    }

    //Private helper functions
}
