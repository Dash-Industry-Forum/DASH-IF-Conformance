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
use App\Interfaces\Module;
use App\Interfaces\ModuleComponents\InitSegmentComponent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CrossCodecs extends InitSegmentComponent
{
    private TestCase $codecCase;

    public function __construct()
    {
        parent::__construct(
            self::class,
            new ReporterContext(
                "CrossValidation",
                "DVB",
                "v1.4.1",
                []
            )
        );

        $this->codecCase = $this->reporter->add(
            section: '5.x / 6.x',
            test: "For each representation, @codecs SHALL be match the codec derived from the segments",
            skipReason: "No media stream found"
        );
    }

    //Public validation functions
    public function validateInitSegment(Representation $representation, Segment $segment): void
    {
        $segmentCodec = $segment->getCodec();
        $representationCodec = $representation->getTransientAttribute('codecs');

        $this->codecCase->pathAdd(
            result: $segmentCodec == $representationCodec,
            severity: "FAIL",
            path: $representation->path() . "-init",
            pass_message: "Codecs match",
            fail_message: "MPD ($representationCodec) does not match segment ($segmentCodec)",
        );
    }

    //Private helper functions
}
