<?php

namespace App\Modules\HbbTV;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\ModuleLogger;
use App\Services\MPDCache;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Interfaces\Module;
use App\Services\Manifest\Representation;
use App\Services\Segment;
//Module checks
use App\Modules\HbbTV\Segments\Dependencies;
use App\Modules\HbbTV\Segments\LegacyCodecs;

class Segments extends Module
{
    public function __construct()
    {
        parent::__construct("HbbTV Segments");
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
            $this->validateSegment($representation, $segment, $segmentIndex);
        }
    }

    private function validateInitialization(Representation $representation, Segment $segment): void
    {
        new Dependencies()->validateDependencies($representation, $segment);
        new LegacyCodecs()->validateCodecs($representation, $segment);
    }

    private function validateSegment(Representation $representation, Segment $segment, int $segmentIndex): void
    {
    }
}
