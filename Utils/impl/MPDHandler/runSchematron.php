<?php

global $logger, $session;


if (!$session) {
    return;
}

$sessionDir = $session->getDir();

file_put_contents($sessionDir . "/manifest.mpd", $this->mpd);

$mpdXml = simplexml_load_string('<mpdresult><xlink>No Result</xlink>' .
  '<schema>No Result</schema><schematron>No Result</schematron></mpdresult>');
$mpdXml->asXML("$sessionDir/mpdresult.xml");

$currentDir = getcwd();

chdir(__DIR__ . '/../../../DASH/mpdvalidator');
$this->findOrDownloadSchema();

$this->mpdValidatorOutput = syscall("java -cp \"saxon9he.jar:xercesImpl.jar:bin\" Validator \"" .
  $sessionDir . "/manifest.mpd" . "\" $sessionDir/resolved.xml " .
  $this->schemaPath . " $sessionDir/mpdresult.xml");

$this->resolved = file_get_contents("$sessionDir/resolved.xml");

$javaRemoved = str_replace("[java]", "", $this->mpdValidatorOutput);
$xlinkOffset = strpos($javaRemoved, "Start XLink resolving");

$this->schematronOutput = substr($javaRemoved, $xlinkOffset);

chdir($currentDir);
