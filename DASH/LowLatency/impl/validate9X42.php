<?php

global $session, $mpdHandler, $logger;

$eventMessageStreamsPresent = false;
$inbandEventMessageStreamsPresent = false;
$representations = $adaptationSet['Representation'];
foreach ($representations as $representationId => $representation) {
    $eventStreams = ($representation['EventStream']) ?
      $representation['EventStream'] : $adaptationSet['EventStream'];
    $inbandEventStreams = ($representation['InbandEventStream']) ?
      $representation['InbandEventStream'] : $adaptationSet['InbandEventStream'];

    $rep_xml = $session->getRepresentationDir($mpdHandler->getSelectedPeriod(), $adaptationSetId, $representationId) . '/atomInfo.xml';

    if (file_exists($rep_xml)) {
        $xml = get_DOM($rep_xml, 'atomlist');
        if (!$xml) {
            continue;
        }

        $emsgBoxes = $xml->getElementsByTagName('emsg');
        if ($emsgBoxes->length > 0 || $eventStreams != null || $inbandEventStreams != null) {
            $eventMessageStreamsPresent = true;
        }
        if ($emsgBoxes->length > 0 || $inbandEventStreams != null) {
            $inbandEventMessageStreamsPresent = true;
        }
    }
}

$logger->test(
    "DASH-IF IOP CR Low Latency Live",
    "Section 9.X.4.2",
    "Event message streams may be used in low latency media presentations Adaptation Set or a " .
    "Low Latency Chunked Adaptation Set",
    $eventMessageStreamsPresent,
    "PASS",
    "Found in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' . ($adaptationSetId + 1),
    "Not found in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' . ($adaptationSetId + 1)
);
if ($inbandEventMessageStreamsPresent) {
    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.2",
        "If Inband Event Streams are present, then they SHOULD be carried in Low Latency " .
        "Segment Adaptation Sets",
        $isLowLatency,
        "PASS",
        "Found in Low Latency Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set ' .
        ($adaptationSetId + 1),
        "Not found in non-Low Latency Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation Set '
        . ($adaptationSetId + 1)
    );
}
