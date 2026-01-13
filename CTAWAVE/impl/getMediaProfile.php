<?php

global $mediaProfileAttributesVideo, $mediaProfileAttributesAudio, $mediaProfileAttributesSubtitle;

global $logger, $mpdHandler;

$compatibleBrands = $xml->getElementsByTagName("ftyp")->item(0)->getAttribute("compatible_brands");
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
