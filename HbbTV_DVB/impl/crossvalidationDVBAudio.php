<?php

global $mpdHandler, $logger;

$adaptation = $mpdHandler->getFeatures()['Period'][$mpdHandler->getSelectedPeriod()]['AdaptationSet'][$adaptationIndex];
$representation1 = $adaptation['Representation'][$xmlIndex1];
$representation2 = $adaptation['Representation'][$xmlIndex2];

$equalRepresentationCount = (sizeof($representation1) == sizeof($representation2));

$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB: Section 10.4",
    "Players SHALL support seamless switching between audio Representations which only differ in bit rate",
    $equalRepresentationCount,
    "PASS",
    "Adaptation $adaptationIndex: " .
    "representations $xmlIndex1 and $xmlIndex2 contain the same attributes",
    "Adaptation $adaptationIndex:  " .
    "representations $xmlIndex1 and $xmlIndex2 contain a different amount of attributes"
);
if ($equalRepresentationCount) {
    foreach ($representation1 as $key1 => $val1) {
        if (is_array($val1)) {
            continue;
        }
        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "DVB: Section 10.4",
            "Players SHALL support seamless switching between audio Representations which " .
            "only differ in bit rate",
            array_key_exists($key1, $representation2),
            "PASS",
            "Adaptation $adaptationIndex: attribute $key1 found in both" .
            "representations $xmlIndex1 and $xmlIndex2",
            "Adaptation $adaptationIndex: attribute $key1 found in " .
            "representations $xmlIndex1 but not in $xmlIndex2"
        );
        if (array_key_exists($key1, $representation2)) {
            if ($key1 != 'bandwidth' && $key1 != 'id') {
                $val2 = $representation2[$key1];
                $logger->test(
                    "HbbTV-DVB DASH Validation Requirements",
                    "DVB: Section 10.4",
                    "Players SHALL support seamless switching between audio Representations which " .
                    "only differ in bit rate",
                    $val1 == $val2,
                    "PASS",
                    "Adaptation $adaptationIndex: attribute $key1 values match between " .
                    "representations $xmlIndex1 and $xmlIndex2",
                    "Adaptation $adaptationIndex: attribute $key1 values differ between  " .
                    "representations $xmlIndex1 and $xmlIndex2"
                );
            }
        }
    }
}

## Section 6.1.1 Table 3 cross-checks for audio representations
// @mimeType
$adaptationMimetype = $adaptation['mimeType'];
$representationMimetype1 = $representation1['mimeType'];
$representationMimetype2 = $representation2['mimeType'];
$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB: Section 6.1.1",
    "@mimeType attribute SHALL be common between all audio Representations in an Adaptation Set",
    $adaptationMimetype != '' || $representationMimetype1 == $representationMimetype2,
    "FAIL",
    "Adaptation $adaptationIndex: " .
    "representations $xmlIndex1 and $xmlIndex2 are valid in this respect",
    "Adaptation $adaptationIndex:  " .
    "representations $xmlIndex1 and $xmlIndex2 are invalid in this respect"
);

// @codecs
$adaptationCodecs = $adaptation['codecs'];
$representationCodecs1 = $representation1['codecs'];
$representationCodecs2 = $representation2['codecs'];
$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB: Section 6.1.1",
    "@codecs attribute SHOULD be common between all audio Representations in an Adaptation Set",
    $adaptationCodecs != '' || $representationCodecs1 == $representationCodecs2,
    "WARN",
    "Adaptation $adaptationIndex: " .
    "representations $xmlIndex1 and $xmlIndex2 are valid in this respect",
    "Adaptation $adaptationIndex:  " .
    "representations $xmlIndex1 and $xmlIndex2 are invalid in this respect"
);

