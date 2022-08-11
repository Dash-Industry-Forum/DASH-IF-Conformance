<?php

global $logger;

$colorPrimaries = $mpParameters['color_primaries'];
$transferCharacteristics = $mpParameters['transfer_char'];
$matrixCoefficients = $mpParameters['matrix_coeff'];
$height = $mpParameters['height'];
$width = $mpParameters['width'];
$frameRate = $mpParameters['framerate'];

$testColorPrimaries = $logger->test(
    "CMAF",
    "Section $section",
    "For a CMAF Track to comply with one of the media profiles in Table $tableSection, it SHALL conform to the " .
    "colour_primaries, transfer_characteristics and matrix_coefficients values from the options listed " .
    "in the table",
    in_array($colorPrimaries, $validColorPrimaries),
    "FAIL",
    "Valid color primaries found",
    "Nonvalid color primaries found"
);
$testTransferCharacteristics = $logger->test(
    "CMAF",
    "Section $section",
    "For a CMAF Track to comply with one of the media profiles in Table $tableSection, it SHALL conform to the " .
    "colour_primaries, transfer_characteristics and matrix_coefficients values from the options listed " .
    "in the table",
    in_array($transferCharacteristics, $validTransferCharacteristics),
    "FAIL",
    "Valid transfer characteristics found",
    "Nonvalid transfer characteristics found"
);
$testMatrixCoefficients = $logger->test(
    "CMAF",
    "Section $section",
    "For a CMAF Track to comply with one of the media profiles in Table $tableSection, it SHALL conform to the " .
    "colour_primaries, transfer_characteristics and matrix_coefficients values from the options listed " .
    "in the table",
    in_array($matrixCoefficients, $validMatrixCoefficients),
    $matrixCoefficients === "0x1" || $matrixCoefficients === "0x5" || $matrixCoefficients === "0x6",
    "FAIL",
    "Valid matrix coefficients found",
    "Nonvalid matrix coefficients found"
);

if (!$testColorPrimaries || !$testTransferCharacteristics || !$testMatrixCoefficients) {
    return false;
}

$testHeight = $logger->test(
    "CMAF",
    "Section $section",
    "For a CMAF Track to comply with one of the media profiles in Table $tableSection, it SHALL not exceed the " .
    "width, height or frame rate listed in the table, even if the AVC Level would permit higher values",
    $height <= $maxHeight,
    "FAIL",
    "Valid height value found",
    "Nonvalid height value found"
);
$testWidth = $logger->test(
    "CMAF",
    "Section $section",
    "For a CMAF Track to comply with one of the media profiles in Table $tableSection, it SHALL not exceed the " .
    "width, height or frame rate listed in the table, even if the AVC Level would permit higher values",
    $width  <= $maxWidth,
    "FAIL",
    "Valid width value found",
    "Nonvalid width value found"
);
$testFrameRate = $logger->test(
    "CMAF",
    "Section $section",
    "For a CMAF Track to comply with one of the media profiles in Table $tableSection, it SHALL not exceed the " .
    "width, height or frame rate listed in the table, even if the AVC Level would permit higher values",
    $frameRate <= $maxFrameRate,
    "FAIL",
    "Valid framerate value found",
    "Nonvalid framerate value found"
);
if (!$testHeight | !$testWidth || !$testFrameRate) {
    return false;
}
return true;
