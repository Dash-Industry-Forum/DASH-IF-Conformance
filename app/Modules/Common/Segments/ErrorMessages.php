<?php

namespace App\Modules\Common\Segments;

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

class ErrorMessages extends SegmentComponent
{
    private TestCase $errorCase;

    public function __construct()
    {
        parent::__construct(
            self::class,
            new ReporterContext(
                "Segments",
                "Global",
                "Error Messages",
                []
            )
        );

        $this->errorCase = $this->reporter->add(
            section: '',
            test: "Segment analysis should not result in errors",
            skipReason: 'No segments found'
        );
    }

    //Public validation functions
    public function validateSegment(Representation $representation, Segment $segment, int $segmentIndex): void
    {

        $errors = $segment->getErrors();

        $this->errorCase->pathAdd(
            result: !count($errors),
            severity: "FAIL",
            path: $representation->path() . "-$segmentIndex",
            pass_message: "No error messages found",
            fail_message: "Error messages found",
        );

        foreach ($errors as $error){
            $this->errorCase->pathAdd(
                result: false,
                severity: "INFO",
                path: $representation->path() . "-$segmentIndex",
                pass_message: "",
                fail_message: $error,
            );
        }
    }

    //Private helper functions
}
