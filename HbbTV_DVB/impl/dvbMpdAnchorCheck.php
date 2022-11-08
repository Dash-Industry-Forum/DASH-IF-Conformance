<?php

global $mpd_dom, $logger;
$allowedKeys = array('t', 'period', 'track', 'group');

$anchors = explode('#', json_decode($_POST['urlcode'])[0]);
if (sizeof($anchors) <= 1) {
    return;
}

$periods = $mpd_dom->getElementsByTagName('Period');
$periodIds = array();
foreach ($periods as $period) {
    $adaptations = $period->getElementsByTagName('AdaptationSet');
    $adaptationIdsGroups = array();
    foreach ($adaptations as $adaptation) {
        $adaptationIdsGroups[] = $adaptation->getAttribute('id') . ',' . $adaptation->getAttribute('group');
    }

    $periodIds[] = array($period->getAttribute('id') => $adaptationIdsGropus);
}


$periodExists = false;
$tExists = false;
$posixExits = false;

$anchors = $anchors[1];
$anchorParts = explode('&', $anchors);
foreach ($anchorParts as $anchor) {
    $key = substr($anchor, 0, strpos($anchor, '='));
    $value = substr($anchor, strpos($anchor, '=') + 1);


    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "DVB DASH Specifics",
        "Anchor keys should be one listed in Table C.1 in clause C.4 in ISO/IEC 23009-1:2014",
        in_array($key, $allowedKeys),
        "WARN",
        "$key is listed",
        "$key is not listed"
    );

    if ($key == 'period') {
        $periodExists = true;
        $foundAtLeastOneValidId = false;
        foreach ($periodIds as $periodId) {
            if (array_key_exists($value, $periodId)) {
                $foundAtLeastOneValidId = true;
                break;
            }
        }
        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "DVB DASH Specifics",
            "Anchors with key \"period\" should have a value matching at least one of the period @id attributes",
            $foundAtLeastOneValidId,
            "WARN",
            "Valid attribute found",
            "No valid attributes found"
        );

        continue;
    }
    if ($key == 'track' || $key == 'group') {
        $validTrackFound = false;
        $validGroupFound = false;
        foreach ($periodIds as $periodId) {
            foreach ($periodId as $adaptationIdGroups) {
                foreach ($adaptationIdGroups as $adaptationIdGroup) {
                    $idGroup = explode(',', $adaptationIdGroup);

                    if ($key == 'track') {
                        if (strpos($idGroup[0], $value) !== false) {
                            $validTrackFound = true;
                        }
                    } elseif ($key == 'group') {
                        if (strpos($idGroup[1], $value) !== false) {
                            $validGroupFound = true;
                        }
                    }
                }
            }
        }
        if ($key == 'track') {
            $logger->test(
                "HbbTV-DVB DASH Validation Requirements",
                "DVB DASH Specifics",
                "Anchors with key \"track\" should have a value matching at least one of the attribute "
                . "@id attributes",
                $validTrackFound,
                "WARN",
                "Valid attribute found",
                "No valid attributes found"
            );
        }
        if ($key == 'group') {
            $logger->test(
                "HbbTV-DVB DASH Validation Requirements",
                "DVB DASH Specifics",
                "Anchors with key \"group\" should have a value matching at least one of the attribute " .
                "@group attributes",
                $validGroupFound,
                "WARN",
                "Valid attribute found",
                "No valid attributes found"
            );
        }
        continue;
    }
    if ($key == 't') {
        $tExists = true;
        if (strpos($value, 'posix') !== false) {
            $posixExits = true;
            $logger->test(
                "HbbTV-DVB DASH Validation Requirements",
                "DVB DASH Specifics",
                "Anchors with key \"t\" and prefix posix should also have an MPD@availabilityStartTime",
                $mpd_dom->getAttribute('availabilityStartTime') != '',
                "WARN",
                "availabilityStartTime attribute found",
                "availabilityStartTime attribute does not exist"
            );

            $timeRange = explode(',', substr($value, strpos($value, 'posix') + 6));
            $t = $this->computeTimerange($timeRange);
        } else {
            if (strpos($value, 'npt') !== false) {
                $timeRange = explode(',', substr($value, strpos($value, 'npt') + 4));
                $t = $this->computeTimerange($timeRange);
            } else {
                $timeRange = explode(',', $value);
                $t = $this->computeTimerange($timeRange);
            }
        }
    }
}

$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB DASH Specifics",
    "\"period\" and \"t\" anchors should not be used at the same time",
    !$periodExists || !$tExists,
    "WARN",
    "Anchor types not used at the same time",
    "Both \"period\" and \"t\" anchors used"
);

if (!$tExists) {
    return;
}

$periodDurations = DASHIF\Utility\periodDurationInfo();
$pStarts = $periodDurations[0];
$pDurations = $periodDurations[1];
$coverage = false;

if ($posixExits) {
    $availabilityStartTime = strtotime($mpd_dom->getAttribute('availabilityStartTime'));
    if ($t[0] - $availabilityStartTime >= $pStarts[0]) {
        if ($t[1] == PHP_INT_MAX) {
            $coverage = true;
        } else {
            if (
                $t[1] - $availabilityStartTime <=
                $pStarts[sizeof($pDurations) - 1] + $pDurations[sizeof($pDurations) - 1]
            ) {
                $coverage = true;
            }
        }
    }
} else {
    if ($t[0] >= $p_starts[0]) {
        if ($t[1] == PHP_INT_MAX) {
            $coverage = true;
        } else {
            if (
                $t[1] <=
                $p_starts[sizeof($p_durations) - 1] + $p_durations[sizeof($p_durations) - 1]
            ) {
                $coverage = true;
            }
        }
    }
}


$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB DASH Specifics",
    "Anchors with key \"t\" should refer to a time available in the mpd",
    $coverage,
    "WARN",
    "Valid anchor reference found",
    "Invalid anchor reference found"
);
