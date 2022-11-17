<?php

global $schema_url, $dvb_conformance, $dvb_conformance_2018, $dvb_conformance_2019,
$low_latency_dashif_conformance;
global $session;


$sessionDir = $session->getDir();

$default_schema_loc = 'schemas/DASH-MPD.xsd';

if ($dvb_conformance && $dvb_conformance_2018) {
    $default_schema_loc = 'schemas/DASH-MPD-2nd.xsd';
} elseif ($dvb_conformance && $dvb_conformance_2019) {
    $default_schema_loc = 'schemas/DASH-MPD-4th-amd1.xsd';
} elseif ($low_latency_dashif_conformance) {
    $default_schema_loc = 'schemas/DASH-MPD-4th-amd1.xsd';
}

if ($schema_url == '') {
  $this->schemaPath = $default_schema_loc;
  return;
}
if (pathinfo($schema_url, PATHINFO_EXTENSION) != 'xsd') {
  $this->schemaPath = $default_schema_loc;
  return;
}

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
  $this->schemaPath = $default_schema_loc;
  return;
}

chmod($saveTo, 0777);
$this->schemaPath = $saveTo;
