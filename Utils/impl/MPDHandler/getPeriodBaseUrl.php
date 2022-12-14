<?php

$periodIdx = $periodIndex;
if ($periodIdx == null) {
    $periodIdx = $this->selectedPeriod;
}

$mpdBaseUrl = $this->features['BaseURL'];

$period = $this->features['Period'][$periodIdx];
$periodBaseUrl = $period['BaseURL'];

$adaptationUrls = array();

$adaptations = $period['AdaptationSet'];
foreach ($adaptations as $adaptation) {
    $representationUrls = array();
    $adaptationBaseUrl = $adaptation['BaseURL'];

    $representations = $adaptation['Representation'];
    foreach ($representations as $representation) {
        $representationUrl = '';
        $representationBaseUrl = $representation['BaseURL'];

        if ($mpdBaseUrl || $periodBaseUrl || $adaptationBaseUrl || $representationBaseUrl) {
            $url = '';
            $urlParts = array($mpdBaseUrl, $periodBaseUrl, $adaptationBaseUrl, $representationBaseUrl);
            foreach ($urlParts as $urlPart) {
                if ($urlPart) {
                    $base = $urlPart[0]['anyURI'];
                    if (DASHIF\Utility\isAbsoluteURL($base)) {
                        $url = $base;
                    } else {
                        $url .=  $base;
                    }
                }
            }
            $representationUrl = $url;
        }
        if ($representationUrl == '') {
            $representationUrl = dirname($this->url) . '/';
        }
        if (!DASHIF\Utility\isAbsoluteURL($representationUrl)) {
            $representationUrl = dirname($this->url) . '/' . $representationUrl;
        }


        $representationUrls[] = $representationUrl;
    }
    $adaptationUrls[] = $representationUrls;
}
return $adaptationUrls;
