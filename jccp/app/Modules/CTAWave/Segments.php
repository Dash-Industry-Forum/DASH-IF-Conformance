<?php

namespace App\Modules\CTAWave;

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
//Module checks
use App\Modules\CTAWave\Segments\SplicingPoints;
use App\Modules\CTAWave\Segments\VideoProfile;
use App\Modules\CTAWave\Segments\AudioProfile;

class Segments extends Module
{
    public function __construct()
    {
        parent::__construct();
        $this->name = "CTAWave Segments Module";
    }


    public function validateMPD(): void
    {
        parent::validateMPD();
    }

    public function validateMultiPeriod(Period $firstPeriod, Period $secondPeriod): void
    {
        new SplicingPoints()->validateSplicingPoints($firstPeriod, $secondPeriod);
    }

    public function validateCrossAdaptationSet(AdaptationSet $adaptationSet): void
    {
        new VideoProfile()->validateVideoProfile($adaptationSet);
        new AudioProfile()->validateAudioProfile($adaptationSet);
    }

    /**
     * @param array<Segment> $segments
     **/
    public function validateSegments(Representation $representation, array $segments): void
    {
        foreach ($segments as $segmentIndex => $segment) {
            if ($segmentIndex == 0) {
                $this->validateInitialization($representation, $segment);
            }
            $this->validateSegment($representation, $segment, $segmentIndex);
        }
    }

    private function validateInitialization(Representation $representation, Segment $segment): void
    {
    }

    private function validateSegment(Representation $representation, Segment $segment, int $segmentIndex): void
    {
    }
}
