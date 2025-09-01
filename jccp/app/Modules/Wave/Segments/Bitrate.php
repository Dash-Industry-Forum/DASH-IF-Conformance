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

    private string $bitrateExplanation = "For presentations presented in an on-demand environment: " .
        "The Average Bitrate of a CMAF Fragment within a CMAF Track SHOULD be within 10% of the Average Bitrate " .
        "calculated over the full duration of the Track.";


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
    /**
     * @param array<Segment> $segments
     **/
    public function validateBitrate(Representation $representation, array $segments): void
    {

        Log::info($representation->path());
        $segmentSizes = [];
        $segmentDurations = [];
        $segmentBitrates = [];

        $totalDuration = 0;
        $totalSize = 0;
        foreach ($segments as $segment) {
            $segmentSize =  filesize($segment->segmentPath);
            $segmentSizes[] = $segmentSize;
            $totalSize += $segmentSize;
            $durations = $segment->getSegmentDurations();
            if (!empty($durations) && $durations[0] > 0) {
                $segmentDurations[] = $durations[0];
                $totalDuration += $durations[0];

                $segmentBitrates[] = round($segmentSize / $durations[0], 2);
            }
        }

        $validSizesAndDurations = $this->waveReporter->test(
            section: $this->section,
            test: $this->bitrateExplanation,
            result: count($segmentSizes) == count($segmentDurations) && !empty($segmentSizes),
            severity: "FAIL",
            pass_message: $representation->path() . " - Both sizes and durations found",
            fail_message: $representation->path() . " - Either sizes or durations missing",
        );

        if (!$validSizesAndDurations) {
            return;
        }

        $totalBitrate = $totalDuration ? $totalSize / $totalDuration : 0;
        $upperLimit = round($totalBitrate * 1.1, 2);
        $lowerLimit = round($totalBitrate * 0.9, 2);

        $atLeastOneFailed = false;

        $this->waveReporter->test(
            section: $this->section,
            test: $this->bitrateExplanation,
            result: true,
            severity: "INFO",
            pass_message: $representation->path() . " - Lower bound is $lowerLimit",
            fail_message: "",
        );
        $this->waveReporter->test(
            section: $this->section,
            test: $this->bitrateExplanation,
            result: true,
            severity: "INFO",
            pass_message: $representation->path() . " - Upper bound is $upperLimit",
            fail_message: "",
        );

        foreach ($segments as $segmentIndex => $segment) {
            $bitrate = $segmentBitrates[$segmentIndex];
            $atLeastOneFailed |= $this->waveReporter->test(
                section: $this->section,
                test: $this->bitrateExplanation,
                result: $bitrate <= $upperLimit && $bitrate >= $lowerLimit,
                severity: "FAIL",
                pass_message: $representation->path() . " - Bitrate for segment $segmentIndex within bounds",
                fail_message: $representation->path() . " - Bitrate for segment $segmentIndex out of bounds"
            );
        }

        if ($atLeastOneFailed) {
            $this->waveReporter->test(
                section: $this->section,
                test: $this->bitrateExplanation,
                result: true,
                severity: "INFO",
                pass_message: $representation->path() . " - Calculated bitrates: " .
                              implode(" | ", $segmentBitrates),
                fail_message: "",
            );
        }
    }

    //Private helper functions
}
