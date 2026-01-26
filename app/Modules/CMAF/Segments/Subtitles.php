<?php

namespace App\Modules\CMAF\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\AdaptationSet;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\SegmentManager;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes\DescriptionType;
use App\Interfaces\ModuleComponents\SegmentComponent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class Subtitles extends SegmentComponent
{
    private TestCase $subsCase;

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

        $this->subsCase = $this->reporter->add(
            section: 'Section 7.5.20',
            test: "All CMAF fragments in a 'im1i' track SHALL contain a 'subs' box",
            skipReason: "No 'im1i' track found"
        );
    }

    //Public validation functions
    public function validateSegment(Representation $representation, Segment $segment, int $segmentIndex): void
    {
        if (!$representation->hasCodec('im1i')) {
            return;
        }
        $this->subsCase->pathAdd(
            result: false,
            severity: "WARN",
            path: $representation->path(),
            pass_message: "",
            fail_message: "This check has not yet been ported due to missing test vectors",
        );
    }

    //Private helper functions
}
