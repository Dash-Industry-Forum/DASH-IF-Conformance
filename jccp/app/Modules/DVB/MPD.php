<?php

namespace App\Modules\DVB;

use App\Services\ModuleLogger;
use App\Services\MPDCache;
use App\Services\ModuleReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MPD extends Module
{
    public function __construct()
    {
        parent::__construct();
        $this->name = "DASH-IF IOP Conformance";
    }

    public function validateMPD(): void
    {
        parent::validateMPD();
        $logger = app(ModuleLogger::class);
        $reporter = app(ModuleReporter::class);

        $mpdCache = app(MPDCache::class);

        $resolved = Cache::get(cache_path(['mpd', 'resolved']));

        $v141context = new ReporterContext("MPD", "DVB", "v1.4.1", ["document" => "ETSI TS 103 285"]);
        $v141reporter = &$reporter->context($v141context);

        $v141reporter->test(
            "Section 4.5",
            "The MPD size after xlink resolution SHALL NOT exceed 256 Kbytes",
            $resolved && strlen($resolved) <= 1024 * 256,
            "FAIL",
            "MPD Size in bounds",
            ($resolved ? "MPD too large" : "No resolved MPD found")
        );

        $logger->test(
            "DVB",
            "Section 4.5",
            "The MPD size after xlink resolution SHALL NOT exceed 256 Kbytes",
            $resolved && strlen($resolved) <= 1024 * 256,
            "FAIL",
            "MPD Size in bounds",
            ($resolved ? "MPD too large" : "No resolved MPD found")
        );
    }
}
