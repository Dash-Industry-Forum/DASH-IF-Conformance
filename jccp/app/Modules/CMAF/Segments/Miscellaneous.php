<?php

namespace App\Modules\CMAF\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Services\Manifest\AdaptationSet;
use App\Services\SegmentManager;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes\DescriptionType;
use App\Interfaces\ModuleComponents\AdaptationComponent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class Miscellaneous extends AdaptationComponent
{
    private TestCase $hdlrCase;

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

        $this->hdlrCase = $this->reporter->add(
            section: 'Section 7.3.4.1',
            test: "A CMAF switching set SHALL have only media type",
            skipReason: 'No video track found'
        );
    }

    //Public validation functions
    public function validateAdaptationSet(AdaptationSet $adaptationSet): void
    {
        $segmentManager = app(SegmentManager::class);


        $hdlrTypes = [];
        //TODO: BaseMediaDecodeTime checks


        foreach ($adaptationSet->allRepresentations() as $representation) {
            $segmentList = $segmentManager->representationSegments($representation);
            if (count($segmentList)) {
                $hdlrTypes[] = $segmentList[0]->getHandlerType();
            } else {
                $hdlrTypes[] = 'UNKNOWN';
            }
        }

        $this->hdlrCase->pathAdd(
            path: $adaptationSet->path(),
            result: count(array_unique($hdlrTypes)) == 1,
            severity: "FAIL",
            pass_message: "Single 'hdlr' type found",
            fail_message: "Differing 'hdlr' types found",
        );
    }

    //Private helper functions
}
