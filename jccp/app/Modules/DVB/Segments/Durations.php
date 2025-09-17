<?php

namespace App\Modules\DVB\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes\DescriptionType;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class Durations
{
    //Private subreporters
    private SubReporter $v141Reporter;

    private string $section = 'Section 4.5';

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->v141Reporter = &$reporter->context(new ReporterContext(
            "Segments",
            "DVB",
            "v1.4.1",
            []
        ));
    }

    //Public validation functions
    public function validateDurations(Representation $representation, Segment $segment): void
    {
        //TODO Check only for audio/video

        $segmentDurations = $segment->getSegmentDurations();
        $validDurations = true;
        if (count($segmentDurations)) {
            foreach ($segmentDurations as $segmentDuration) {
                if ($segmentDuration > 15) {
                    $validDurations = false;
                }
            }
        }

        $this->v141Reporter->test(
            section: $this->section,
            test: "Each subsegment shall have a duration of not more than 15 seconds",
            result: $validDurations,
            severity: "FAIL",
            pass_message: $representation->path() . " All durations are <= 15 seconds",
            fail_message: $representation->path() . " At least one segment duration > 15 seconds"
        );

        if (!$validDurations) {
            $this->v141Reporter->test(
                section: $this->section,
                test: "Each subsegment shall have a duration of not more than 15 seconds",
                result: true,
                severity: "INFO",
                pass_message: $representation->path() . " Found durations: " . implode(',', $segmentDurations),
                fail_message: "",
            );
        }
    }

    //Private helper functions
}
