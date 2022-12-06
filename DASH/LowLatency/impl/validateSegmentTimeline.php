<?php

global $mpdHandler, $period_timing_info;

$isSegmentStarts = $infoFileAdapt[$representation_id]['isSegmentStart'];
$presentationStarts = $infoFileAdapt[$representation_id]['PresStart'];
$presentationEnds = $infoFileAdapt[$representation_id]['NextPresStart'];

$segmentIndexes = array_keys($isSegmentStarts, '1');
$segmentCount = sizeof($segmentIndexes);
$segmentIndex = 0;

$timescale = ($segment_access_rep['timescale']) ? $segment_access_rep['timescale'] : 1;
$presentationTimeOffset = ($segment_access_rep['presentationTimeOffset']) ?
  $segment_access_rep['presentationTimeOffset'] : 0;

$segementElements = $segment_access_rep['SegmentTimeline'][0]['S'];
$segmentElementCount = sizeof($segmentElements);
foreach ($segementElements as $sIndex => $s) {
    $t = ($s['t']) ? $s['t'] : 0;
    $t -= $presentationTimeOffset;
    $d = $s['d'];
    $r = $s['r'];
    $k = $s['k'];

    if ($segmentIndex != $segmentCount - 1) {
        $numberOfChunks = $segmentIndexes[$segmentIndex + 1] - $segmentIndexes[$segmentIndex];
        $logger->test(
            "DASH-IF IOP CR Low Latency Live",
            "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
            "If the SegmentTimeline element is present and if each chunk is an addressable object then for every " .
            "CMAF Fragment an entry in S element SHALL be present with @k as set to the number of chunks in the " .
            "corresponding CMAF Fragment",
            $numberOfChunks <= 1 || ($k == null && $k = $numberOfChunks),
            "FAIL",
            "Valid value of 'k' for Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
            ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1) . "Segment $i",
            "Invalid value of 'k' for Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
            ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1) . "Segment $i",
        );
    }

    if ($r < 0) {
        $until = ($sIndex != $segmentElementCount - 1) ?
          $segmentElements[$sIndex + 1]['t'] : $period_timing_info[1] * $timescale;
        while ($t < $until) {
            $logger->test(
                "DASH-IF IOP CR Low Latency Live",
                "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
                "If the SegmentTimeline element is present then for every CMAF Fragment an entry in S element " .
                "SHALL be present with @t as set to earliest presentation time",
                $t / $timescale == $presentationStarts[$segmentIndex],
                "FAIL",
                "@t of $t is equal to earliest presentation time for Period " . ($mpdHandler->getSelectedPeriod() + 1) .
                ' Adaptation ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1) . "Segment $i",
                "@t of $t is not equal to earliest presentation time for Period " . ($mpdHandler->getSelectedPeriod() + 1) .
                ' Adaptation ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1) . "Segment $i",
            );
            $logger->test(
                "DASH-IF IOP CR Low Latency Live",
                "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
                "If the SegmentTimeline element is present then for every CMAF Fragment an entry in S element " .
                "SHALL be present with @d as set to CMAF Fragment duration",
                $d / $timescale == $presentationEnds[$segmentIndex] - $presentationStarts[$segmentIndex],
                "FAIL",
                "@d of $d is equal to fragment duration for Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
                ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1) . "Segment $i",
                "@d of $d is not equal to fragment duration for Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
                ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1) . "Segment $i",
            );
            $t += $d;
            $segmentIndex++;
        }
    } else {
        for ($j = 0; $j < $r + 1; $j++) {
            $logger->test(
                "DASH-IF IOP CR Low Latency Live",
                "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
                "If the SegmentTimeline element is present then for every CMAF Fragment an entry in S element " .
                "SHALL be present with @t as set to earliest presentation time",
                $t / $timescale == $presentationStarts[$segmentIndex],
                "FAIL",
                "@t of $t is equal to earliest presentation time for Period " . ($mpdHandler->getSelectedPeriod() + 1) .
                ' Adaptation ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1) . "Segment $i",
                "@t of $t is not equal to earliest presentation time for Period " . ($mpdHandler->getSelectedPeriod() + 1) .
                ' Adaptation ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1) . "Segment $i",
            );
            $logger->test(
                "DASH-IF IOP CR Low Latency Live",
                "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
                "If the SegmentTimeline element is present then for every CMAF Fragment an entry in S element " .
                "SHALL be present with @d as set to CMAF Fragment duration",
                $d / $timescale == $presentationEnds[$segmentIndex] - $presentationStarts[$segmentIndex],
                "FAIL",
                "@d of $d is equal to fragment duration for Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
                ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1) . "Segment $i",
                "@d of $d is not equal to fragment duration for Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
                ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1) . "Segment $i",
            );
            $t += $d;
            $segmentIndex++;
        }
    }

    if ($sIndex != 0) {
        $s_prev = $segmentElements[$sIndex - 1];
        $logger->test(
            "DASH-IF IOP CR Low Latency Live",
            "Section 9.X.4.5 (As part of MPEG-DASH 8.X.3 referenced in 8.X.4)",
            "If the SegmentTimeline element is present and if consecutive CMAF Fragments have the same duration " .
            "then their corresponding S element SHOULD be combined to a single S element",
            $s_prev['d'] != $d,
            "WARN",
            "Segments $sIndex duration differs from its predecessor for Period " . ($mpdHandler->getSelectedPeriod() + 1) .
            ' Adaptation ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1) . "Segment $i",
            "Segments $sIndex duration is equal to its predecessor for Period " . ($mpdHandler->getSelectedPeriod() + 1) .
            ' Adaptation ' . ($adaptationSetId + 1) . ' Representation ' . ($representationId + 1) . "Segment $i",
        );
    }
}
