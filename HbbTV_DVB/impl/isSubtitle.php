<?php

global $mpd_features, $current_period, $current_adaptation_set, $current_representation,
       $session_dir, $subtitle_segments_location;

$subtitleRepresentation = false;
$adaptation = $mpd_features['Period'][$current_period]['AdaptationSet'][$current_adaptation_set];
$representation = $adaptation['Representation'][$current_representation];

if (
        ($adaptation['mimeType'] == 'application/mp4' || $representation['mimeType'] == 'application/mp4') &&
        ($adaptation['codecs'] == 'stpp' || $representation['codecs'] == 'stpp')
) {
    $contentType = $adaptation['contentType'];
    if ($contentType == '') {
        if (sizeof($adaptation['ContentComponent']) != 0) {
            $contentComponent = $adaptation['ContentComponent'][0];
            if ($contentComponent['contentType'] == 'text') {
                $subtitleRepresentation = true;
            }
        } else {
            $subtitleRepresentation = true;
        }
    } elseif ($contentType == 'text') {
        $subtitleRepresentation = true;
    }
}

if ($subtitleRepresentation) {
    $subtitle_dir = "$session_dir/Period$current_period/Adapt$current_adaptation_set" .
                    "rep$current_representation/Subtitles/";
    if (!file_exists($subtitle_dir)) {
        $oldmask = umask(0);
        mkdir($subtitle_dir, 0777, true);
        umask($oldmask);
    }
}

return $subtitleRepresentation;
