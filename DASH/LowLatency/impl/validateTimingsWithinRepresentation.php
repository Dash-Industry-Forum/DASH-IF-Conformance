<?php

global $session, $current_period, $presentation_times, $decode_times, $logger;

$representations = $adaptationSet['Representation'];

$presStarts = $infoFileAdapt[$representationId]['PresStart'];
$presEnds = $infoFileAdapt[$representationId]['PresEnd'];
$previousEarliestPresentationTime = 0;
$previousLatestPresentationTime = 0;
for ($i = 0; $i < sizeof($presStarts); $i++) {
    $earliestPresentationTime = $presStarts[$i];
    $latestPresentationTime = $presEnds[$i];

    if ($i > 0) {
        $logger->test(
            "DASH-IF IOP CR Low Latency Live",
            "Section 9.X.4.5",
            "CMAF chunks SHOULD be generated such that the range of presentation times contained in any CMAF chunk " .
            " of the CMAF Track do not overlap with the range of presentation times in any other CMAF chunk of the " .
            "same CMAF Track",
            $previousEarliestPresentationTime <= $earliestPresentationTime &&
            $latestPresentationTime >= $previousEarliestPresentationTime,
            "WARN",
            "Chunk $i does not overlap with previous",
            "Chunk $i overlaps with previous",
        );
    }

    $previousEarliestPresentationTime = $earliestPresentationTime;
    $previousLatestPresentationTime = $latestPresentationTime;

    $presentation_times[$current_period][$adaptationSetId][$representationId][] = $earliestPresentationTime;
}

$repXml = $session->getRepresentationDir($current_period, $adaptationSetId, $representationId) . '/atomInfo.xml';

if (file_exists($repXml)) {
    $xml = get_DOM($repXml, 'atomlist');

    if (!$xml) {
        return;
    }

    $timescale = $xml->getElementsByTagName('mdhd')->item(0)->getAttribute('timescale');
    $tfdtBoxes = $xml->getElementsByTagName('tfdt');
    foreach ($tfdtBoxes as $tfdt) {
        $decode_times[$current_period][$adaptationSetId][$representationId][] =
        $tfdt->getAttribute('baseMediaDecodeTime') / $timescale;
    }
}
