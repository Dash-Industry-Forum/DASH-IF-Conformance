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

class Durations
{
    //Private subreporters
    private SubReporter $v141Reporter;

    private TestCase $durationCase;
    private TestCase $minDurationCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->v141Reporter = &$reporter->context(new ReporterContext(
            "Segments",
            "DVB",
            "v1.4.1",
            []
        ));

        $this->durationCase = $this->v141Reporter->add(
            section: '4.5',
            test: 'Each subsegment shall have a duration of not more than 15 seconds',
            skipReason: ''
        );

        //TODO Actual check is 960ms
        $this->minDurationCase = $this->v141Reporter->add(
            section: '4.5',
            test: 'Each subsegment shall be at least 1 second',
            skipReason: ''
        );
    }

    //Public validation functions
    public function validateDurations(Representation $representation, Segment $segment, int $segmentIndex): void
    {
        //TODO Check only for audio/video
        $this->validateDurationLowerBound($representation, $segment, $segmentIndex);
        $this->validateDurationUpperBound($representation, $segment, $segmentIndex);
    }

    private function validateDurationLowerBound(
        Representation $representation,
        Segment $segment,
        int $segmentIndex
    ): void {
        //TODO Check whether not last segment in period
        $segmentDurations = $segment->getSegmentDurations();
        $validDurations = true;
        if (count($segmentDurations)) {
            foreach ($segmentDurations as $segmentDuration) {
                if ($segmentDuration < 1) {
                    $validDurations = false;
                }
            }
        }

        $this->minDurationCase->pathAdd(
            result: $validDurations,
            severity: "FAIL",
            path: $representation->path() . "-$segmentIndex",
            pass_message: "All durations valid",
            fail_message: "At least one under threshold"
        );

        if (!$validDurations) {
            $this->minDurationCase->pathAdd(
                result: true,
                severity: "INFO",
                path: $representation->path() . "-$segmentIndex",
                pass_message: "Found durations: " . implode(',', $segmentDurations),
                fail_message: "",
            );
        }
    }

    private function validateDurationUpperBound(
        Representation $representation,
        Segment $segment,
        int $segmentIndex
    ): void {
        $segmentDurations = $segment->getSegmentDurations();
        $validDurations = true;
        if (count($segmentDurations)) {
            foreach ($segmentDurations as $segmentDuration) {
                if ($segmentDuration > 15) {
                    $validDurations = false;
                }
            }
        }

        $this->durationCase->pathAdd(
            result: $validDurations,
            severity: "FAIL",
            path: $representation->path() . "-$segmentIndex",
            pass_message: "All durations valid",
            fail_message: "At least one above threshold"
        );

        if (!$validDurations) {
            $this->durationCase->pathAdd(
                result: true,
                severity: "INFO",
                path: $representation->path() . "-$segmentIndex",
                pass_message: "Found durations: " . implode(',', $segmentDurations),
                fail_message: "",
            );
        }
    }

    //Private helper functions
}
