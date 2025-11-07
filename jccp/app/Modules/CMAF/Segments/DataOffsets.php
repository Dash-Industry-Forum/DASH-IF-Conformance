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

class DataOffsets
{
    //Private subreporters
    private SubReporter $cmafReporter;

    private TestCase $offsetCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->cmafReporter = &$reporter->context(new ReporterContext(
            "Segments",
            "Legacy",
            "CMAF",
            []
        ));

        $this->offsetCase = $this->cmafReporter->add(
            section: 'Section 7.3.2.3',
            test: "All media samples in a CMAF chunk shall be addressed by byte offsets  in a 'trun' box",
            skipReason: "No valid track found"
        );
    }

    //Public validation functions
    public function validateDataOffsets(Representation $representation, Segment $segment, int $segmentIndex): void
    {
        $sidxBoxes = $segment->boxAccess()->sidx();
        $moofBoxes = $segment->boxAccess()->moof();
        $trunBoxes = $segment->boxAccess()->trun();

        $allReferences = [];
        foreach ($sidxBoxes as $sidxBox) {
            foreach ($sidxBox->references as $reference) {
                $allReferences[] = $reference;
            }
        }

        if (count($allReferences) != count($moofBoxes) || count($allReferences) != count($trunBoxes)) {
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
        while ($index < count($trunBoxes)) {
            if ($trunBoxes[$index]->dataOffset < ($moofBoxes[$index]->boxSize + 8)) {
                $allValid = false;
                break;
            }
            if ($trunBoxes[$index]->dataOffset > (($moofBoxes[$index]->boxSize + 8) + $allReferences[$index]->size)) {
                $allValid = false;
                break;
            }

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

    //Private helper functions
}
