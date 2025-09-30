<?php

namespace App\Modules\Wave;

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
use App\Modules\Wave\Segments\TextComponentConstraints;
use App\Modules\Wave\Segments\Bitrate;
use App\Modules\Wave\Segments\EncryptionScheme;
use App\Modules\Wave\Segments\SegmentEncryption;
use App\Modules\Wave\Segments\SplicingPoints;
use App\Modules\Wave\Segments\TrackRoles;
use App\Modules\Wave\Segments\TimedEventData;
use App\Modules\Wave\Segments\AddressableMediaObject;
use App\Modules\Wave\Segments\KeyRotation;

class Segments extends Module
{
    private SplicingPoints $splicingValidator;

    public function __construct()
    {
        parent::__construct();
        $this->name = "Wave HLS Interop Segments Module";

        $this->splicingValidator = new SplicingPoints();
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
        $this->splicingValidator->validateSegmentDurations($representation, $segments);
        new Bitrate()->validateBitrate($representation, $segments);
        new TimedEventData()->validateTimedEventdata($representation, $segments);
        foreach ($segments as $segmentIndex => $segment) {
            Log::info($segmentIndex . " " . $representation->path());
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
        $this->splicingValidator->validateSplicingPoints($representation, $segment, $segmentIndex);
        new AddressableMediaObject()->validateAddressableMediaObject($representation, $segment);
        new KeyRotation()->validateKeyRotation($representation, $segment, $segmentIndex);
    }
}
