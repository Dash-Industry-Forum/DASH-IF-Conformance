<?php

namespace App\Modules\LowLatency\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Services\Manifest\AdaptationSet;
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

class ChunkedCrossAdaptation
{
    //Private subreporters
    private SubReporter $legacyReporter;

    private TestCase $crossValidCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->legacyReporter = &$reporter->context(new ReporterContext(
            "Segments",
            "LEGACY",
            "Low Latency",
            []
        ));

        $this->crossValidCase = $this->legacyReporter->add(
            section: '9.X.4.5',
            test: "One of the following should hold",
            skipReason: "No Chunked Adaptation Set with multiple representations Found",
        );
    }

    //Public validation functions
    public function validateChunkedCrossAdaptation(AdaptationSet $adaptationSet): void
    {
        $segmentTemplates = $adaptationSet->getDOMElements('SegmentTemplate');
        if (!count($segmentTemplates)) {
            return;
        }

        //TODO: Reset this check to ==, current is intentionally wrong for testing purposes
        if ($segmentTemplates->item(0)->getAttribute('availabilityTimeComplete') != "") {
            return;
        }


        $firstValid = $this->validateFirstOption($adaptationSet);
        $secondValid = $this->validateSecondOption($adaptationSet);

        $this->crossValidCase->pathAdd(
            path: $adaptationSet->path(),
            result: $firstValid || $secondValid,
            severity: "WARN",
            pass_message: "At least one option valid",
            fail_message: "None of the options valid",
        );
    }

    //Private helper functions
    private function validateFirstOption(AdaptationSet $adaptationSet): bool
    {
        $segmentManager = app(SegmentManager::class);

        $latencyElements = $adaptationSet->getDOMElements('Latency');

        if (!count($latencyElements)) {
            $this->crossValidCase->pathAdd(
                path: $adaptationSet->path() . "-Option1",
                result: false,
                severity: "INFO",
                pass_message: "",
                fail_message: "Missing target latency"
            );
            return false;
        }
        $targetDuration = $latencyElements->item(0)->getAttribute("target");

        $allDurationsValid = true;
        $allDurationsHalf = true;


        foreach ($adaptationSet->allRepresentations() as $representation) {
            $segments = $segmentManager->representationSegments($representation);
            foreach ($segments as $segment) {
                $segmentDuration = array_reduce($segment->getSegmentDurations(), function ($res, $cur) {
                    $res += $cur;
                    return $res;
                }, 0);

                if (($segmentDuration * 1000) > intval($targetDuration)) {
                    $allDurationsValid = false;
                    break;
                }
                if (($segmentDuration * 1000) > (0.5 * intval($targetDuration))) {
                    $allDurationsHalf = false;
                    break;
                }
            }
            if (!$allDurationsValid) {
                break;
            }
        }

        $this->crossValidCase->pathAdd(
            path: $adaptationSet->path() . "-Option1",
            result: $allDurationsValid,
            severity: "INFO",
            pass_message: "All durations smaller than the target duration",
            fail_message: "At least one segment longer than the target duration"
        );

        if ($allDurationsValid) {
            $this->crossValidCase->pathAdd(
                path: $adaptationSet->path() . "-Option1",
                result: $allDurationsValid,
                severity: "INFO",
                pass_message: "All durations smaller than half the target duration",
                fail_message: "At least one segment longer than half the target duration"
            );
        }

        return $allDurationsValid;
    }
    private function validateSecondOption(AdaptationSet $adaptationSet): bool
    {
        return false;
    }
}
