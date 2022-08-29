<?php

global $mpd_features, $current_period, $current_adaptation_set, $current_representation,
$segment_duration_array, $logger, $session;

if (!$this->hasJPGraph || !$this->hasJPBarGraph){
  return;
}

$period = $mpd_features['Period'][$current_period];
$adaptationSet = $period['AdaptationSet'][$current_adaptation_set];
$representation = $adaptationSet['Representation'][$current_representation];
$adaptationSetId = $current_adaptation_set + 1;
$representationId = ($representation['id'] != null) ? $representation['id'] : $current_representation + 1;

//only if there is a segment template in the representation get the timescale and duration
if (sizeof($representation['SegmentTemplate']) != 0) {
    $segmentTemplate = $representation['SegmentTemplate'][0];
} elseif (sizeof($adaptationSet['SegmentTemplate']) != 0) {
    $segmentTemplate = $adaptationSet['SegmentTemplate'][0];
} elseif (sizeof($period['SegmentTemplate']) != 0) {
    $segmentTemplate = $period['SegmentTemplate'][0];
} else {
    $MPDDurationSeconds = 'Not_Set';
}

if ($MPDDurationSeconds != 'Not_Set') { //if there is a segment template
//array to hold the duration of segments present in atom file that are different from that advertised in the MPD
    $durationDifferenceArray = array();
    $duration = $segmentTemplate['duration'];
    $timescale = $segmentTemplate['timescale'];
    if (($duration != '') && ($timescale != '')) {
        $MPDDurationSeconds = round(($duration / $timescale), 2);
        $index = 0;
        foreach ($segment_duration_array as $segmentDurationAtom) {
            $index++;
            if ($segmentDurationAtom != $MPDDurationSeconds) {
                $durationDifferenceArray[$index] = $segmentDurationAtom;
            }
        }
    } else {
        if (sizeof($segmentTemplate['SegmentTimeline']) != 0) {
            $MPDDurationSeconds_array = array();
            $segmentTimeline = $segmentTemplate['SegmentTimeline'][0];
            $segmentTimelineCount = sizeof($segmentTimeline['S']);
            for ($i = 0; $i < $segmentTimelineCount; $i++) {
                $segmentTimelineInstance = $segmentTimeline['S'][$i];
                $repetition = $segmentTimelineInstance['r'];
                $duration = $segmentTimelineInstance['d'];
                if ($repetition == -1) {
                    $MPDDurationSeconds = round(($duration / $timescale), 2);
                    $index = 0;
                    foreach ($segment_duration_array as $segmentDurationAtom) {
                        $index++;
                        if ($segmentDurationAtom != $MPDDurationSeconds) {
                            $durationDifferenceArray[$index] = $segmentDurationAtom;
                        }
                    }
                } else {
                    if ($repetition == '') {
                        $repetition = 1;
                    }
                    for ($i = 0; $i < $repetition; $i++) {
                        $MPDDurationSeconds_array[] = round(($duration / $timescale), 2);
                    }
                }
            }
            for ($j = 0; $j < count($MPDDurationSeconds_array); $j++) {
                if ($MPDDurationSeconds_array[$j] != $segment_duration_array[$j]) {
                    $durationDifferenceArray[$j] = $segment_duration_array[$j];
                }
            }
        } else {
            $MPDDurationSeconds = 'Not_Set';
        }
    }
}

$totalSegmentDuration = array_sum($segment_duration_array);

$differenceMessage  = implode(
    ' ',
    array_map(function ($v, $k) {
        return sprintf(" seg: '%s' -> duration: '%s' sec \n", $k, $v);
    },
    $durationDifferenceArray, array_keys($durationDifferenceArray))
);
$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB: Section 'Duration Self consistency'",
    "Durations of the segments should correspond to those signaled in the MPD",
    empty($durationDifferenceArray),
    "INFO",
    "All parsed segments correspond in Adaptation set $adaptationSetId, " .
    "representation $representationId",
    "Not all parsed segments correspond in Adaptation set $adaptationSetId, " .
    "representation $representationId: $differenceMessage"
);

$repDir = $session->getRepresentationDir($current_period, $current_adaptation_set, $current_representation);
$abs = get_DOM("$repDir/atomInfo.xml", 'atomlist'); // load mpd from url
if ($abs) {
    if ($abs->getElementsByTagName('mehd')->length && $abs->getElementsByTagName('mvhd')->length) {
        $fragmentDuration = $abs->getElementsByTagName('mehd')->item(0)->getAttribute('fragmentDuration');
        $fragmentDurationSeconds = (float)($fragmentDuration) /
                                   (float)($abs->getElementsByTagName('mvhd')->item(0)->getAttribute('timeScale'));

        $hdlrType = 'unknown';
        if ($abs->getElementsByTagName('hdlr')->length) {
            $hdlrType = $abs->getElementsByTagName('hdlr')->item(0)->getAttribute('hdlrType');
        }

        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "DVB: Section 'Duration Self consistency'",
            "Durations of the fragments should match the sum all contained segmenet durations",
            abs(($fragmentDurationSeconds - $totalSegmentDuration) / $totalSegmentDuration) <= 0.00001,
            "INFO",
            "The fragment duration of track with hdlrType '$hdlrType' in Adaptation $adaptationSetId, " .
            "representation $representationId matches the sum of it's segments",
            "The fragment duration of track with hdlrType '$hdlrType' in Adaptation $adaptationSetId, " .
            "representation $representationId does not match the sum of it's segments"
        );
    }
}

if (!empty($MPDDurationSeconds_array)) {
    $MPDDurationSeconds = 'Not_Set'; //to avoid giving an array to the python code as an argument
}


$sessionDir = $session->getDir();
$durationArrayString = implode(',', $segment_duration_array);



if($this->hasJPGraph && $this->hasJPBarGraph){
  $segmentDuration = $segment_duration_array;
  if (!$segmentDuration->len){
    $segmentDuration[] = 0;
  }
  $location = "$repDir/segmentDurations.png";

  $graph = new Graph();
  $graph->title->set("Segment duration report");
  $graph->SetScale("textlin");

  $p1 = new BarPlot($segmentDuration);
  $graph->Add($p1);

  $graph->Stroke($location);
}


// Check if the average segment duration is consistent with that of the duration information in the MPD
$segmentCount = sizeof($segment_duration_array);
$averageSegmentDuration = (array_sum($segment_duration_array) ) / ($segmentCount);
if ($MPDDurationSeconds != 'Not_Set') {
    $drift = abs((round($averageSegmentDuration, 2) - round($MPDDurationSeconds, 2)) / round($MPDDurationSeconds, 2));
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "DVB: Section 'Duration Self consistency'",
        "The average segment duration MUST be consistent with the durations advertised by the MPD",
        $drift <= 0.00001,
        "FAIL",
        "Average segment duration consistent with MPD",
        "Average segment duration not consistent with MPD"
    );
}

return $representationLocation . '_.png';
