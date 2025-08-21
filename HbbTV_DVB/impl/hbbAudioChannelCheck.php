<?php

global $logger;

$scheme = $channelConfiguration->item(0)->getAttribute("schemeIdUri");
if (strpos($codecs, 'ec-3') !== false) {
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
}
