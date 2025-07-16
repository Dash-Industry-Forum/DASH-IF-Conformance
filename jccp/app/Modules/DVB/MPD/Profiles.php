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

class Profiles
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
    public function validateProfiles(): void
    {
        $mpdCache = app(MPDCache::class);

        //These checks can be done on the MPD, as representation
        //profiles are required to be a subset of the ones in the MPD.
        $this->v141reporter->test(
            section: "Section 11.1",
            test: "All Representations [...] should be such that they will be inferred to have an " .
                  "@profiles attribute that includes one or more of the profile names defined in " .
                  "clause 4.1",
            result: $mpdCache->hasProfile("urn:dvb:dash:profile:dvb-dash:2014") ||
                    $mpdCache->hasProfile("urn:dvb:dash:profile:dvb-dash:2017"),
            severity: "FAIL",
            pass_message: "At least one profile detected",
            fail_message: "Neither profile detected"
        );

        $this->v141reporter->test(
            section: "Section 11.1",
            test: "All Representations [...] should be such that they will be inferred to have an " .
            "@profiles attribute that includes either the one defined in clause 4.2.5 or the " .
            "one defined in clause 4.2.8",
            result: $mpdCache->hasProfile("urn:dvb:dash:profile:dvb-dash:isoff-ext-live:2014") xor
                    $mpdCache->hasProfile("urn:dvb:dash:profile:dvb-dash:isoff-ext-on-demand:2014"),
            severity: "FAIL",
            pass_message: "One profile detected",
            fail_message: "Neither or both profile(s) detected"
        );
    }

    //Private helper functions
}
