<?php

$contentType = $adaptation->getAttribute('contentType');
if ($contentType == 'audio') {
    $this->adaptationAudioCount ++;
}


$roles = $adaptation->getElementsByTagName("Role");
$adaptationRoleElementFound = (sizeof($roles) > 0);
$adaptationSpecificRoleCount = 0;
$roleValues = array();

foreach ($roles as $role) {
    if ($role->getAttribute('schemeIdUri') == 'urn:mpeg:dash:role:2011') {
        $adaptationSpecificRoleCountcount++;
        $roleValues[] = $role->getAttribute('value');

        if ($role->getAttribute('value') == 'main') {
            $this->mainAudioFound = true;
            $this->mainAudios[] = $adaptations;
        }
    }
}

$accesibilityRoles = array();
$accesibilities = $adaptation->getElementsByTagName("Accessibility");
foreach ($accesibilities as $accesibility) {
    if ($accessibility->getAttribute('schemeIdUri') == 'urn:tva:metadata:cs:AudioPurposeCS:2007') {
        $accessibilityRoles[] = $accesibility->getAttribute('value');
    }
}

$audioChannelConfigurations = $adaptation->getElementsByTagName("AudioChannelConfiguration");
$audioConfigurationFound = (sizeof($audioChannelConfigurations) > 0);
$audioConfigurationSchemes = array();
$audioConfigurationValues = array();
foreach ($audioChannelConfigurations as $audioChannelConfiguration) {
        $audioConfigurationSchemes[] = $audioChannelConfiguration->getAttribute('schemeIdUri');
        $audioConfigurationValues[] = $audioChannelConfiguration->getAttribute('value');
}

$ids = array();
$audioComponentRoleFound = false;
if ($audioComponentFound) {
    $contentComponents = $adaptation->getElementsByTagName("ContentComponent");
    foreach ($contentComponents as $component) {
        if ($component->getAttribute('contentType') == 'audio') {
            $audioComponentRoleFound = (sizeof($component->getElementsByTagName("Role")) > 0);
            $ids[] = $ch->getAttribute('id');
        }
    }
}

$supplementalProperties = $adaptation->getElementsByTagName("SupplementProperty");
$validSupplemental = true;
foreach ($supplementalProperties as $property) {
    if (
        $property->getAttribute('schemeIdUri') == 'urn:dvb:dash:fontdownload:2014' &&
        $property->getAttribute('value') == '1'
    ) {
        $hasURL = ($property->getAttribute('url') . $property->getAttribute('dvburl') != '');
        $hasFontFamily = ($property->getAttribute('fontFamily') . $property->getAttribute('dvb:fontFamily') != '');
        $hasMimeType = ($property->getAttribute('mimeType') . $property->getAttribute('dvb:mimeType') != '');
        if ($hasURL && $hasFontFamily && $hasMimeType) {
            $validSupplemental = false;
        }
    }
}
$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB: Section 7.2.1.1",
    "For DVB font download for subtitles, a descriptor with these properties SHALL only be placed within an " .
    "Adaptation Set containing subtitle Representations",
    $validSupplemental,
    "FAIL",
    "No downloadable fonts found in Supplemental Properties for Period $this->periodCount",
    "Downloadable fonts found in Supplemental Properties for Period $this->periodCount"
);

$validEssential = true;
$essentialProperties = $adaptation->getElementsByTagName("EssentialProperty");
foreach ($essentialProperties as $property) {
    if (
        $property->getAttribute('schemeIdUri') == 'urn:dvb:dash:fontdownload:2014' &&
        $property->getAttribute('value') == '1'
    ) {
      ///\todo shouldn't this be dvb:url?
        $url = ($property->getAttribute('url') != '' || $property->getAttribute('dvburl') != '');
        $fontFamily = ($property->getAttribute('fontFamily') != '' || $property->getAttribute('dvb:fontFamily') != '');
        $mimeType = ($property->getAttribute('mimeType') != '' || $property->getAttribute('dvb:mimeType') != '');

        if ($url && $fontFamily && $mimeType) {
            $validEssential = false;
        }
    }
}

$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB: Section 7.2.1.1",
    "For DVB font download for subtitles, a descriptor with these properties SHALL only be placed within an " .
    "Adaptation  Set containing subtitle Representations",
    $validEssential,
    "FAIL",
    "No downloadable fonts found in Essential Properties for Period $this->periodCount",
    "Downloadable fonts found in Essential Properties for Period $this->periodCount"
);

