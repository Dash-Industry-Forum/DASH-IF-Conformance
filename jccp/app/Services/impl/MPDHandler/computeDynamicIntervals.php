<?php

///\Todo Bring this file up to naming specs
global $period_timing_info, $modules, $availability_times;

$bufferduration = ($this->features['timeShiftBufferDepth'] != null) ?
  DASHIF\Utility\timeParsing($this->features['timeShiftBufferDepth']) : INF;

$AST = $this->features['availabilityStartTime'];
$segmentduration = 0;
if ($segmentAccess['SegmentTimeline'] != null) {
    if (count($segmentTimings) > 1) {
        $segmentduration = ($segmentTimings[$segmentCount - 1] - $segmentTimings[0]) / ((float)($segmentCount - 1));
    }
} else {
    $segmentduration = ($segmentAccess['duration'] != null) ? $segmentAccess['duration'] : 0;
}
$timescale = ($segmentAccess['timescale'] != null) ? $segmentAccess['timescale'] : 1;
$availabilityTimeOffset = (array_key_exists("availabilityTimeOffset", $segmentAccess) &&
  $segmentAccess['availabilityTimeOffset'] != 'INF') ? $segmentAccess['availabilityTimeOffset'] : 0;

$pto = ($segmentAccess['presentationTimeOffset'] != '') ?
  (int)($segmentAccess['presentationTimeOffset']) / $timescale : 0;

if ($segmentduration != 0) {
    $segmentduration /= $timescale;
}

$avgsum = array();
$sumbandwidth = array();
$adaptation_sets = $this->features['Period'][$this->selectedPeriod]['AdaptationSet'];
for ($k = 0; $k < sizeof($adaptation_sets); $k++) {
    $representations = $adaptation_sets[$k]['Representation'];
    $sum = 0;
    for ($l = 0; $l < sizeof($representations); $l++) {
        $sum += $representations[$l]['bandwidth'];
    }

    $sumbandwidth[] = $sum;
    $avgsum[] = $sum / sizeof($representations);
}
$sumbandwidth = array_sum($sumbandwidth);
$avgsum = array_sum($avgsum) / sizeof($avgsum);
$percent = $avgsum / $sumbandwidth;

if ($segmentduration == 0) {
    $segmentduration = 1;
}

$buffercapacity = $bufferduration / $segmentduration; //actual buffer capacity

date_default_timezone_set("UTC"); //Set default timezone to UTC
$now = time(); // Get actual time
$AST = strtotime($AST);
$LST = $now - ($AST + $period_timing_info["start"] - $pto - $availabilityTimeOffset - $segmentduration);
$LSN = intval($LST / $segmentduration);
$earliestsegment = $LSN - $buffercapacity * $percent;

$new_array = $segmentTimings;
$new_array[] = $LST * $timescale;
sort($new_array);
$ind = array_search($LST * $timescale, $new_array);

$SST = ($ind - 1 - $buffercapacity * $percent < 0) ? 0 : $ind - 1 - $buffercapacity * $percent;

foreach ($modules as $module) {
    if ($module->name == "DASH-IF Low Latency") {
        if ($module->isEnabled()) {
            $ASAST = array();
            $NSAST = array();
            $count = $LSN - intval($earliestsegment);
            for ($i = $count; $i > 0; $i--) {
                  $ASAST[] = $now - $LST - $bufferduration * $i;
                  $NSAST[] = $now - ($LST - $bufferduration * $i + $availabilityTimeOffset);
            }
            $availability_times[$adaptationSetId][$representationId]['ASAST'] = $ASAST;
            $availability_times[$adaptationSetId][$representationId]['NSAST'] = $NSAST;
        }
        break;
    }
}

return [intval($earliestsegment), $LSN, $SST];