// @audioSamplingRate
$adaptationSampleRate = $adaptation['audioSamplingRate'];
$representationSamplerate1 = $representation1['audioSamplingRate'];
$representationSamplerate2 = $representation2['audioSamplingRate'];
$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB: Section 6.1.1",
    "@audioSamplingRate attribute SHOULD be common between all audio Representations in an Adaptation Set",
    $adaptationSampleRate != '' || $representationSamplerate1 == $representationSamplerate2,
    "WARN",
    "Adaptation $adaptationIndex: " .
    "representations $xmlIndex1 and $xmlIndex2 are valid in this respect",
    "Adaptation $adaptationIndex:  " .
    "representations $xmlIndex1 and $xmlIndex2 are invalid in this respect"
);

// AudioChannelConfiguration and Role
$adaptationChannelConfiguration = array();
foreach ($adaptation['AudioChannelConfiguration'] as $configuration) {
    $adaptationChannelConfiguration[] = $configuration;
}

$commonAudioChannelConfiguration = true;
if (empty($adaptationChannelConfiguration)) {
    $representationChannelConfiguration1 = array();
    foreach ($representation1['AudioChannelConfiguration'] as $configuration) {
        $representationChannelConfiguration1[] = $configuration;
    }
    $representationChannelConfiguration2 = array();
    foreach ($representation2['AudioChannelConfiguration'] as $configuration) {
        $representationChannelConfiguration2[] = $configuration;
    }


    if (!empty($representationChannelConfiguration1) && !empty($representationChannelConfiguration2)) {
        if (sizeof($representationChannelConfiguration1) != sizeof($representationChannelConfiguration2)) {
            $commonAudioChannelConfiguration = false;
        } else {
            for ($racc = 0; $racc < sizeof($representationChannelConfiguration1); $racc++) {
                $configuration1 = $representationChannelConfiguration1[$racc];
                $configuration2 = $representationChannelConfiguration2[$racc];

                if (!nodes_equal($configuration1, $configuration2)) {
                    $commonAudioChannelConfiguration = false;
                }
            }
        }
    } else {
        $commonAudioChannelConfiguration = false;
    }
}
$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB: Section 6.1.1",
    "AudioChannelConfiguration attribute SHOULD be common between all audio Representations in an Adaptation Set",
    $commonAudioChannelConfiguration,
    "WARN",
    "Adaptation $adaptationIndex: " .
    "representations $xmlIndex1 and $xmlIndex2 are valid in this respect",
    "Adaptation $adaptationIndex:  " .
    "representations $xmlIndex1 and $xmlIndex2 are invalid in this respect"
);

$adaptationRoles = array();
foreach ($adaptation['Role'] as $role) {
    $adaptationRoles[] = $role;
}
$commonRole = true;
if (empty($adaptationRoles)) {
    $role1 = array();
    foreach ($representation1['Role'] as $role) {
        $role1[] = $role;
    }
    $role2 = array();
    foreach ($representation2['Role'] as $role) {
        $role2[] = $role;
    }

    if (!empty($role1) && !empty($role2)) {
        if (sizeof($role1) != sizeof($role2)) {
            $commonRole = false;
        } else {
            for ($rr = 0; $rr < sizeof($role1); $rr++) {
                if (!nodes_equal($role1[$rr], $role2[$rr])) {
                    $commonRole = false;
                }
            }
        }
    } elseif ((!empty($role1) && empty($role2)) || (empty($role1) && !empty($role2))) {
        $commonRole = false;
    }
}
$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB: Section 6.1.1",
    "Role element SHALL be common between all audio Representations in an Adaptation Set",
    $commonRole,
    "FAIL",
    "Adaptation $adaptationIndex: " .
    "representations $xmlIndex1 and $xmlIndex2 are valid in this respect",
    "Adaptation $adaptationIndex:  " .
    "representations $xmlIndex1 and $xmlIndex2 are invalid in this respect"
);
##

