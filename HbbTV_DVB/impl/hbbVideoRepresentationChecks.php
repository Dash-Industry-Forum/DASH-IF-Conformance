<?php

global $logger;
$width = $adaptation->getAttribute('width');
$height = $adaptation->getAttribute('height');
$frameRate = $adaptation->getAttribute('frameRate');
$scanType = $adaptation->getAttribute('scanType');
$codecs = $adaptation->getAttribute('codecs');

$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "HbbTV Secion E.2.1",
    "The video content referenced by MPD shall only be encoded using video codecs defined in 7.3.1 (AVC)",
    $codecs == null || strpos($codecs, 'avc') !== false,
    "FAIL",
    "Only valid codecs found for adaptation set $adaptationNumber, period $periodNumber",
    "Invalid codecs in $codecs found for adaptation set $adaptationNumber, period $periodNumber"
);

$representations = $adaptation->getElementsByTagName('Representation');

for ($i = 0; $i < $representations->length; $i++) {
    $representation = $representations->item($i);
    ///\Correctness This check doesn't seem to do the same as above
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "HbbTV Secion E.2.1",
        "The video content referenced by MPD shall only be encoded using video codecs defined in 7.3.1 (AVC)",
        $codecs != null || strpos($representation->getAttribute('codecs'), 'avc') !== false,
        "FAIL",
        "Only valid codecs found for adaptation set $adaptationNumber, period $periodNumber",
        "Invalid codecs in " . $representation->getAttribute('codecs') . " found for adaptation set " .
        "$adaptationNumber, period $periodNumber"
    );
}
