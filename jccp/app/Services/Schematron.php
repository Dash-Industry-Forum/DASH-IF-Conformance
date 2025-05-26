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
        include 'impl/MPDHandler/findOrDownloadSchema.php';
    }
}
