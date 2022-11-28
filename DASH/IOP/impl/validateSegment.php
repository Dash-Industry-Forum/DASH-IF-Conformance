<?php

global $session;

$rep_xml = $session->getSelectedRepresentationDir() .  '/atomInfo.xml';

if (!file_exists($rep_xml)) {
    return;
}

$xml = DASHIF\Utility\parseDOM($rep_xml, 'atomlist');
if (!$xml) {
    return;
}

$this->validateSegmentCommon($xml);
$this->validateSegmentOnDemand($xml);
