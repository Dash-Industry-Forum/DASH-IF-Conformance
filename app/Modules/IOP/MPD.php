<?php

namespace App\Modules\IOP;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\ModuleLogger;
use App\Services\MPDCache;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Reporter\TestCase;
use App\Services\Manifest\Representation;
use App\Interfaces\Module;
use App\Services\Segment;
//Module checks
use App\Modules\IOP\MPD\Common;
use App\Modules\IOP\MPD\Live;
use App\Modules\IOP\MPD\OnDemand;
use App\Modules\IOP\MPD\MixedOnDemand;

class MPD extends Module
{
    public function __construct()
    {
        parent::__construct("IOP MPD");
    }

    public function validateMPD(): void
    {
        parent::validateMPD();

        new Common()->validateCommon();
        new Live()->validateLive();
        new OnDemand()->validateOnDemand();
        new MixedOnDemand()->validateMixed();
    }

    /**
     * @param array<Segment> $segments
     **/
    public function validateSegments(Representation $representation, array $segments): void
    {
    }
}
