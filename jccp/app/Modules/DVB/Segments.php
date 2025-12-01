<?php

namespace App\Modules\DVB;

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
use App\Modules\DVB\Segments\LegacyCodecs;
use App\Modules\DVB\Segments\Codecs;
use App\Modules\DVB\Segments\CrossCodecs;
use App\Modules\DVB\Segments\BoxCount;
use App\Modules\DVB\Segments\Durations;
use App\Modules\DVB\Segments\SAPTypes;
use App\Modules\DVB\Segments\Subtitle;
use App\Modules\DVB\Segments\ContentProtection;
use App\Modules\DVB\Segments\ContinuousPeriods;
use App\Modules\DVB\Segments\SwitchableRepresentation;
use App\Modules\DVB\Segments\BitStream;
use App\Modules\DVB\Segments\AVS3BitStream;
use App\Modules\DVB\Segments\CrossAudio;
use App\Modules\DVB\Segments\SEILocations;

class Segments extends Module
{
    public function __construct()
    {
        parent::__construct();
        $this->name = "DVB Segments Module";
    }

    public function validateMPD(): void
    {
        parent::validateMPD();
    }

    public function validateMultiPeriod(Period $firstPeriod, Period $secondPeriod): void
    {
        new ContinuousPeriods()->validateContinuity($firstPeriod, $secondPeriod);
    }

    public function validateCrossAdaptationSet(AdaptationSet $adaptationSet): void
    {
        new ContentProtection()->validateContentProtection($adaptationSet);
        new SwitchableRepresentation()->validateSwitchableRepresentations($adaptationSet);
    }

    /**
     * @param array<Segment> $segments
     **/
    public function validateSegments(Representation $representation, array $segments): void
    {
        //NOTE: Removed legacy self-consistency checks in this commit, as they were disabled to begin with.
        //TODO: Re-implent segment vs mpd timing checks in this commit
        foreach ($segments as $segmentIndex => $segment) {
            if ($segmentIndex == 0) {
                $this->validateInitialization($representation, $segment);
            }
            $this->validateSegment($representation, $segment, $segmentIndex);
        }
    }

    private function validateInitialization(Representation $representation, Segment $segment): void
    {
        new LegacyCodecs()->validateCodecs($representation, $segment);
        new Codecs()->validateCodecs($representation, $segment);
        new BitStream()->validateBitStream($representation, $segment);
        new AVS3BitStream()->validateBitStream($representation, $segment);
        new CrossCodecs()->validateCodec($representation, $segment);
        new CrossAudio()->validateAudioParameters($representation, $segment);
    }

    private function validateSegment(Representation $representation, Segment $segment, int $segmentIndex): void
    {
        new BoxCount()->validateBoxCount($representation, $segment, $segmentIndex);
        new Durations()->validateDurations($representation, $segment, $segmentIndex);
        new SAPTypes()->validateSAPTypes($representation, $segment, $segmentIndex);
        new Subtitle()->validateSubtitles($representation, $segment, $segmentIndex);
        new SEILocations()->validateSEILocations($representation, $segment, $segmentIndex);
    }
}
