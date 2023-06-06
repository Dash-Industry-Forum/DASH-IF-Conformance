<?php

global $mediaProfileAttributesVideo, $mediaProfileAttributesAudio, $mediaProfileAttributesSubtitle;

global $logger;

$compatibleBrands = $xml->getElementsByTagName("ftyp")->item(0)->getAttribute("compatible_brands");
if ($hdlrType == 'vide') {
    $mediaProfileParameters = $mediaProfileAttributesVideo;
    $videoSampleDescription = $xml->getElementsByTagName("vide_sampledescription")->item(0);
    $sdType = $videoSampleDescription->getAttribute("sdType");
    if ($sdType == 'avc1' || $sdType == 'avc3') {
        $mediaProfileParameters['codec'] = "AVC";

        $nalUnits = $xml->getElementsByTagName("NALUnit");

        $hasNalUnits = $logger->test(
            "CTAWAVE",
            "WAVE Content Spec 2018Ed-Section 4.2.1",
            "Each WAVE video Media Profile SHALL conform to normative ref. listed in Table 1",
            $nalUnits->length,
            "FAIL",
            "NAL Units found for track $representationIndex of switching set $adaptationIndex",
            "NAL Units not found for track $representationIndex of switching set $adaptationIndex",
        );
        if (!$hasNalUnits) {
            return "unknown";
        }

        for ($nalIndex = 0; $nalIndex < $nalUnits->length; $nalIndex++) {
            if ($nalUnits->item($nalIndex)->getAttribute("nal_type") == "0x07") {
                $spsIndex = $nalIndex;
                 break;
            }
        }

        $comment = $nalUnits->item($spsIndex)->getElementsByTagName("comment")->item(0);
        $mediaProfileParameters['profile'] = $comment->getAttribute("profile");
        $mediaProfileParameters['level'] = (float)($comment->getAttribute("level_idc")) / 10;
        $mediaProfileParameters['width'] = $videoSampleDescription->getAttribute("width");
        $mediaProfileParameters['height'] = $videoSampleDescription->getAttribute("height");

        if ($comment->getAttribute("vui_parameters_present_flag") == "0x1") {
            if ($comment->getAttribute("video_signal_type_present_flag") == "0x1") {
                if ($comment->getAttribute("colour_description_present_flag") == "0x1") {
                    $mediaProfileParameters['color_primaries'] = $comment->getAttribute("colour_primaries");
                    $mediaProfileParameters['transfer_char'] = $comment->getAttribute("transfer_characteristics");
                    $mediaProfileParameters['matrix_coeff'] = $comment->getAttribute("matrix_coefficients");
                } elseif ($comment->getAttribute("colour_description_present_flag") == "0x0") {
                    $mediaProfileParameters['color_primaries'] = "0x1";
                    $mediaProfileParameters['transfer_char'] = "0x1";
                    $mediaProfileParameters['matrix_coeff'] = "0x1";
                }
            }
            if ($comment->getAttribute("timing_info_present_flag") == "0x1") {
                $numberOfUnitsPerTick = $comment->getAttribute("num_units_in_tick");
                $timeScale = $comment->getAttribute("time_scale");
                $mediaProfileParameters['framerate'] = $timeScale / (2 * $numberOfUnitsPerTick);
            }
        }

        if (strpos($compatibleBrands, "cfsd") !== false) {
            $mediaProfileParameters['brand'] = "cfsd";
        }
        if (strpos($compatibleBrands, "cfhd") !== false) {
            $mediaProfileParameters['brand'] = "cfhd";
        }
    }
    if ($sdType == 'hev1' || $sdType == 'hvc1') {
        $mediaProfileParameters['codec'] = "HEVC";
        $hvcC = $xml->getElementsByTagName("hvcC");
        if ($hvcC->length > 0) {
            $hvcC = $xml->getElementsByTagName("hvcC")->item(0);
            if (
                ($hvcC->getAttribute("profile_idc") == "1") ||
                ($hvcC->getAttribute("compatibility_flag_1")) == "1"
            ) {
                $profile = "Main";
            } elseif (
                ($hvcC->getAttribute("profile_idc") == "2") ||
                ($hvcC->getAttribute("compatibility_flag_2")) == "1"
            ) {
                $profile = "Main10";
            } else {
                $profile = "Other";
            }

            $tier = $hvcC->getAttribute("tier_flag");
            $mediaProfileParameters['tier'] = $tier;//Tier=0 is the main-tier.
            $mediaProfileParameters['profile'] = $profile;
            //HEVC std defines level_idc is 30 times of actual level number.
            $mediaProfileParameters['level'] = (float)($hvcC->getAttribute("level_idc")) / 30;
        }
        $mediaProfileParameters['width'] = $videoSampleDescription->getAttribute("width");
        $mediaProfileParameters['height'] = $videoSampleDescription->getAttribute("height");

        $nalUnits = $xml->getElementsByTagName("NALUnit");
        $hasNalUnits = $logger->test(
            "CTAWAVE",
            "WAVE Content Spec 2018Ed-Section 4.2.1",
            "Each WAVE video Media Profile SHALL conform to normative ref. listed in Table 1",
            $nalUnits->length,
            "FAIL",
            "NAL Units found for track $representationIndex of switching set $adaptationIndex",
            "NAL Units not found for track $representationIndex of switching set $adaptationIndex",
        );
        if (!$hasNalUnits) {
            return "unknown";
        }

        for ($nalIndex = 0; $nalIndex < $nalUnits->length; $nalIndex++) {
            if ($nalUnits->item($nalIndex)->getAttribute("nal_unit_type") == "33") {
                $spsIndex = $nalIndex;
                 break;
            }
        }
        $sps = $nalUnits->item($spsIndex);
        if ($sps->getAttribute("vui_parameters_present_flag") == "1") {
            if ($sps->getAttribute("video_signal_type_present_flag") == "1") {
                if ($sps->getAttribute("colour_description_present_flag") == "1") {
                    $mediaProfileParameters['color_primaries'] = $sps->getAttribute("colour_primaries");
                    $mediaProfileParameters['transfer_char'] = $sps->getAttribute("transfer_characteristics");
                    $mediaProfileParameters['matrix_coeff'] = $sps->getAttribute("matrix_coeffs");
                } elseif ($sps->getAttribute("colour_description_present_flag") == "0") {
                    $mediaProfileParameters['color_primaries'] = "1";
                    $mediaProfileParameters['transfer_char'] = "1";
                    $mediaProfileParameters['matrix_coeff'] = "1";
                }
            }
            if ($sps->getAttribute("vui_timing_info_present_flag") == "1") {
                $numberOfUnitsPerTick = $sps->getAttribute("vui_num_units_in_tick");
                $timeScale = $sps->getAttribute("vui_time_scale");
                $mediaProfileParameters['framerate'] = $timeScale / ($numberOfUnitsPerTick);
            }
        }
        if (strpos($compatibleBrands, "chh1") !== false) {
            $mediaProfileParameters['brand'] = "chh1";
        } elseif (strpos($compatibleBrands, "cud1") !== false) {
            $mediaProfileParameters['brand'] = "cud1";
        } elseif (strpos($compatibleBrands, "clg1") !== false) {
            $mediaProfileParameters['brand'] = "clg1";
        } elseif (strpos($compatibleBrands, "chd1") !== false) {
            $mediaProfileParameters['brand'] = "chd1";
        }
    }
    return $this->checkAndGetConformingVideoProfile($mediaProfileParameters, $representationIndex, $adaptationIndex);
}
if ($hdlrType == 'soun') {
    $mediaProfileParameters = $mediaProfileAttributesAudio;
    $audioSampleDescription = $xml->getElementsByTagName("soun_sampledescription")->item(0);
    $sdType = $audioSampleDescription->getAttribute("sdType");
    if ($sdType == "mp4a") {
        $mediaProfileParameters['codec'] = "AAC";
        $decoderSpecInfo = $audioSampleDescription->getElementsByTagName("DecoderSpecificInfo")->item(0);
        $audioObj = $decoderSpecInfo->getAttribute("audioObjectType");
        $mediaProfileParameters['profile'] = $audioObj;
        $channels = $decoderSpecInfo->getAttribute("channelConfig");

        $mediaProfileParameters['channels'] = $channels;
        $mediaProfileParameters['sampleRate'] = $audioSampleDescription->getAttribute('sampleRate');

        if (strpos($compatibleBrands, "caaa") !== false) {
            $mediaProfileParameters['brand'] = "caaa";
        } elseif (strpos($compatibleBrands, "caac") !== false) {
            $mediaProfileParameters['brand'] = "caac";
        } elseif (strpos($compatibleBrands, "camc") !== false) {
            $mediaProfileParameters['brand'] = "camc";
        }

        $levelcomment = $xml->getElementsByTagName("iods_OD");
        if ($levelcomment->length > 0) {
            $profileLevelString = $levelcomment->item(0)->getAttribute("Comment");
            if ($profileLevelString !== null) {
                $profileLevel = str_replace("audio profile/level is ", "", $profileLevelString);
                $mediaProfileParameters['level'] == $profileLevel;
            }
        }
    }
    if ($sdType == "ec-3") {
        $mediaProfileParameters['codec'] = "EAC-3";
        $mediaProfileParameters['profile'] = "EAC-3";

        $brandPosition = strpos($compatibleBrands, "ceac");
        if ($brandPosition !== false) {
            $mediaProfileParameters['brand'] = substr($compatibleBrands, $brandPosition, $brandPosition + 3);
        }
    }
    if ($sdType == "ac-3") {
        $mediaProfileParameters['codec'] = "AC-3";
        $mediaProfileParameters['profile'] = "AC-3";

        $brandPosition = strpos($compatibleBrands, "ceac");
        if ($brandPosition !== false) {
            $mediaProfileParameters['brand'] = substr($compatibleBrands, $brandPosition, $brandPosition + 3);
        }
    }
    if ($sdType == "ac-4") {
        $mediaProfileParameters['codec'] = "AC-4";
        $mediaProfileParameters['profile'] = "AC-4";

        $brandPosition = strpos($compatibleBrands, "ca4s");
        if ($brandPosition !== false) {
            $mediaProfileParameters['brand'] = substr($compatibleBrands, $brandPosition, $brandPosition + 3);
        }

        $dac4 = $audioSampleDescription->getElementsByTagName("ac4_dsi_v1");
        if ($dac4->length > 0) {
            if ($dac4->item(0)->hasAttribute("mdcompat_0")) {
                 $mediaProfileParameters['level'] = $dac4->item(0)->getAttribute("mdcompat_0");
            }
        }
    }
    if ($sdType == "mhm1") {
        $mediaProfileParameters['codec'] = "MPEG-H";
        $mediaProfileParameters['sampleRate'] = $audioSampleDescription->getAttribute('sampleRate');

        $brandPosition = strpos($compatibleBrands, "cmhs");
        if ($brandPosition !== false) {
            $mediaProfileParameters['brand'] = substr($compatibleBrands, $brandPosition, $brandPosition + 3);
        }

        $mhaC = $audioSampleDescription->getElementsByTagName("mhaC");
        if ($mhaC->length > 0) {
            $mediaProfileParameters['profile'] = $mhaC->item(0)->getAttribute("mpegh3daProfileLevelIndication");
            $mediaProfileParameters['channel'] = $mhaC->item(0)->getAttribute("referenceChannelLayout");
        }
    }
    return $this->checkAndGetConformingAudioProfile($mediaProfileParameters, $representationIndex, $adaptationIndex);
}
if ($hdlrType == 'subt') {
    $mediaProfileParameters = $mediaProfileAttributesSubtitle;
    $subtitleSampleDescription = $xml->getElementsByTagName("subt_sampledescription")->item(0);
    $sdType = $subtitleSampleDescription->getAttribute("sdType");
    if ($sdType == "stpp") {
        $mime = $subtitleSampleDescription->getElementsByTagName("mime");
        if ($mime->length) {
            $contentType = $mime->getAttribute("content_type");
            $subtypePosition = strpos($contentType, "ttml+xml") || strpos($contentType, "mp4");
            $codecPosition = strpos($contentType, "im1t") || strpos($contentType, "im1i");

            $mediaProfileParameters['mimeType'] = "";
            if (strpos($contentType, "application") !== false) {
                $mediaProfileParameters['mimeType'] = "application";
            }
            $mediaProfileParameters['codec'] = "";
            if ($codecPosition !== false) {
                $mediaProfileParameters['codec'] = substr($contentType, $codecPosition, $codecPosition + 3);
            }

            if (strpos($contentType, "ttml+xml") !== false) {
                $mediaProfileParameters['mimeSubtype'] = "ttml+xml";
            } elseif (strpos($contentType, "mp4") !== false) {
                $mediaProfileParameters['mimeSubtype'] = "mp4";
            }
        }

        if (strpos($compatibleBrands, "im1t") !== false) {
            $mediaProfileParameters['brand'] = "im1t";
        } elseif (strpos($compatibleBrands, "im1i") !== false) {
            $mediaProfileParameters['brand'] = "im1i";
        }
    }
    return $this->checkAndGetConformingSubtitleProfile($mediaProfileParameters, $representationIndex, $adaptationIndex);
}
return $MP;
