<?php

namespace App\Modules\Common;

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

use App\Modules\Common\Segments\ErrorMessages;

class Segments extends Module
{
    public function __construct()
    {
        parent::__construct("Global Segments");
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
            new ErrorMessages()->withSegment($representation, $segment, $segmentIndex);
        }
    }

}
