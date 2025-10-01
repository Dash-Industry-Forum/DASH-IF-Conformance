<?php

namespace App\Modules;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Cache;
use App\Interfaces\Module;
use App\Services\ModuleLogger;
use App\Services\MPDCache;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Reporter\TestCase;
use Illuminate\Support\Facades\Log;

class Schematron extends Module
{
    //TODO Move to module
    private string $schemaPath;
    private SubReporter $schematronReporter;
    private SubReporter $globalReporter;

    private TestCase $xlinkCase;
    private TestCase $mpdCase;
    private TestCase $schematronRunCase;

    public function __construct()
    {
        parent::__construct();
        $this->name = "Global Module";
        $this->registerChecks();
    }

    public function registerChecks(): void
    {
        $reporter = app(ModuleReporter::class);
        $this->globalReporter = &$reporter->context(new ReporterContext("MPD", "Global", "", array()));
        $this->schematronReporter = &$reporter->context(new ReporterContext("MPD", "Schematron", "", array()));

        $this->xlinkCase = $this->globalReporter->add(
            section: "",
            test: "xlink resolution SHALL be succesful",
            skipReason: "Unable to run schematron"
        );

        $this->mpdCase = $this->globalReporter->add(
            section: "",
            test: "MPD Validation SHALL be succesful",
            skipReason: "Unable to run schematron"
        );

        $this->schematronRunCase = $this->globalReporter->add(
            section: "",
            test: "Schematron Validation SHALL run succesfully",
            skipReason: "Unable to run schematron"
        );
    }

    public function validateMPD(): void
    {
        parent::validateMPD();
        $this->validateSchematron();
        $this->validate();
    }

    public function getValidatorOutput(): string
    {
        if (!Cache::get(cache_path(['validator','output']))) {
            $this->runValidator();
        }
        return Cache::get(cache_path(['validator','output']), '');
    }

    public function getSchematronOutput(): string
    {
        if (!Cache::get(cache_path(['mpd','schematron']))) {
            $this->runSchematron();
        }
        return Cache::get(cache_path(['mpd','schematron']), '');
    }


    private function runSchematron(): void
    {
        Log::info("Running Schematron!");
        $sessionDir = session_dir();
        if (!Cache::get(cache_path(['mpd','resolved']))) {
            $this->runValidator();
        }

        $validatorPath = __DIR__ . "/../../../DASH/mpdvalidator";
        $schematronCommand = implode(" ", [
            "java",
            "-jar",
            "${validatorPath}/saxon9he.jar",
            "-versionmsg:off",
            "-s:${sessionDir}/resolved.xml",
            "-o:${sessionDir}/schematron.xml",
            "-xsl:${validatorPath}/schematron/output/val_schema.xsl"
        ]);

        Process::run($schematronCommand);

        //Make sure we cache our schematron code
        Cache::remember(cache_path(['mpd','schematron']), 10, function () use ($sessionDir) {
            return file_get_contents($sessionDir . "schematron.xml");
        });
    }

    private function runValidator(): void
    {

        $sessionDir = session_dir();

        file_put_contents($sessionDir . "manifest.mpd", app(MPDCache::class)->getMPD());

        $mpdXml = simplexml_load_string('<mpdresult><xlink>No Result</xlink>' .
        '<schema>No Result</schema><schematron>No Result</schematron></mpdresult>');
        $mpdXml->asXML($sessionDir . "mpdresult.xml");

        $validatorPath = __DIR__ . "/../../../DASH/mpdvalidator";
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

        $mpdValidatorOutput = $validatorResult->output();

        //Cache resolved
        Cache::remember(cache_path(['mpd','resolved']), 10, function () use ($sessionDir) {
            return file_get_contents($sessionDir . "resolved.xml");
        });

        Cache::remember(cache_path(['validator','output']), 10, function () use ($mpdValidatorOutput) {
            $javaRemoved = str_replace("[java]", "", $mpdValidatorOutput);
            $xlinkOffset = strpos($javaRemoved, "Start XLink resolving");
            return substr($javaRemoved, $xlinkOffset);
        });
    }

    public function validateSchematron(): void
    {
        if (!Cache::get(cache_path(['mpd','schematron']))) {
            $this->runSchematron();
        }

        $schematronOutput = $this->getSchematronOutput();
        if (!$schematronOutput) {
            return;
        }

        $doc = new \DOMDocument();
        $doc->loadXML($schematronOutput);

        $namespace = 'http://purl.oclc.org/dsdl/svrl';
        $schematronResult = $doc->getElementsByTagNameNS($namespace, 'schematron-output')->item(0);
        $failedAssertions = $schematronResult->getElementsByTagNameNS($namespace, 'failed-assert');
        foreach ($failedAssertions as $failedAssertion) {
            $testLocation = $failedAssertion->getAttribute('location');
            $testDescription = $failedAssertion->getAttribute('test');
            $testRole = $failedAssertion->getAttribute('role');
            $textComponents = $failedAssertion->getElementsByTagNameNS($namespace, 'text');

            foreach ($textComponents as $textComponent) {
                //Always false, as we're parsing failed assertions
                $this->schematronReporter->add(
                    section: $testLocation,
                    test: $testDescription,
                    skipReason: ''
                )->add(
                    result: false,
                    severity: $testRole == "warn" ? "WARN" : "FAIL",
                    pass_message: "",
                    fail_message: $textComponent->nodeValue
                );
            }
        }
    }

    public function validate(): void
    {
        if (
            !Cache::get(cache_path(['mpd','resolved'])) ||
            !Cache::get(cache_path(['validator','output']))
        ) {
            $this->runValidator();
        }

        $logger = app(ModuleLogger::class);

        $validatorOutput = $this->getValidatorOutput();


        if (!$validatorOutput) {
            return;
        }


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

        $this->schematronRunCase->add(
            result: strpos($validatorOutput, 'Schematron validation successful') !== false,
            severity: "FAIL",
            pass_message: "Schematron validation succesful",
            fail_message: "Schematron validation failed"
        );
    }
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
}
