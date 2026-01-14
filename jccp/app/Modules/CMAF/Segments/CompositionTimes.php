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
                "LEGACY",
                "CMAF",
                []
            )
        );

        $this->offsetCase = $this->reporter->add(
            section: 'Section 9.2.1',
            test: "Video tracks SHALL contain either 'trun' v1 or 'elst', but no both",
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

        $trunV1 = count(array_unique($trunVersions)) == 1 && $trunVersions[0] == "1";

        $this->offsetCase->pathAdd(
            result: $trunV1 xor count($elstBoxes),
            severity: "FAIL",
            path: $representation->path() . "-$segmentIndex",
            pass_message: "'trun' v1 OR 'elst' found",
            fail_message: "None or mixed elements found"
        );
        if (count($elstBoxes) && !$trunV1) {
            //TODO: Validate earliestCompositionTime
            $this->offsetCase->pathAdd(
                result: true,
                severity: "INFO",
                path: $representation->path() . "-$segmentIndex",
                pass_message: "'elst' is not validated yet",
                fail_message: ""
            );
        }
    }

    //Private helper functions
}
