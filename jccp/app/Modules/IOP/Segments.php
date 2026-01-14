<?php

namespace App\Modules\IOP;

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
use App\Modules\IOP\Segments\AVC;
use App\Modules\IOP\Segments\HEVC;
use App\Modules\IOP\Segments\Video;
use App\Modules\IOP\Segments\OnDemand;
use App\Modules\IOP\Segments\CrossAdaptation;

class Segments extends Module
{
    public function __construct()
    {
        parent::__construct("IOP Segments");
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
        new CrossAdaptation()->withAdaptationSet($adaptationSet);
    }

    /**
     * @param array<Segment> $segments
     **/
    ///NOTE: Removed checks that are dependent on ISOSegmentValidator error output in this commit
    public function validateSegments(Representation $representation, array $segments): void
    {
        new OnDemand()->validateOnDemand($representation, $segments);
        foreach ($segments as $segmentIndex => $segment) {
            if ($segmentIndex == 0) {
                $this->validateInitialization($representation, $segment);
            }
            $this->validateSegment($representation, $segment, $segmentIndex);
        }
    }

    private function validateInitialization(Representation $representation, Segment $segment): void
    {
        new AVC()->withInitSegment($representation, $segment);
        new HEVC()->withInitSegment($representation, $segment);
        new Video()->withInitSegment($representation, $segment);
    }

    private function validateSegment(Representation $representation, Segment $segment, int $segmentIndex): void
    {
    }
}
