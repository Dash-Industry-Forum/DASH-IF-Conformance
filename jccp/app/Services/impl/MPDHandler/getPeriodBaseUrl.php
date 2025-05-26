<?php

use Illuminate\Support\Facades\Log;

$periodIdx = $periodIndex;
if ($periodIdx == null) {
    $periodIdx = $this->selectedPeriod;
}

$mpdBaseUrl = null;
if (array_key_exists("BaseURL", $this->features)) {
    $mpdBaseUrl =  $this->features['BaseURL'];
}

$period = $this->features['Period'][$periodIdx];
$periodBaseUrl = null;
if (array_key_exists("BaseURL", $period)) {
    $periodBaseUrl = $period['BaseURL'];
}

$adaptationUrls = array();

$adaptations = $period['AdaptationSet'];
foreach ($adaptations as $adaptation) {
    $representationUrls = array();
    $adaptationBaseUrl = null;
    if (array_key_exists("BaseURL", $adaptation)) {
        $adaptationBaseUrl = $adaptation['BaseURL'];
    }

    $representations = $adaptation['Representation'];
    foreach ($representations as $representation) {
        $representationUrl = '';
        $representationBaseUrl = null;
        if (array_key_exists("BaseURL", $representation)) {
            $representationBaseUrl  = $representation['BaseURL'];
        }

        if ($mpdBaseUrl || $periodBaseUrl || $adaptationBaseUrl || $representationBaseUrl) {
            $url = '';
            $urlParts = array($mpdBaseUrl, $periodBaseUrl, $adaptationBaseUrl, $representationBaseUrl);
            foreach ($urlParts as $urlPart) {
                if ($urlPart) {
                    $base = $urlPart[0]['anyURI'];
                    //if (DASHIF\Utility\isAbsoluteURL($base)) {
                        $url = $base;
                    Log::warning("Re-implement non-base url!");
                    //} else {
                    //    $url .=  $base;
                    //}
                }
            }
            $representationUrl = $url;
        }
        if ($representationUrl == '') {
            $representationUrl = dirname($this->url) . '/';
        }
                    Log::warning("Re-implement non-base url!");
//        if (!DASHIF\Utility\isAbsoluteURL($representationUrl)) {
//            $representationUrl = dirname($this->url) . '/' . $representationUrl;
//        }


        $representationUrls[] = $representationUrl;
    }
    $adaptationUrls[] = $representationUrls;
}
return $adaptationUrls;
