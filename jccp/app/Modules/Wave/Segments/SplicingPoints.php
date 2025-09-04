<?php

namespace App\Modules\Wave\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SplicingPoints
{
    //Private subreporters
    private SubReporter $waveReporter;

    private string $section = '4.4.2 - Presentation Splicing';

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
    public function validateSplicingPoints(Representation $representation, Segment $segment): void
    {

        $boxTree = $segment->getBoxNameTree();
        if (!$boxTree) {
            return;
        }

        $moofBoxes = $boxTree->filterChildrenRecursive('moof');

        $this->waveReporter->test(
            section: $this->section,
            test: "CMAF Fragment boundaries SHALL be created at all splice points.",
            result: count($moofBoxes) == 1,
            severity: "FAIL",
            pass_message: $representation->path() . " - Single 'moof' box in segment",
            fail_message: $representation->path() . " - " . count($moofBoxes) . " 'moof' boxes in segment",
        );
    }

    /**
     * @param array<Segment> $segments
     **/
    public function validateSegmentDurations(Representation $representation, array $segments): void
    {
        $segmentCount = count($segments);

        if (!$segmentCount) {
            return;
        }

        $sampleDuration = $segments[0]->getSampleDuration();

        for ($i = 0; $i < $segmentCount - 1; $i++) {
            $fragmentDurationCurrent = array_sum($segments[$i]->getFragmentDurations());
            $fragmentDurationNext = array_sum($segments[$i + 1]->getFragmentDurations());

            $this->waveReporter->test(
                section: $this->section,
                test: "CMAF Fragment [...] SHALL be within one ISOBMFF sample duration",
                result: abs($fragmentDurationNext - $fragmentDurationCurrent) <= $sampleDuration,
                severity: "FAIL",
                pass_message: $representation->path() .
                              " - Duration difference for segment and next within sample duration",
                fail_message: $representation->path() .
                              " - Duration difference for segment and next not within sample duration",
            );
        }
    }

    //Private helper functions
}
