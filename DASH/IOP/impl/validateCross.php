<?php

global $mpdHandler;

$period = $mpdHandler->getFeatures()['Period'][$mpdHandler->getSelectedPeriod()];
$adaptationSets = $period['AdaptationSet'];

foreach ($adaptationSets as $id => $adaptationSet) {
    $this->validateCrossAvcHevc($adaptationSet, $id);
}
