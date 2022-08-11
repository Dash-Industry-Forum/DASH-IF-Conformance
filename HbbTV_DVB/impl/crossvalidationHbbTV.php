<?php

global $logger;

## Section E.3.2 checks on Adaptation Sets
// Second bullet on same trackID
$tkhdBox1 = $xmlDom1->getElementsByTagName('tkhd')->item(0);
$trackID1 = $tkhdBox1->getAttribute('trackID');
$tfhdBoxes1 = $xmlDom1->getElementsByTagName('tfhd');

$tkhdBox2 = $xmlDom2->getElementsByTagName('tkhd')->item(0);
$trackID2 = $tkhdBox2->getAttribute('trackID');
$tfhdBoxes2 = $xmlDom2->getElementsByTagName('tfhd');

$validTfhd = true;
foreach ($tfhdBoxes1 as $index => $tfhd_r) {
    if ($tfhd_r->getAttribute('trackID') != $tfhdBoxes2->item($index)->getAttribute('trackID')) {
        $validTfhd = false;
    }
}

$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "HbbTV: Section E.3.2",
    "All ISO BMFF Representations SHALL have the same track_ID in the track header box and track fragment header box",
    $trackID1 == $trackID2 && $validTfhd,
    "FAIL",
    "Adaptation $adaptationIndex: " .
    "representations $xmlIndex1 and $xmlIndex2 are valid in this respect",
    "Adaptation $adaptationIndex:  " .
    "representations $xmlIndex1 and $xmlIndex2 are invalid in this respect"
);

// Third bullet on initialization segment identicalness
$stsdBox1 = $xmlDom1->getElementsByTagName('stsd')->item(0);
$stsdBox2 = $xmlDom2->getElementsByTagName('stsd')->item(0);

$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "HbbTV: Section E.3.2",
    "Initialization Segment SHALL be common for all Representations",
    nodes_equal($stsdBox1, $stsdBox2),
    "FAIL",
    "Adaptation $adaptationIndex: " .
    "representations $xmlIndex1 and $xmlIndex2 are valid in this respect",
    "Adaptation $adaptationIndex:  " .
    "representations $xmlIndex1 and $xmlIndex2 are invalid in this respect"
);

$hdlrBox1 = $xmlDom1->getElementsByTagName('hdlr')->item(0);
$hdlrType1 = $hdlrBox1->getAttribute('handler_type');
$sdType1 = $xmlDom1->getElementsByTagName($hdlrType1 . '_sampledescription')->item(0)->getAttribute('sdType');

$hdlrBox2 = $xmlDom1->getElementsByTagName('hdlr')->item(0);
$hdlrType2 = $hdlrBox2->getAttribute('handler_type');
$sdType2 = $xmlDom2->getElementsByTagName($hdlrType2 . '_sampledescription')->item(0)->getAttribute('sdType');

 ## Highlight HEVC and AVC for different representations in the same Adaptation Set
if ($hdlrType1 == 'vide' && $hdlrType2 == 'vide') {
    $haveHEVCAndAVC = (($sdType1 == 'hev1' || $sdType1 == 'hvc1') && strpos($sdType2, 'avc')) ||
                      (($sdType2 == 'hev1' || $sdType2 == 'hvc1') && strpos($sdType1, 'avc'));

    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "HbbTV: Section 'Adaptation Sets'",
        "Terminals cannot switch between HEVC and AVC video Represntations present in the same Adaptation Set",
        !$haveHEVCAndAVC,
        "WARN",
        "Adaptation $adaptationIndex: " .
        "representations $xmlIndex1 and $xmlIndex2 are valid in this respect",
        "Adaptation $adaptationIndex:  " .
        "representations $xmlIndex1 and $xmlIndex2 are invalid in this respect"
    );
}

## Highlight 5.1 Audio and 2.0 Audio
if ($hdlrType1 == 'soun' && $hdlrType2 == 'soun') {
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
            "HbbTV: Section 'Adaptation Sets'",
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
}
