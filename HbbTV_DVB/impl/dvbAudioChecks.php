<?php

global $logger;

$contentType = $adaptation->getAttribute('contentType');
if ($contentType == 'audio') {
    $this->adaptationAudioCount++;
}


$roles = $adaptation->getElementsByTagName("Role");
$adaptationRoleElementFound = (sizeof($roles) > 0);
$adaptationSpecificRoleCount = 0;
$roleValues = array();

foreach ($roles as $role) {
    if ($role->getAttribute('schemeIdUri') == 'urn:mpeg:dash:role:2011') {
        $adaptationSpecificRoleCount++;
        $roleValues[] = $role->getAttribute('value');

        if ($role->getAttribute('value') == 'main') {
            $this->mainAudioFound = true;
            $this->mainAudios[] = $adaptation;
        }
    }
}

$accessibilityRoles = array();
$accessibilities = $adaptation->getElementsByTagName("Accessibility");
foreach ($accessibilities as $accessibility) {
    if ($accessibility->getAttribute('schemeIdUri') == 'urn:tva:metadata:cs:AudioPurposeCS:2007') {
        $accessibilityRoles[] = $accessibility->getAttribute('value');
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

$adapt_role_element_found = false;
$adaptationMimeType = $adaptation->getAttribute('mimeType');
$adapt_audioSamplingRate = $adaptation->getAttribute('audioSamplingRate');

$adaptationCodecs = $adaptation->getAttribute('codecs');


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


$dependencyIds = array();

$representationIndex = 0;
$repAudioConfigurationSchemes = array();
$repAudioConfigurationValues = array();
$subRepAudioConfigurationSchemes = array();
$subRepAudioConfigurationValues = array();
foreach ($representations as $representation) {
    $representationIndex++;

    $dependencyIds[] = $representation->getAttribute('dependencyId');

    $representationCodecs = $representation->getAttribute('codecs');

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
    }

    ///\Resiliency Validate EC3, AC4 and DTS as above for representation


    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "Section 6.1.1",
        "All audio Representations SHALL either define or inherit the elements and attributes shown in Table 3",
        $adaptationRoleElementFound  || $representationRoleElementFound,
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
