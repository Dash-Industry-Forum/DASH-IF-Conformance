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
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SegmentIndex
{
    //Private subreporters
    private SubReporter $cmafReporter;

    private TestCase $sidxCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->cmafReporter = &$reporter->context(new ReporterContext(
            "Segments",
            "LEGACY",
            "CMAF",
            []
        ));

        $this->sidxCase = $this->cmafReporter->add(
            section: 'Section 7.3.3.3',
            test: "Each sbusegment referenced in the 'sidx' box SHALL be a single fragment",
            skipReason: "No 'sidx' box used",
        );
    }

    //Public validation functions
    public function validateSegmentIndex(Representation $representation, Segment $segment, int $segmentIndex): void
    {

        $sidxReferenceTypes = $segment->getSIDXReferenceTypes();

        if (!count($sidxReferenceTypes)) {
            return;
        }

        $validReferences = true;
        foreach ($sidxReferenceTypes as $referenceType) {
            if ($referenceType != '0') {
                $validReferences = false;
            }
        }

        $this->sidxCase->pathAdd(
            result: $validReferences,
            severity: "FAIL",
            path: $representation->path() . "-$segmentIndex",
            pass_message: "Only valid reference types found",
            fail_message: "At least one invalid reference type found",
        );
    }

    //Private helper functions
}
