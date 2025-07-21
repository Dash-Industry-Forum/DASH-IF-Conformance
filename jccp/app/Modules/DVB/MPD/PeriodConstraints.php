<?php

namespace App\Modules\DVB\MPD;

use App\Services\MPDCache;
use App\Services\Manifest\Period;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PeriodConstraints
{
    //Private subreporters
    private SubReporter $v141reporter;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->v141reporter = &$reporter->context(new ReporterContext(
            "MPD",
            "DVB",
            "v1.4.1",
            ["document" => "ETSI TS 103 285"]
        ));
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
        $this->v141reporter->test(
            section: "Section 4.2.2",
            test: "'The Period.SegmentList element SHALL NOT be present'",
            result: count($period->getDOMElements('SegmentList')) == 0,
            severity: "FAIL",
            pass_message: "SegmentList not present in Period " . $period->path(),
            fail_message: "SegmentList found in Period " . $period->path()
        );


        $isLive =  $period->hasProfile("urn:dvb:dash:profile:dvb-dash:isoff-ext-live:2014");
        $isOnDemand = $period->hasProfile("urn:dvb:dash:profile:dvb-dash:isoff-ext-on-demand:2014");


        $this->v141reporter->test(
            section: "Section 4.2.2",
            test: "Each Period element shall conform to either 4.2.4 or 4.2.6",
            result: $isLive xor $isOnDemand,
            severity: "FAIL",
            pass_message: "Either of the profiles signalled in Period " . $period->path(),
            fail_message: "Neither or both profiles signalled in Period " . $period->path(),
        );

        if ($isOnDemand) {
            $this->v141reporter->test(
                section: "Section 4.2.6",
                test: "'The Period.SegmentTemplate element SHALL NOT be present'",
                result: count($period->getDOMElements('SegmentTemplate')) == 0,
                severity: "FAIL",
                pass_message: "SegmentTemplate not present in Period " . $period->path(),
                fail_message: "SegmentTemplate found in Period " . $period->path()
            );
        }
    }
}