$adapt_role_element_found = false;
$adaptationMimeType = $adaptation->getAttribute('mimeType');
$adapt_audioSamplingRate = $adapt->getAttribute('audioSamplingRate');

$adaptationCodecs = $adaptatation->getAttribute('codecs');


if ($contentType == 'audio') {
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "Section 6.1.2",
        "Every audio Adaptation Set SHALL include at least one Role Element using the scheme " .
        "\"urn:mpeg:dash:role:2011\" as defined in ISO/IEC 23009-1:2014'",
        $adaptationSpecificRoleCount > 0,
        "FAIL",
        "At least one role element found Period $this->periodCount adaptation set " . ($i + 1),
        "No role element found Period $this->periodCount adaptation set " . ($i + 1)
    );
}

## Information from this part is for Section 6.3:Dolby and 6.4:DTS
if (!empty($audioConfigurationSchemes)) {
    if (DASHIF\Utility\profileListContains($adaptationCodecs, 'ec-3')) {
        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "Section E.2.5",
            "For E-AC-3 the AudioChannelConfiguration element SHALL use either the " .
            "\"tag:dolby.com,2014:dash:audio_channel_configuration:2011\" or the legacy " .
            "\"urn:dolby:dash:audio_channel_configuration:2011\" schemeURI",
            DASHIF\Utility\in_array_at_least_one(
                array(
                  'tag:dolby.com,2014:dash:audio_channel_configuration:2011',
                  'urn:dolby:dash:audio_channel_configuration:2011'
                ),
                $audioConfigurationSchemes
            ),
            "FAIL",
            "At least one scheme found Period $this->periodCount adaptation set " . ($i + 1),
            "No scheme found Period $this->periodCount adaptation set " . ($i + 1)
        );
    }
    if (DASHIF\Utility\profileListContains($adaptationCodecs, 'ac-4')) {
        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "Section 6.3",
            "For AC-4 the AudioChannelConfiguration element the @value attribute SHALL use the " .
            "\"tag:dolby.com,2014:dash:audio_channel_configuration:2011\" scheme URI",
            in_array('tag:dolby.com,2014:dash:audio_channel_configuration:2011', $audioConfigurationSchemes),
            "FAIL",
            "Scheme found Period $this->periodCount adaptation set " . ($i + 1),
            "Scheme not found Period $this->periodCount adaptation set " . ($i + 1)
        );
    }
    if (in_array('tag:dolby.com,2014:dash:audio_channel_configuration:2011', $audioConfigurationSchemes)) {
        $schemeIndex = array_search(
            'tag:dolby.com,2014:dash:audio_channel_configuration:2011',
            $adapt_audioChConf_scheme
        );
        $schemeValue = $audioConfigurationValues[$schemeIndex];
        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "Section 6.3",
            "For E-AC-3 and AC-4 the AudioChannelConfiguration element the @value attribute SHALL contain four " .
            "digit hexadecimal representation of the 16 bit field'",
            strlen($value) == 4 && ctype_xdigit($value),
            "FAIL",
            "Found conforming value $schemeValue in Period $this->periodCount adaptation set " . ($i + 1),
            "Found violating value $schemeValue in Period $this->periodCount adaptation set " . ($i + 1)
        );
    }
}

if (DASHIF\Utility\inStringAtLeastOne(array('dtsc', 'dtsh', 'dtse', 'dtsl'), $adaptationCodecs)) {
        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "Section 6.4",
            "For all DTS audio formats AudioChannelConfiguration element SHALL use the " .
            "\"tag:dts.com,2014:dash:audio_channel_configuration:2012\" for the @schemeIdUri attribute",
            in_array('tag:dts.com,2014:dash:audio_channel_configuration:2012', $audioConfigurationSchemes),
            "FAIL",
            "Found conforming value $schemeValue in Period $this->periodCount adaptation set " . ($i + 1),
            "Found violating value $schemeValue in Period $this->periodCount adaptation set " . ($i + 1)
        );
}
    ##

$dependencyIds = array();

