<?php

namespace App\Modules\LowLatency\Segments;

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
use App\Modules\LowLatency\Segments\SegmentAdaptation;
use App\Modules\LowLatency\Segments\ChunkedAdaptation;

class SegmentOrChunked
{
    //Private subreporters
    private SubReporter $legacyReporter;

    private TestCase $validCase;

    private SegmentAdaptation $segmentAdaptationValidator;
    private ChunkedAdaptation $chunkedAdaptationValidator;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->legacyReporter = &$reporter->context(new ReporterContext(
            "Segments",
            "LEGACY",
            "Low Latency",
            []
        ));

        $this->validCase = $this->legacyReporter->add(
            section: '9.X.4.3',
            test: 'A Low Latency Adaptation Set SHALL either be a Segment or a Chunked Set',
            skipReason: "",
        );

        $this->segmentAdaptationValidator = new SegmentAdaptation();
        $this->chunkedAdaptationValidator = new ChunkedAdaptation();
    }

    //Public validation functions
    /**
     * @param array<Segment> $segments
     **/
    public function validateSegmentOrChunked(Representation $representation, array $segments): void
    {

        $segmentTemplates = $representation->getDOMElements('SegmentTemplate');
        if (!count($segmentTemplates)) {
            $segmentTemplates = $representation->getAdaptationSet()->getDOMElements('SegmentTemplate');
        }
        if (!count($segmentTemplates)) {
            $this->validCase->pathAdd(
                path: $representation->path(),
                result: false,
                severity: "FAIL",
                pass_message: "",
                fail_message: "Unable to detect from SegmentTemplate",
            );
            return;
        }

        //TODO: Reset this check to ==, current is intentionally wrong for testing purposes
        if ($segmentTemplates->item(0)->getAttribute('availabilityTimeComplete') != "") {
            $this->validCase->pathAdd(
                path: $representation->path(),
                result: true,
                severity: "INFO",
                pass_message: "Detected Segment Adaptation Set",
                fail_message: "",
            );
            $this->segmentAdaptationValidator->validateSegmentAdaptation($representation, $segments);
        } else {
            $this->validCase->pathAdd(
                path: $representation->path(),
                result: true,
                severity: "INFO",
                pass_message: "Detected Chunked Adaptation Set",
                fail_message: "",
            );
            $this->chunkedAdaptationValidator->validateChunkedAdaptation($representation, $segments);
        }
    }

    //Private helper functions
}
