<?php

namespace App\Modules\DVB\Segments;

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

class BoxCount extends SegmentComponent
{
    private TestCase $trafCase;
    private TestCase $sidxCase;

    public function __construct()
    {
        parent::__construct(
            self::class,
            new ReporterContext(
                "Segments",
                "DVB",
                "v1.4.1",
                []
            )
        );

        $this->trafCase = $this->reporter->add(
            section: 'Section 4.3',
            test: "The 'moof' box shall contain only one 'traf' box",
            skipReason: ''
        );
        $this->sidxCase = $this->reporter->add(
            section: 'Section 4.3',
            test: "The segment shall contain only one 'sidx' box",
            skipReason: 'Stream does not match on-demand profile in section 4.2.6'
        );
    }

    //Public validation functions
    public function validateSegment(Representation $representation, Segment $segment, int $segmentIndex): void
    {

        $boxTree = $segment->getBoxNameTree();

        $moofBoxes = $boxTree->filterChildrenRecursive('moof');
        $validTraf = true;
        $trafCount = 0;
        foreach ($moofBoxes as $moofBox) {
            $trafBoxes = $moofBox->filterChildrenRecursive('traf');
            if (count($trafBoxes) != 1) {
                $validTraf = false;
                break;
            }
        }
        $this->trafCase->pathAdd(
            result: $validTraf,
            severity: "FAIL",
            path: $representation->path() . "-$segmentIndex",
            pass_message: "All 'moof' box(es) valid",
            fail_message: "At least one 'moof' box invalid",
        );

        $isOnDemand = $representation->hasProfile("urn:dvb:dash:profile:dvb-dash:isoff-ext-on-demand:2014");
        if ($isOnDemand) {
            $sidxBoxes = $boxTree->filterChildrenRecursive('sidx');
            $this->sidxCase->pathAdd(
                result: count($sidxBoxes) == 1,
                severity: "FAIL",
                path: $representation->path() . "-$segmentIndex",
                pass_message: "1 'sidx' found",
                fail_message: count($sidxBoxes) . " 'sidx' boxes found"
            );
        }
    }

    //Private helper functions
}
