<?php

if (!$this->downloadTime) {
    return null;
}
if (!$this->dom) {
    return null;
}

//TODO Explicit check for type is dynamic

$mpdElement = $this->dom->getElementsByTagName("MPD");
if (!count($mpdElement)) {
    return null;
}

$minimumUpdatePeriod = $mpdElement->item(0)->getAttribute('minimumUpdatePeriod');
if (!$minimumUpdatePeriod) {
    return null;
}

$interval = null;
try {
    $interval = new \DateInterval($minimumUpdatePeriod);
} catch (Exception $e) {
}

if (!$interval) {
    return null;
}

return $this->downloadTime->add($interval);
