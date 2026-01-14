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

class Durations
{
    //Private subreporters
    private SubReporter $cmafReporter;

    private TestCase $offsetCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->cmafReporter = &$reporter->context(new ReporterContext(
            "Segments",
            "LEGACY",
            "CMAF",
            []
        ));

        $this->offsetCase = $this->cmafReporter->add(
            section: 'Section 7.3.2.2',
            test: "baseMediaDecodeTime SHALL be equal to the sum of all prior segments",
            skipReason: "No valid track found"
        );
    }

    //Public validation functions

    /**
     * @param array<Segment> $segments
     **/
    public function validateDurations(Representation $representation, array $segments): void
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
