<?php

namespace App\Modules\DVB\MPD;

use App\Services\MPDCache;
use App\Services\Manifest\Period;
use App\Services\ModuleReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class Dimensions
{
    //TODO: Add "before xlink" counterparts.

    //Private subreporters
    private SubReporter $v141reporter;

    private TestCase $sizeCase;
    private TestCase $periodCountCase;
    private TestCase $adaptationSetCountCase;
    private TestCase $representationCountCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->v141reporter = &$reporter->context(new ReporterContext(
            "MPD",
            "DVB",
            "v1.4.1",
            ["document" => "ETSI TS 103 285"]
        ));

        $this->sizeCase = $this->v141reporter->add(
            section: "Section 4.5",
            test: "The MPD size SHALL NOT exceed 256 Kbytes",
            skipReason: "No MPD"
        );
        $this->periodCountCase = $this->v141reporter->add(
            section: "Section 4.5",
            test: "The MPD has a maximum of 64 periods",
            skipReason: "No MPD"
        );
        $this->adaptationSetCountCase = $this->v141reporter->add(
            section: "Section 4.5",
            test: "Each Period has a maximum of 16 Adaptation Sets",
            skipReason: "No Period found"
        );
        $this->representationCountCase = $this->v141reporter->add(
            section: "Section 4.5",
            test: "Each Adaptation Set has a maximum of 16 Representations",
            skipReason: "No Adaptation Set found"
        );
    }

    //Public validation functions
    public function validateDimensions(): void
    {
        //TODO: Build 'after xlink' variants

        $mpdCache = app(MPDCache::class);

        $mpdSize = strlen($mpdCache->getMPD());

        $this->sizeCase->add(
            result: $mpdSize <= 1024 * 256,
            severity: "FAIL",
            pass_message: "MPD size valid",
            fail_message: "MPD size too large ($mpdSize)"
        );

        $this->validateCounts();
    }

    //Private helper functions
    private function validateCounts(): void
    {
        $mpdCache = app(MPDCache::class);

        $allPeriods = $mpdCache->allPeriods();
        $this->periodCountCase->add(
            result: count($allPeriods) <= 64,
            severity: "FAIL",
            pass_message: count($allPeriods) . " Period(s)",
            fail_message: count($allPeriods) . " Periods",
        );

        foreach ($allPeriods as $period) {
            $adaptationSets = $period->allAdaptationSets();
            $this->adaptationSetCountCase->pathAdd(
                path: $period->path(),
                result: count($adaptationSets) <= 16,
                severity: "FAIL",
                pass_message: count($adaptationSets) . " Adaptation set(s)",
                fail_message: count($adaptationSets) . " Adaptation sets",
            );
            foreach ($adaptationSets as $adaptationSet) {
                $representations = $adaptationSet->allRepresentations();
                $this->representationCountCase->pathAdd(
                    path: $adaptationSet->path(),
                    result: count($representations) <= 16,
                    severity: "FAIL",
                    pass_message: count($representations) . " Representation(s)",
                    fail_message: count($representations) . " Representations",
                );
            }
        }
    }
}
