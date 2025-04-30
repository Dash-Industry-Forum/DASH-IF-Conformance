<?php
use Illuminate\Support\Facades\Log;

if (!$this->downloadTime) {
    return null;
}
if (!$this->dom) {
    return null;
}

//TODO Explicit check for type is dynamic

$mpdElement = $this->dom;
if ($mpdElement->tagName != "MPD") {
    $mpdElements = $this->dom->getElementsByTagName("MPD");
    if (!count($mpdElements)) {
        Log::error("No MPD element in dom");
        return null;
    }
    $mpdElement = $mpdElements->item(0);
}


$minimumUpdatePeriod = $mpdElement->getAttribute('minimumUpdatePeriod');

if (!$minimumUpdatePeriod) {
    return null;
}

$interval = DASHIF\Utility\timeParsing($minimumUpdatePeriod);

if (!$interval) {
    return null;
}

$originalTime = $this->downloadTime->getTimestamp();
$nextTime = $originalTime + $interval;
return new DateTimeImmutable("@$nextTime");
