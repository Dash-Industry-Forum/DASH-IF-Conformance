<?php

global $period_timing_info;

return $this->getPeriodDurationInfo($period ? $period : $this->selectedPeriod);


///\RefactorTodo; This 'period selection' mechanism seems to actively counteract the MPD processing one...

/*
$availabilityStartTime = $this->features['availabilityStartTime'];

$start = null;
$duration = null;

switch ($this->features['type']) {
    case 'static':{
        $start = $periodDurationInfo['start'];
        $duration = $periodDurationInfo['duration'];
        break;
    }
    case 'dynamic':{
        if (sizeof($this->features['Period']) == 1) {
            $current_period = 0;
            $this->selectPeriod(0);
            $periodDurationInfo = $this->getPeriodDurationInfo(0);
            $start = $periodDurationInfo['start'];
            $duration = $periodDurationInfo['duration'];
        } else {
            $now = time();
            for ($p = 0; $p < sizeof($mpd_features['Period']); $p++) {
                $whereami = $now - (strtotime($availabilityStartTime) + $period_info[0][$p]);

                if ($whereami <= $period_info[1][$p]) {
                    $current_period = $p;
                    $start = $period_info[0][$p];
                    $duration = $period_info[1][$p];
                    break;
                }
            }
        }
        break;
    }
    default:
        fwrite(STDERR, "Unable to parse timing info for MPD type '" . $this->features['type'] . "'\n");
        break;
}

$period_timing_info = [$start, $duration];
return $period_timing_info;
 */
