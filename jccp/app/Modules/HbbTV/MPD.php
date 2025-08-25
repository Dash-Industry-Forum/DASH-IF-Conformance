<?php

namespace App\Modules\HbbTV;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\ModuleLogger;
use App\Services\MPDCache;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Interfaces\Module;

//Module checks

class MPD extends Module
{
    private SubReporter $legacyreporter;

    public function __construct()
    {
        parent::__construct();
        $this->name = "HbbTV MPD Module";
    }

    public function validateMPD(): void
    {
        parent::validateMPD();

        $this->legacyreporter = &app(ModuleReporter::class)->context(new ReporterContext(
            "MPD",
            "HbbTV",
            "LEGACY",
            []
        ));

        $mpdCache = app(MPDCache::class);

        $minimumUpdatePeriod = $mpdCache->getAttribute('minimumUpdatePeriod');

        $this->legacyreporter->test(
            section: "Unkown",
            test: "MPD@minimumUpdatePeriod SHOULD have a value of 1 second or higher",
            result: ($minimumUpdatePeriod != '' && timeParsing($minimumUpdatePeriod) < 1),
            severity: "WARN",
            pass_message: "Check succeeded",
            fail_message: "Check failed"
        );
    }
}
