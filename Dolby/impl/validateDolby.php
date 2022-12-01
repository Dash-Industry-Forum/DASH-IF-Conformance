<?php

global $session, $logger, $mpdHandler;

$period = $mpdHandler->getFeatures()['Period'][$mpdHandler->getSelectedPeriod()];
$adaptationSet = $period['AdaptationSet'][$mpdHandler->getSelectedAdaptationSet()];
$representation = $adaptationSet['Representation'][$mpdHandler->getSelectedRepresentation()];

$codecs = ($adaptationSet['codecs'] == NULL) ? $representation['codecs'] : $adaptationSet['codecs'];
$isDolby = (($codecs != NULL) && ((substr($codecs, 0, 4) == "ac-3") || (substr($codecs, 0, 4) == "ec-3") || (substr($codecs, 0, 4) == "ac-4")));

$mimeType = $representation['mimeType'];
if (!$mimeType){
  $mimeType = $adaptationSet['mimeType'];
}

if ($isDolby && $mimeType == 'audio/mp4' )
{
  $atomXml = $session->getSelectedRepresentationDir();
  $xml = DASHIF\Utility\parseDOM("$atomXml/atomInfo.xml", 'atomlist');
  if ($xml){
  $this->compareTocWithDac4($xml);
  }
}
