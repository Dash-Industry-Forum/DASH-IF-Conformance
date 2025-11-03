<?php

global $mpdHandler, $period_timing_info, $logger, $session;


$repDir = $session->getSelectedRepresentationDir();
$errorFilePath = "$repDir/stderr.txt";

$xmlRepresentation = DASHIF\Utility\parseDOM("$repDir/atomInfo.xml", 'atomlist');
if ($xmlRepresentation) {
    if ($this->DVBEnabled) {
        $mediaTypes = DASHIF\Utility\mediaTypes(
            $mpdHandler->getDom()->getElementsByTagName('Period')->item($mpdHandler->getSelectedPeriod())
        );
        $this->commonDVBValidation($xmlRepresentation, $mediaTypes);
    }
}
