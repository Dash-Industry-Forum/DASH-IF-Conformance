<?php

namespace App\Modules\WaveHLSInterop\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Reporter\TestCase;
use App\Services\Validators\Boxes;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SplicingPoints
{
    //Private subreporters
    private SubReporter $waveReporter;

    private TestCase $fragmentBoundaryCase;
    private TestCase $fragmentDurationCase;

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

        $this->fragmentBoundaryCase = $this->waveReporter->add(
            section: '4.4.2 - Presentation Splicing',
            test: "CMAF Fragment boundaries SHALL be created at all splice points.",
            skipReason: "Unable to parse segments"
        );

        $this->fragmentDurationCase = $this->waveReporter->add(
            section: '4.4.2 - Presentation Splicing',
            test: "CMAF Fragment [duration difference] SHALL be within one ISOBMFF sample duration",
            skipReason: 'Single fragment'
        );
    }

    //Public validation functions
    public function validateSplicingPoints(Representation $representation, Segment $segment, int $segmentIndex): void
    {

        $boxTree = $segment->getBoxNameTree();
        if (!$boxTree) {
            return;
        }

        $moofBoxes = $boxTree->filterChildrenRecursive('moof');

        $this->fragmentBoundaryCase->pathAdd(
            result: count($moofBoxes) == 1,
            severity: "FAIL",
            path: $representation->path() . "-$segmentIndex",
            pass_message: "Single 'moof' box",
            fail_message: count($moofBoxes) . " 'moof' boxes",
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

        $this->fragmentDurationCase->pathAdd(
            result: true,
            severity: "INFO",
            path: $representation->path(),
            pass_message: "Calculated sample duration: " . number_format($sampleDuration, 3),
            fail_message: "",
        );

        for ($i = 0; $i < $segmentCount - 1; $i++) {
            $fragmentDurationCurrent = array_sum($segments[$i]->getFragmentDurations() ?? []);
            $fragmentDurationNext = array_sum($segments[$i + 1]->getFragmentDurations() ?? []);

            $this->fragmentDurationCase->pathAdd(
                result: abs($fragmentDurationNext - $fragmentDurationCurrent) <= $sampleDuration,
                severity: "FAIL",
                path: $representation->path() . "-$i",
                pass_message: "Difference with next segment valid",
                fail_message: "Difference of " .
                              number_format(abs($fragmentDurationNext - $fragmentDurationCurrent), 3) .
                              " with next segment not valid",
            );
        }
    }

    //Private helper functions
}
