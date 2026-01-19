<?php

namespace App\Modules\IOP\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Services\Manifest\AdaptationSet;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes\DescriptionType;
use App\Interfaces\ModuleComponents\InitSegmentComponent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class Video extends InitSegmentComponent
{
    private TestCase $editListCase;
    private TestCase $timingCase;

    public function __construct()
    {
        parent::__construct(
            self::class,
            new ReporterContext(
                "Segments",
                "DASH-IF IOP",
                "4.3",
                []
            )
        );

        $this->editListCase = $this->reporter->add(
            section: '6.2.5.2',
            test: "Edit lists SHALL NOT be present unless On-Demand profile is signalled",
            skipReason: "@bitstreamSwitching flag not set, or no video representation found",
        );
        $this->timingCase = $this->reporter->add(
            section: '6.2.5.2',
            test: "The composition time and decoded time for the first sample SHALL match",
            skipReason: "@bitstreamSwitching flag not set, or no video representation found",
        );
    }

    //Public validation functions
    public function validateInitSegment(Representation $representation, Segment $segment): void
    {
        if ($representation->getTransientAttribute('bitstreamSwitching') == '') {
            return;
        }

        $codecs = $representation->getTransientAttribute('codecs');
        if (
            strpos($codecs, 'hvc') === false &&
            strpos($codecs, 'hev') === false &&
            strpos($codecs, 'avc') === false
        ) {
            return;
        }

        $isOndemand = $representation->hasProfile('http://dashif.org/guidelines/dash-if-ondemand');
        if (!$isOndemand) {
            $isOndemand = $representation->hasProfile('http://dashif.org/guidelines/dash') &&
                $representation->hasProfile('urn:mpeg:dash:profile:isoff-on-demand:2011');
        }

        $editLists = $segment->boxAccess()->elst();

        $this->editListCase->pathAdd(
            path: $representation->path() . "-init",
            result: count($editLists) == 0 || $isOndemand,
            severity: "FAIL",
            pass_message: "No edit lists found, or on-demand profile signalled",
            fail_message: "Edit lists found, but no on-demand profile signalled",
        );

        $this->validateTiming($representation, $segment);
    }

    //Private helper functions
    private function validateTiming(Representation $representation, Segment $segment): void
    {
        $trun = $segment->boxAccess()->trun();
        $tfdt = $segment->boxAccess()->tfdt();

        $compositionTime = count($trun) ? $trun[0]->earliestCompositionTime : '';
        $decodeTime = count($tfdt) ? $tfdt[0]->decodeTime : '';


        $this->timingCase->pathAdd(
            path: $representation->path() . "-init",
            result: $compositionTime != '' && $compositionTime == $decodeTime,
            severity: "FAIL",
            pass_message: "Timings match",
            fail_message: "Timings mismatched or invalid",
        );
    }
}
