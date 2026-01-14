<?php

namespace App\Modules\WaveHLSInterop;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\ModuleLogger;
use App\Services\MPDCache;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Interfaces\Module;
//Module checks
use App\Modules\WaveHLSInterop\Segments\TextComponentConstraints;
use App\Modules\WaveHLSInterop\Segments\Bitrate;
use App\Modules\WaveHLSInterop\Segments\EncryptionScheme;
use App\Modules\WaveHLSInterop\Segments\SegmentEncryption;
use App\Modules\WaveHLSInterop\Segments\SplicingPoints;
use App\Modules\WaveHLSInterop\Segments\TrackRoles;
use App\Modules\WaveHLSInterop\Segments\TimedEventData;
use App\Modules\WaveHLSInterop\Segments\AddressableMediaObject;
use App\Modules\WaveHLSInterop\Segments\KeyRotation;

class Segments extends Module
{
    public function __construct()
    {
        parent::__construct();
        $this->name = "Wave HLS Interop Segments Module";
    }


    public function validateMPD(): void
    {
        parent::validateMPD();
    }

    /**
     * @param array<Segment> $segments
     **/
    public function validateSegments(Representation $representation, array $segments): void
    {
        new SplicingPoints()->validateSegmentDurations($representation, $segments);
        new Bitrate()->validateBitrate($representation, $segments);
        new TimedEventData()->validateTimedEventdata($representation, $segments);
        foreach ($segments as $segmentIndex => $segment) {
            if ($segmentIndex == 0) {
                $this->validateInitialization($representation, $segment);
            }
            $this->validateSegment($representation, $segment, $segmentIndex);
        }
    }

    private function validateInitialization(Representation $representation, Segment $segment): void
    {
        new TextComponentConstraints()->validateTextComponentConstraints($representation, $segment);
        new EncryptionScheme()->validateEncryptionScheme($representation, $segment);
        new TrackRoles()->validateTrackRoles($representation, $segment);
    }

    private function validateSegment(Representation $representation, Segment $segment, int $segmentIndex): void
    {
        new SegmentEncryption()->validateSegmentEncryption($representation, $segment, $segmentIndex);
        new SplicingPoints()->validateSplicingPoints($representation, $segment, $segmentIndex);
        new AddressableMediaObject()->validateAddressableMediaObject($representation, $segment, $segmentIndex);
        new KeyRotation()->validateKeyRotation($representation, $segment, $segmentIndex);
    }
}
