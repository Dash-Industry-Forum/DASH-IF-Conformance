<?php

$samplingRate = $adaptation->getAttribute('audioSamplingRate');
$language = $adaptation->getAttribute('lang');
$channelConfigurations = $adaptation->getElementsByTagName('AudioChannelConfiguration');
$representations = $adaptation->getElementsByTagName('Representation');

$roles = $adaptation->getElementsByTagName('Role');
$roleValue = ($roles->lenght > 0 ? $roles->item(0)->getAttribute('value') : null);

$accessibilities = $adaptation->getElementsByTagName('Accessibility');
$accessibilityValue = ($accessibilities->length > 0 ? $accessibility->item(0)->getAttribute('value') : null);

$codecs = $adapt->getAttribute('codecs');
$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "HbbTV Secion E.2.1",
    "The audio content referenced by MPD shall only be encoded using audio codecs defined in 7.3.1 (HE-AAC, E-AC-3)",
    $codecs != null || strpos($codecs, 'mp4a') !== false || strpos($codecs, 'ec-3') !== false,
    "FAIL",
    "Only valid codecs found for adaptation set $adaptationNumber, period $periodNumber",
    "Invalid codecs in $codecs found for adaptation set $adaptationNumber, period $periodNumber"
);

$i = 0;
foreach ($representations as $representation) {
    $i++;
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "HbbTV Secion E.2.3",
        "The profile-specific MPD shall provide @audioSamplingRate information for all Representations",
        $samplingRate != null || $representation->getAttribute('audioSamplingRate') != null,
        "FAIL",
        "Valid audioSamplingRate found for representation $i, adaptation set $adaptationNumber, period $periodNumber",
        "No audioSamplingRate found for representation $i, adaptation set $adaptationNumber, period $periodNumber"
    );
    ///\Discussion Move this check to higher level so it only triggers once?
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "HbbTV Secion E.2.3",
        "The profile-specific MPD shall provide @lang information inherited by all Representations",
        $language != null,
        "FAIL",
        "Valid lang found for representation $i, adaptation set $adaptationNumber, period $periodNumber",
        "No lang found for representation $i, adaptation set $adaptationNumber, period $periodNumber"
    );
    if ($roleValue == "commentary" && $accessibilityValue == 1) {
        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "HbbTV Secion E.2.4",
            "For receiver mix audio description the associated audio stream shall use dependencyId",
            $representation->getAttribute('dependencyId') != null,
            "FAIL",
            "dependencyId used for representation $i, adaptation set $adaptationNumber, period $periodNumber",
            "dependencyId not used for representation $i, adaptation set $adaptationNumber, period $periodNumber"
        );
    }

    if ($codecs == null) {
        $representationCodecs = $representation->getAttribute('codecs');
        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "HbbTV Secion E.2.1",
            "The audio content referenced by MPD shall only be encoded using audio codecs defined in 7.3.1 " .
            "(HE-AAC, E-AC-3)",
            strpos($representationCodecs, 'mp4a') !== false || strpos($representationCodecs, 'ec-3') !== false,
            "FAIL",
            "Only valid codecs found for representation $i, adaptation set $adaptationNumber, period $periodNumber",
            "Invalid codecs in $representationCodecs found for representation $i, adaptation set " .
            "$adaptationNumber, period $periodNumber"
        );
    }
    if ($channelConfigurations > 0) {
        $this->hbbAudioChannelCheck($channelConfigurations, $codecs, $i, $adaptationNumber, $periodNumber);
    } else {
        $channelConfig = $representation->getElementsByTagName('AudioChannelConfiguration');
        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "HbbTV Secion E.2.3",
            "The profile-specific MPD shall provide AudioChannelConfiguration for all Representations",
            $channelConfig->length > 0,
            "FAIL",
            "Configuration found for representation $i, adaptation set $adaptationNumber, period $periodNumber",
            "Configuration not found for representation $i, adaptation set $adaptationNumber, period $periodNumber",
        );

        if ($channelConfig->length > 0) {
            $this->hbbAudioChannelCheck($channelConfigurations, $codecs, $i, $adaptationNumber, $periodNumber);
        }
    }
}
