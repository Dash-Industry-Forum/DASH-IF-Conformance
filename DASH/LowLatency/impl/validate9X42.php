<?php

global $session_dir, $current_period, $adaptationSet_template, $reprsentation_template, $logger;

$eventMessageStreamsPresent = false;
$inbandEventMessageStreamsPresent = false;
$representations = $adaptationSet['Representation'];
foreach ($representations as $representationId => $representation) {
    $eventStreams = ($representation['EventStream']) ?
      $representation['EventStream'] : $adaptationSet['EventStream'];
    $inbandEventStreams = ($representation['InbandEventStream']) ?
      $representation['InbandEventStream'] : $adaptationSet['InbandEventStream'];

    $adapt_dir = str_replace('$AS$', $adaptationSetId, $adaptationSet_template);
    $rep_xml_dir = str_replace(
        array('$AS$', '$R$'),
        array($adaptationSetId, $representationId),
        $reprsentation_template
    );
    $rep_xml = $session_dir . '/Period' . $current_period . '/' . $adapt_dir . '/' . $rep_xml_dir . '.xml';

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
    "Found in Period " . ($current_period + 1) . ' Adaptation Set ' . ($adaptationSetId + 1),
    "Not found in Period " . ($current_period + 1) . ' Adaptation Set ' . ($adaptationSetId + 1)
);
if ($inbandEventMessageStreamsPresent) {
    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.2",
        "If Inband Event Streams are present, then they SHOULD be carried in Low Latency " .
        "Segment Adaptation Sets",
        $isLowLatency,
        "PASS",
        "Found in Low Latency Period " . ($current_period + 1) . ' Adaptation Set ' .
        ($adaptationSetId + 1),
        "Not found in non-Low Latency Period " . ($current_period + 1) . ' Adaptation Set '
        . ($adaptationSetId + 1)
    );
}
