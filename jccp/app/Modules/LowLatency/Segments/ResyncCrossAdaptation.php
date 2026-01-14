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
use App\Interfaces\ModuleComponents\AdaptationComponent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ResyncCrossAdaptation extends AdaptationComponent
{
    private TestCase $resyncPresentCase;
    private TestCase $resyncMarkerValidCase;
    private TestCase $resyncTypeValidCase;
    private TestCase $resyncDurationValidCase;
    private TestCase $qualityRankingCase;

    private int $lowestQualityRanking = 0;

    public function __construct()
    {
        parent::__construct(
            self::class,
            new ReporterContext(
                "Segments",
                "LEGACY",
                "Low Latency",
                []
            )
        );

        $this->resyncPresentCase = $this->reporter->add(
            section: '9.X.4.5',
            test: "Resync element SHALL/SHOULD be present (conditional)",
            skipReason: "No representations found",
        );
        $this->resyncTypeValidCase = $this->reporter->add(
            section: '9.X.4.5',
            test: "Resync element type SHALL be valid",
            skipReason: "No resync element found",
        );
        $this->resyncMarkerValidCase = $this->reporter->add(
            section: '9.X.4.5',
            test: "Resync element @marker SHALL be 'true'",
            skipReason: "No resync element found",
        );
        $this->resyncDurationValidCase = $this->reporter->add(
            section: '9.X.4.5',
            test: "Resync element @dT SHOULD be lower than half the latency, and SHALL not exceed the target latency",
            skipReason: "No resync element found",
        );
        $this->qualityRankingCase = $this->reporter->add(
            section: '9.X.4.5',
            test: "@qualityRanking should be used",
            skipReason: "No representation found",
        );
    }

    //Public validation functions
    public function validateAdaptationSet(AdaptationSet $adaptationSet): void
    {
        $this->lowestQualityRanking = 0;
        $lowestBandwidth = 0;
        $allRepresentations = $adaptationSet->allRepresentations();
        foreach ($allRepresentations as $representation) {
            if ($lowestBandwidth == 0 || $representation->getTransientAttribute('bandwidth') < $lowestBandwidth) {
                $lowestBandwidth = $representation->getTransientAttribute('bandwidth');
            }
            $qualityRanking = $representation->getTransientAttribute('qualityRanking');
            if ($qualityRanking != '' && intval($qualityRanking) > $this->lowestQualityRanking) {
                $this->lowestQualityRanking = intval($qualityRanking);
            }
        }
        foreach ($allRepresentations as $representation) {
            if ($representation->getTransientAttribute('bandwidth') == $lowestBandwidth) {
                $this->validateLowestBandwithResync($representation);
            } else {
                $this->validateOtherBandwithResync($representation);
            }
            $this->validateCommon($representation);
        }
    }

    //Private helper functions
    private function validateLowestBandwithResync(Representation $representation): void
    {
        $resync = $representation->getDOMElements('Resync');

        $this->resyncPresentCase->pathAdd(
            path: $representation->path(),
            result: !empty($resync) && $resync->item(0) !== null,
            severity: "FAIL",
            pass_message: "Resync element present",
            fail_message: "Resync element missing (Lowest bandwidth)",
        );

        if (empty($resync) || $resync->item(0) == null) {
            return;
        }

        $resyncType = $resync->item(0)->getAttribute('type');


        $this->resyncTypeValidCase->pathAdd(
            path: $representation->path(),
            result: $resyncType == '1' || $resyncType == '2',
            severity: "FAIL",
            pass_message: "Resync type valid",
            fail_message: "Resync type invalid (Lowest bandwidth)",
        );

        $qualityRanking = $representation->getTransientAttribute('qualityRanking');
        if ($qualityRanking == '') {
            $this->qualityRankingCase->pathAdd(
                path: $representation->path(),
                result: false,
                severity: "WARN",
                pass_message: "",
                fail_message: "qualityRanking missing (Lowest bandwidth)",
            );
        } else {
            $this->qualityRankingCase->pathAdd(
                path: $representation->path(),
                result: intval($qualityRanking) == $this->lowestQualityRanking,
                severity: "FAIL",
                pass_message: "@qualityRanking valid",
                fail_message: "@qualityRanking invalid (Lowest bandwidth)",
            );
        }
    }

    private function validateOtherBandwithResync(Representation $representation): void
    {
        $resync = $representation->getDOMElements('Resync');

        $this->resyncPresentCase->pathAdd(
            path: $representation->path(),
            result: !empty($resync) && $resync->item(0) !== null,
            severity: "WARN",
            pass_message: "Resync element present",
            fail_message: "Resync element missing (Other bandwidth)",
        );

        if (empty($resync) || $resync->item(0) == null) {
            return;
        }


        $resyncType = $resync->item(0)->getAttribute('type');


        $this->resyncTypeValidCase->pathAdd(
            path: $representation->path(),
            result: $resyncType == '1' || $resyncType == '2' || $resyncType == '3',
            severity: "FAIL",
            pass_message: "Resync type valid",
            fail_message: "Resync type invalid (Other Bandwidth)",
        );

        $qualityRanking = $representation->getTransientAttribute('qualityRanking');
        $this->qualityRankingCase->pathAdd(
            path: $representation->path(),
            result: $qualityRanking != '',
            severity: "WARN",
            pass_message: "@qualityRanking present",
            fail_message: "@qualityRanking missing (Other bandwidth)",
        );
    }

    private function validateCommon(Representation $representation): void
    {
        $resync = $representation->getDOMElements('Resync');
        if (empty($resync) || $resync->item(0) == null) {
            return;
        }

        $resyncMarker = $resync->item(0)->getAttribute('marker');
        $this->resyncMarkerValidCase->pathAdd(
            path: $representation->path(),
            result: $resyncMarker == 'TRUE',
            severity: "FAIL",
            pass_message: "Resync marker valid",
            fail_message: "Resync marker invalid",
        );


        $resyncDuration = intval($resync->item(0)->getAttribute('dT'));

        //Note: Unsure if this is the right way to parse this value
        $targetLatency = $representation->getTransientAttribute('TargetLatency');
        if (!$targetLatency == '') {
            $targetLatency = intval($targetLatency);
        } else {
            $targetLatency = 1;
        }

        $severity = "";
        $failMessage = "";
        if ($resyncDuration > (0.5 * $targetLatency)) {
            $severity = "WARN";
            $failMessage = "Exceeds half the determined target latency ($targetLatency)";
        }
        if ($resyncDuration > $targetLatency) {
            $severity = "FAIL";
            $failMessage = "Exceeds the determined target duration ($targetLatency)";
        }

        $this->resyncDurationValidCase->pathAdd(
            path: $representation->path(),
            result: $severity == "",
            severity: $severity,
            pass_message: "Resync duration valid",
            fail_message: $failMessage,
        );
    }
}
