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
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class Miscellaneous
{
    //Private subreporters
    private SubReporter $cmafReporter;

    private TestCase $hdlrCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->cmafReporter = &$reporter->context(new ReporterContext(
            "Segments",
            "Legacy",
            "CMAF",
            []
        ));

        $this->hdlrCase = $this->cmafReporter->add(
            section: 'Section 7.3.4.1',
            test: "A CMAF switching set SHALL have only media type",
            skipReason: 'No video track found'
        );
    }

    //Public validation functions
    public function validateMiscellaneous(AdaptationSet $adaptationSet): void
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
