<?php

global $mpd_features, $current_period;
global $current_adaptation_set, $current_representation;
global $session;

$period = $mpd_features['Period'][$current_period];
$adaptationSet = $period['AdaptationSet'][$current_adaptation_set];
$representation = $adaptationSet['Representation'][$current_representation];

///\TodoRefactor: Re-enalbe this one
return;
if ($representation['mimeType'] == 'audio/mp4' )
{
  $atomXml = $session->getRepresentationDir($current_period, $current_adaptation_set, $current_representation);
  $xml = get_DOM("$atomXml/atomInfo.xml", 'atomlist');
  $this->compareTocWithDac4($xml);
}
