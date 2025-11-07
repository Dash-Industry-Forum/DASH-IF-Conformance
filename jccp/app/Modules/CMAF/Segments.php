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
## Segment checks
use App\Modules\CMAF\Segments\CompositionTimes;
use App\Modules\CMAF\Segments\MetaData;
use App\Modules\CMAF\Segments\DataOffsets;
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
        parent::__construct();
        $this->name = "CMAF Segments Module";
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
        //NOTE: Removed some comparison checks as we download only a subset of the fragments
        //TODO: Re-implement identical boxes checks from this commit
        new VideoMediaProfile()->validateVideoMediaProfiles($adaptationSet);
        new AudioMediaProfile()->validateAudioMediaProfiles($adaptationSet);
        new SubtitleMediaProfile()->validateSubtitleMediaProfiles($adaptationSet);
        new HEVCComparison()->validateHEVC($adaptationSet);
    }

    /**
     * @param array<Segment> $segments
     **/
    public function validateSegments(Representation $representation, array $segments): void
    {
        new Durations()->validateDurations($representation, $segments);
        foreach ($segments as $segmentIndex => $segment) {
            if ($segmentIndex == 0) {
                $this->validateInitialization($representation, $segment);
            }
            $this->validateSegment($representation, $segment, $segmentIndex);
        }
    }

    private function validateInitialization(Representation $representation, Segment $segment): void
    {
        new Initialization()->validateInitialization($representation, $segment);
    }

    private function validateSegment(Representation $representation, Segment $segment, int $segmentIndex): void
    {
        new CompositionTimes()->validateCompositionTimes($representation, $segment, $segmentIndex);
        new MetaData()->validateMetaData($representation, $segment, $segmentIndex);
        new SegmentIndex()->validateSegmentIndex($representation, $segment, $segmentIndex);
        new EncryptionProfile()->validateEncryptionProfile($representation, $segment, $segmentIndex);
        new Subtitles()->validateSubtitleSegment($representation, $segment, $segmentIndex);
        new DataOffsets()->validateDataOffsets($representation, $segment, $segmentIndex);
    }
}