## Section 6.4 on DTS audio frame durations
///\Correctness The codecs are checked separate on each representation, shouldn't they be equal?
$DTSCodecs = ['dtsc','dtsh','dtse','dtsl'];
$DTSCodecFound = DASHIF\Utility\inStringAtLeastOne($DTSCodecs, $adaptationCodecs);
if ($adaptationCodecs == '') {
    $DTSCodecFound = (DASHIF\Utility\inStringAtLeastOne($DTSCodecs, $representationCodecs1) &&
                      DASHIF\Utility\inStringAtLeastOne($DTSCodecs, $representationCodecs2));
}
if ($DTSCodecFound) {
    $timescale1 = (int)($xmlDom1->getElementsByTagName('mdhd')->item(0)->getAttribute('timescale'));
    $timescale2 = (int)($xmlDom2->getElementsByTagName('mdhd')->item(0)->getAttribute('timescale'));
    $trunBoxes1 = $xmlDom1->getElementsByTagName('trun');
    $trunBoxes2 = $xmlDom2->getElementsByTagName('trun');

    if ($trunBoxes1->length == $trunBoxes2->length) {
        $trunCount = $trunBoxes1->length;
        for ($t = 0; $t < $trunCount; $t++) {
            $cummulatedSampleDuration1 = (int)($trunBoxes1->item($t)->getAttribute('cummulatedSampleDuration'));
            $cummulatedSampleDuration2 = (int)($trunBoxes2->item($t)->getAttribute('cummulatedSampleDuration'));

            $logger->test(
                "HbbTV-DVB DASH Validation Requirements",
                "DVB: Section 6.4",
                "the audio frame duration SHALL remain constant for all streams within a given Adaptation Set",
                $cummulatedSampleDuration1 / $timescale1 == $cummulatedSampleDuration2 / $timescale2,
                "FAIL",
                "Adaptation $adaptationIndex: " .
                "representations $xmlIndex1 and $xmlIndex2 are valid in this respect",
                "Adaptation $adaptationIndex:  " .
                "representations $xmlIndex1 and $xmlIndex2 are invalid in this respect"
            );
        }
    }
}
##

## Adaptation Set check for consistent representations: Highlight 5.1 audio and 2.0 Audio in the same adaptation set
$sampleDescription1 = $xmlDom1->getElementsByTagName('soun_sampledescription')->item(0);
$decoderSpecificInfo1 = $sampleDescription1->getElementsByTagName('DecoderSpecificInfo')->item(0);
$decoderSpecificAttributes1 = $decoderSpecificInfo1->attributes;
$audioDecoderConfiguration1 = '';
foreach ($decoderSpecificAttributes1 as $conf_att_r) {
    if (strpos($conf_att_r->value, 'config is') !== false) {
        $audioDecoderConfiguration1 = $conf_att_r->value;
    }
}

$sampleDescription2 = $xmlDom2->getElementsByTagName('soun_sampledescription')->item(0);
$decoderSpecificInfo2 = $sampleDescription2->getElementsByTagName('DecoderSpecificInfo')->item(0);
$decoderSpecificAttributes2 = $decoderSpecificInfo2->attributes;
$audioDecoderConfiguration2 = '';
foreach ($decoderSpecificAttributes2 as $conf_att_d) {
    if (strpos($conf_att_d->value, 'config is') !== false) {
        $audioDecoderConfiguration2 = $conf_att_d->value;
    }
}

if ($audioDecoderConfiguration1 != '' && $audioDecoderConfiguration2 != '') {
    $hasSurroundAndStereo = ($audioDecoderConfiguration1 == 'config is 5+1' &&
                             $audioDecoderConfiguration2 == 'config is stereo')
                            ||
                            ($audioDecoderConfiguration2 == 'config is 5+1' &&
                            $audioDecoderConfiguration1 == 'config is stereo');
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "DVB",
        "5.1 Audio and 2.0 Audio SHOULD NOT be present within the same Adaptation Set for the presence of " .
        "consistent Representations within an Adaptation Set",
        !$hasSurroundAndStereo,
        "WARN",
        "Adaptation $adaptationIndex: " .
        "representations $xmlIndex1 and $xmlIndex2 are valid in this respect",
        "Adaptation $adaptationIndex:  " .
        "representations $xmlIndex1 and $xmlIndex2 are invalid in this respect"
    );
}
##
