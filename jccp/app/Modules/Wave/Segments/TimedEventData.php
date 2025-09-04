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

class TimedEventData
{
    //Private subreporters
    private SubReporter $waveReporter;

    private string $section = '4.5.2 - Carriage of Timed Event Data';

    private string $emsgExplanation = "All 'emsg' boxes inserted [..] after the start of the first CMAF chunk " .
        "SHALL be repeated before the first chunk of the next segment";

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
    public function validateTimedEventdata(Representation $representation, array $segments): void
    {
        $activeSegment = false;
        $expectRepeat = [];
        foreach ($segments as $segmentIndex => $segment) {
            $emsgNum = -1;
            $emsgBoxes = $segment->getEmsgBoxes();
            $topLevelBoxes = $segment->getTopLevelBoxNames();
            foreach ($topLevelBoxes as $boxName) {
                if ($boxName == 'moof') {
                    foreach ($expectRepeat as $expected) {
                        $this->waveReporter->test(
                            section: $this->section,
                            test: $this->emsgExplanation,
                            result: false,
                            severity: "FAIL",
                            pass_message: "",
                            fail_message: $representation->path() . " - EmsgBox with time " .
                              $expected->presentationTime . " no repeated in segment $segmentIndex",
                        );
                    }
                }
                if ($boxName == 'mdat') {
                    $activeSegment = true;
                }

                if ($boxName != 'emsg') {
                    continue;
                }

                $emsgNum++;

                if ($activeSegment) {
                    $expectRepeat[] = $emsgBoxes[$emsgNum];
                    continue;
                }

                if (!count($expectRepeat)) {
                    //We don't expect repetitions, so this is fine.
                    continue;
                }

                $diffExpect = array_udiff(
                    $expectRepeat,
                    [$emsgBoxes[$emsgNum]],
                    array($this,'compareEmsg')
                );

                $expectRepeat = $diffExpect;
            }
        }
    }
    public function compareEmsg(Boxes\EventMessage $box1, Boxes\EventMessage $box2): int
    {
        if ($box1->equals($box2)) {
            return 0;
        }
        return $box1->presentationTime <=> $box2->presentationTime;
    }

    //Private helper functions
}
