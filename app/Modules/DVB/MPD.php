<?php

namespace App\Modules\DVB;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\ModuleLogger;
use App\Services\MPDCache;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Reporter\TestCase;
use App\Services\Manifest\Representation;
use App\Interfaces\Module;
use App\Services\Segment;
//Module checks
use App\Modules\DVB\MPD\TLSBitrate;
use App\Modules\DVB\MPD\Dimensions;
use App\Modules\DVB\MPD\Profiles;
use App\Modules\DVB\MPD\UTCTiming;
use App\Modules\DVB\MPD\PeriodConstraints;
use App\Modules\DVB\MPD\MetricReporting;
use App\Modules\DVB\MPD\VideoChecks;
use App\Modules\DVB\MPD\AudioChecks;
use App\Modules\DVB\MPD\EventChecks;
use App\Modules\DVB\MPD\SCTEChecks;
use App\Modules\DVB\MPD\SubtitleChecks;
use App\Modules\DVB\MPD\BandwidthChecks;
use App\Modules\DVB\MPD\ContentProtectionChecks;
use App\Modules\DVB\MPD\Resolution;
use App\Modules\DVB\MPD\Codecs;
use App\Modules\DVB\MPD\UpdateConstraints;

class MPD extends Module
{
    private TestCase $minimumUpdateCase;

    public function __construct()
    {
        parent::__construct("DVB MPD");
    }

    public function validateMPD(): void
    {
        parent::validateMPD();


        $mpdCache = app(MPDCache::class);

        $minimumUpdatePeriod = $mpdCache->getAttribute('minimumUpdatePeriod');

        $reporter = app(ModuleReporter::class);
        $this->minimumUpdateCase = $reporter->context(new ReporterContext(
            "MPD",
            "LEGACY",
            "DVB",
            []
        ))->add(
            section: "Unknown",
            test: "MPD@minimumUpdatePeriod SHOULD have a value of 1 second or higher",
            skipReason: ''
        );
        $this->minimumUpdateCase->add(
            result: ($minimumUpdatePeriod != '' && timeParsing($minimumUpdatePeriod) >= 1),
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
        //TODO: Re-implement player compatibility checks from this commit

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
        new EventChecks()->validateEvents();
        new SCTEChecks()->validateSCTE();
        new UpdateConstraints()->validateUpdateConstraints();

        $resolutionChecker = new Resolution();
        $codecChecker = new Codecs();

        foreach ($mpdCache->allPeriods() as $period) {
            foreach ($period->allAdaptationSets() as $adaptationSet) {
                foreach ($adaptationSet->allRepresentations() as $representation) {
                    $resolutionChecker->validateResolution($representation);
                    $codecChecker->validateCodecs($representation);
                }
            }
        }


        //TODO Move font checks to validateSubtitles() only!
    }

    /**
     * @param array<Segment> $segments
     **/
    public function validateSegments(Representation $representation, array $segments): void
    {
    }
}
