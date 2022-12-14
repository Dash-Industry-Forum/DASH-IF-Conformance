<?php

global $mpdHandler, $logger;

$baseUrl = '';
//test link https://media.axprod.net/TestVectors/v7-MultiDRM-SingleKey/Manifest.mpd
if ($mpdHandler->getDom()->getElementsByTagName('BaseURL')->length != 0) {
    $baseUrl = $mpdHandler->getDom()->getElementsByTagName('BaseURL')->item(0)->textContent;
}

$usesTLS = false;

//check if TLS is used
if (strpos($baseUrl, 'https') !== false) {
    $usesTLS = true;
} elseif (strpos($baseUrl, 'http') !== false) {
    $usesTLS = false;
} elseif (strpos($mpdHandler->getUrl(), 'https') !== false) {
    $usesTLS = true;
}

//If TLS is not used, skip the remainder of this function
if (!$usesTLS) {
    return;
}



//Check if any combination excedes the constraint
$period_id = 1;
foreach ($mpdHandler->getDom()->getElementsByTagName('Period') as $period) {
    $videoBandwidths = array();
    $audioBandwidths = array();
    $subtitleBandwidths = array();

    foreach ($period->getElementsByTagName('AdaptationSet') as $adaptationSet) {
        foreach ($adaptationSet->getElementsByTagName('Representation') as $representation) {
            $representationId = $representation->getAttribute('id');
            $representationBandwith = $representation->getAttribute('bandwidth');
            $mimeType = $adaptationSet->getAttribute('mimeType');
            if ($mimeType == 'video/mp4') {
                $videoBandwidths[$representationId] = $representationBandwith;
            } elseif ($mimeType == 'audio/mp4') {
                $audioBandwidths[$representationId] = $representationBandwith;
            } elseif ($mimeType == 'application/mp4') {
                $subtitleBandwidths[$representationId] = $representationBandwith;
            } elseif ($mimeType == '') {
                if ($representation->getAttribute('mimeType') == 'video/mp4') {
                    $videoBandwidths[$representationId] = $representationBandwith;
                } elseif ($representation->getAttribute('mimeType') == 'audio/mp4') {
                    $audioBandwidths[$representationId] = $representationBandwith;
                } elseif ($representation->getAttribute('mimeType') == 'application/mp4') {
                    $subtitleBandwidths[$representationId] = $representationBandwith;
                }
            }
        }
    }

    if (count($videoBandwidths) == 0) {
        $videoBandwidths['No video'] = 0;
    }
    if (count($audioBandwidths) == 0) {
        $audioBandwidths['No audio'] = 0;
    }
    if (count($subtitleBandwidths) == 0) {
        $subtitleBandwidths['No Subtitles'] = 0;
    }

    foreach ($videoBandwidths as $vRepId => $vRepBandwidth) {
        foreach ($audioBandwidths as $aRepId => $aRepBandwidth) {
            foreach ($subtitleBandwidths as $sRepId => $sRepBandwidth) {
                $totalBandwidth = $vRepBandwidth + $aRepBandwidth + $sRepBandwidth;

                $bandWidthMessage = "V@" . number_format($vRepBandwidth / 1000000, 2) . "Mbit/s, ";
                $bandWidthMessage .= "A@" . number_format($aRepBandwidth / 1000000, 2) . "Mbit/s, ";
                $bandWidthMessage .= "S@" . number_format($sRepBandwidth / 1000000, 2) . "Mbit/s";
                $totalBandWidthMessage = "Total: " . number_format($totalBandwidth / 1000000, 2) . "Mbit/s";

                $combinationMessage = "V:" . $vRepId . ", ";
                $combinationMessage .= "A:" . $aRepId . ", ";
                $combinationMessage .= "S:" . $sRepId;


                $logger->test(
                    "HbbTV-DVB DASH Validation Requirements",
                    "HbbTV: Section 'TLS'",
                    "Bitrate checks for terminal that does support UHD HFR video (max 51 Mbit/s)",
                    $totalBandwidth <= 51000000,
                    "WARN",
                    "Period $period_id ($combinationMessage) does not exceed bounds: $totalBandWidthMessage",
                    "Period $period_id ($combinationMessage) exceeds bounds: $bandWidthMessage $totalBandWidthMessage"
                );
                $logger->test(
                    "HbbTV-DVB DASH Validation Requirements",
                    "HbbTV: Section 'TLS'",
                    "Bitrate checks for terminal that does support UHD video, but not HFR video (max 39 Mbit/s)",
                    $totalBandwidth <= 39000000,
                    "WARN",
                    "Period $period_id ($combinationMessage) does not exceed bounds: $totalBandWidthMessage",
                    "Period $period_id ($combinationMessage) exceeds bounds: $totalBandWidthMessage: $bandWidthMessage"
                );
                $logger->test(
                    "HbbTV-DVB DASH Validation Requirements",
                    "HbbTV: Section 'TLS'",
                    "Bitrate checks for terminal that does not support UHD video (max 12 Mbit/s)",
                    $totalBandwidth <= 12000000,
                    "WARN",
                    "Period $period_id ($combinationMessage) does not exceed bounds: $totalBandWidthMessage",
                    "Period $period_id ($combinationMessage) exceeds bounds: $totalBandWidthMessage: $bandWidthMessage"
                );
            }
        }
    }
    $period_id++;
}
