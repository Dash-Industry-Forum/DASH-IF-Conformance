<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Cache;
use App\Services\ModuleLogger;
use App\Services\MPDCache;
use App\Services\ModuleReporter;
use App\Services\Reporter\Context as ReporterContext;

class Schematron
{
    private string $schemaPath;

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
        echo "Running Schematron!\n";
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
        $schematronResult = Process::run($schematronCommand);

        $resolvedMPD = Cache::remember(cache_path(['mpd','schematron']), 10, function () use ($sessionDir) {
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

        $currentDir = getcwd();


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



        $resolvedMPD = Cache::remember(cache_path(['mpd','resolved']), 10, function () use ($sessionDir) {
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

        $reporter = app(ModuleReporter::class);
        $schematronContext = new ReporterContext("MPD", "Schematron", "", array());
        $schematronReporter = &$reporter->context($schematronContext);


        $logger = app(ModuleLogger::class);
        $logger->setModule("Schematron");
        $logger->setHook("MPD");

        $schematronOutput = $this->getSchematronOutput();
        if (!$schematronOutput) {
            $logger->validatorMessage("Schematron was unable to run");
            return;
        }

        $doc = new \DOMDocument();
        $doc->loadXML($schematronOutput);

        $schematronResult = $doc->getElementsByTagNameNS(
            'http://purl.oclc.org/dsdl/svrl',
            'schematron-output'
        )->item(0);
        $failedAssertions = $schematronResult->getElementsByTagNameNS(
            'http://purl.oclc.org/dsdl/svrl',
            'failed-assert'
        );
        foreach ($failedAssertions as $failedAssertion) {
            $testLocation = $failedAssertion->getAttribute('location');
            $testDescription = $failedAssertion->getAttribute('test');
            $textComponents = $failedAssertion->getElementsByTagNameNS(
                'http://purl.oclc.org/dsdl/svrl',
                'text'
            );

            foreach ($textComponents as $textComponent) {
                $schematronReporter->test(
                    $testLocation,
                    $testDescription,
                    false, //Always false, as we're parsing failed assertions
                    "FAIL",
                    "",
                    $textComponent->nodeValue
                );
                $logger->test(
                    "Schematron",
                    $testLocation,
                    $testDescription,
                    false, //Always false, as we're parsing failed assertions
                    "FAIL",
                    "",
                    $textComponent->nodeValue
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

        $logger->setModule("Schematron");
        $logger->setHook("MPD");

        if (!$validatorOutput) {
            $logger->validatorMessage("Validator was unable to run");
        }

        $logger->test(
            "MPEG-DASH",
            "Commmon",
            "Schematron Validation",
            strpos($validatorOutput, 'XLink resolving successful') !== false,
            "FAIL",
            "XLink resolving succesful",
            "XLink resolving failed"
        );

        $logger->test(
            "MPEG-DASH",
            "Commmon",
            "Schematron Validation",
            strpos($validatorOutput, 'MPD validation successful') !== false,
            "FAIL",
            "MPD validation succesful",
            "MPD validation failed"
        );

        $logger->test(
            "MPEG-DASH",
            "Commmon",
            "Schematron Validation",
            strpos($validatorOutput, 'Schematron validation successful') !== false,
            "FAIL",
            "Schematron validation succesful",
            "Schematron validation failed"
        );
    }

    //\TODO Should we return true/false here
    private function findOrDownloadSchema(): void
    {
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
