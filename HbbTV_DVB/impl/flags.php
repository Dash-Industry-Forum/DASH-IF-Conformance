<?php

global $additional_flags, $mpd_features, $hbbtv_conformance, $dvb_conformance,
        $current_period, $current_adaptation_set, $current_representation;

$adaptation = $mpd_features['Period'][$current_period]['AdaptationSet'][$current_adaptation_set];
$representation = $adaptation['Representation'][$current_representation];

## General
if ($this->HbbTvEnabled) {
    $additional_flags .= ' -hbbtv -isolive';
}
if ($this->DVBEnabled) {
    $additional_flags .= ' -dvb';
}

## Framerate checks
$framerate = 0;
if ($representation['frameRate'] != null) {
    $framerate = $representation['frameRate'];
}
if ($adaptation['frameRate'] != null) {
    $framerate = $adaptation['frameRate'];
}
$additional_flags .= ' -framerate ' . $framerate;

## Codec checks
$codecs = $representation['codecs'];
if ($adaptation['codecs'] != null) {
    $codecs = $adaptation['codecs'];
}
$codec_arr = explode('.', $codecs);
if ((strpos($codecs, 'hev') !== false || strpos($codecs, 'hvc') !== false)) {
    if (!empty($codec_arr[1])) {
        $additional_flags .= " -codecprofile " . $codec_arr[1];
    }
    if (!empty($codec_arr[3])) {
        $additional_flags .= " -codectier " . substr($codec_arr[3], 0, 1);
    }
    if (!empty($codec_arr[3]) && strlen($codec_arr[3]) > 1) {
        $additional_flags .= " -codeclevel " . substr($codec_arr[3], 1);
    }
}
if (strpos($codecs, 'avc') !== false) {
    if (!empty($codec_arr[1]) && strlen($codec_arr[1]) > 1) {
        $additional_flags .= " -codecprofile " . (string)hexdec(substr($codec_arr[1], 0, 2));
    }
    if (!empty($codec_arr[1]) && strlen($codec_arr[1]) == 6) {
        $additional_flags .= " -codeclevel " . (string)hexdec(substr($codec_arr[1], -2));
    }
}

## Content protection checks
$contentProtectionLength = 0;
if ($representation['ContentProtection'] != null) {
    $contentProtectionLength = sizeof($representation['ContentProtection']);
}
if ($adaptation['ContentProtection'] != null) {
    $contentProtectionLength = sizeof($adaptation['ContentProtection']);
}
if ($contentProtectionLength > 0) {
    $additional_flags .= ' -dash264enc';
}
