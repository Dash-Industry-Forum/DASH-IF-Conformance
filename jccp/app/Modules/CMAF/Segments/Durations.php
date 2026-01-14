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
                "LEGACY",
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
        //TODO: Version that works without sidx boxes
        $currentOffset = 0;

        foreach ($segments as $segmentIndex => $segment) {
            $sidxBoxes = $segment->boxAccess()->sidx();
            $tfdtBoxes = $segment->boxAccess()->tfdt();

            $allReferences = [];
            foreach ($sidxBoxes as $sidxBox) {
                foreach ($sidxBox->references as $reference) {
                    $allReferences[] = $reference;
                }
            }

            if (count($allReferences) != count($tfdtBoxes)) {
                $this->offsetCase->pathAdd(
                    path: $representation->path() . "-$segmentIndex",
                    result: false,
                    severity: "FAIL",
                    pass_message: "",
                    fail_message: "Unable to check due to inconsistent box counts"
                );
                return;
            }

            $index = 0;
            $allValid = true;
            while ($index < count($allReferences)) {
                if ($tfdtBoxes[$index]->decodeTime != $currentOffset) {
                    $allValid = false;
                    break;
                }

                $currentOffset += $allReferences[$index]->duration;
                $index++;
            }
            $this->offsetCase->pathAdd(
                path: $representation->path() . "-$segmentIndex",
                result: $allValid,
                severity: "FAIL",
                pass_message: "All trun offsets between begin and end of referenced boxes",
                fail_message: "At least one offset out of bounds"
            );
        }
    }

    //Private helper functions
}
