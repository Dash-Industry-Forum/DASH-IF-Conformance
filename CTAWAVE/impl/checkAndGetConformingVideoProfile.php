<?php

global $logger;

$codec = $mediaProfileParameters['codec'];
$profile = $mediaProfileParameters['profile'];
$colorPrimaries = $mediaProfileParameters['color_primaries'];
$transferCharacteristics = $mediaProfileParameters['transfer_char'];
$matrixCoefficients = $mediaProfileParameters['matrix_coeff'];
$height = $mediaProfileParameters['height'];
$width = $mediaProfileParameters['width'];
$frameRate = $mediaProfileParameters['framerate'];
$level = $mediaProfileParameters['level'];

if ($codec == "AVC") {
    $validProfile = $logger->test(
        "CTAWAVE",
        "WAVE Content Spec 2018Ed-Section 4.2.1",
        "Each WAVE video Media Profile SHALL conform to normative ref. listed in Table 1",
        $profile == "high" || $profile == "main",
        "FAIL",
        "Valid profile for track $representationIndex of switching set $adaptationIndex",
        "Invalid profile for track $representationIndex of switching set $adaptationIndex",
    );
    if (!$validProfile) {
        return "unknown";
    }

    $validColorPrimaries = $logger->test(
        "CTAWAVE",
        "WAVE Content Spec 2018Ed-Section 4.2.1",
        "Each WAVE video Media Profile SHALL conform to normative ref. listed in Table 1",
        $colorPrimaries == 1,
        "FAIL",
        "Valid color primaries for track $representationIndex of switching set $adaptationIndex",
        "Invalid or missing color primaries for track $representationIndex of switching set $adaptationIndex",
    );
    $validTransferCharacteristics = $logger->test(
        "CTAWAVE",
        "WAVE Content Spec 2018Ed-Section 4.2.1",
        "Each WAVE video Media Profile SHALL conform to normative ref. listed in Table 1",
        $transferCharacteristics == 1,
        "FAIL",
        "Valid transfer characteristics for track $representationIndex of switching set $adaptationIndex",
        "Invalid or missing transfer characteristics for track $representationIndex of switching set $adaptationIndex",
    );
    $validMatrixCoefficients = $logger->test(
        "CTAWAVE",
        "WAVE Content Spec 2018Ed-Section 4.2.1",
        "Each WAVE video Media Profile SHALL conform to normative ref. listed in Table 1",
        $matrixCoefficients == 1,
        "FAIL",
        "Valid matrix coefficients for track $representationIndex of switching set $adaptationIndex",
        "Invalid or missing matrix coefficients for track $representationIndex of switching set $adaptationIndex",
    );
    if (!$validColorPrimaries || !$validTransferCharacteristics || !$validMatrixCoefficients) {
        return "unknown";
    }



    $validHeight = $logger->test(
        "CTAWAVE",
        "WAVE Content Spec 2018Ed-Section 4.2.1",
        "Each WAVE video Media Profile SHALL conform to normative ref. listed in Table 1",
        $heigth <= 1080,
        "FAIL",
        "Valid height for track $representationIndex of switching set $adaptationIndex",
        "Invalid height for track $representationIndex of switching set $adaptationIndex",
    );
    $validWidth = $logger->test(
        "CTAWAVE",
        "WAVE Content Spec 2018Ed-Section 4.2.1",
        "Each WAVE video Media Profile SHALL conform to normative ref. listed in Table 1",
        $width <= 1920,
        "FAIL",
        "Valid width for track $representationIndex of switching set $adaptationIndex",
        "Invalid width for track $representationIndex of switching set $adaptationIndex",
    );
    $validFrameRate = $logger->test(
        "CTAWAVE",
        "WAVE Content Spec 2018Ed-Section 4.2.1",
        "Each WAVE video Media Profile SHALL conform to normative ref. listed in Table 1",
        $frameRate <= 60,
        "FAIL",
        "Valid framerate for track $representationIndex of switching set $adaptationIndex",
        "Invalid framerate for track $representationIndex of switching set $adaptationIndex",
    );

    if (!$validHeight || !$validWidth || !$validFrameRate) {
        return "unknown";
    }

    if ($level <= 4.0) {
        return "HD";
    }
    if ($level <= 4.2) {
        return "AVC_HDHF";
    }

    //Found invalid level config
    $logger->test(
        "CTAWAVE",
        "WAVE Content Spec 2018Ed-Section 4.2.1",
        "Each WAVE video Media Profile SHALL conform to normative ref. listed in Table 1",
        false,
        "FAIL",
        "",
        "Invalid level for track $representationIndex of switching set $adaptationIndex",
    );
    return "unknown";
}

