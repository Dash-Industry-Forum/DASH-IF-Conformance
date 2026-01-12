<?php

namespace App\Modules\IOP\Segments;

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

class OnDemand
{
    //Private subreporters
    private SubReporter $legacyReporter;

    private TestCase $sidxCase;
    private TestCase $selfInitCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->legacyReporter = &$reporter->context(new ReporterContext(
            "Segments",
            "DASH-IF IOP",
            "4.3",
            []
        ));

        $this->sidxCase = $this->legacyReporter->add(
            section: '3.10.3.2',
            test: "Only a single 'sidx' box shall be present",
            skipReason: "No on-demand profile signalled, or no segments found",
        );
        $this->selfInitCase = $this->legacyReporter->add(
            section: '3.10.3.2',
            test: "Each representation SHALL have on segment",
            skipReason: "No on-demand profile signalled",
        );
    }

    //Public validation functions
    /**
     * @param array<Segment> $segments
     **/
    public function validateOnDemand(Representation $representation, array $segments): void
    {

        $isOndemand = $representation->hasProfile('http://dashif.org/guidelines/dash-if-ondemand');
        if (!$isOndemand) {
            $isOndemand = $representation->hasProfile('http://dashif.org/guidelines/dash') &&
                $representation->hasProfile('urn:mpeg:dash:profile:isoff-on-demand:2011');
        }

        if (!$isOndemand) {
            return;
        }

        $this->selfInitCase->pathAdd(
            path: $representation->path(),
            result: count($segments) == 1,
            severity: "FAIL",
            pass_message: "Exactly 1 segment found",
            fail_message: "0 or multiple segments found",
        );

        if (count($segments) == 0) {
            return;
        }

        $sidx = $segments[0]->boxAccess()->sidx();
        $this->sidxCase->pathAdd(
            path: $representation->path(),
            result: count($sidx) == 1,
            severity: "FAIL",
            pass_message: "Exactly 1 'sidx' box found",
            fail_message: "0 or multiple 'sidx' boxes found",
        );
    }

    //Private helper functions
}
