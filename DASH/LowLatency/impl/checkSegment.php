<?php

global $session, $mpdHandler;

$repXml = $session->getRepresentationDir($mpdHandler->getSelectedPeriod(), $adaptationSetId, $representationId) . '/atomInfo.xml';

if (!file_exists($repXml)) {
    return null;
}

$xml = get_DOM($repXml, 'atomlist');
if (!$xml) {
    return null;
}

$segmentIndex = 0;
$moofsPerSegments = array();

$timescale = $xml->getElementsByTagName('mdhd')->item(0)->getAttribute('timescale');
$moofs = $xml->getElementsByTagName('moof');
$truns = $xml->getElementsByTagName('trun');
$cumulatedDuration = 0;
for ($i = 0; $i < $moofs->length; $i++) {
    $trun = $truns->item($i);

    $segmentDuration = $segmentDurations[$segmentIndex];
    $cumulatedDuration += $trun->getAttribute('cummulatedSampleDuration') / $timescale;

    if ($segmentDuration == PHP_INT_MAX) {
        $moofsPerSegments[] = $moofs->length - $i + 1;
        break;
    }

    if ($cumulatedDuration > $segmentDuration) {
        $moofsPerSegments[] = $i;
        $segmentIndex++;
        $cumulatedDuration = 0;
    }
}

return $moofsPerSegments;
