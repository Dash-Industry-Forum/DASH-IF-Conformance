<?php

global $session_dir, $current_period, $adaptation_set_template, $reprsentation_template, $reprsentation_mdat_template;

$adaptDir = str_replace('$AS$', $adapatationSetId, $adaptation_set_template);
$repXmlDir = str_replace(array('$AS$', '$R$'), array($adapatationSetId, $representationId), $reprsentation_template);
$repXml = $session_dir . '/Period' . $current_period . '/' . $adaptDir . '/' . $repXmlDir . '.xml';

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
