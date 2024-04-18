<?php

$period = ($periodIndex == null ? $this->getSelectedPeriod() : $periodIndex);
$adaptation = ($adaptationIndex == null ? $this->getSelectedAdaptationSet() : $adaptationIndex);
$representation = ($representationIndex == null ? $this->getSelectedRepresentation() : $representationIndex);

$framerate = 0;

$periods = $mpdHandler->getElementsByTagName("Period");

if ($period >= count($periods)) {
    return null;
}

$thisPeriod = $periods->item($period);

if ($thisPeriod->hasAttribute('framerate')) {
    $framerate = $thisPeriod->getAttribute('framerate');
}

$adaptationSets = $thisPeriod->getElementsByTagName("AdaptationSet");
if ($adaptation >= count($adaptationSets)) {
    return null;
}

$thisAdaptation = $adaptationSets->item($adaptation);

if ($thisAdaptation->hasAttribute('framerate')) {
    $framerate = $thisAdaptation->getAttribute('framerate');
}

$representations = $thisAdaptation->getElementsByTagName("Representation");

if ($representation >= count($representations)) {
    return null;
}

$thisRepresentation = $representations->item($representation);

if ($thisRepresentation->hasAttribute('framerate')) {
    $framerate = $thisRepresentation->getAttribute('framerate');
}

return $framerate;
