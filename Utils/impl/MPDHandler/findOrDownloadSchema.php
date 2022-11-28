<?php

global $schema_url, $dvb_conformance_2018, $dvb_conformance_2019;
global $session;


global $modules;

$llEnabled = false;
$dvbEnabled = false;
$dvbVersion = '';
$useLatestXSD = false;

foreach ($modules as $module) {
   if ($module->isEnabled()) {
     if ($module->name == "HbbTV_DVB"){
       $dvbEnabled = $module->isDVBEnabled();
       $dvbVersion = $module->DVBVersion;
     }
     if ($module->name == "DASH-IF Low Latency"){
       $llEnabled = $module->isEnabled();
     }
     if ($module->name == "MPEG-DASH Common"){
       $useLatestXSD = $module->useLatestXSD;
     }
   }
}


$schemaLocation = 'schemas/DASH-MPD.xsd';
if ($useLatestXSD){
  $schema_url = 'https://raw.githubusercontent.com/MPEGGroup/DASHSchema/5th-Ed/DASH-MPD.xsd';
}

if ($dvbEnabled){ 
  if ($dvbVersion == "2019"){
    $schemaLocation = 'schemas/DASH-MPD-4th-amd1.xsd';
  }else{
    //Default to 2018 xsd
    $schemaLocation = 'schemas/DASH-MPD-2nd.xsd';
  }
} elseif ($llEnabled) {
    $schemaLocation = 'schemas/DASH-MPD-4th-amd1.xsd';
}

if ($schema_url == '') {
  $this->schemaPath = $schemaLocation;
  return;
}
if (pathinfo($schema_url, PATHINFO_EXTENSION) != 'xsd') {
  $this->schemaPath = $schemaLocation;
  return;
}

$sessionDir = $session->getDir();
$saveTo = "$sessionDir/schema.xsd";
$fp = fopen($saveTo, 'w+');
if ($fp === false) {
    return null;
}

$ch = curl_init($schema_url);
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
