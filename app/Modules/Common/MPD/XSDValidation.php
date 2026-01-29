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


    public function validateMPD(): void
    {
        $this->validate();
    }

    public function getValidatorOutput(): string
    {
        if (!Cache::get(cache_path(['validator','output']))) {
            $this->runValidator();
        }
        return Cache::get(cache_path(['validator','output']), '');
    }


    private function runValidator(): void
    {

        $sessionDir = session_dir();

        file_put_contents($sessionDir . "manifest.mpd", app(MPDCache::class)->getMPD());
        return;

        /*
        $mpdXml = simplexml_load_string('<mpdresult><xlink>No Result</xlink>' .
        '<schema>No Result</schema><schematron>No Result</schematron></mpdresult>');
        $mpdXml->asXML($sessionDir . "mpdresult.xml");

        $validatorPath = base_path() . "/schematron";
        $this->findOrDownloadSchema();

        $validatorCommand = implode(" ", [
            "java","-cp",
            "\"${validatorPath}/saxon9he.jar:${validatorPath}xercesImpl.jar:${validatorPath}/bin\"",
            "Validator",
            "\"${sessionDir}manifest.mpd\"",
            "${sessionDir}resolved.xml",
            "${validatorPath}/$this->schemaPath",
            "${sessionDir}mpdresult.xml"
        ]);


        $validatorResult = Process::run($validatorCommand);

        if (!$validatorResult->successful()) {
  $this->globalReporter->add(
                section: "Conformance Tool",
                test: "MPD Validator shall be able to run",
                skipReason: ""
            )->add(
                result: false,
                severity: "FAIL",
                pass_message: "",
                fail_message: "Stderr: " . $validatorResult->errorOutput()
            );

            return;
        }

        $mpdValidatorOutput = $validatorResult->output();

        //Cache resolved
        Cache::remember(cache_path(['mpd','resolved']), 3600, function () use ($sessionDir) {
            return file_get_contents($sessionDir . "resolved.xml");
        });

        Cache::remember(cache_path(['validator','output']), 3600, function () use ($mpdValidatorOutput) {
            $javaRemoved = str_replace("[java]", "", $mpdValidatorOutput);
            $xlinkOffset = strpos($javaRemoved, "Start XLink resolving");
            return substr($javaRemoved, $xlinkOffset);
        });
*/
    }

    public function validate(): void
    {
        if (
            !Cache::get(cache_path(['mpd','resolved'])) ||
            !Cache::get(cache_path(['validator','output']))
        ) {
            $this->runValidator();
        }

        $validatorOutput = $this->getValidatorOutput();


        if (!$validatorOutput) {
            return;
        }


        /*
        $this->xlinkCase->add(
            result: strpos($validatorOutput, 'XLink resolving successful') !== false,
            severity: "FAIL",
            pass_message: "XLink resolving succesful",
            fail_message: "XLink resolving failed"
        );

        $this->mpdCase->add(
            result: strpos($validatorOutput, 'MPD validation successful') !== false,
            severity: "FAIL",
            pass_message: "MPD validation succesful",
            fail_message: "MPD validation failed"
        );
         */
    }
    /*
    private function findOrDownloadSchema(): void
    {
    //\TODO Rewrite function
        global $session;


        global $modules;
        if (!$modules) {
            $modules = [];
        }

        $schemaUrl = '';

        $llEnabled = false;
        $dvbEnabled = false;
        $dvbVersion = '';
        $useLatestXSD = false;

        foreach ($modules as $module) {
            if ($module->isEnabled()) {
                if ($module->name == "HbbTV_DVB") {
                    $dvbEnabled = $module->isDVBEnabled();
                    $dvbVersion = $module->DVBVersion;
                }
                if ($module->name == "DASH-IF Low Latency") {
                    $llEnabled = $module->isEnabled();
                }
                if ($module->name == "MPEG-DASH Common") {
                    $useLatestXSD = $module->useLatestXSD;
                }
            }
        }


        $schemaLocation = 'schemas/DASH-MPD.xsd';
        if ($useLatestXSD) {
            $schemaUrl = 'https://raw.githubusercontent.com/MPEGGroup/DASHSchema/5th-Ed/DASH-MPD.xsd';
        }

        if ($dvbEnabled) {
            if ($dvbVersion == "2019") {
                $schemaLocation = 'schemas/DASH-MPD-4th-amd1.xsd';
            } else {
              //Default to 2018 xsd
                $schemaLocation = 'schemas/DASH-MPD-2nd.xsd';
            }
        } elseif ($llEnabled) {
            $schemaLocation = 'schemas/DASH-MPD-4th-amd1.xsd';
        }

        if ($schemaUrl == '') {
            $this->schemaPath = $schemaLocation;
            return;
        }
        if (pathinfo($schemaUrl, PATHINFO_EXTENSION) != 'xsd') {
            $this->schemaPath = $schemaLocation;
            return;
        }

        $sessionDir = $session->getDir();
        $saveTo = "$sessionDir/schema.xsd";
        $fp = fopen($saveTo, 'w+');
        if ($fp === false) {
            return;
        }

        $ch = curl_init($schemaUrl);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        curl_exec($ch);

        if (curl_errno($ch)) {
            return;
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        if ($statusCode != 200) {
            $this->schemaPath = $schemaLocation;
            return;
        }

        chmod($saveTo, 0777);
        $this->schemaPath = $saveTo;
    }
     */
}
