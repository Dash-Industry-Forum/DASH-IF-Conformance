<?php

global $logger, $mpdHandler;

$compatibleBrands = $xml->getElementsByTagName("ftyp")->item(0)->getAttribute("compatibleBrands");
$hdlrType = $xml->getElementsByTagName("hdlr")->item(0)->getAttribute("handler_type");

if ($hdlrType == 'vide') {
    return "__REWRITTEN__";
}
if ($hdlrType == 'soun') {
    $mediaProfileParameters = $this->CMAFMediaProfileAttributesAudio;
    $audioSampleDescription = $xml->getElementsByTagName("soun_sampledescription")->item(0);
    $sdType = $audioSampleDescription->getAttribute("sdType");
    if ($sdType == "mp4a") {
        $mediaProfileParameters['codec'] = "AAC";
        $decoderSpecificInfo = $audioSampleDescription->getElementsByTagName("DecoderSpecificInfo")->item(0);
        $mediaProfileParameters['sampleRate'] = $audioSampleDescription->getAttribute('sampleRate');
        $mediaProfileParameters['profile'] = hexdec($decoderSpecificInfo->getAttribute("audioObjectType"));
        $mediaProfileParameters['channels'] = hexdec($decoderSpecificInfo->getAttribute("channelConfig"));

        if (strpos($compatibleBrands, "caaa") !== false) {
            $mediaProfileParameters['brand'] = "caaa";
        } elseif (strpos($compatibleBrands, "caac")) {
            $mediaProfileParameters['brand'] = "caac";
        }

        $iodsODBoxes = $xml->getElementsByTagName("iods_OD");
        if ($iodsODBoxes->length > 0) {
            $iodsComment = $iodsODBoxes->getAttribute("Comment");
            if ($iodsComment !== null) {
                $mediaProfileParameters['level'] == str_replace("audio profile/level is ", "", $iodsComment);
            }
        }
    }
    return $this->getAudioTrackMediaProfile($mediaProfileParameters);
}
if ($hdlrType == 'text') {
    $mediaProfileParameters = $this->CMAFMediaProfileAttributesSubtitle;
    $testSampleDescription = $xml->getElementsByTagName("text_sampledescription")->item(0);
    $sdType = $testSampleDescription->getAttribute("sdType");
    if ($sdType == 'wvtt') {
        $mediaProfileParameters['codec'] = "WebVTT";

        if (strpos($compatibleBrands, "cwvt") !== false) {
            $mediaProfileParameters['brand'] = "cwvt";
        }
    }
    return $this->getSubtitleTrackMediaProfile($mediaProfileParameters);
}
if ($hdlrType == 'subt') {
    $mediaProfileParameters = $this->CMAFMediaProfileAttributesSubtitle;
    $subtitleSampleDescription = $xml->getElementsByTagName("subt_sampledescription")->item(0);
    $sdType = $subtitleSampleDescription->getAttribute("sdType");
    if ($sdType == "stpp") {
        $mime = $subtitleSampleDescription->getElementsByTagName("mime");
        if ($mime->length > 0) {
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
        }
        if (strpos($compatibleBrands, "im1i") !== false) {
            $mediaProfileParameters['brand'] = "im1i";
        }
    }
    return $this-> getSubtitleTrackMediaProfile($mediaProfileParameters);
}
return null;
