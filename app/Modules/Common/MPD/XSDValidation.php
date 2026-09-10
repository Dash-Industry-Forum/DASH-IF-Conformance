<?php

namespace App\Modules\Common\MPD;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Cache;
use App\Interfaces\Module;
use App\Services\MPDCache;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Reporter\TestCase;
use Illuminate\Support\Facades\Log;

class XSDValidation
{
    //TODO Move to module
    //private string $schemaPath;
    private SubReporter $xsdReporter;
    private SubReporter $legacyReporter;
    //private SubReporter $globalReporter;

    /*
    private TestCase $xlinkCase;
    private TestCase $mpdCase;
     */
    private TestCase $xsdCase;
    private TestCase $xsd2Case;
    private TestCase $xsd4Case;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->xsdReporter = &$reporter->context(new ReporterContext(
            "MPD",
            "Global",
            "XSD",
            []
        ));
        $this->xsdCase = $this->xsdReporter->add(
            section: "DASH 5th",
            test: "MPD SHALL be valid",
            skipReason: ''
        );

        $this->legacyReporter = &$reporter->context(new ReporterContext(
            "MPD",
            "LEGACY",
            "XSD",
            []
        ));
        $this->xsd2Case = $this->legacyReporter->add(
            section: "DASH 2nd (DVB 2018)",
            test: "MPD SHALL be valid",
            skipReason: ''
        );
        $this->xsd4Case = $this->legacyReporter->add(
            section: "DASH 4th-amd1 (DVB 2019)",
            test: "MPD SHALL be valid",
            skipReason: ''
        );
    }

    private function xsdValidation(TestCase &$testCase, string $xsdLocation): void
    {
        libxml_use_internal_errors(true);
        $mpdCache = app(MPDCache::class);
        $domDocument = $mpdCache->getDocument();

        $testCase->add(
            result: true,
            severity: "INFO",
            pass_message: "Used resource: $xsdLocation",
            fail_message: ""
        );

        $validMPD = $domDocument->schemaValidate(resource_path($xsdLocation));
        if ($validMPD) {
            $testCase->add(
                result: true,
                severity: "PASS",
                pass_message: "MPD valid",
                fail_message: ""
            );
        } else {
            foreach (libxml_get_errors() as $error) {
                $testCase->pathAdd(
                    path: "Line: $error->line",
                    result: false,
                    severity: $error->level == LIBXML_ERR_WARNING ? "WARN" : "FAIL",
                    pass_message: "",
                    fail_message: $error->message
                );
            }
        }
        libxml_use_internal_errors(false);
    }

    public function validateXSD(): void
    {
        $this->xsdValidation($this->xsdCase, "xsd/DASH5th.xsd");
        $this->xsdValidation($this->xsd2Case, "xsd/DASH2nd.xsd");
        $this->xsdValidation($this->xsd4Case, "xsd/DASH4th-amd1.xsd");
    }
}
