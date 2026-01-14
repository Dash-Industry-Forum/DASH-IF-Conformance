<?php

namespace App\Modules\WaveHLSInterop\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Reporter\TestCase;
use App\Services\Validators\Boxes\DescriptionType;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class Bitrate
{
    //Private subreporters
    private SubReporter $waveReporter;

    private TestCase $bitrateCase;

    public function __construct()
    {
        $this->registerChecks();
    }

    private function registerChecks(): void
    {
        $reporter = app(ModuleReporter::class);
        $this->waveReporter = &$reporter->context(new ReporterContext(
            "Segments",
            "CTA-5005-A",
            "Final",
            []
        ));

        $this->bitrateCase = $this->waveReporter->add(
            section: '4.1.2 -  Basic On-Demand and Live Streaming',
            test:  "The Average Bitrate of a CMAF Fragment [..] SHOULD be within 10% of the Average Bitrate " .
                   "[..] of the Track.",
            skipReason: "MPD is not signalled as on-demand"
        );
    }

    //Public validation functions
    /**
     * @param array<Segment> $segments
     **/
    public function validateBitrate(Representation $representation, array $segments): void
    {

        $segmentSizes = [];
        $segmentDurations = [];
        $segmentBitrates = [];

        $totalDuration = 0;
        $totalSize = 0;
        foreach ($segments as $segment) {
            $segmentSize =  $segment->getSize();
            $segmentSizes[] = $segmentSize;
            $totalSize += $segmentSize;
            $durations = $segment->getSegmentDurations();
            if (!empty($durations) && $durations[0] > 0) {
                $segmentDurations[] = $durations[0];
                $totalDuration += $durations[0];

                $segmentBitrates[] = round($segmentSize / $durations[0], 2);
            }
        }

        $validSizesAndDurations = $this->bitrateCase->add(
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

        $this->bitrateCase->add(
            result: true,
            severity: "INFO",
            pass_message: $representation->path() . " - Lower bound is $lowerLimit",
            fail_message: "",
        );
        $this->bitrateCase->add(
            result: true,
            severity: "INFO",
            pass_message: $representation->path() . " - Upper bound is $upperLimit",
            fail_message: "",
        );

        foreach ($segments as $segmentIndex => $segment) {
            $bitrate = $segmentBitrates[$segmentIndex];
            $atLeastOneFailed |= $this->bitrateCase->pathAdd(
                result: $bitrate <= $upperLimit && $bitrate >= $lowerLimit,
                severity: "FAIL",
                path: $representation->path() . "-${segmentIndex}",
                pass_message: "Bitrate within bounds",
                fail_message: "Bitrate out of bounds"
            );
        }

        if ($atLeastOneFailed) {
            $this->bitrateCase->add(
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
