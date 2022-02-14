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

function IOP_ValidateMPD() {
    global $session_dir, $mpd_features, $mpd_log, $mpd_xml_report;
    
    $mpdreport = open_file($session_dir . '/' . $mpd_log . '.txt', 'a+b');
    if(!$mpdreport)
        return;
    
    $messages = '';
    
    $mpd_profiles = $mpd_features['profiles'];
    if(strpos($mpd_profiles, 'http://dashif.org/guidelines/dash') !== FALSE) {
        $messages .= IOP_ValidateMPD_Common();
    }
    if(strpos($mpd_profiles, 'http://dashif.org/guidelines/dash') !== FALSE &&
       strpos($mpd_profiles, 'urn:mpeg:dash:profile:isoff-live:2011') !== FALSE) {
        $messages .= IOP_ValidateMPD_Live_OnDemand();
    }
    if(strpos($mpd_profiles, 'http://dashif.org/guidelines/dash-if-ondemand') !== FALSE) {
        $messages .= IOP_ValidateMPD_OnDemand();
    }
    if(strpos($mpd_profiles, 'http://dashif.org/guidelines/dash-if-mixed') !== FALSE) {
        $messages .= IOP_ValidateMPD_Mixed_OnDemand();
    }
    
    fwrite($mpdreport, $messages);
    fclose($mpdreport);
    
    ## For reporting
    $mpdreportText = file_get_contents($session_dir . '/' . $mpd_log . '.txt');
    $returnValue = "true";
    if(strpos($mpdreportText, 'DASH-IF IOP 4.3 check violated') !== FALSE) {
        $returnValue = "error";
    }
    
    $mpd_xml = simplexml_load_file($session_dir . '/' . $mpd_xml_report);
    $mpd_xml->dashif = $returnValue;
    $mpd_xml->asXml($session_dir . '/' . $mpd_xml_report);
    
    return $returnValue;
}

