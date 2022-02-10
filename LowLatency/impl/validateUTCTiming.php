<?php
global $mpd_features, $utc_timing_info, $logger;


$valid_utc_timing_present = FALSE;
$utc_timings = $mpd_features['UTCTiming'];
foreach ($utc_timings as $utc_timing) {
    $accepted_uris = array('urn:mpeg:dash:utc:http-xsdate:2014', 'urn:mpeg:dash:utc:http-iso:2014', 'urn:mpeg:dash:utc:http-ntp:2014');
    $schemeIdUri = $utc_timing['schemeIdUri'];
    if(in_array($schemeIdUri, $accepted_uris) === TRUE) {
        $valid_utc_timing_present = TRUE;
        $utc_timing_info[] = $utc_timing;
    }
}


$logger->test(
  "DASH-IF IOP CR Low Latency Live",
  "Section 9.X.4.2",
  "'At least one UTC timing description SHALL be present and be restricted with @schemeIdUri set to one of {urn:mpeg:dash:utc:http-xsdate:2014, urn:mpeg:dash:utc:http-iso:2014, urn:mpeg:dash:utc:http-ntp:2014}'",
  $utc_timings != null,
  "FAIL",
  "UTCTiming element found in MPD",
  "UTCTiming element not found in MPD"
);

if($utc_timings == null) {
  return;
}

$logger->test(
  "DASH-IF IOP CR Low Latency Live",
  "Section 9.X.4.2",
  "'At least one UTC timing description SHALL be present and be restricted with @schemeIdUri set to one of {urn:mpeg:dash:utc:http-xsdate:2014, urn:mpeg:dash:utc:http-iso:2014, urn:mpeg:dash:utc:http-ntp:2014}'",
  $valid_utc_timing_present,
  "FAIL",
  "At least one of the UTCTiming elements use the mentioned schemeIdUris in @schemeIdUri attribute in the MPD",
  "None of the UTCTiming elements use the mentioned schemeIdUris in @schemeIdUri attribute in the MPD"
);

?>
