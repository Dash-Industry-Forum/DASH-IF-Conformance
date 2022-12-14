<?php

global $logger;

$codec = $mpParameters['codec'];
if ($codec != "AVC" && $codec != "HEVC") {
    return "unknown";
}

$level = $mpParameters['level'];
$profile = $mpParameters['profile'];
$colorPrimaries = $mpParameters['color_primaries'];
$transferCharacteristics = $mpParameters['transfer_char'];
$matrixCoefficients = $mpParameters['matrix_coeff'];
$height = $mpParameters['height'];
$width = $mpParameters['width'];
$frameRate = $mpParameters['framerate'];

if ($codec == "AVC") {
    $validProfile = $logger->test(
        "CMAF",
        "Section A.2",
        "For a CMAF Track to comply with one of the media profiles in Table A.1, it SHALL not exceed the profile " .
        "or level listed in the table",
        $mpParameters['profile'] <= 100,
        "FAIL",
        "Valid profile found",
        "Nonvalid profile found"
    );

    if (!$validProfile) {
        return "unknown";
    }


    if ($level <= 3.1) {
        $validity = $this->determineVideoProfileValidity(
            $mpParameters,
            "A.2",
            "A.1",
            array("0x1","0x5","0x6"),
            array("0x1","0x6"),
            array("0x1","0x5","0x6"),
            576,
            864,
            60
        );

        if ($validity) {
            return "AVC SD";
        }
        return "unknown";
    }
    if ($level <= 4.0) {
        $validity = $this->determineVideoProfileValidity(
            $mpParameters,
            "A.2",
            "A.1",
            array("0x1"),
            array("0x1"),
            array("0x1"),
            1080,
            1920,
            60
        );

        if ($validity) {
            return "AVC HD";
        }
        return "unknown";
    }
    if ($level <= 4.0) {
        $validity = $this->determineVideoProfileValidity(
            $mpParameters,
            "A.2",
            "A.1",
            array("0x1"),
            array("0x1"),
            array("0x1"),
            1080,
            1920,
            60
        );

        if ($validity) {
            return "AVC HDHF";
        }
        return "unknown";
    }
    return "unknown";
}
if ($codec == "HEVC") {
    $validTier = $logger->test(
        "CMAF",
        "Section B.5",
        "For a CMAF Track to comply with one of the media profiles in Table B.1, it SHALL not exceed the " .
        "tier, profile or level listed in the table",
        $mpParameters['tier'] == 0,
        "FAIL",
        "Valid hevc tier found",
        "Nonvalid hevc tier found"
    );
    if (!$validTier) {
        return "unknown";
    }

    $validProfile = $logger->test(
        "CMAF",
        "Section B.5",
        "For a CMAF Track to comply with one of the media profiles in Table B.1, it SHALL not exceed the " .
        "profile or level listed in the table",
        $profile == "Main" || $profile == "Main10",
        "FAIL",
        "Valid profile found",
        "Nonvalid profile found"
    );

    if (!$validProfile) {
        return "unknown";
    }
    if ($mpParameters['profile'] == 'Main') {
        $validLevel = $logger->test(
            "CMAF",
            "Section B.5",
            "For a CMAF Track to comply with one of the media profiles in Table B.1, it SHALL not exceed the " .
            "profile or level listed in the table",
            $mpParameters['level'] <= 4.1 || $mpParameters['level'] <= 5.0,
            "FAIL",
            "Valid level found",
            "Nonvalid level found"
        );

        if (!$validLevel) {
            return "unknown";
        }
        if ($mpParameters['level'] <= 4.1) {
            $validity = $this->determineVideoProfileValidity(
                $mpParameters,
                "B.5",
                "B.1",
                array("0x1"),
                array("0x1"),
                array("0x1"),
                1080,
                1920,
                60
            );

            if ($validity) {
                return "HEVC HHD8";
            }
            return "unknown";
        }
        if ($mpParameters['level'] <= 5.0) {
            $validity = $this->determineVideoProfileValidity(
                $mpParameters,
                "B.5",
                "B.1",
                array("0x1"),
                array("0x1"),
                array("0x1"),
                2160,
                3840,
                60
            );

            if ($validity) {
                return "HEVC UHD8";
            }
            return "unknown";
        }
        return "unknown";
    }

    if ($mpParameters['profile'] == 'Main10') {
        $validLevel = $logger->test(
            "CMAF",
            "Section B.5",
            "For a CMAF Track to comply with one of the media profiles in Table B.1, it SHALL not exceed the " .
            "profile or level listed in the table",
            $mpParameters['level'] <= 4.1 || $mpParameters['level'] <= 5.1,
            "FAIL",
            "Valid level found",
            "Nonvalid level found"
        );

        if (!$validLevel) {
            return "unknown";
        }

        if ($mpParameters['level'] <= 4.1) {
            $validity = $this->determineVideoProfileValidity(
                $mpParameters,
                "B.5",
                "B.1",
                array("0x1"),
                array("0x1"),
                array("0x1"),
                1080,
                1920,
                60
            );

            if ($validity) {
                return "HEVC HHD10";
            }
            return "unknown";
        }
        if ($mpParameters['level'] <= 5.1) {
            $validity = $this->determineVideoProfileValidity(
                $mpParameters,
                "B.5",
                "B.1",
                array("0x1", "0x9"),
                array("0x1", "0x14", "0x15"),
                array("0x1", "0x9", "0x10"),
                2160,
                3840,
                60
            );

            if ($validity) {
                if ($mpParameters['brand'] == 'cud1') {
                    return "HEVC UHD10";
                }
                if ($mpParameters['brand'] == 'clg1') {
                    return "HEVC HLG10";
                }
                  return "HEVC UHD10, HEVC HLG10";
            }

            $validity2 = $this->determineVideoProfileValidity(
                $mpParameters,
                "B.5",
                "B.1",
                array("0x9"),
                array("0x16"),
                array("0x9", "0x10"),
                2160,
                3840,
                60
            );

            if ($validity2) {
                return "HEVC HDR10";
            }

            $validity3 = $this->determineVideoProfileValidity(
                $mpParameters,
                "B.5",
                "B.1",
                array("0x9"),
                array("0x14","0x18"),
                array("0x9"),
                2160,
                3840,
                60
            );

            if ($validity3) {
                return "HEVC HLG10";
            }

            return "unknown";
        }
        return "unknown";
    }
    return "unknown";
}

return "unknown";
