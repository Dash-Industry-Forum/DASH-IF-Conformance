<?php
/* This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

function low_latency_validate_mpd() {
    global $session_dir, $mpd_log, $mpd_xml_report;
    
    $messages = '';
    
    $mpdreport = fopen($session_dir . '/' . $mpd_log . '.txt', 'a+b');
    if(!$mpdreport)
        return;
    
    $messages .= validateProfiles();
    $messages .= validateServiceDescription();
    $messages .= validateUTCTiming();
    $messages .= validateLeapSecondInformation();
    
    fwrite($mpdreport, $messages);
    fclose($mpdreport);
    
    $returnValue = (strpos($messages, 'violated') != '') ? 'error' : ((strpos($messages, 'warning') != '') ? 'warning' : 'true');
    $mpd_xml = simplexml_load_file($session_dir . '/' . $mpd_xml_report);
    $mpd_xml->dashif_ll = $returnValue;
    $mpd_xml->asXml($session_dir . '/' . $mpd_xml_report);
    
    return $returnValue;
}

function validateProfiles() {
    global $mpd_features;
    
    $messages = '';
    if(strpos($mpd_features['profiles'], 'http://www.dashif.org/guidelines/low-latency-live-v5') === FALSE) {
        $messages .= "DASH-IF IOP CR Low Latency Live warning section 9.X.4.1: \"A Media Presentation that follows a DASH-IF Low-Latency Service Offering according to this specification SHOULD be signalled with the @profiles identifier 'http://www.dashif.org/guidelines/low-latency-live-v5'\", specified identifier is not found in the MPD.\n";
    }
    
    return $messages;
}

function validateServiceDescription() {
    global $mpd_features, $service_description_info;
    
    $messages = '';
    
    $service_descriptions = $mpd_features['ServiceDescription'];
    $periods = $mpd_features['Period'];
    foreach ($periods as $period_id => $period) {
        $valid_service_description_present = FALSE;
        
        ## Check for ServiceDescription element presence
        if($period['ServiceDescription'] != NULL){
            $service_descriptions = $period['ServiceDescription'];
        }
        
        if($service_descriptions == NULL) {
            $messages .= 'DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.2: "At least one ServiceDescription element SHALL be present", ServiceDescription element not found in neither MPD nor Period ' . ($period_id+1) . ".\n";
            continue;
        }
        
        foreach ($service_descriptions as $service_description) {
            $valid_scope_present = TRUE;
            $valid_scope_info_present = FALSE;
            $valid_latency_present = FALSE; 
            $valid_latency_has_info = FALSE;
            $valid_playback_speed_present = FALSE;
            $valid_playback_speed_has_info = FALSE;
            $other_elements_present_info = '';
            
            ## Check for Scope element within each ServiceDescription
            $scopes = $service_description['Scope'];
            if($scopes != NULL) {
                $valid_scope_info_present = TRUE;
            }
            
            ## Check for Latency element within each ServiceDescription
            $latencys = $service_description['Latency'];
            foreach ($latencys as $latency) {
                if($latency['target'] != NULL) {
                    $valid_latency_present = TRUE;
                }
                if($latency['max'] != NULL || $latency['min'] != NULL) {
                    $valid_latency_has_info = TRUE;
                }
            }
            
            ## Check for PlaybackSpeed element within each ServiceDescription
            $playback_speeds = $service_description['PlaybackSpeed'];
            if($playback_speeds == NULL) {
                $valid_playback_speed_present = TRUE;
            }
            foreach ($playback_speeds as $playback_speed) {
                $valid_playback_speed_has_info = TRUE;
                if($playback_speed['max'] == NULL && $playback_speed['min'] == NULL) {
                    $valid_playback_speed_present = FALSE;
                }
                else {
                    $valid_playback_speed_present = TRUE;
                }
            }
            
            ## Check for remaining elements if they exist
            foreach ($service_description as $service_description_element_index => $service_description_element) {
                if(is_array($service_description_element)) {
                    if($service_description_element_index != 'Scope' && $service_description_element_index != 'Latency' && $service_description_element_index != 'PlaybackSpeed') {
                        $other_elements_present_info .= $service_description_element_index . ' ';
                    }
                }
            }
            
            if($valid_scope_present && $valid_latency_present && $valid_playback_speed_present) {
                $valid_service_description_present = TRUE;
                // Add the correct service description
                break;
            }
        }
        
        if(!$valid_service_description_present) {
            $messages .= 'DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.2: "At least one ServiceDescription element SHALL be present as described in Bullet 1 in this clause", no valid ServiceDescription is found in Period ' . ($period_id+1) . ".\n";
        }
        else {
            $service_description_info[] = $service_description;
            if($valid_latency_has_info) {
                $messages .= 'Information on DASH-IF IOP CR Low Latency Live check Section 9.X.4.2: "ServiceDescription element shall be present with a Latency element that MAY contain @max or @min attributes", optional attributes are found in the SeriveDescription Latency element in Period ' . ($period_id+1) . ".\n";
            }
            if($valid_scope_info_present) {
                $messages .= 'Information on DASH-IF IOP CR Low Latency Live check Section 9.X.4.2: "ServiceDescription element shall be present where one or more Scope element MAY be present", Scope element(s) found in the SeriveDescription element in Period ' . ($period_id+1) . ".\n";
            }
            if($valid_playback_speed_has_info) {
                $messages .= 'Information on DASH-IF IOP CR Low Latency Live check Section 9.X.4.2: "ServiceDescription MAY have a PlaybackSpeed element", PlaybackSpeed element found in the SeriveDescription Latency element in Period ' . ($period_id+1) . ".\n";
            }
            if($other_elements_present_info != '') {
                $messages .= 'Information on DASH-IF IOP CR Low Latency Live check Section 9.X.4.2: "Other service description parameters MAY be present", {' . $other_elements_present_info . '} additional elements are found in the valid ServiceDescription element in Period ' . ($period_id+1) . ".\n";
            }
        }
    }
    
    return $messages;
}

function validateUTCTiming() {
    global $mpd_features, $utc_timing_info;
    
    $error_messages = '';
    
    $valid_utc_timing_present = FALSE;
    $utc_timings = $mpd_features['UTCTiming'];
    foreach ($utc_timings as $utc_timing) {
        $accepted_uris = array('urn:mpeg:dash:utc:http-xsdate:2014', 'urn:mpeg:dash:utc:http-iso:2014', 'urn:mpeg:dash:utc:http-ntp:2014');
        $schemeIdUri = $utc_timing['schemeIdUri'];
        if(in_array($schemeIdUri, $accepted_uris) === TRUE) {
            $valid_utc_timing_present = TRUE;
            $utc_timing_info[] = $utc_timing;
        }
    }
    if($utc_timings == NULL) {
        $error_messages .= 'DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.2: "At least one UTC timing description SHALL be present and be restricted with @schemeIdUri set to one of {urn:mpeg:dash:utc:http-xsdate:2014, urn:mpeg:dash:utc:http-iso:2014, urn:mpeg:dash:utc:http-ntp:2014}", UTCTiming element not found in the MPD' . ".\n";
    }
    elseif(!$valid_utc_timing_present) {
        $error_messages .= 'DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.2: "At least one UTC timing description SHALL be present and be restricted with @schemeIdUri set to one of {urn:mpeg:dash:utc:http-xsdate:2014, urn:mpeg:dash:utc:http-iso:2014, urn:mpeg:dash:utc:http-ntp:2014}", none of the UTCTiming elements use the mentioned schemeIdUris in @schemeIdUri attribute in the MPD' . ".\n";
    }
    
    return $error_messages;
}

function validateLeapSecondInformation() {
    global $mpd_features;
    
    $messages = '';
    
    $leap_second_information = $mpd_features['LeapSecondInformation'];
    if($leap_second_information == NULL) {
        $messages .= 'DASH-IF IOP Low Latancy Live check warning Section 9.X.4.2: "Low latency content SHOULD provide a LeapSecondInformation element providing correction for leap seconds", LeapSecondInformation element is not found in the MPD.' . "\n";
    }
    
    return $messages;
}