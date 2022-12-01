<?php

global $mpdHandler;

$subtitleRepresentation = false;
$adaptation = $mpdHandler->getFeatures()['Period'][$mpdHandler->getSelectedPeriod()]['AdaptationSet'][$mpdHandler->getSelectedAdaptationSet()];
$representation = $adaptation['Representation'][$mpdHandler->getSelectedRepresentation()];

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
    $subtitle_dir = $session->getSelectedRepresentationDir() .
    '/Subtitles/';
    if (!file_exists($subtitle_dir)) {
        $oldmask = umask(0);
        mkdir($subtitle_dir, 0777, true);
        umask($oldmask);
    }
}

return $subtitleRepresentation;
