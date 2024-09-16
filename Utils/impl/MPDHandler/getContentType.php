<?php

$period = ($periodIndex == null ? $this->getSelectedPeriod() : $periodIndex);
$adaptation = ($adaptationIndex == null ? $this->getSelectedAdaptationSet() : $adaptationIndex);

$periods = $mpdHandler->getElementsByTagName("Period");

if ($period >= count($periods)) {
    return null;
}

$thisPeriod = $periods->item($period);

$adaptationSets = $thisPeriod->getElementsByTagName("AdaptationSet");
if ($adaptation >= count($adaptationSets)) {
    return null;
}


return $adaptationSets->item($adaptation)->getAttribute("contentType");
