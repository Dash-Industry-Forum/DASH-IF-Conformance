<?php

namespace App\Modules\Wave\Segments;

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

class Bitrate
{
    //Private subreporters
    private SubReporter $waveReporter;

    private string $section = '4.1.2 - Basic On-Demand and Live Streaming';

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->waveReporter = &$reporter->context(new ReporterContext(
            "Segments",
            "CTA-5005-A",
            "Final",
            []
        ));
    }

    //Public validation functions
    public function validateBitrate(Representation $representation, Segment $segment): void
    {

        Log::info($representation->path());
        $segmentSizes = $segment->runAnalyzedFunction('getSegmentSizes');
        $segmentDurations = $segment->runAnalyzedFunction('getSegmentDurations');

        if (
            !$this->waveReporter->test(
                section: $this->section,
                test: "For presentations [..]", //Todo fix!
                result: count($segmentSizes) && count($segmentDurations),
                severity: "FAIL",
                pass_message: "Both sizes and durations found for Representation " . $representation->path(),
                fail_message: "Either sizes or durations missing for Representation " . $representation->path()
            )
        ) {
            return;
        }
    }

    //Private helper functions
}
