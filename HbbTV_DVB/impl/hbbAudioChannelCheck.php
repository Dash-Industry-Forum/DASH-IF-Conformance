<?php

global $logger;

$scheme = $channelConfiguration->item(0)->getAttribute("schemeIdUri");
$value = $channelConfiguration->item(0)->getAttribute("value");
if (strpos($codecs, 'mp4a') !== false) {
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "HbbTV Secion E.2.5",
        "For HE-AAC the Audio Channel Configuration shall use " .
        "urn:mpeg:dash:23003:3:audio_channel_configuration:2011 schemeIdURI",
        strpos($scheme, "urn:mpeg:dash:23003:3:audio_channel_configuration:2011") !== false,
        "FAIL",
        "Scheme found for $representationNumber, adaptation $adaptationNumber, period $periodNumber",
        "Scheme not found for $representationNumber, adaptation $adaptationNumber, period $periodNumber",
    );

    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "HbbTV Secion E.2.5",
        "For HE-AAC the Audio Channel Configuration shall use " .
        "urn:mpeg:dash:23003:3:audio_channel_configuration:2011 schemeIdURI " .
        "with value set to an integer number",
        is_numeric($value) && $value == round($value),
        "FAIL",
        "Valid integer found for representation $representationNumber, adaptation $adaptationNumber, " .
        "period $periodNumber",
        "$value is not a valid integer in representation $representationNumber, adaptation $adaptationNumber, " .
        "period $periodNumber",
    );
} elseif (strpos($codecs, 'ec-3') !== false) {
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "HbbTV Secion E.2.5",
        "For E-AC-3 the Audio Channel Configuration shall use either the " .
        "tag:dolby.com,2014:dash:audio_channel_configuration:2011 " .
        "or urn:dolby:dash:audio_channel_configuration:2011 schemeIdURI",
        strpos($scheme, "tag:dolby.com,2014:dash:audio_channel_configuration:2011") !== false ||
        strpos($scheme, "urn:dolby:dash:audio_channel_configuration:2011") !== false,
        "FAIL",
        "Scheme found for $representationNumber, adaptation $adaptationNumber, period $periodNumber",
        "Scheme not found for $representationNumber, adaptation $adaptationNumber, period $periodNumber",
    );
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "HbbTV Secion E.2.5",
        "For E-AC-3 the Audio Channel Configuration value shall contain a four digit hexadecimal number",
        strlen($value) == 4 && ctype_xdigit($value),
        "FAIL",
        "Valid value found for representation $representationNumber, adaptation $adaptationNumber, period " .
        "$periodNumber",
        "$value is not a valid hexadecimal number in representation $representationNumber, adaptation " .
        "$adaptationNumber, period $periodNumber",
    );
}
