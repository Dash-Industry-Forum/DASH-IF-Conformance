<?php

global $mpd_features, $service_description_info, $logger;

$service_descriptions = $mpd_features['ServiceDescription'];
$logger->test(
    "DASH-IF IOP CR Low Latency Live",
    "Section 9.X.4.2",
    "'At least one ServiceDescription element SHALL be present'",
    $service_descriptions != null,
    "PASS",
    "ServiceDescription found in MPD",
    "ServiceDescription not found in MPD"
);

$periods = $mpd_features['Period'];
foreach ($periods as $period_id => $period) {
    $valid_service_description_present = false;

    ## Check for ServiceDescription element presence
    if ($period['ServiceDescription'] != null) {
        $service_descriptions = $period['ServiceDescription'];
    }

    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.2",
        "'At least one ServiceDescription element SHALL be present'",
        $service_descriptions != null,
        "FAIL",
        "ServiceDescription either found in MPD or Period " . ($period_id + 1),
        "ServiceDescription not found in MPD nor Period " . ($period_id + 1)
    );
    if ($service_descriptions == null) {
        continue;
    }

    foreach ($service_descriptions as $service_description) {
        $valid_scope_present = true;
        $valid_scope_info_present = false;
        $valid_latency_present = false;
        $valid_latency_has_info = false;
        $valid_playback_speed_present = false;
        $valid_playback_speed_has_info = false;
        $other_elements_present_info = '';

        ## Check for Scope element within each ServiceDescription
        $scopes = $service_description['Scope'];
        if ($scopes != null) {
            $valid_scope_info_present = true;
        }

        ## Check for Latency element within each ServiceDescription
        $latencys = $service_description['Latency'];
        foreach ($latencys as $latency) {
            if ($latency['target'] != null) {
                $valid_latency_present = true;
            }
            if ($latency['max'] != null || $latency['min'] != null) {
                $valid_latency_has_info = true;
            }
        }

        ## Check for PlaybackSpeed element within each ServiceDescription
        $playback_speeds = $service_description['PlaybackSpeed'];
        if ($playback_speeds == null) {
            $valid_playback_speed_present = true;
        }
        foreach ($playback_speeds as $playback_speed) {
            $valid_playback_speed_has_info = true;
            if ($playback_speed['max'] == null && $playback_speed['min'] == null) {
                $valid_playback_speed_present = false;
            } else {
                $valid_playback_speed_present = true;
            }
        }

        ## Check for remaining elements if they exist
        foreach ($service_description as $service_description_element_index => $service_description_element) {
            if (is_array($service_description_element)) {
                if (
                    $service_description_element_index != 'Scope' &&
                    $service_description_element_index != 'Latency' &&
                    $service_description_element_index != 'PlaybackSpeed'
                ) {
                    $other_elements_present_info .= $service_description_element_index . ' ';
                }
            }
        }

        if ($valid_scope_present && $valid_latency_present && $valid_playback_speed_present) {
            $valid_service_description_present = true;
            // Add the correct service description
            break;
        }
    }

    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.2",
        "'At least one ServiceDescription element SHALL be present'",
        $valid_service_description_present,
        "FAIL",
        "Valid ServiceDescription found for Period " . ($period_id + 1),
        "No valid ServiceDescription  found for Period " . ($period_id + 1) . ", not running further checks"
    );

    if ($valid_service_description_present) {
        $service_description_info[] = $service_description;
        $logger->test(
            "DASH-IF IOP CR Low Latency Live",
            "Section 9.X.4.2",
            "ServiceDescription element shall be present with a Latency element " .
            "that MAY contain @max or @min attributes",
            $valid_latency_has_info,
            "PASS",
            "Optional attributes found for Period " . ($period_id + 1),
            "Optional attributes not found for Period " . ($period_id + 1)
        );
        $logger->test(
            "DASH-IF IOP CR Low Latency Live",
            "Section 9.X.4.2",
            "ServiceDescription element shall be present where one or more Scope element MAY be present",
            $valid_scope_info_present,
            "PASS",
            "Optional 'Scope' elements found for Period " . ($period_id + 1),
            "Optional 'Scope' elements not found for Period " . ($period_id + 1)
        );
        $logger->test(
            "DASH-IF IOP CR Low Latency Live",
            "Section 9.X.4.2",
            "ServiceDescription MAY have a PlaybackSpeed element",
            $valid_playback_speed_has_info,
            "PASS",
            "Optional 'PlaybackSpeed' elements found for Period " . ($period_id + 1),
            "Optional 'PlaybackSpeed' elements not found for Period " . ($period_id + 1)
        );
        $logger->test(
            "DASH-IF IOP CR Low Latency Live",
            "Section 9.X.4.2",
            "Other service description parameters MAY be present",
            $other_elements_present_info != '',
            "PASS",
            "{" . $other_elements_present_info . "} additional elements found for Period " . ($period_id + 1),
            "No additional elements not found for Period " . ($period_id + 1)
        );
    }
}
