<?php

namespace App\Modules\DVB\MPD;

use App\Services\MPDCache;
use App\Services\Manifest\Period;
use App\Services\Manifest\ProfileSpecificMPD;
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

        $dvbDash2014MPD = $mpdCache->profileSpecificMPD("urn:dvb:dash:profile:dvb-dash:2014");
        $dvbDash2017MPD = $mpdCache->profileSpecificMPD("urn:dvb:dash:profile:dvb-dash:2017");

        $this->v141reporter->test(
            section: "Section 11.1",
            test: "All Representations [...] should be such that they will be inferred to have an " .
                  "@profiles attribute that includes one or more of the profile names defined in " .
                  "clause 4.1",
            result: $dvbDash2014MPD?->isValid() || $dvbDash2017MPD?->isValid(),
            severity: "FAIL",
            pass_message: "At least one profile detected",
            fail_message: "Neither profile detected"
        );

        if ($dvbDash2014MPD?->isValid()) {
            $this->validateSubProfiles($dvbDash2014MPD);
        }
        if ($dvbDash2017MPD?->isValid()) {
            $this->validateSubProfiles($dvbDash2017MPD);
        }
    }

    private function validateSubProfiles(ProfileSpecificMPD $profileSpecificMPD): void
    {
        foreach ($profileSpecificMPD->representations as $representation) {
            $onDemandProfile = $representation->hasProfile("urn:dvb:dash:profile:dvb-dash:isoff-ext-on-demand:2014");
            $liveProfile = $representation->hasProfile("urn:dvb:dash:profile:dvb-dash:isoff-ext-live:2014");

            $this->v141reporter->test(
                section: "Section 11.1",
                test: "All Representations [...] should be such that they will be inferred to have an " .
                "@profiles attribute that includes [...] either the one defined in clause 4.2.5 or the " .
                "one defined in clause 4.2.8",
                result: $onDemandProfile xor $liveProfile,
                severity: "FAIL",
                pass_message: $representation->path() . " One profile detected",
                fail_message: $representation->path() . " Neither or both profile(s) detected"
            );

            if ($onDemandProfile) {
                $this->v141reporter->test(
                    section: "Section 4.3",
                    test: "[Conforming to clause 4.2.6 ...] Each representation shall have only one segment",
                    result: $representation->initializationUrl() === null && count($representation->segmentUrls()) == 1,
                    severity: "FAIL",
                    pass_message: $representation->path() . " A single, self-initialzing segment found",
                    fail_message: $representation->path() . " Either an initializationUrl or multiple segments found",
                );
            }
        }
    }
}
