<?php
use Illuminate\Support\Facades\Log;

global $logger, $session;

if (!function_exists('syscall')){
function sysCall($command) {
    $result = '';
    if ($proc = popen("($command)2>&1", "r")){
        while(!feof($proc)){
            $result .= fgets($proc, 1000);
        }
        pclose($proc);
    }
    return $result;
}
}

$sessionDir = '/tmp';// $session->getDir();

file_put_contents($sessionDir . "/manifest.mpd", $this->mpd);

$mpdXml = simplexml_load_string('<mpdresult><xlink>No Result</xlink>' .
  '<schema>No Result</schema><schematron>No Result</schematron></mpdresult>');
$mpdXml->asXML("$sessionDir/mpdresult.xml");

$currentDir = getcwd();

chdir(__DIR__ . '/../../../../../DASH/mpdvalidator');
$this->findOrDownloadSchema();

$this->mpdValidatorOutput = syscall("java -cp \"saxon9he.jar:xercesImpl.jar:bin\" Validator \"" .
  $sessionDir . "/manifest.mpd" . "\" $sessionDir/resolved.xml " .
  $this->schemaPath . " $sessionDir/mpdresult.xml");

$this->resolved = file_get_contents("$sessionDir/resolved.xml");

$javaRemoved = str_replace("[java]", "", $this->mpdValidatorOutput);
$xlinkOffset = strpos($javaRemoved, "Start XLink resolving");

$this->schematronOutput = substr($javaRemoved, $xlinkOffset);

chdir($currentDir);
