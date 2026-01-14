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

class MetaData extends SegmentComponent
{
    private TestCase $metaCase;
    private TestCase $udtaCase;

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

        $this->metaCase = $this->reporter->add(
            section: 'Section 7.5.2',
            test: "When metadata is carried in a 'meta' box, it SHALL NOT occur at the file level",
            skipReason: 'No video track found'
        );
        $this->udtaCase = $this->reporter->add(
            section: 'Section 7.5.2',
            test: "When metadata is carried in a 'udta' box, it SHALL NOT occur at the file level",
            skipReason: 'No video track found'
        );
    }

    //Public validation functions
    public function validateSegment(Representation $representation, Segment $segment, int $segmentIndex): void
    {

        $boxList = $segment->getTopLevelBoxNames();

        $this->metaCase->pathAdd(
            result: !in_array('meta', $boxList),
            severity: "FAIL",
            path: $representation->path() . "-$segmentIndex",
            pass_message: "'meta' not found at top level",
            fail_message: "'meta' found at top level",
        );
        $this->udtaCase->pathAdd(
            result: !in_array('udta', $boxList),
            severity: "FAIL",
            path: $representation->path() . "-$segmentIndex",
            pass_message: "'udta' not found at top level",
            fail_message: "'udta' found at top level",
        );
    }

    //Private helper functions
}
