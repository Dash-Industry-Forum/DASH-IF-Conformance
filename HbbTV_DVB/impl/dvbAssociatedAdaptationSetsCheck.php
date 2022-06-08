<?php

    global $mpd_dom;
    $periods = $mpd_dom->getElementsByTagName('Period');
    $len = $periods->length;

for ($i = 0; $i < $len; $i++) {
    $assets1 = $periods->item($i)->getElementsByTagName('AssetIdentifier');

    if ($assets1->length == 0) {
        continue;
    }
    for ($j = $i + 1; $j < $period_cnt; $j++) {
         $assets2 = $periods->item($j)->getElementsByTagName('AssetIdentifier');

        if ($assets2->length == 0) {
            continue;
        }

        if ($this->checkAssetIdentifiers($assets1, $assets2)) {
            $this->checkAdaptationSetsIds(
                $period1->getElementsByTagName('AdaptationSet'),
                $period2->getElementsByTagName('AdaptationSet')
            );
        }
    }
}
