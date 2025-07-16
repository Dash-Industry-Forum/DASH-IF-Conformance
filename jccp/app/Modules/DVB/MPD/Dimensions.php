<?php

namespace App\Modules\DVB\MPD;

use App\Services\MPDCache;
use App\Services\Manifest\Period;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class Dimensions
{
    //Private subreporters
    private SubReporter $v141reporter;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->v141reporter = &$reporter->context(new ReporterContext(
            "MPD",
            "DVB",
            "v1.4.1",
            ["document" => "ETSI TS 103 285"]
        ));
    }

    //Public validation functions
    public function validateDimensions(): void
    {
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
    }

    //Private helper functions
}
