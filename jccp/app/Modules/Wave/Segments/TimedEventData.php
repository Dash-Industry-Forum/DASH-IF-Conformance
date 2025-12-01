<?php

namespace App\Modules\Wave\Segments;

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

class TimedEventData
{
    //Private subreporters
    private SubReporter $waveReporter;

    private TestCase $emsgCase;

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

        $this->emsgCase = $this->waveReporter->add(
            section: '4.5.2 - Carriage of Timed Event Data',
            test: "All 'emsg' boxes inserted [..] after the start of the first CMAF chunk " .
                  "SHALL be repeated before the first chunk of the next segment",
            skipReason: "No 'emsg' boxes found"
        );
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
            $activeSegment = false;
            $emsgNum = -1;
            $emsgBoxes = $segment->boxAccess()->emsg();
            $topLevelBoxes = $segment->getTopLevelBoxNames();
            foreach ($topLevelBoxes as $boxName) {
                if ($boxName == 'moof') {
                    foreach ($expectRepeat as $expected) {
                        $this->emsgCase->pathAdd(
                            result: false,
                            severity: "FAIL",
                            path: $representation->path() . "-$segmentIndex",
                            pass_message: "",
                            fail_message: " EmsgBox with time " .
                              $expected->presentationTime . " not repeated",
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

                $this->emsgCase->pathAdd(
                    result: true,
                    severity: "FAIL",
                    path: $representation->path() . "-$segmentIndex",
                    pass_message: " EmsgBox with time " .
                      $emsgBoxes[$emsgNum]->presentationTime . " repeated",
                    fail_message: "",
                );

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
