<?php

namespace App\Modules\DVB;

use App\Services\ModuleLogger;
use App\Services\MPDCache;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Interfaces\Module;
use App\Modules\DVB\TLSBitrate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MPD extends Module
{
    private SubReporter $v141reporter;
    private SubReporter $legacyreporter;

    public function __construct()
    {
        parent::__construct();
        $this->name = "DASH-IF IOP Conformance";

        $reporter = app(ModuleReporter::class);

        $this->legacyreporter = &$reporter->context(new ReporterContext(
            "MPD",
            "DVB",
            "LEGACY",
            []
        ));
        $this->v141reporter = &$reporter->context(new ReporterContext(
            "MPD",
            "DVB",
            "v1.4.1",
            ["document" => "ETSI TS 103 285"]
        ));
    }

    public function validateMPD(): void
    {
        parent::validateMPD();
        $mpdCache = app(MPDCache::class);

        ///TODO Make this a remember function
        $resolved = Cache::get(cache_path(['mpd', 'resolved']));

        $this->v141reporter->test(
            "Section 4.5",
            "The MPD size after xlink resolution SHALL NOT exceed 256 Kbytes",
            $resolved && strlen($resolved) <= 1024 * 256,
            "FAIL",
            "MPD Size in bounds",
            ($resolved ? "MPD too large" : "No resolved MPD found")
        );

        $minimumUpdatePeriod = $mpdCache->getAttribute('minimumUpdatePeriod');

        $this->legacyreporter->test(
            "Unknown",
            "MPD@minimumUpdatePeriod SHOULD have a value of 1 second or higher",
            ($minimumUpdatePeriod != '' && timeParsing($minimumUpdatePeriod) < 1),
            "WARN",
            "Check succeeded",
            "Check failed"
        );

        new TLSBitrate()->validateTLSBitrate();
    }
}
