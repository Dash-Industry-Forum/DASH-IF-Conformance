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
//Module checks
use App\Modules\DVB\MPD\TLSBitrate;
use App\Modules\DVB\MPD\Dimensions;
use App\Modules\DVB\MPD\Profiles;
use App\Modules\DVB\MPD\UTCTiming;
use App\Modules\DVB\MPD\PeriodConstraints;
use App\Modules\DVB\MPD\MetricReporting;
use App\Modules\DVB\MPD\VideoChecks;
use App\Modules\DVB\MPD\AudioChecks;
use App\Modules\DVB\MPD\SubtitleChecks;
use App\Modules\DVB\MPD\BandwidthChecks;
use App\Modules\DVB\MPD\ContentProtectionChecks;

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
            section: "Unkown",
            test: "MPD@minimumUpdatePeriod SHOULD have a value of 1 second or higher",
            result: ($minimumUpdatePeriod != '' && timeParsing($minimumUpdatePeriod) < 1),
            severity: "WARN",
            pass_message: "Check succeeded",
            fail_message: "Check failed"
        );

        //NOTE: All 'ContentComponent' checks have been removed, as they're no longer in the spec.
        //NOTE: Removed 'validRelative' checks (e.g. v141 - 11.9.5) as they were both invalid and
        //      incompatible with the spec
        //NOTE: Removed checks for 'Associated' adaptation sets, as everything is declared optional
        //      in v141, as well as described by a different spec
        //NOTE: Removed xlink checks, as they depended on a non-existent global
        //NOTE: Removed checks that were related to profileSpecificMPD, as they did not really check anything.
        //NOTE: Removed anchor keys checks as they are not in v141, but described in a different spec.

        new Profiles()->validateProfiles();
        new Dimensions()->validateDimensions();
        new TLSBitrate()->validateTLSBitrate();
        new UTCTiming()->validateUTCTimingElement();
        new PeriodConstraints()->validatePeriodConstraints();
        new MetricReporting()->validateMetricReporting();
        new VideoChecks()->validateVideo();
        new AudioChecks()->validateAudio();
        new SubtitleChecks()->validateSubtitles();
        new BandwidthChecks()->validateBandwidth();
        new ContentProtectionChecks()->validateContentProtection();

        //TODO Move font checks to validateSubtitles() only!
    }
}
