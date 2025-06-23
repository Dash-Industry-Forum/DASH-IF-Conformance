<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Cache;
use App\Services\ModuleLogger;
use App\Services\MPDCache;

if (!function_exists('systemCall')) {
    function systemCall(string $command): string
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
    private string $schemaPath;

    public function getValidatorOutput(): string
    {
        if (!Cache::get(cache_path(['validator','output']))) {
            $this->run();
        }
        return Cache::get(cache_path(['validator','output']), '');
    }

    private function run(): void
    {

        $sessionDir = session_dir();

        file_put_contents($sessionDir . "manifest.mpd", app(MPDCache::class)->getMPD());

        $mpdXml = simplexml_load_string('<mpdresult><xlink>No Result</xlink>' .
        '<schema>No Result</schema><schematron>No Result</schematron></mpdresult>');
        $mpdXml->asXML($sessionDir . "mpdresult.xml");

        $currentDir = getcwd();

        echo "Current path: " . __DIR__  . "\n";
        chdir(__DIR__ . '/../../../DASH/mpdvalidator');
        $this->findOrDownloadSchema();

        $validatorCommand =
            "java -cp \"saxon9he.jar:xercesImpl.jar:bin\" Validator " .
            "\"" . $sessionDir . "manifest.mpd\" " .
            $sessionDir . "resolved.xml " .
            $this->schemaPath . " " . $sessionDir . "mpdresult.xml";

        echo $validatorCommand . "\n";

        $validatorResult = Process::run($validatorCommand);

        chdir($currentDir);


        $mpdValidatorOutput = $validatorResult->output();



        $resolvedMPD = Cache::remember(cache_path(['resolvedmpd']), 10, function () use ($sessionDir) {
            return file_get_contents($sessionDir . "resolved.xml");
        });



        Cache::remember(cache_path(['validator','output']), 10, function () use ($mpdValidatorOutput) {
            $javaRemoved = str_replace("[java]", "", $mpdValidatorOutput);
            $xlinkOffset = strpos($javaRemoved, "Start XLink resolving");
            return substr($javaRemoved, $xlinkOffset);
        });
    }

    public function validate(): void
    {
        if (
            !Cache::get(cache_path(['resolvedmpd'])) ||
            !Cache::get(cache_path(['validator','output']))
        ) {
            $this->run();
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

        //TODO Schmatron analysis report
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
