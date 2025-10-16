<?php

global $logger, $mpdHandler;

$period = $mpdHandler->getFeatures()['Period'][$mpdHandler->getSelectedPeriod()];
$adaptationSet = $period['AdaptationSet'][$mpdHandler->getSelectedAdaptationSet()];
$repInfo = $adaptationSet['Representation'][$mpdHandler->getSelectedRepresentation()];

$codecs = ($adaptationSet['codecs'] == null) ? $repInfo['codecs'] : $adaptationSet['codecs'];
$isDolby = ($codecs != null) && (
  (substr($codecs, 0, 4) == "ac-3") ||
  (substr($codecs, 0, 4) == "ec-3") ||
  (substr($codecs, 0, 4) == "ac-4")
);

$mimeType = $repInfo['mimeType'] ?? $adaptationSet['mimeType'];

if ($isDolby && $mimeType == 'audio/mp4') {
    $this->compareTocWithDac4($representation);
}
