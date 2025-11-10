<?php

namespace App\Modules\LowLatency;

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
use App\Modules\LowLatency\MPD\ServiceDescription;

class MPD extends Module
{
    private SubReporter $legacyReporter;
    private TestCase $profileCase;


    public function __construct()
    {
        parent::__construct();
        $this->name = "Low Latency MPD Module";
    }

    public function validateMPD(): void
    {
        parent::validateMPD();
        $this->initializeChecks();


        $mpdCache = app(MPDCache::class);

        $this->profileCase->add(
            result: $mpdCache->hasProfile('http://www.dashif.org/guidelines/low-latency-live-v5'),
            severity: "WARN",
            pass_message: "Profile found",
            fail_message: "Profile not found",
        );

        new ServiceDescription()->validateServiceDescription();
    }

    private function initializeChecks(): void
    {
        $reporter = app(ModuleReporter::class);
        $this->legacyReporter =  $reporter->context(new ReporterContext(
            "MPD",
            "LEGACY",
            "Low Latency",
            []
        ));
        $this->profileCase = $this->legacyReporter->add(
            section: "9.X.4.1",
            test: "Media presentation offering Low Latency SHOULD be signalled with " .
                  "'http://www.dashif.org/guidelines/low-latency-live-v5'",
            skipReason: ''
        );
    }

    /**
     * @param array<Segment> $segments
     **/
    public function validateSegments(Representation $representation, array $segments): void
    {
    }
}
