<?php

namespace App\Modules\Common;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Cache;
use App\Interfaces\Module;
use App\Services\MPDCache;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Reporter\TestCase;
use Illuminate\Support\Facades\Log;
//Module checks
use App\Modules\Common\MPD\Schematron;
use App\Modules\Common\MPD\XSDValidation;

class MPD extends Module
{
    public function __construct()
    {
        parent::__construct("Global Module");
    }

    public function validateMPD(): void
    {
        parent::validateMPD();

        new Schematron()->validateSchematron();
        new XSDValidation()->validateXSD();
    }
}
