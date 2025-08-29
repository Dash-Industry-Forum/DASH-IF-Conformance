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
        foreach ($segments as $segmentIndex => $segment) {
            Log::info($segmentIndex . " " . $representation->path());
            if ($segmentIndex == 0) {
                $this->validateInitialization($representation, $segment);
            }
            $this->validateSegment($segment);
        }
    }

    private function validateInitialization(Representation $representation, Segment $segment): void
    {
        new Bitrate()->validateBitrate($representation, $segment);
        new TextComponentConstraints()->validateTextComponentConstraints($representation, $segment);
    }

    private function validateSegment(Segment $segment): void
    {
    }
}
