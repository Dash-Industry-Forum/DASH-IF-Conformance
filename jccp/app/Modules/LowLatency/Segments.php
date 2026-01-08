<?php

namespace App\Modules\LowLatency;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\ModuleLogger;
use App\Services\MPDCache;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Manifest\Period;
use App\Services\Manifest\AdaptationSet;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Interfaces\Module;
## Segment checks
use App\Modules\LowLatency\Segments\SegmentOrChunked;
use App\Modules\LowLatency\Segments\ChunkedCrossAdaptation;
use App\Modules\LowLatency\Segments\DASHProfile;
use App\Modules\LowLatency\Segments\SelfInitializingSidx;
use App\Modules\LowLatency\Segments\SegmentTiming;
use App\Modules\LowLatency\Segments\EventMessages;

class Segments extends Module
{
    public function __construct()
    {
        parent::__construct();
        $this->name = "Low Latency Segments Module";
    }

    public function validateMPD(): void
    {
        parent::validateMPD();
    }

    public function validateMultiPeriod(Period $firstPeriod, Period $secondPeriod): void
    {
    }

    public function validateCrossAdaptationSet(AdaptationSet $adaptationSet): void
    {
        new ChunkedCrossAdaptation()->validateChunkedCrossAdaptation($adaptationSet);
    }

    /**
     * @param array<Segment> $segments
     **/
    ///NOTE: Removed checks that are dependent on ISOSegmentValidator error output in this commit
    public function validateSegments(Representation $representation, array $segments): void
    {
        new EventMessages()->validateEventMessages($representation);
        new SegmentOrChunked()->validateSegmentOrChunked($representation, $segments);
        new SelfInitializingSidx()->validateSidx($representation, $segments);
        new SegmentTiming()->validateTimings($representation, $segments);
        foreach ($segments as $segmentIndex => $segment) {
            if ($segmentIndex == 0) {
                $this->validateInitialization($representation, $segment);
            }
            $this->validateSegment($representation, $segment, $segmentIndex);
        }
    }

    private function validateInitialization(Representation $representation, Segment $segment): void
    {
        new DASHProfile()->validateCMAFProfile($representation, $segment);
    }

    private function validateSegment(Representation $representation, Segment $segment, int $segmentIndex): void
    {
    }
}
