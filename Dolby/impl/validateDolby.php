<?php

global $mpd_features, $current_period;
global $current_adaptation_set, $current_representation;
global $session, $logger;

$period = $mpd_features['Period'][$current_period];
$adaptationSet = $period['AdaptationSet'][$current_adaptation_set];
$representation = $adaptationSet['Representation'][$current_representation];

$codecs = ($adaptationSet['codecs'] == NULL) ? $representation['codecs'] : $adaptationSet['codecs'];
$isDolby = (($codecs != NULL) && ((substr($codecs, 0, 4) == "ac-3") || (substr($codecs, 0, 4) == "ec-3") || (substr($codecs, 0, 4) == "ac-4")));

if ($isDolby && $representation['mimeType'] == 'audio/mp4' )
{
  $atomXml = $session->getRepresentationDir($current_period, $current_adaptation_set, $current_representation);
  $xml = get_DOM("$atomXml/atomInfo.xml", 'atomlist');
  $this->compareTocWithDac4($xml);
}
