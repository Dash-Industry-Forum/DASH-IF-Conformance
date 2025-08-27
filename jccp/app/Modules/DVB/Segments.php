<?php

namespace App\Modules\DVB;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\ModuleLogger;
use App\Services\MPDCache;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Interfaces\Module;
//Module checks
use App\Modules\DVB\MPD\TLSBitrate;
use App\Modules\DVB\MPD\Dimensions;
use App\Modules\DVB\MPD\Profiles;
use App\Modules\DVB\MPD\UTCTiming;
use App\Modules\DVB\MPD\PeriodConstraints;
use App\Modules\DVB\MPD\MetricReporting;
use App\Modules\DVB\MPD\VideoChecks;
use App\Modules\DVB\MPD\AudioChecks;
use App\Modules\DVB\MPD\SubtitleChecks;
use App\Modules\DVB\MPD\BandwidthChecks;
use App\Modules\DVB\MPD\ContentProtectionChecks;

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
}
