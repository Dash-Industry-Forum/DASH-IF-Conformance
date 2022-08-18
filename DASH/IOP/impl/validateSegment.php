<?php

global $session, $current_period, $current_adaptation_set, $current_representation;

$rep_xml = $session->getRepresentationDir($current_period, $current_adaptation_set, $current_representation) .
  '/atomInfo.xml';

if (!file_exists($rep_xml)) {
    return;
}

$xml = get_DOM($rep_xml, 'atomlist');
if (!$xml) {
    return;
}

$this->validateSegmentCommon($xml);
$this->validateSegmentOnDemand($xml);
