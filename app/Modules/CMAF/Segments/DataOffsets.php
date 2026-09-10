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

class DataOffsets extends SegmentComponent
{
    private TestCase $offsetCase;

    public function __construct()
    {
        parent::__construct(
            self::class,
            new ReporterContext(
                "Segments",
                "Edition 3",
                "CMAF",
                []
            )
        );

        $this->offsetCase = $this->reporter->add(
            section: 'Section 7.3.5',
            test: "All media samples in a CMAF Fragment SHALL be addressed by byte offsets in the 'trun' box",
            skipReason: "No valid track found"
        );
    }

    //Public validation functions
    public function validateSegment(Representation $representation, Segment $segment, int $segmentIndex): void
    {
        $trunBox = $segment->boxAccess()->trun()[0];
        $samples = $segment->getNalSamples();

        if (count($samples) != $trunBox->sampleCount) {
            $this->offsetCase->pathAdd(
                path: $representation->path() . "-$segmentIndex",
                result: false,
                severity: "FAIL",
                pass_message: "",
                fail_message: "Trun signals " . $trunBox->sampleCount . " but " . count($samples) . " samples found",
            );
            return;
        }

        $index = 0;
        $allValid = true;
        while ($index < $trunBox->sampleCount) {
            if ($trunBox->sizes[$index] != $samples[$index]->size) {
                $allValid = false;
                break;
            }
            $index++;
        }
        $this->offsetCase->pathAdd(
            path: $representation->path() . "-$segmentIndex",
            result: $allValid,
            severity: "FAIL",
            pass_message: "All sample sizes correspond with their 'trun' counterpart",
            fail_message: "Not all sizes corrsespond to their trun counterpart",
        );
    }

    //Private helper functions
}
