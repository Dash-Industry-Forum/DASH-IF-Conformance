<?php

namespace App\Modules\DVB;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\ModuleLogger;
use App\Services\MPDCache;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Interfaces\Module;
use App\Modules\DVB\MPD\TLSBitrate;
use App\Modules\DVB\MPD\Dimensions;
use App\Modules\DVB\MPD\Profiles;
use App\Modules\DVB\MPD\UTCTiming;
use App\Modules\DVB\MPD\PeriodConstraints;

class MPD extends Module
{
    private SubReporter $legacyreporter;

    public function __construct()
    {
        parent::__construct();
        $this->name = "DASH-IF IOP Conformance";

        $reporter = app(ModuleReporter::class);

        $this->legacyreporter = &$reporter->context(new ReporterContext(
            "MPD",
            "DVB",
            "LEGACY",
            []
        ));
    }

    public function validateMPD(): void
    {
        parent::validateMPD();
        $mpdCache = app(MPDCache::class);

        $minimumUpdatePeriod = $mpdCache->getAttribute('minimumUpdatePeriod');

        $this->legacyreporter->test(
            "Unknown",
            "MPD@minimumUpdatePeriod SHOULD have a value of 1 second or higher",
            ($minimumUpdatePeriod != '' && timeParsing($minimumUpdatePeriod) < 1),
            "WARN",
            "Check succeeded",
            "Check failed"
        );

        new Profiles()->validateProfiles();
        new Dimensions()->validateDimensions();
        new TLSBitrate()->validateTLSBitrate();
        new UTCTiming()->validateUTCTimingElement();
        new PeriodConstraints()->validatePeriodConstraints();
    }
}
