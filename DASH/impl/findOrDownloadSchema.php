<?php
global $session_id, $schema_url, $dvb_conformance, $dvb_conformance_2018, $dvb_conformance_2019, $low_latency_dashif_conformance;

$default_schema_loc = 'schemas/DASH-MPD.xsd';

if($dvb_conformance && $dvb_conformance_2018) {
    $default_schema_loc = 'schemas/DASH-MPD-2nd.xsd';
}
elseif($dvb_conformance && $dvb_conformance_2019) {
    $default_schema_loc = 'schemas/DASH-MPD-4th-amd1.xsd';
}
elseif($low_latency_dashif_conformance) {
    $default_schema_loc = 'schemas/DASH-MPD-4th-amd1.xsd';
}

if($schema_url == '') {
    return $default_schema_loc;
}
if(pathinfo($schema_url, PATHINFO_EXTENSION) != 'xsd'){
    return $default_schema_loc;
}

$saveTo = "schemas/DASH-MPD_CustomSchema_$session_id.xsd";
$fp = fopen($saveTo, 'w+');
if($fp === FALSE){
    return NULL;
}

$ch = curl_init($schema_url);
curl_setopt($ch, CURLOPT_FILE, $fp);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);

curl_exec($ch);

if(curl_errno($ch)){
    return NULL;
}

$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
fclose($fp);

if($statusCode == 200){
    //
} else{
    delete_schema($saveTo);
    return $default_schema_loc;
}

chmod($saveTo, 0777);
return $saveTo;
?>
