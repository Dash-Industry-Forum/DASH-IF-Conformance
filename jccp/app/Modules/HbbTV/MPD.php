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

        $reporter = app(ModuleReporter::class);
        $this->legacyreporter = $reporter->context(new ReporterContext(
            "MPD",
            "LEGACY",
            "HbbTV",
            []
        ));

        $this->legacyreporter->dependencyAdd(
            section: "Unknown",
            test: "Inherit DVB legacy checks",
            dependentModule: "DVB MPD Module",
            dependentSpec: "LEGACY - DVB",
            dependentSection: "Unknown"
        );
    }
}
