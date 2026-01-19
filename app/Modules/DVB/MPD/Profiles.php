<?php

namespace App\Modules\DVB\MPD;

use App\Services\MPDCache;
use App\Services\Manifest\Period;
use App\Services\Manifest\ProfileSpecificMPD;
use App\Services\ModuleReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class Profiles
{
    //Private subreporters
    private SubReporter $v141reporter;

    private TestCase $profileCase;
    private TestCase $subProfileCase;
    private TestCase $segmentCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->v141reporter = &$reporter->context(new ReporterContext(
            "MPD",
            "DVB",
            "v1.4.1",
            ["document" => "ETSI TS 103 285"]
        ));

        $this->profileCase = $this->v141reporter->add(
            section: "Section 11.1",
            test: "Each Representation @profile SHALL include at least one of those in section 4.1",
            skipReason: "No representations found"
        );
        $this->subProfileCase = $this->v141reporter->add(
            section: "Section 11.1",
            test: "Each representation @profile SHALL include either section 4.2.5 or section 4.2.8",
            skipReason: "No representations found"
        );
        $this->segmentCase = $this->v141reporter->add(
            section: "Section 11.1",
            test: "Each representation shall have only one segment",
            skipReason: "No on-demand representations found"
        );
    }

    //Public validation functions
    public function validateProfiles(): void
    {
        $mpdCache = app(MPDCache::class);

        $dvbDash2014MPD = $mpdCache->profileSpecificMPD("urn:dvb:dash:profile:dvb-dash:2014");
        $dvbDash2017MPD = $mpdCache->profileSpecificMPD("urn:dvb:dash:profile:dvb-dash:2017");

        $this->profileCase->pathAdd(
            path: "MPD",
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

            $this->subProfileCase->pathAdd(
                path: $representation->path(),
                result: $onDemandProfile xor $liveProfile,
                severity: "FAIL",
                pass_message: "One subprofile detected",
                fail_message: "Neither or both subprofile(s) detected"
            );

            if ($onDemandProfile) {
                $this->segmentCase->pathAdd(
                    path: $representation->path(),
                    result: $representation->initializationUrl() === null && count($representation->segmentUrls()) == 1,
                    severity: "FAIL",
                    pass_message: "Single segment found",
                    fail_message: "Multiple segments found",
                );
            }
        }
    }
}