if ($codec == "HEVC") {
    $validTier = $logger->test(
        "CTAWAVE",
        "WAVE Content Spec 2018Ed-Section 4.2.1",
        "Each WAVE video Media Profile SHALL conform to normative ref. listed in Table 1",
        $mediaProfileParameters['tier'] == 0,
        "FAIL",
        "Valid HEVC tier for track $representationIndex of switching set $adaptationIndex",
        "Invalid HEVC tier for track $representationIndex of switching set $adaptationIndex",
    );
    if (!$validTier) {
        return "unknown";
    }
    if ($profile == "Main10") {
        if ($level <= 4.1) {
            $validHeight = $logger->test(
                "CTAWAVE",
                "WAVE Content Spec 2018Ed-Section 4.2.1",
                "Each WAVE video Media Profile SHALL conform to normative ref. listed in Table 1",
                $heigth <= 1080,
                "FAIL",
                "Valid height for track $representationIndex of switching set $adaptationIndex",
                "Invalid height for track $representationIndex of switching set $adaptationIndex",
            );
            $validWidth = $logger->test(
                "CTAWAVE",
                "WAVE Content Spec 2018Ed-Section 4.2.1",
                "Each WAVE video Media Profile SHALL conform to normative ref. listed in Table 1",
                $width <= 1920,
                "FAIL",
                "Valid width for track $representationIndex of switching set $adaptationIndex",
                "Invalid width for track $representationIndex of switching set $adaptationIndex",
            );
            $validFrameRate = $logger->test(
                "CTAWAVE",
                "WAVE Content Spec 2018Ed-Section 4.2.1",
                "Each WAVE video Media Profile SHALL conform to normative ref. listed in Table 1",
                $frameRate <= 60,
                "FAIL",
                "Valid framerate for track $representationIndex of switching set $adaptationIndex",
                "Invalid framerate for track $representationIndex of switching set $adaptationIndex",
            );

            if (!$validHeight || !$validWidth || !$validFrameRate) {
                return "unknown";
            }

            $validColorPrimaries = $logger->test(
                "CTAWAVE",
                "WAVE Content Spec 2018Ed-Section 4.2.1",
                "Each WAVE video Media Profile SHALL conform to normative ref. listed in Table 1",
                $colorPrimaries == "1",
                "FAIL",
                "Valid color primaries for track $representationIndex of switching set $adaptationIndex",
                "Invalid or missing color primaries for track $representationIndex of switching set $adaptationIndex",
            );
            $validTransferCharacteristics = $logger->test(
                "CTAWAVE",
                "WAVE Content Spec 2018Ed-Section 4.2.1",
                "Each WAVE video Media Profile SHALL conform to normative ref. listed in Table 1",
                $transferCharacteristics == "1",
                "FAIL",
                "Valid transfer characteristics for track $representationIndex of switching set $adaptationIndex",
                "Invalid or missing transfer characteristics for track $representationIndex of " .
                "switching set $adaptationIndex",
            );
            $validMatrixCoefficients = $logger->test(
                "CTAWAVE",
                "WAVE Content Spec 2018Ed-Section 4.2.1",
                "Each WAVE video Media Profile SHALL conform to normative ref. listed in Table 1",
                $matrixCoefficients == "1",
                "FAIL",
                "Valid matrix coefficients for track $representationIndex of switching set $adaptationIndex",
                "Invalid or missing matrix coefficients for track $representationIndex of " .
                "switching set $adaptationIndex",
            );
            if (!$validColorPrimaries || !$validTransferCharacteristics || !$validMatrixCoefficients) {
                return "unknown";
            }

            return "HHD10";
        }
        if ($level <= 5.1) {
            //Check for other HEVC Media Profiles. Level <=5.1
            $validHeight = $logger->test(
                "CTAWAVE",
                "WAVE Content Spec 2018Ed-Section 4.2.1",
                "Each WAVE video Media Profile SHALL conform to normative ref. listed in Table 1",
                $heigth <= 2160,
                "FAIL",
                "Valid height for track $representationIndex of switching set $adaptationIndex",
                "Invalid height for track $representationIndex of switching set $adaptationIndex",
            );
            $validWidth = $logger->test(
                "CTAWAVE",
                "WAVE Content Spec 2018Ed-Section 4.2.1",
                "Each WAVE video Media Profile SHALL conform to normative ref. listed in Table 1",
                $width <= 3840,
                "FAIL",
                "Valid width for track $representationIndex of switching set $adaptationIndex",
                "Invalid width for track $representationIndex of switching set $adaptationIndex",
            );
            $validFrameRate = $logger->test(
                "CTAWAVE",
                "WAVE Content Spec 2018Ed-Section 4.2.1",
                "Each WAVE video Media Profile SHALL conform to normative ref. listed in Table 1",
                $frameRate <= 60,
                "FAIL",
                "Valid framerate for track $representationIndex of switching set $adaptationIndex",
                "Invalid framerate for track $representationIndex of switching set $adaptationIndex",
            );

            if (!$validHeight || !$validWidth || !$validFrameRate) {
                return "unknown";
            }


            if (
                in_array($colorPrimaries, array(1,9)) &&
                in_array($transferCharacteristics, array(1,14,15)) &&
                in_array($matrixCoefficients, array(1,9,10))
            ) {
                return "UHD10";
            }

            if (
                $colorPrimaries == "9" &&
                $transferCharacteristics == "16" &&
                in_array($matrixCoefficients, array(9,10))
            ) {
                return "HDR10";
            }

            if (
                $colorPrimaries == "9" &&
                in_array($transferCharacteristics, array(14,18)) &&
                $matrixCoefficients == "9"
            ) {
                return "HLG10";
            }
            //Found invalid config
            $logger->test(
                "CTAWAVE",
                "WAVE Content Spec 2018Ed-Section 4.2.1",
                "Each WAVE video Media Profile SHALL conform to normative ref. listed in Table 1",
                false,
                "FAIL",
                "",
                "Invalid combination of color primaries, transfer characteristics and matrix coefficients " .
                "for track $representationIndex of switching set $adaptationIndex",
            );
            return "unknown";
        }
        //Found invalid level config
        $logger->test(
            "CTAWAVE",
            "WAVE Content Spec 2018Ed-Section 4.2.1",
            "Each WAVE video Media Profile SHALL conform to normative ref. listed in Table 1",
            false,
            "FAIL",
            "",
            "Invalid level for track $representationIndex of switching set $adaptationIndex",
        );
        return "unknown";
    }
    if ($profile == "Main") {
        $validColorPrimaries = $logger->test(
            "CTAWAVE",
            "WAVE Content Spec 2018Ed-Section 4.2.1",
            "Each WAVE video Media Profile SHALL conform to normative ref. listed in Table 1",
            $colorPrimaries == "1",
            "FAIL",
            "Valid color primaries for track $representationIndex of switching set $adaptationIndex",
            "Invalid or missing color primaries for track $representationIndex of switching set $adaptationIndex",
        );
        $validTransferCharacteristics = $logger->test(
            "CTAWAVE",
            "WAVE Content Spec 2018Ed-Section 4.2.1",
            "Each WAVE video Media Profile SHALL conform to normative ref. listed in Table 1",
            $transferCharacteristics == "1",
            "FAIL",
            "Valid transfer characteristics for track $representationIndex of switching set $adaptationIndex",
            "Invalid or missing transfer characteristics for track $representationIndex of " .
            "switching set $adaptationIndex",
        );
        $validMatrixCoefficients = $logger->test(
            "CTAWAVE",
            "WAVE Content Spec 2018Ed-Section 4.2.1",
            "Each WAVE video Media Profile SHALL conform to normative ref. listed in Table 1",
            $matrixCoefficients == "1",
            "FAIL",
            "Valid matrix coefficients for track $representationIndex of switching set $adaptationIndex",
            "Invalid or missing matrix coefficients for track $representationIndex of " .
            "switching set $adaptationIndex",
        );
        if (!$validColorPrimaries || !$validTransferCharacteristics || !$validMatrixCoefficients) {
            return "unknown";
        }

        if ($level <= "4.1") {
            $validHeight = $logger->test(
                "CTAWAVE",
                "WAVE Content Spec 2018Ed-Section 4.2.1",
                "Each WAVE video Media Profile SHALL conform to normative ref. listed in Table 1",
                $heigth <= 1080,
                "FAIL",
                "Valid height for track $representationIndex of switching set $adaptationIndex",
                "Invalid height for track $representationIndex of switching set $adaptationIndex",
            );
            $validWidth = $logger->test(
                "CTAWAVE",
                "WAVE Content Spec 2018Ed-Section 4.2.1",
                "Each WAVE video Media Profile SHALL conform to normative ref. listed in Table 1",
                $width <= 1920,
                "FAIL",
                "Valid width for track $representationIndex of switching set $adaptationIndex",
                "Invalid width for track $representationIndex of switching set $adaptationIndex",
            );
            $validFrameRate = $logger->test(
                "CTAWAVE",
                "WAVE Content Spec 2018Ed-Section 4.2.1",
                "Each WAVE video Media Profile SHALL conform to normative ref. listed in Table 1",
                $frameRate <= 60,
                "FAIL",
                "Valid framerate for track $representationIndex of switching set $adaptationIndex",
                "Invalid framerate for track $representationIndex of switching set $adaptationIndex",
            );

            if (!$validHeight || !$validWidth || !$validFrameRate) {
                return "unknown";
            }
            return "HHD10";
        }
        if ($level <= "5.0") {
            $validHeight = $logger->test(
                "CTAWAVE",
                "WAVE Content Spec 2018Ed-Section 4.2.1",
                "Each WAVE video Media Profile SHALL conform to normative ref. listed in Table 1",
                $heigth <= 2160,
                "FAIL",
                "Valid height for track $representationIndex of switching set $adaptationIndex",
                "Invalid height for track $representationIndex of switching set $adaptationIndex",
            );
            $validWidth = $logger->test(
                "CTAWAVE",
                "WAVE Content Spec 2018Ed-Section 4.2.1",
                "Each WAVE video Media Profile SHALL conform to normative ref. listed in Table 1",
                $width <= 3840,
                "FAIL",
                "Valid width for track $representationIndex of switching set $adaptationIndex",
                "Invalid width for track $representationIndex of switching set $adaptationIndex",
            );
            $validFrameRate = $logger->test(
                "CTAWAVE",
                "WAVE Content Spec 2018Ed-Section 4.2.1",
                "Each WAVE video Media Profile SHALL conform to normative ref. listed in Table 1",
                $frameRate <= 60,
                "FAIL",
                "Valid framerate for track $representationIndex of switching set $adaptationIndex",
                "Invalid framerate for track $representationIndex of switching set $adaptationIndex",
            );

            if (!$validHeight || !$validWidth || !$validFrameRate) {
                return "unknown";
            }
            return "UHD10";
        }
        //Found invalid level
        $logger->test(
            "CTAWAVE",
            "WAVE Content Spec 2018Ed-Section 4.2.1",
            "Each WAVE video Media Profile SHALL conform to normative ref. listed in Table 1",
            false,
            "FAIL",
            "",
            "Invalid level for track $representationIndex of switching set $adaptationIndex",
        );
    }
    //Found invalid profile config
    $logger->test(
        "CTAWAVE",
        "WAVE Content Spec 2018Ed-Section 4.2.1",
        "Each WAVE video Media Profile SHALL conform to normative ref. listed in Table 1",
        false,
        "FAIL",
        "",
        "Invalid profile for track $representationIndex of switching set $adaptationIndex",
    );
    return "unknown";
}

//Found invalid codec config
$logger->test(
    "CTAWAVE",
    "WAVE Content Spec 2018Ed-Section 4.2.1",
    "Each WAVE video Media Profile SHALL conform to normative ref. listed in Table 1",
    false,
    "FAIL",
    "",
    "Invalid codec for track $representationIndex of switching set $adaptationIndex",
);
return "unknown";
