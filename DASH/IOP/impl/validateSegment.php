<?php

global $session, $mpdHandler, $current_adaptation_set, $current_representation;

$rep_xml = $session->getRepresentationDir($mpdHandler->getSelectedPeriod(), $current_adaptation_set, $current_representation) .
  '/atomInfo.xml';

if (!file_exists($rep_xml)) {
    return;
}

$xml = DASHIF\Utility\parseDOM($rep_xml, 'atomlist');
if (!$xml) {
    return;
}

$this->validateSegmentCommon($xml);
$this->validateSegmentOnDemand($xml);
