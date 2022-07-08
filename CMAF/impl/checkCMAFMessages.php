<?php

global $session_dir, $current_period;

$validCMFC = true;
$validCMF2 = true;

$logs = file_get_contents($session_dir . '/Period' . $current_period . '/' . $logFile . '.txt');
$logs_array = explode("\n", $logs);
$size = sizeof($logs_array);
for ($log_index = 0; $log_index < $size; $log_index++) {
    $log = $logs_array[$log_index];
    if (strpos($log, "CMAF check 'cmf2' violated: Section 7.7") !== false) {
        $validCMF2 = false;
    } elseif (strpos($log, 'CMAF check violated') !== false) {
        $validCMFC = false;
        $validCMF2 = false;
    }
}

return [$validCMFC, $validCMF2];