function IOP_ValidateMPD_Common() {
    global $mpd_dom;
    
    $messages = '';
    
    if(checkYearMonth($mpd_dom->getAttribute('mediaPresentationDuration'))) {
        $messages .= "DASH-IF IOP Section check violated 3.2.7.4: 'MPD fields having datatype xs:duration shall not use year or month units', year and/or month unit is found in @mediaPresentationDuration in MPD.\n";
    }
    if(checkYearMonth($mpd_dom->getAttribute('minimumUpdatePeriod'))) {
        $messages .= "DASH-IF IOP Section check violated 3.2.7.4: 'MPD fields having datatype xs:duration shall not use year or month units', year and/or month unit is found in @minimumUpdatePeriod in MPD.\n";
    }
    if(checkYearMonth($mpd_dom->getAttribute('minBufferTime'))) {
        $messages .= "DASH-IF IOP Section check violated 3.2.7.4: 'MPD fields having datatype xs:duration shall not use year or month units', year and/or month unit is found in @minBufferTime in MPD.\n";
    }
    if(checkYearMonth($mpd_dom->getAttribute('timeShiftBufferDepth'))) {
        $messages .= "DASH-IF IOP Section check violated 3.2.7.4: 'MPD fields having datatype xs:duration shall not use year or month units', year and/or month unit is found in @timeShiftBufferDepth in MPD.\n";
    }
    if(checkYearMonth($mpd_dom->getAttribute('suggestedPresentationDelay'))) {
        $messages .= "DASH-IF IOP Section check violated 3.2.7.4: 'MPD fields having datatype xs:duration shall not use year or month units', year and/or month unit is found in @suggestedPresentationDelay in MPD.\n";
    }
    if(checkYearMonth($mpd_dom->getAttribute('maxSegmentDuration'))) {
        $messages .= "DASH-IF IOP Section check violated 3.2.7.4: 'MPD fields having datatype xs:duration shall not use year or month units', year and/or month unit is found in @maxSegmentDuration in MPD.\n";
    }
    if(checkYearMonth($mpd_dom->getAttribute('maxSubsegmentDuration'))) {
        $messages .= "DASH-IF IOP Section check violated 3.2.7.4: 'MPD fields having datatype xs:duration shall not use year or month units', year and/or month unit is found in @maxSubsegmentDuration in MPD.\n";
    }
    
    $periods = $mpd_dom->getElementsByTagName('Period');
    for($i=0; $i<$periods->length; $i++) {
        $period = $periods->item($i);
        if(checkYearMonth($period->getAttribute('start'))) {
            $messages .= "DASH-IF IOP Section check violated 3.2.7.4: 'MPD fields having datatype xs:duration shall not use year or month units', year and/or month unit is found in @start for " . $period->getNodePath() . ".\n";
        }
        if(checkYearMonth($period->getAttribute('duration'))) {
            $messages .= "DASH-IF IOP Section check violated 3.2.7.4: 'MPD fields having datatype xs:duration shall not use year or month units', year and/or month unit is found in @duration for " . $period->getNodePath() . ".\n";
        }
    }
    
    $random_accesses = $mpd_dom->getElementsByTagName('RandomAccess');
    for($i=0; $i<$random_accesses->length; $i++) {
        $random_access = $random_accesses->item($i);
        if(checkYearMonth($random_access->getAttribute('minBufferTime'))) {
            $messages .= "DASH-IF IOP Section check violated 3.2.7.4: 'MPD fields having datatype xs:duration shall not use year or month units', year and/or month unit is found in @minBufferTime for " . $random_access->getNodePath() . ".\n";
        }
    }
    
    $segment_templates = $mpd_dom->getElementsByTagName('SegmentTemplate');
    for($i=0; $i<$segment_templates->length; $i++) {
        $segment_template = $segment_templates->item($i);
        if(checkYearMonth($segment_template->getAttribute('timeShiftBufferDepth'))) {
            $messages .= "DASH-IF IOP Section check violated 3.2.7.4: 'MPD fields having datatype xs:duration shall not use year or month units', year and/or month unit is found in @timeShiftBufferDepth for " . $segment_template->getNodePath() . ".\n";
        }
    }
    
    $segment_bases = $mpd_dom->getElementsByTagName('SegmentBase');
    for($i=0; $i<$segment_bases->length; $i++) {
        $segment_base = $segment_bases->item($i);
        if(checkYearMonth($segment_base->getAttribute('timeShiftBufferDepth'))) {
            $messages .= "DASH-IF IOP Section check violated 3.2.7.4: 'MPD fields having datatype xs:duration shall not use year or month units', year and/or month unit is found in @timeShiftBufferDepth for " . $segment_base->getNodePath() . ".\n";
        }
    }
    
    $segment_lists = $mpd_dom->getElementsByTagName('SegmentList');
    for($i=0; $i<$segment_lists->length; $i++) {
        $segment_list = $segment_lists->item($i);
        if(checkYearMonth($segment_list->getAttribute('timeShiftBufferDepth'))) {
            $messages .= "DASH-IF IOP Section check violated 3.2.7.4: 'MPD fields having datatype xs:duration shall not use year or month units', year and/or month unit is found in @timeShiftBufferDepth for " . $segment_list->getNodePath() . ".\n";
        }
    }
    
    $ranges = $mpd_dom->getElementsByTagName('Range');
    for($i=0; $i<$ranges->length; $i++) {
        $range = $ranges->item($i);
        if(checkYearMonth($range->getAttribute('time'))) {
            $messages .= "DASH-IF IOP Section check violated 3.2.7.4: 'MPD fields having datatype xs:duration shall not use year or month units', year and/or month unit is found in @time for " . $range->getNodePath() . ".\n";
        }
        if(checkYearMonth($range->getAttribute('duration'))) {
            $messages .= "DASH-IF IOP Section check violated 3.2.7.4: 'MPD fields having datatype xs:duration shall not use year or month units', year and/or month unit is found in @duration for " . $range->getNodePath() . ".\n";
        }
    }
    
    return $messages;
}

