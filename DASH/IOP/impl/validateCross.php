<?php

global $mpd_features, $current_period;

$period = $mpd_features['Period'][$current_period];
$adaptationSets = $period['AdaptationSet'];

foreach ($adaptationSets as $id => $adaptationSet) {
    $this->validateCrossAvcHevc($adaptationSet, $id);
}
