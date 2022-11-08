<?php

if ($segmentAccessType == 'SegmentBase') {
    array(0);
}

if ($segmentAccessType != 'SegmentTemplate') {
    array();
}

$segmentCount = 0;

///\Note start is always 0... leads to negative segmentStartTimes?

$start = 0;

$duration = 0;
if ($segmentAccess['duration'] != null) {
    $duration = $segmentAccess['duration'];
}

$timescale = 1;
if ($segmentAccess['timescale'] != null) {
    $timescale = $segmentAccess['timescale'];
}

$availabilityTimeOffset = 0;
if ($segmentAccess['availabilityTimeOffset'] != null && $segmentAccess['availabilityTimeOffset'] != 'INF') {
    $availabilityTimeOffset =  $segmentAccess['availabilityTimeOffset'];
}

$presentationTimeOffset = 0;
if ($segmentAccess['presentationTimeOffset'] != '') {
    $presentationTimeOffset = (int)($segmentAccess['presentationTimeOffset']) / $timescale;
}

if ($duration != 0) {
    $duration /= $timescale;
    $segmentCount = ceil(($presentationDuration - $start) / $duration);
}

$timeOffset = $presentationTimeOffset + $availabilityTimeOffset;
$segmentTimings = array();

$segmentTimeline = $segmentAccess['SegmentTimeline'];
if ($segmentTimeline == null) {
    $segmentStartTime = $start - $timeOffset;

    for ($index = 0; $index < $segmentCount; $index++) {
        $segmentTimings[] = ($segmentStartTime + ($index * $duration));
    }
    return $segmentTimings;
}


$segmentEntries = $segmentTimeline[0]['S'];

if ($segmentEntries == null) {
    return array();
}


$segmentTime = 0;
if ($segmentEntries[0]['t']) {
    $segmentTime =  $segmentEntries[0]['t'] ;
}
$segmentTime -= $timeOffset;

foreach ($segmentEntries as $index => $segmentEntry) {
    $d = $segmentEntry['d'];
    $r = 0;
    if ($segmentEntry['r']) {
        $r = $segmentEntry['r'];
    }
    $t = 0;
    if ($segmentEntry['t']) {
        $t = $segmentEntry['t'];
    }
    $t -= $timeOffset;

    if ($r == 0) {
        $segmentTimings[] = (float) $segmentTime;
        $segmentTime += $d;
        continue;
    }
    if ($r < 0) {
        $endTime = $presentationDuration * $timescale;
        if (isset($segmentEntries[$index + 1])) {
            $endTime = ($segmentEntries[$index + 1]['t']);
        }

        while ($segmentTime < $endTime) {
            $segmentTimings[] = (float) $segmentTime;
            $segmentTime += $d;
        }
        continue;
    }
    for ($repeat = 0; $repeat <= $r; $repeat++) {
        $segmentTimings[] = (float) $segmentTime;
        $segmentTime += $d;
    }
}

return $segmentTimings;
