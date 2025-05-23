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
// TODO: we should probably romove these checks for files existance. 
$this->validateSegmentCommon($representation);
$this->validateSegmentOnDemand($representation);
