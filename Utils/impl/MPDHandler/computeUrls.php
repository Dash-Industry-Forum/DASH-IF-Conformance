<?php

$initialization = $segmentAccess['initialization'];
$media = $segmentAccess['media'];
$bandwidth = $representation['bandwidth'];
$id = $representation['id'];

$startNumber = 1;
if ($segmentAccess['startNumber'] != null) {
    $startNumber =  $segmentAccess['startNumber'];
}

$segmentUrls = array();

if ($initialization != null) {
    $initializationUrl = '';
    $init = str_replace(array('$Bandwidth$', '$RepresentationID$'), array($bandwidth, $id), $initialization);

    if (DASHIF\Utility\isAbsoluteURL($init)) {
        $segmentUrls[] = $init;
    } else {
        if (substr($baseUrl, -1) == '/') {
            $url = $baseUrl . $init;
        } else {
            $url = $baseUrl . "/" . $init;
        }
        $segmentUrls[] = $url;
    }
}

$currentTime = 0;

$index = 0;
$segmentCount = sizeof($segmentInfo);
if ($this->features['type'] == 'dynamic') {
    list($index, $segmentCount, $currentTime) = $this->computeDynamicIntervals(
        $adaptationSetId,
        $representationId,
        $segmentAccess,
        $segmentInfo,
        $segmentCount
    );
}

///\Todo translate checks below into actual "check"
while ($index < $segmentCount) {
    $segmentUrl = str_replace(
        array('$Bandwidth$', '$Number$', '$RepresentationID$', '$Time$'),
        array($bandwidth, $index + $startNumber, $id, $segmentInfo[$currentTime]),
        $media
    );

    $pos = strpos($segmentUrl, '$Number');
    if ($pos !== false) {
        if (substr($segmentUrl, $pos + strlen('$Number'), 1) === '%') {
            $segmentUrl = sprintf($segmentUrl, $startNumber + $index);
            $segmentUrl = str_replace('$Number', '', $segmentUrl);
            $segmentUrl = str_replace('$', '', $segmentUrl);
        } else {
            fwrite(STDERR, "It cannot happen! the format should be either \$Number$ or \$Number%xd$!\n");
        }
    }
    $pos = strpos($segmentUrl, '$Time');
    if ($pos !== false) {
        if (substr($segmentUrl, $pos + strlen('$Time'), 1) === '%') {
            $segmentUrl = sprintf($segmentUrl, $segmentInfo[$index]);
            $segmentUrl = str_replace('$Time', '', $segmentUrl);
            $segmentUrl = str_replace('$', '', $segmentUrl);
        } else {
            fwrite(STDERR, "It cannot happen! the format should be either \$Time$ or \$Time%xd$!\n");
        }
    }

    if (!DASHIF\Utility\isAbsoluteURL($segmentUrl)) {
        if (substr($baseUrl, -1) == '/') {
            $segmentUrl = $baseUrl . $segmentUrl;
        } else {
            $segmentUrl = $baseUrl . "/" . $segmentUrl;
        }
    }
    $segmentUrls[] = $segmentUrl;
    $index++;
    $currentTime++;
}


return $segmentUrls;
