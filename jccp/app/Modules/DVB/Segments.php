<?php

namespace App\Modules\DVB;

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
## Segment checks
use App\Modules\DVB\Segments\Codecs;
use App\Modules\DVB\Segments\BoxCount;
use App\Modules\DVB\Segments\Durations;

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

    /**
     * @param array<Segment> $segments
     **/
    public function validateSegments(Representation $representation, array $segments): void
    {
        foreach ($segments as $segmentIndex => $segment) {
            if ($segmentIndex == 0) {
                $this->validateInitialization($representation, $segment);
            }
            $this->validateSegment($representation, $segment);
        }
    }

    private function validateInitialization(Representation $representation, Segment $segment): void
    {
        new Codecs()->validateCodecs($representation, $segment);
    }

    private function validateSegment(Representation $representation, Segment $segment): void
    {
        new BoxCount()->validateBoxCount($representation, $segment);
        new Durations()->validateDurations($representation, $segment);
    }
}
