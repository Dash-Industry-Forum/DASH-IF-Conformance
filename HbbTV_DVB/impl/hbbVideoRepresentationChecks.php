<?php

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
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "HbbTV Secion E.2.3",
        "The profile-specific MPD shall provide @width information for all Representations",
        $width != null || $representation->getAttribute('width') != null,
        "FAIL",
        "Valid width found for representation $i, adaptation set $adaptationNumber, period $periodNumber",
        "No width found for representation $i, adaptation set $adaptationNumber, period $periodNumber"
    );
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "HbbTV Secion E.2.3",
        "The profile-specific MPD shall provide @height information for all Representations",
        $height != null || $representation->getAttribute('height') != null,
        "FAIL",
        "Valid height found for representation $i, adaptation set $adaptationNumber, period $periodNumber",
        "No height found for representation $i, adaptation set $adaptationNumber, period $periodNumber"
    );
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "HbbTV Secion E.2.3",
        "The profile-specific MPD shall provide @frameRate information for all Representations",
        $frameRate != null || $representation->getAttribute('frameRate') != null,
        "FAIL",
        "Valid frameRate found for representation $i, adaptation set $adaptationNumber, period $periodNumber",
        "No frameRate found for representation $i, adaptation set $adaptationNumber, period $periodNumber"
    );
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "HbbTV Secion E.2.3",
        "The profile-specific MPD shall provide @scanType information for all Representations",
        $scanType != null || $representation->getAttribute('scanType') != null,
        "FAIL",
        "Valid scanType found for representation $i, adaptation set $adaptationNumber, period $periodNumber",
        "No scanType found for representation $i, adaptation set $adaptationNumber, period $periodNumber"
    );

    ///\Discussion This check doesn't seem to do the same as above
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
