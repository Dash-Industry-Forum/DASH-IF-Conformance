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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SAPTypes
{
    //Private subreporters
    private SubReporter $v141Reporter;

    private TestCase $sapTypeCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->v141Reporter = &$reporter->context(new ReporterContext(
            "Segments",
            "DVB",
            "v1.4.1",
            []
        ));

        $this->sapTypeCase = $this->v141Reporter->add(
            section: '5.1.2',
            test: 'Segments shall start with SAP types 1 or 2',
            skipReason: 'No h264 stream found'
        );
    }

    //Public validation functions
    public function validateSAPTypes(Representation $representation, Segment $segment, int $segmentIndex): void
    {
        //TODO Check only for AVC
        $segmentSAPTypes = $segment->getSegmentSAPTypes();
        $validSAPTypes = true;
        if (!count($segmentSAPTypes)) {
            $validSAPTypes = false;
        } else {
            foreach ($segmentSAPTypes as $sapType) {
                if ($sapType != '1' && $sapType != '2') {
                    $validSAPTypes = false;
                }
            }
        }

        $this->sapTypeCase->pathAdd(
            result: $validSAPTypes,
            severity: "FAIL",
            path: $representation->path() . "-$segmentIndex",
            pass_message: "All SAP types valid",
            fail_message: "At least one invalid or unsignalled SAP type"
        );
    }

    //Private helper functions
}
