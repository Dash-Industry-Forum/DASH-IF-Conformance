<?php
global $current_adaptation_set, $current_representation, $segment_accesses, $period_timing_info;

$mpd_timing = array();

// Calculate segment timing information
$segment_access = $segment_accesses[$current_adaptation_set][$current_representation];
foreach ($segment_access as $seg_acc) {
    $pto = ($seg_acc['presentationTimeOffset'] != '') ? (int)($seg_acc['presentationTimeOffset']) : 0;
    $duration = ($seg_acc['duration'] != '') ? (int)($seg_acc['duration']) : 0;
    $timescale = ($seg_acc['timescale'] != '') ? (int)($seg_acc['timescale']) : 1;

    $pres_start = $period_timing_info[0] - $pto / $timescale;

    $segtimeline = $seg_acc['SegmentTimeline'];
    if (sizeof($segtimeline) != 0) {
        $stags = $segtimeline[sizeof($segtimeline) - 1]['S'];
        for ($s = 0; $s < sizeof($stags); $s++) {
            $duration = (int)($stags[$s]['d']);
            $repeat = ($stags[$s]['r'] != '') ? (int)($stags[$s]['r']) : 0;
            $time = $stags[$s]['t'];
            $time_next = ($stags[$s + 1]['t'] != null) ? ($stags[$s + 1]['t']) : '';

            $segmentDuration = $duration / $timescale;

            if ($repeat == -1) {
                if ($time_next != '') {
                    $time = (int)$time;
                    $time_next = (int)$time_next;

                    $index = 0;
                    while ($time_next - $time != 0) {
                        $mpd_timing[] = $pres_start + $index * $segmentDuration;

                        $time += $duration;
                        $index++;
                    }
                } else {
                    $segment_cnt = ceil($period_timing_info[1] / $segmentDuration);

                    for ($i = 0; $i < $segment_cnt; $i++) {
                        $mpd_timing[] = $pres_start + $i * $segmentDuration;
                    }
                }
            } else {
                for ($r = 0; $r < $repeat + 1; $r++) {
                    $mpd_timing[] = $pres_start + $r * $segmentDuration;
                }
            }
        }
    } else {
        if ($duration == 0) {
            $mpd_timing[] = $pres_start;
        } else {
            $segmentDuration = $duration / $timescale;
            $segment_cnt = $period_timing_info[1] / $segmentDuration;

            for ($i = 0; $i < $segment_cnt; $i++) {
                $mpd_timing[] = $pres_start + $i * $segmentDuration;
            }
        }
    }
}

return $mpd_timing;
