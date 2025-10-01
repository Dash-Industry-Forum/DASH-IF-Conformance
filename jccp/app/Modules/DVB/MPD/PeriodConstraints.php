<?php

namespace App\Modules\DVB\MPD;

use App\Services\MPDCache;
use App\Services\Manifest\Period;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PeriodConstraints
{
    //Private subreporters
    private SubReporter $v141reporter;

    private TestCase $segmentListCase;
    private TestCase $segmentTemplateCase;
    private TestCase $liveXorVodCase;
    private TestCase $mainVideoCase;
    private TestCase $mainAudioCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->v141reporter = &$reporter->context(new ReporterContext(
            "MPD",
            "DVB",
            "v1.4.1",
            ["document" => "ETSI TS 103 285"]
        ));

        $this->segmentListCase = $this->v141reporter->add(
            section: "Section 4.2.2",
            test: "'The Period.SegmentList element SHALL NOT be present'",
            skipReason: "No period found in MPD"
        );
        $this->segmentTemplateCase = $this->v141reporter->add(
            section: "Section 4.2.2",
            test: "'The Period.SegmentTemplate element SHALL NOT be present'",
            skipReason: "No on-demand period found in MPD"
        );
        $this->liveXorVodCase = $this->v141reporter->add(
            section: "Section 4.2.2",
            test: "Each Period element shall conform to either 4.2.4 or 4.2.6",
            skipReason: "No period found in MPD"
        );
        $this->mainVideoCase = $this->v141reporter->add(
            section: "Section 4.2.2",
            test: "At least one Adaptation Set SHALL cary a 'main' role",
            skipReason: "No video adaptationsets found found in MPD"
        );
        $this->mainAudioCase = $this->v141reporter->add(
            section: "Section 6.1.2",
            test: "At least one Adaptation Set SHALL cary a 'main' role",
            skipReason: "No audio adaptationsets found found in MPD"
        );
    }

    //Public validation functions
    public function validatePeriodConstraints(): void
    {
        $mpdCache = app(MPDCache::class);
        foreach ($mpdCache->allPeriods() as $period) {
            $this->validateSinglePeriod($period);
        }
    }

    //Private helper functions
    private function validateSinglePeriod(Period $period): void
    {
        $this->segmentListCase->pathAdd(
            path: $period->path(),
            result: count($period->getDOMElements('SegmentList')) == 0,
            severity: "FAIL",
            pass_message: "SegmentList not present",
            fail_message: "SegmentList found"
        );

        $this->validateLiveXorOnDemand($period);
        $this->validateMainVideo($period);
        $this->validateMainAudio($period);
    }

    private function validateLiveXorOnDemand(Period $period): void
    {
        $isLive =  $period->hasProfile("urn:dvb:dash:profile:dvb-dash:isoff-ext-live:2014");
        $isOnDemand = $period->hasProfile("urn:dvb:dash:profile:dvb-dash:isoff-ext-on-demand:2014");


        $this->liveXorVodCase->pathAdd(
            path: $period->path(),
            result: $isLive xor $isOnDemand,
            severity: "FAIL",
            pass_message: "Either of the profiles signalled",
            fail_message: "Neither or both profiles signalled"
        );

        if ($isOnDemand) {
            $this->segmentTemplateCase->pathAdd(
                path: $period->path(),
                result: count($period->getDOMElements('SegmentTemplate')) == 0,
                severity: "FAIL",
                pass_message: "SegmentTemplate not present",
                fail_message: "SegmentTemplate found"
            );
        }
    }

    private function validateMainVideo(Period $period): void
    {
        $videoCount = 0;
        $foundMain = false;

        foreach ($period->allAdaptationSets() as $adaptationSet) {
            if ($adaptationSet->getAttribute('contentType') != 'video') {
                continue;
            }
            $videoCount++;
            foreach ($adaptationSet->getDOMElements('Role') as $role) {
                if (
                    $role->getAttribute('schemeIdUri') == 'urn:mpeg:dash:role:2011' &&
                    $role->getAttribute('value') == 'main'
                ) {
                    $foundMain = true;
                    break;
                }
            }
            if ($foundMain) {
                break;
            }
        }

        $this->mainVideoCase->pathAdd(
            path: $period->path(),
            result: $videoCount < 2 ||  $foundMain,
            severity: "FAIL",
            pass_message: $foundMain ? "Main role found" : "Single video adaptation found",
            fail_message: "Multiple video adaptations, but none of them carries the 'main' role",
        );
    }

    /**
     * @todo Find out what the definition of 'DASH presentation' should be. Uses Period for now.
     **/
    private function validateMainAudio(Period $period): void
    {
        $audioCount = 0;
        $foundMain = false;

        foreach ($period->allAdaptationSets() as $adaptationSet) {
            if ($adaptationSet->getAttribute('contentType') != 'audio') {
                continue;
            }
            $audioCount++;
            foreach ($adaptationSet->getDOMElements('Role') as $role) {
                if (
                    $role->getAttribute('schemeIdUri') == 'urn:mpeg:dash:role:2011' &&
                    $role->getAttribute('value') == 'main'
                ) {
                    $foundMain = true;
                    break;
                }
            }
            if ($foundMain) {
                break;
            }
        }

        $this->mainAudioCase->pathAdd(
            path: $period->path(),
            result: $audioCount < 2 ||  $foundMain,
            severity: "FAIL",
            pass_message: $foundMain ? "Main role found" : "Single audio adaptation found",
            fail_message: "Multiple audio adaptations, but none of them carries the 'main' role",
        );
    }
}
