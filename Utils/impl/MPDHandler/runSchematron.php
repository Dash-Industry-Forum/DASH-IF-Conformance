<?php

global $logger, $session;


$sessionDir = $session->getDir();


global $mpd_xml_string;

$mpdXml = simplexml_load_string($mpd_xml_string);
$mpdXml->asXML("$sessionDir/mpdresult.xml");

$currentDir = getcwd();

chdir(__DIR__ . '/../../../DASH/mpdvalidator');
$this->findOrDownloadSchema();

$this->mpdValidatorOutput = syscall("java -cp \"saxon9he.jar:xercesImpl.jar:bin\" Validator \"" .
  explode('#', $this->url)[0] . "\" $sessionDir/resolved.xml " . 
  $this->schemaPath . " $sessionDir/mpdresult.xml");


$javaRemoved = str_replace("[java]","", $this->mpdValidatorOutput);
$xlinkOffset = strpos($javaRemoved, "Start XLink resolving");

$this->schematronOutput = substr($javaRemoved, $xlinkOffset);

chdir($currentDir);

