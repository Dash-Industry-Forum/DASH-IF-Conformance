<?php

namespace App\Modules\CMAF;

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
use Keepsuit\LaravelOpenTelemetry\Facades\Tracer;
## Segment checks
use App\Modules\CMAF\Segments\CompositionTimes;
use App\Modules\CMAF\Segments\MetaData;
use App\Modules\CMAF\Segments\DataOffsets;
use App\Modules\CMAF\Segments\Miscellaneous;
use App\Modules\CMAF\Segments\Subtitles;
use App\Modules\CMAF\Segments\EncryptionProfile;
use App\Modules\CMAF\Segments\SegmentIndex;
use App\Modules\CMAF\Segments\VideoMediaProfile;
use App\Modules\CMAF\Segments\AudioMediaProfile;
use App\Modules\CMAF\Segments\SubtitleMediaProfile;
use App\Modules\CMAF\Segments\HEVCComparison;
use App\Modules\CMAF\Segments\Durations;
use App\Modules\CMAF\Segments\Initialization;

class Segments extends Module
{
    public function __construct()
    {
        parent::__construct("CMAF Segments");
    }

    public function validateMPD(): void
    {
        parent::validateMPD();
    }

    //NOTE: Removed all chekcs w.r.t switching set alignment in this commit

    public function validateMultiPeriod(Period $firstPeriod, Period $secondPeriod): void
    {
    }

    public function validateCrossAdaptationSet(AdaptationSet $adaptationSet): void
    {
        //NOTE: Removed some comparison checks as we download only a subset of the fragments
        //TODO: Re-implement identical boxes checks from this commit
        //TODO: Re-implement earliestPresentationTime and duration checks for tracks
        new VideoMediaProfile()->withAdaptationSet($adaptationSet);
        new AudioMediaProfile()->withAdaptationSet($adaptationSet);
        new SubtitleMediaProfile()->withAdaptationSet($adaptationSet);
        new HEVCComparison()->withAdaptationSet($adaptationSet);
        new Miscellaneous()->withAdaptationSet($adaptationSet);
    }

    /**
     * @param array<Segment> $segments
     **/
    public function validateSegments(Representation $representation, array $segments): void
    {
        //TODO: Re-implement cmaf messages when ISOValidator is re-supported
        new Durations()->withSegmentList($representation, $segments);
        foreach ($segments as $segmentIndex => $segment) {
            if ($segmentIndex == 0) {
                $this->validateInitialization($representation, $segment);
            }
            $this->validateSegment($representation, $segment, $segmentIndex);
        }
    }

    private function validateInitialization(Representation $representation, Segment $segment): void
    {
        new Initialization()->withInitSegment($representation, $segment);
    }

    private function validateSegment(Representation $representation, Segment $segment, int $segmentIndex): void
    {
        new CompositionTimes()->withSegment($representation, $segment, $segmentIndex);
        new MetaData()->withSegment($representation, $segment, $segmentIndex);
        new SegmentIndex()->withSegment($representation, $segment, $segmentIndex);
        new EncryptionProfile()->withSegment($representation, $segment, $segmentIndex);
        new Subtitles()->withSegment($representation, $segment, $segmentIndex);
        new DataOffsets()->withSegment($representation, $segment, $segmentIndex);
    }
}
