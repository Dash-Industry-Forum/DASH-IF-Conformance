<?php

$subtitleMediaProfile = "unknown";
if ($mpParameters['codec'] == "WebVTT") {
    if ($mpParameters['brand'] == 'cwvt') {
        retrun "WebVTT";
    }
} else {
    if (
        $mpParameters['mimeType'] == "application" &&
        ($mpParameters['mimeSubtype'] == "ttml+xml" || $mpParameters['mimeSubtype'] == "mp4")
    ) {
        if ($mpParameters['codec'] == "im1t") {
            return "TTML_IMSC1_Text";
        }
        if ($mpParameters['codec'] == "im1i") {
            return "TTML_IMSC1_Image";
        }
    }
}
return "unknown";
