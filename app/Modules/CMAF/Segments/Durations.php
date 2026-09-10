<?php

namespace App\Modules\CMAF\Segments;

use App\Services\Manifest\Representation;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Interfaces\ModuleComponents\SegmentListComponent;

class Durations extends SegmentListComponent
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
            section: 'Section 7.3.2.2',
            test: "baseMediaDecodeTime SHALL be equal to the sum of all prior segments",
            skipReason: "No valid track found"
        );
    }

    //Public validation functions

    public function validateSegmentList(Representation $representation, array $segments): void
    {
        $currentOffset = null;

        foreach ($segments as $segmentIndex => $segment) {
            $tfdtBoxes = $segment->boxAccess()->tfdt();
            $tfhdBoxes = $segment->boxAccess()->tfhd();
            $trunBoxes = $segment->boxAccess()->trun();

            if ($currentOffset === null && count($tfdtBoxes) > 0) {
                $currentOffset = $tfdtBoxes[0]->decodeTime;
            }

            if (count($tfdtBoxes) != count($tfhdBoxes)) {
                $this->offsetCase->pathAdd(
                    path: $representation->path() . "-$segmentIndex",
                    result: false,
                    severity: "FAIL",
                    pass_message: "",
                    fail_message: "Mismatched count between 'tfhd' and 'tfdt' boxes",
                );
                return;
            }

            $allValid = true;
            for ($index = 0; $index < count($tfhdBoxes); $index++) {
                if ($tfdtBoxes[0]->decodeTime != $currentOffset) {
                    $allValid = false;
                    break;
                }

                $currentOffset += ($trunBoxes[$index]->sampleCount * $tfhdBoxes[$index]->sampleDuration);
            }
            $this->offsetCase->pathAdd(
                path: $representation->path() . "-$segmentIndex",
                result: $allValid,
                severity: "FAIL",
                pass_message: "Values correct",
                fail_message: "Mismatch between value increase and sum of sample durations"
            );
        }
    }

    //Private helper functions
}
