<?php

namespace App\Modules\LowLatency\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Services\Manifest\AdaptationSet;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes\DescriptionType;
use App\Services\Validators\Boxes\SIDXBox;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SegmentTiming
{
    //Private subreporters
    private SubReporter $legacyReporter;

    private TestCase $templateDurationCase;
    private TestCase $repeatCase;
    private TestCase $chunkCountCase;
    private TestCase $timelineEPTCase;
    private TestCase $timelineDurationCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->legacyReporter = &$reporter->context(new ReporterContext(
            "CrossValidation",
            "LEGACY",
            "Low Latency",
            []
        ));

        //TODO: Extract to different spec and create dependency
        $this->templateDurationCase = $this->legacyReporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.3',
            test: "The tolerance for the EPT relative to the first stegment SHALL NOT exceed 50%",
            skipReason: "No SegmentTimeline found",
        );
        $this->repeatCase = $this->legacyReporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.3',
            test: "If an 'S' elements and it predecessor share the same duration, they SHOULD be combined",
            skipReason: "No SegmentTimeline found",
        );
        $this->chunkCountCase = $this->legacyReporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.3',
            test: "If each chunk is an addressable object, '@k SHALL be present in the SegmentTimeline",
            skipReason: "No SegmentTimeline found",
        );
        $this->timelineEPTCase = $this->legacyReporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.3',
            test: "The 't' attribute SHALL correspond to the EPT of the corresponding segment",
            skipReason: "No SegmentTimeline found",
        );
        $this->timelineDurationCase = $this->legacyReporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.3',
            test: "The 'd' attribute SHALL correspond to the duration of the corresponding segment",
            skipReason: "No SegmentTimeline found",
        );
    }

    //Public validation functions
    /**
     * @param array<Segment> $segments
     **/
    public function validateTimings(Representation $representation, array $segments): void
    {
        $segmentTemplate = $representation->getTransientDOMElements('SegmentTemplate');
        if (!empty($segmentTemplate) && $segmentTemplate[0]) {
            $this->validateSegmentTemplate($representation, $segments, $segmentTemplate[0]);
        }

        $segmentTimeline = $representation->getTransientDOMElements('SegmentTimeline');
        if (!empty($segmentTimeline) && $segmentTimeline[0]) {
            $this->validateSegmentTimeline($representation, $segments, $segmentTimeline[0]);
        }
    }

    //Private helper functions
    /**
     * @param array<Segment> $segments
     **/
    private function validateSegmentTemplate(
        Representation $representation,
        array $segments,
        \DOMElement $segmentTemplate
    ): void {
        $duration = $segmentTemplate->getAttribute('duration');
        if ($duration == '') {
            return;
        }
        $duration = floatval($duration);

        if (empty($segments)) {
            return;
        }

        $ept = $segments[0]->getEPT();

        for ($i = 1; $i < count($segments); $i++) {
            $thisEpt = $segments[$i]->getEPT();

            $upperBound = ($i + 0.5) * $duration;
            $lowerBound = $upperBound - $duration;

            $this->templateDurationCase->pathAdd(
                path: $representation->path() . "-" . ($i - 1),
                result: (($thisEpt - $ept) > $lowerBound) && (($thisEpt - $ept) < $upperBound),
                severity: "FAIL",
                pass_message: "Within bounds",
                fail_message: "Out of bounds",
            );
        }
    }
    /**
     * @param array<Segment> $segments
     **/
    private function validateSegmentTimeline(
        Representation $representation,
        array $segments,
        \DOMElement $segmentTimeline
    ): void {
        $this->validateRepeats($representation, $segmentTimeline);
        $this->validateChunkCount($representation, $segmentTimeline);
        $this->validateTimelineEntries($representation, $segments, $segmentTimeline);
    }

    private function validateRepeats(
        Representation $representation,
        \DOMElement $segmentTimeline
    ): void {
        $sEntries = $segmentTimeline->getElementsByTagName('S');

        $validRepeats = true;
        for ($i = 1; $i < count($sEntries); $i++) {
            if ($sEntries[$i - 1]->getAttribute('d') == $sEntries[$i]->getAttribute('d')) {
                $validRepeats = false;
                break;
            }
        }
        $this->repeatCase->pathAdd(
            path: $representation->path() . "-" . ($i - 1),
            result: $validRepeats,
            severity: "WARN",
            pass_message: "All repetitions marked correctly",
            fail_message: "Not all repetitions marked correctly",
        );
    }
    private function validateChunkCount(
        Representation $representation,
        \DOMElement $segmentTimeline
    ): void {
        $sEntries = $segmentTimeline->getElementsByTagName('S');

        $chunkCountPresent = false;
        $chunkCountValid = true;

        foreach ($sEntries as $sEntry) {
            $validRepeats = true;
        }
        for ($i = 0; $i < count($sEntries) - 1; $i++) {
            if ($sEntries[$i]->getAttribute('k') != '') {
                $chunkCountPresent = true;
            } else {
                $chunkCountValid = false;
            }
        }
        $this->chunkCountCase->pathAdd(
            path: $representation->path(),
            result: !$chunkCountPresent || $chunkCountValid,
            severity: "INFO",
            pass_message: ($chunkCountPresent ? "All " : "No") . " chunks signalled as addressable",
            fail_message: "Only some counts present, assuming non-addressable"
        );
    }

    /**
     * @param array<Segment> $segments
     **/
    private function validateTimelineEntries(
        Representation $representation,
        array $segments,
        \DOMElement $segmentTimeline
    ): void {
        $entries = $this->extractEntries($segmentTimeline);

        for ($i = 0; $i < count($segments); $i++) {
            $this->timelineEPTCase->pathAdd(
                path: $representation->path() . "-$i",
                result: $entries[$i]['time'] == $segments[$i]->getEPT(),
                severity: "FAIL",
                pass_message: "EPT matches",
                fail_message: "EPT does  not match"
            );
            $segmentDurations = $segments[$i]->getSegmentDurations();
            $this->timelineDurationCase->pathAdd(
                path: $representation->path() . "-$i",
                result: !empty($segmentDurations) && $entries[$i]['duration'] == $segmentDurations[0],
                severity: "FAIL",
                pass_message: "Duration matches",
                fail_message: "Duration does not match, or unable to calculate segment duration"
            );
        }
    }

    /**
     * @return array<array<string,int>>
     **/
    ///TODO Implement negative repeats
    private function extractEntries(\DOMElement $segmentTimeline): array
    {
        $sEntries = $segmentTimeline->getElementsByTagName('S');

        $res = [];

        $time = 0;
        foreach ($sEntries as $sEntry) {
            if ($sEntry->getAttribute('t') != '') {
                $time = intval($sEntry->getAttribute('t'));
            }
            $duration = intval($sEntry->getAttribute('d'));
            $repeat = 1;
            if ($sEntry->getAttribute('r') != '') {
                $repeat = intval($sEntry->getAttribute('r')) + 1;
            }
            for ($i = 0; $i < $repeat; $i++) {
                $res[] = [
                    't' => $time,
                    'd' => $duration,
                ];
                $time += $duration;
            }
        }

        return $res;
    }
}
