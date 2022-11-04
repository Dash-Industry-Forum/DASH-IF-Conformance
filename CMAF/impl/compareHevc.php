<?php

global $logger;

$attributeNamesSPS = array('vui_parameters_present_flag', 'video_signal_type_present_flag',
                           'colour_description_present_flag','colour_primaries',
                           'transfer_characteristics', 'matrix_coeffs',
                           'chroma_loc_info_present_flag','chroma_sample_loc_type_top_field',
                           'chroma_sample_loc_type_bottom_field', 'neutral_chroma_indication_flag',
                           'sps_extension_present_flag', 'sps_range_extension_flag',
                           'extended_precision_processing_flag');

$attributeNamesSEI = array('length', 'zero-bit', 'nuh_layer_id', 'nuh_temporal_id_plus1');

$hvcCBox1 = $xml1->getElementsByTagName('hvcC')->item(0);
$hvcCBox2 = $xml2->getElementsByTagName('hvcC')->item(0);

$spsArray1 = getNALArray($hvcCBox1, '33');
$spsArray2 = getNALArray($hvcCBox2, '33');

$logger->test(
    "CMAF",
    "Section B.2.4",
    "CMAF Switching Sets SHALL be constrained to include identical SPS VUI color mastering and " .
    "dynamic range information in the first sample entry of every CMAF header in the CMAF " .
    "switching set to provide consistent initialization and calibration",
    ($spsArray1 == null) == ($spsArray2 == null),
    "FAIL",
    "Presence of SPS NAL identical $id1 and $id2",
    "Presence of SPS NAL not identical $id1 and $id2"
);

if ($spsArray1 != null && $spsArray2 != null) {
    $spsUnit1 = getNALUnit($spsArray1);
    $spsUnit2 = getNALUnit($spsArray2);

    foreach ($attributeNamesSPS as $attributeName) {
        $nalUnitAttribute1 = $spsUnit1->getAttribute($attributeName);
        $nalUnitAttribute2 = $spsUnit2->getAttribute($attributeName);

        $logger->test(
            "CMAF",
            "Section B.2.4",
            "CMAF Switching Sets SHALL be constrained to include identical SPS VUI color mastering and " .
            "dynamic range information in the first sample entry of every CMAF header in the CMAF " .
            "switching set to provide consistent initialization and calibration",
            $nalUnitAttribute1 == $nalUnitAttribute2,
            "FAIL",
            "Identical $attributeName for representations $id1 and $id2",
            "Nonidentical $attributeName for representations $id1 and $id2",
        );
    }
}

$prefixSeiArray1 = getNALArray($hvcCBox1, '39');
$prefixSeiArray2 = getNALArray($hvcCBox2, '39');

$logger->test(
    "CMAF",
    "Section B.2.4",
    "CMAF Switching Sets SHALL be constrained to include identical SEI NALS in the first sample entry " .
    "of every CMAF header in the CMAF switching set to provide consistent initialization and calibration",
    ($prefixSeiArray1 == null) == ($prefixSeiArray2 == null),
    "FAIL",
    "Presence of prefix SEI NAL identical $id1 and $id2",
    "Presence of prefix SEI NAL not identical $id1 and $id2"
);

if ($prefixSeiArray1 != null && $prefixSeiArray2 != null) {
    $prefixSeiUnit1 = getNALUnit($prefixSeiArray1);
    $prefixSeiUnit2 = getNALUnit($prefixSeiArray2);

    foreach ($attributeNamesSEI as $attributeName) {
        $nalUnitAttribute1 = $prefixSeiUnit1->getAttribute($attributeName);
        $nalUnitAttribute2 = $prefixSeiUnit2->getAttribute($attributeName);


        $logger->test(
            "CMAF",
            "Section B.2.4",
            "CMAF Switching Sets SHALL be constrained to include identical SEI NALS in the first sample entry " .
            "of every CMAF header in the CMAF switching set to provide consistent initialization and calibration",
            $nalUnitAttribute1 == $nalUnitAttribute2,
            "FAIL",
            "Identical $attributeName in prefix SEI for representations $id1 and $id2",
            "Nonidentical $attributeName in prefix SEI for representations $id1 and $id2",
        );
    }
}

$suffixSeiArray1 = getNALArray($hvcCBox1, '40');
$suffixSeiArray2 = getNALArray($hvcCBox2, '40');

$logger->test(
    "CMAF",
    "Section B.2.4",
    "CMAF Switching Sets SHALL be constrained to include identical SEI NALS in the first sample entry " .
    "of every CMAF header in the CMAF switching set to provide consistent initialization and calibration",
    ($suffixSeiArray1 == null) == ($suffixSeiArray2 == null),
    "FAIL",
    "Presence of suffix SEI NAL identical $id1 and $id2",
    "Presence of suffix SEI NAL not identical $id1 and $id2"
);
if ($suffixSeiArray1 != null && $suffixSeiArray2 != null) {
    $sufSeiUnit1 = getNALUnit($suffixSeiArray1);
    $sufSeiUnit2 = getNALUnit($suffixSeiArray2);

    foreach ($attributeNamesSEI as $attributeName) {
        $nalUnitAttribute1 = $sufSeiUnit1->getAttribute($attributeName);
        $nalUnitAttribute2 = $sufSeiUnit2->getAttribute($attributeName);

        $logger->test(
            "CMAF",
            "Section B.2.4",
            "CMAF Switching Sets SHALL be constrained to include identical SEI NALS in the first sample entry " .
            "of every CMAF header in the CMAF switching set to provide consistent initialization and calibration",
            $nalUnitAttribute1 == $nalUnitAttribute2,
            "FAIL",
            "Identical $attributeName in suffix SEI for representations $id1 and $id2",
            "Nonidentical $attributeName in suffix SEI for representations $id1 and $id2",
        );
    }
}
