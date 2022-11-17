<?php

global $current_adaptation_set, $current_representation;
global $session, $logger, $mpdHandler;

$period = $mpdHandler->getFeatures()['Period'][$mpdHandler->getSelectedPeriod()];
$adaptationSet = $period['AdaptationSet'][$current_adaptation_set];
$representation = $adaptationSet['Representation'][$current_representation];

$codecs = ($adaptationSet['codecs'] == NULL) ? $representation['codecs'] : $adaptationSet['codecs'];
$isDolby = (($codecs != NULL) && ((substr($codecs, 0, 4) == "ac-3") || (substr($codecs, 0, 4) == "ec-3") || (substr($codecs, 0, 4) == "ac-4")));

$mimeType = $representation['mimeType'];
if (!$mimeType){
  $mimeType = $adaptationSet['mimeType'];
}

if ($isDolby && $mimeType == 'audio/mp4' )
{
  $atomXml = $session->getRepresentationDir($mpdHandler->getSelectedPeriod(), $current_adaptation_set, $current_representation);
  $xml = get_DOM("$atomXml/atomInfo.xml", 'atomlist');
  if ($xml){
  $this->compareTocWithDac4($xml);
  }
}
