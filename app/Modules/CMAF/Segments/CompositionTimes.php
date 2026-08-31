<?php

namespace App\Modules\CMAF\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes\DescriptionType;
use App\Interfaces\ModuleComponents\SegmentComponent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CompositionTimes extends SegmentComponent
{
    private TestCase $offsetCase;

    public function __construct()
    {
        parent::__construct(
            self::class,
            new ReporterContext(
                "Segments",
                "Edition 3",
                "CMAF",
                []
            )
        );

        $this->offsetCase = $this->reporter->add(
            section: 'Section 9.2.1',
            test: "Video tracks SHALL contain either 'trun(v1)' OR 'elst' combined with 'trun(v0)', but not both",
            skipReason: 'No video track found'
        );
    }

    //Public validation functions
    public function validateSegment(Representation $representation, Segment $segment, int $segmentIndex): void
    {

        $boxTree = $segment->getBoxNameTree();

        $elstBoxes = $boxTree->filterChildrenRecursive('elst');
        $trunBoxes = $boxTree->filterChildrenRecursive('trun');
        $trunVersions = [];

        foreach ($trunBoxes as $trunBox) {
            $trunVersions[] = $trunBox->version;
        }


        if (count(array_unique($trunVersions)) != 1) {
            $this->offsetCase->pathAdd(
                result: false,
                severity: "FAIL",
                path: $representation->path() . "-$segmentIndex",
                pass_message: "",
                fail_message: "Mixed 'trun' versions found",
            );
            return;
        }

        if ($trunVersions[0] == "0") {
            $this->offsetCase->pathAdd(
                result: count($elstBoxes) > 0,
                severity: "FAIL",
                path: $representation->path() . "-$segmentIndex",
                pass_message: "'trun(v0) with 'elst' box found",
                fail_message: "Missing 'elst' box with 'trun(v0)'"
            );
        } else {
            $this->offsetCase->pathAdd(
                result: count($elstBoxes) == 0,
                severity: "FAIL",
                path: $representation->path() . "-$segmentIndex",
                pass_message: "'trun(v1) without 'elst' box found",
                fail_message: "Disallowed 'elst' box with 'trun(v1)' found"
            );
        }
    }

    //Private helper functions
}
