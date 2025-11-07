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
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class Subtitles
{
    //Private subreporters
    private SubReporter $cmafReporter;

    private TestCase $subsCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->cmafReporter = &$reporter->context(new ReporterContext(
            "Segments",
            "Legacy",
            "CMAF",
            []
        ));

        $this->subsCase = $this->cmafReporter->add(
            section: 'Section 7.5.20',
            test: "All CMAF fragments in a 'im1i' track SHALL contain a 'subs' box",
            skipReason: "No 'im1i' track found"
        );
    }

    //Public validation functions
    public function validateSubtitleSegment(Representation $representation, Segment $segment, int $segmentIndex): void
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
