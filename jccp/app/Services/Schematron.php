<?php

namespace App\Services;

use App\Services\ModuleLogger;

if (!function_exists('systemCall')) {
    function systemCall($command)
    {
        $result = '';
        if ($proc = popen("($command)2>&1", "r")) {
            while (!feof($proc)) {
                $result .= fgets($proc, 1000);
            }
            pclose($proc);
        }
        return $result;
    }
}

class Schematron
{
    private string $mpd;
    public string $resolved = '';
    private string $schemaPath;
    private $mpdValidatorOutput;
    private $schematronOutput;
    private $schematronIssuesReport;

    public function __construct(string $mpd = '')
    {
        $this->mpd = $mpd;
        if ($this->mpd == '') {
            return;
        }

        $this->runSchematron();
        $this->validateSchematron();
    }

    public function getSchematronOutput()
    {
        return $this->schematronOutput;
    }

    private function runSchematron()
    {

        $sessionDir = '/tmp';// $session->getDir();

        file_put_contents($sessionDir . "/manifest.mpd", $this->mpd);

        $mpdXml = simplexml_load_string('<mpdresult><xlink>No Result</xlink>' .
        '<schema>No Result</schema><schematron>No Result</schematron></mpdresult>');
        $mpdXml->asXML("$sessionDir/mpdresult.xml");

        $currentDir = getcwd();

        chdir(__DIR__ . '/../../../DASH/mpdvalidator');
        $this->findOrDownloadSchema();

        $this->mpdValidatorOutput = systemCall("java -cp \"saxon9he.jar:xercesImpl.jar:bin\" Validator \"" .
        $sessionDir . "/manifest.mpd" . "\" $sessionDir/resolved.xml " .
        $this->schemaPath . " $sessionDir/mpdresult.xml");

        $this->resolved = file_get_contents("$sessionDir/resolved.xml");

        $javaRemoved = str_replace("[java]", "", $this->mpdValidatorOutput);
        $xlinkOffset = strpos($javaRemoved, "Start XLink resolving");

        $this->schematronOutput = substr($javaRemoved, $xlinkOffset);

        chdir($currentDir);
    }

    private function validateSchematron()
    {
        $logger = app(ModuleLogger::class);
        if (!$this->schematronOutput) {
            $logger->validatorMessage("No schematron?");
        }

        $logger->setModule("Schematron");
        $logger->setHook("MPD");

        $logger->validatorMessage("MPDValidator output: " . $this->mpdValidatorOutput);
        $logger->validatorMessage("Schematron output: " . $this->schematronOutput);

        $logger->test(
            "MPEG-DASH",
            "Commmon",
            "Schematron Validation",
            strpos($this->schematronOutput, 'XLink resolving successful') !== false,
            "FAIL",
            "XLink resolving succesful",
            "XLink resolving failed"
        );

        $logger->test(
            "MPEG-DASH",
            "Commmon",
            "Schematron Validation",
            strpos($this->schematronOutput, 'MPD validation successful') !== false,
            "FAIL",
            "MPD validation succesful",
            "MPD validation failed"
        );

        $logger->test(
            "MPEG-DASH",
            "Commmon",
            "Schematron Validation",
            strpos($this->schematronOutput, 'Schematron validation successful') !== false,
            "FAIL",
            "Schematron validation succesful",
            "Schematron validation failed"
        );

        if ($this->schematronOutput != '') {
            if (strpos($this->schematronOutput, 'Schematron validation successful') === false) {
                $this->schematronIssuesReport = analyzeSchematronIssues($this->mpdValidatorOutput);
            }
        }
    }

    private function findOrDownloadSchema()
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
            return null;
        }

        $ch = curl_init($schemaUrl);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        curl_exec($ch);

        if (curl_errno($ch)) {
            return null;
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