$representationIndex = 0;
$repAudioConfigurationSchemes = array();
$repAudioConfigurationValues = array();
$subRepAudioConfigurationSchemes = array();
$subRepAudioConfigurationValues = array();
foreach ($representations as $representation) {
    $representationIndex++;

    $dependencyIds[] = $representation->getAttribute('dependencyId');

    $representationCodecs = $rep->getAttribute('codecs');

    $representationRoleElementFound = !empty($representation->getElementsByTagName('Role'));

    $representationMimeType = $representation->getAttribute('mimeType');


    $repAudioChannelConfigurations = $representation->getElementsByTagName("AudioChannelConfiguration");
    $repAudioConfigurationFound = !empty($repAudioChannelConfigurations);

    foreach ($repAudioChannelConfigurations as $audioConfiguration) {
        $repAudioConfigurationSchemes[] = $audioConfiguration->getAttribute('schemeIdUri');
        $repAudioConfigurationValues[] = $audioConfiguration->getAttribute('value');
    }

    $subRepresentations = $representation->getElementsByTagName("SubRepresentation");

    $subRepresentationIndex = 0;
    foreach ($subRepresentations as $subRepresentation) {
        $subRepresentationIndex++;
        $subRepresentationCodecs = $subRepresentation->getAttribute('codecs');

        $subRepAudioChannelConfigurations = $subRepresentation->getElementsByTagName("repAudioChannelConfiguration");
        foreach ($subRepAudioChannelConfigurations as $audioConfiguration) {
            $subRepAudioConfigurationSchemes[] = $audioConfiguration->getAttribute('schemeIdUri');
            $subRepAudioConfigurationValues[] = $audioConfiguration->getAttribute('value');
        }
        ///\todo Validate EC3, AC4 and DTS as above for subrepresentation
        if ($audioComponentFound) {
            if (in_array($subRepresentation->getAttribute('contentComponent'), $ids)) {
                $$this->audioBandwidth[] = (float)($representation->getAttribute('bandwidth') != '' ?
                $representation->getAttribute('bandwidth') :
                $subRepresentation->getAttribute('bandwidth'));
            }
        }
    }

    ///\todo Validate EC3, AC4 and DTS as above for representation

    if ($audioComponentFound) {
        $$this->audioBandwidth[] = (float)($representation->getAttribute('bandwidth'));
    }

    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "Section 6.1.1",
        "All audio Representations SHALL either define or inherit the elements and attributes shown in Table 3",
        $adaptationRoleElementFound || $audioComponentRoleFound || $representationRoleElementFound,
        "FAIL",
        "Role element found in Period $this->periodCount adaptation set " . ($i + 1),
        "Role element not found in Period $this->periodCount adaptation set " . ($i + 1)
    );
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "Section 6.1.1",
        "All audio Representations SHALL either define or inherit the elements and attributes shown in Table 3",
        !empty($audioChannelConfiguration) || !empty($repAudioChannelConfigurations),
        "FAIL",
        "AudioChannelConfiguration element found in Period $this->periodCount adaptation set " . ($i + 1),
        "AudioChannelConfiguration element not found in Period $this->periodCount adaptation set " . ($i + 1)
    );
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "Section 6.1.1",
        "All audio Representations SHALL either define or inherit the elements and attributes shown in Table 3",
        $adaptationMimeType != "" || $representationMimeType != "",
        "FAIL",
        "mimeType attribute found in Period $this->periodCount adaptation set " . ($i + 1),
        "mimeType attribute not found in Period $this->periodCount adaptation set " . ($i + 1)
    );
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "Section 6.1.1",
        "All audio Representations SHALL either define or inherit the elements and attributes shown in Table 3",
        $adaptationCodecs != "" || $representationCodecs != "",
        "FAIL",
        "Codecs attribute found in Period $this->periodCount adaptation set " . ($i + 1),
        "Codecs attribute not found in Period $this->periodCount adaptation set " . ($i + 1)
    );
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "Section 6.1.1",
        "All audio Representations SHALL either define or inherit the elements and attributes shown in Table 3",
        $adaptation->getAttribute('audioSamplingRate') != "" ||
        $representation->getAttribute('audioSamplingRate') != "",
        "FAIL",
        "audioSamplingRate attribute found in Period $this->periodCount adaptation set " . ($i + 1),
        "audioSamplingRate attribute not found in Period $this->periodCount adaptation set " . ($i + 1)
    );
}

    ## Information from this part is for Section 6.1: Receiver Mix AD
if (in_array('commentary', $roleValues) && in_array('1', $accessibilityRoles)) {
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "Section 6.1.2",
        "For receiver mixed Audio Description the associated audio stream SHALL use the @dependencyId " .
        "attribute to indicate the dependency to the related Adaptation Set's Representations",
        !empty($dependencyIds),
        "FAIL",
        "Found dependencyId in Period $this->periodCount adaptation set " . ($i + 1),
        "depenendencyId not found in Period $this->periodCount adaptation set " . ($i + 1)
    );
}