function checkYearMonth($var) {
    $y = str_replace("P", "", $var);
    if(strpos($y, 'Y') !== false){ // Year
        $Y = explode("Y", $y);

        $y = substr($y, strpos($y, 'Y') + 1);
    }
    else
        $Y[0] = 0;
    
    if(strpos($y, 'M') !== false && strpos($y, 'M') < strpos($y, 'T')){ // Month
        $Mo = explode("M", $y);
        $y = substr($y, strpos($y, 'M') + 1);
    }
    
    $duration = ($Y[0] * 365 * 24 * 60 * 60) + 
                ($Mo[0] * 30 * 24 * 60 * 60);
    
    return ($duration>0);
}

function IOP_ValidateMPD_Live_OnDemand() {
    global $mpd_features, $profiles;
    
    $messages = '';
    
    $periods = $mpd_features['Period'];
    foreach($periods as $period_i => $period) {
        $adaptation_sets = $period['AdaptationSet'];
        foreach($adaptation_sets as $adaptation_set_i => $adaptation_set)  {
            $representations = $adaptation_set['Representation'];
            foreach($representations as $representation_i => $representation) {
                $rep_profiles = $profiles[$period_i][$adaptation_set_i][$representation_i];
                if(strpos($rep_profiles, 'http://dashif.org/guidelines/dash-if-ondemand') !== FALSE) {
                    $segment_template = get_segment_access($period['SegmentTemplate'], $adaptation_set['SegmentTemplate']);
                    $segment_template = get_segment_access($segment_template, $representation['SegmentTemplate']);
                    if($segment_template == NULL || ($segment_template != NULL && $segment_template['media'] == NULL)) {
                        $messages .= "DASH-IF IOP 4.3 check violated Section 3.10.2: 'SegmentTemplate@media attribute SHALL be present', @media not found for Period $period_i Adaptation Set $adaptation_set_i Representation $representation_i.\n";
                    }
                }
            }
        }
    }
}

function IOP_ValidateMPD_OnDemand() {
    global $mpd_features, $profiles;
    
    $messages = '';
    
    $periods = $mpd_features['Period'];
    foreach($periods as $period_i => $period) {
        $adaptation_sets = $period['AdaptationSet'];
        foreach($adaptation_sets as $adaptation_set_i => $adaptation_set)  {
            $representations = $adaptation_set['Representation'];
            foreach($representations as $representation_i => $representation) {
                $rep_profiles = $profiles[$period_i][$adaptation_set_i][$representation_i];
                if(strpos($rep_profiles, 'http://dashif.org/guidelines/dash-if-ondemand') !== FALSE) {
                    $segment_template = get_segment_access($period['SegmentTemplate'], $adaptation_set['SegmentTemplate']);
                    $segment_template = get_segment_access($segment_template, $representation['SegmentTemplate']);
                    if($segment_template == NULL || ($segment_template != NULL && $segment_template['indexRange'] == NULL)) {
                        $messages .= "DASH-IF IOP 4.3 check violated Section 3.10.3: 'SegmentTemplate@indexRange attribute SHALL be present', SegmentTemplate@indexRange not found for Period $period_i Adaptation Set $adaptation_set_i Representation $representation_i.\n";
                    }
                }
            }
        }
    }
    
    return $messages;
}

function IOP_ValidateMPD_Mixed_OnDemand() {
    global $mpd_features;
    
    $messages = '';
    
    $periods = $mpd_features['Period'];
    foreach($periods as $period_i => $period) {
        if($period['profiles'] == NULL) {
            $messages .= "DASH-IF IOP 4.3 check violated Section 3.10.4: \"For on-demand content that offers a mixture of periods, the @profiles signaling shall be presentin each Period\", @profiles signaling not found for Period $period_i.\n";
        }
    }
    
    return $messages;
}
