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
        parent::__construct("Wave HLS Interop Segments");
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
        new SplicingPoints()->withSegmentList($representation, $segments);
        new Bitrate()->withSegmentList($representation, $segments);
        new TimedEventData()->withSegmentList($representation, $segments);
        foreach ($segments as $segmentIndex => $segment) {
            if ($segmentIndex == 0) {
                $this->validateInitialization($representation, $segment);
            }
            $this->validateSegment($representation, $segment, $segmentIndex);
        }
    }

    private function validateInitialization(Representation $representation, Segment $segment): void
    {
        new TextComponentConstraints()->withInitSegment($representation, $segment);
        new EncryptionScheme()->withInitSegment($representation, $segment);
        new TrackRoles()->withInitSegment($representation, $segment);
    }

    private function validateSegment(Representation $representation, Segment $segment, int $segmentIndex): void
    {
        new SegmentEncryption()->withSegment($representation, $segment, $segmentIndex);
        new SplicingPoints()->withSegment($representation, $segment, $segmentIndex);
        new AddressableMediaObject()->withSegment($representation, $segment, $segmentIndex);
        new KeyRotation()->withSegment($representation, $segment, $segmentIndex);
    }
}
