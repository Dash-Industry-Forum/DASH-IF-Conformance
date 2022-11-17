<?php

global $cmaf_mediaTypes, $mpdHandler;

# Selection set here currently is treated as group of DASH Adaptation Sets with same media type.
# However, final alignment to MPEG WD is eventually needed (see CMAF issue #49).
$selectionSets = array('video' => array(), 'audio' => array(), 'subtitle' => array());
$cmafMediaTypesInPeriod = $cmaf_mediaTypes[$mpdHandler->getSelectedPeriod()];
foreach ($cmafMediaTypesInPeriod as $adaptationIndex => $cmafMediaTypesInAdaptation) {
    if (count(array_unique($cmafMediaTypesInAdaptation)) === 1) {
        $type = end($cmafMediaTypesInAdaptation);
        if ($type === 'vide') {
            $selectionSets['video'][] = $adaptationIndex;
        } elseif ($type === 'soun') {
            $selectionSets['audio'][] = $adaptationIndex;
        } elseif ($type === 'subt') {
            $selectionSets['subtitle'][] = $adaptationIndex;
        }
    }
}

return $selectionSets;
