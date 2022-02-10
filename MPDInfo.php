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

###############################################################################
/*
 * This PHP script is responsible for common MPD information extracting/computing.
 * @name: MPDInfo.php
 * @entities: 
 *      @functions{
 *          current_period(),
 *          period_duration_info(),
 *          derive_profiles(),
 *          time_parsing($var),
 *          dynamic_number($segmentbase, $segmentduration, $start)
 *      }
 */
###############################################################################

/*
 * Compute the current period, since only one period is processed in this program.
 * If static MPD, first period will be the one to be processed.
 * If dynamic MPD, the current period will be determined based on time now
 * @name: current_period
 * @input: NA
 * @output: array of start and duration of the current period
 */
function current_period(){
    global $mpd_features, $current_period, $period_timing_info;
    $period_info = period_duration_info();
    $AST = $mpd_features['availabilityStartTime'];
    
    if($mpd_features['type'] === 'static'){
        $start = $period_info[0][$current_period];
        $duration = $period_info[1][$current_period];
    }
    elseif($mpd_features['type'] === 'dynamic'){
        if(sizeof($mpd_features['Period']) == 1){
            $current_period = 0;
            $start = $period_info[0][0];
            $duration = $period_info[1][0];
        }
        else{
            $now = time();
            for($p=0; $p< sizeof($mpd_features['Period']); $p++){
                $whereami = $now - (strtotime($AST) + $period_info[0][$p]);

                if($whereami <= $period_info[1][$p]){
                    $current_period = $p;
                    $start = $period_info[0][$p];
                    $duration = $period_info[1][$p];
                    break;
                }
            }
        }
    }
    
    $period_timing_info = [$start, $duration];
    return $period_timing_info;
}

/*
 * Compute the start time and the duration of each period in the MPD.
 * @name: period_duration_info
 * @input: NA
 * @output: array of start times and durations of each period
 */
function period_duration_info(){
    global $mpd_features;
    
    $periods = $mpd_features['Period'];
    $mediapresentationduration = time_parsing($mpd_features['mediaPresentationDuration']);
    
    $starts = array();
    $durations = array();
    for($i=0; $i<sizeof($periods); $i++){
        $period = $periods[$i];
        
        $start = $period['start'];
        $duration = $period['duration'];
        if($start == ''){
            if($i > 0){
                if($durations[$i-1] != '')
                    $start = (float)($starts[$i-1] + $durations[$i-1]);
                else{
                    if($mpd_features['type'] == 'dynamic'){
                        //early available period
                    }
                }
            }
            else{
                if($mpd_features['type'] == 'static')
                    $start = 0;
                elseif($mpd_features['type'] == 'dynamic'){
                    //early available period
                }
            }
        }
        else
            $start = time_parsing($start);
        
        if($duration == ''){
            if($i != sizeof($periods)-1){
                $duration = time_parsing($periods[$i+1]['start']) - $start;
            }
            else{
                $duration = $mediapresentationduration - $start;
            }
        }
        else
            $duration = time_parsing($duration);
        
        $starts[] = $start;
        $durations[] = min([$duration, 1800]);
    }
    
    return [$starts, $durations];
}

function mdp_timing_info(){
    global $current_adaptation_set, $current_representation, $segment_accesses, $period_timing_info;
    
    $mpd_timing = array();
    
    // Calculate segment timing information
    $segment_access = $segment_accesses[$current_adaptation_set][$current_representation];
    foreach($segment_access as $seg_acc){
        $pto = ($seg_acc['presentationTimeOffset'] != '') ? (int)($seg_acc['presentationTimeOffset']) : 0;
        $duration = ($seg_acc['duration'] != '') ? (int)($seg_acc['duration']) : 0;
        $timescale = ($seg_acc['timescale'] != '') ? (int)($seg_acc['timescale']) : 1;
        
        $pres_start = $period_timing_info[0] - $pto/$timescale;
        
        $segtimeline = $seg_acc['SegmentTimeline'];
        if(sizeof($segtimeline) != 0){
            $stags = $segtimeline[sizeof($segtimeline)-1]['S'];
            for($s=0; $s<sizeof($stags); $s++){
                $duration = (int)($stags[$s]['d']);
                $repeat = ($stags[$s]['r'] != '') ? (int)($stags[$s]['r']) : 0;
                $time = $stags[$s]['t'];
                $time_next = ($stags[$s+1]['t'] != NULL) ? ($stags[$s+1]['t']) : '';
                
                $segmentDuration = $duration/$timescale;
                
                if($repeat == -1){
                    if($time_next != ''){
                        $time = (int)$time;
                        $time_next = (int)$time_next;
                        
                        $index = 0;
                        while($time_next-$time != 0){
                            $mpd_timing[] = $pres_start + $index*$segmentDuration;
                            
                            $time += $duration;
                            $index++;
                        }
                    }
                    else{
                        $segment_cnt = ceil($period_timing_info[1]/$segmentDuration);
                        
                        for($i=0; $i<$segment_cnt; $i++){
                            $mpd_timing[] = $pres_start + $i*$segmentDuration;
                        }
                    }
                }
                else{
                    for($r=0; $r<$repeat+1; $r++){
                        $mpd_timing[] = $pres_start + $r*$segmentDuration;
                    }
                }
            }
        }
        else{
            if($duration == 0){
                $mpd_timing[] = $pres_start;
            }
            else{
                $segmentDuration = $duration/$timescale;
                $segment_cnt = $period_timing_info[1]/$segmentDuration;
                
                for($i=0; $i<$segment_cnt; $i++){
                    $mpd_timing[] = $pres_start + $i*$segmentDuration;
                }
            }
        }
    }
    
    return $mpd_timing;
}

/*
 * Derive the profiles in the current period.
 * @name: derive_profiles
 * @input: NA
 * @output: profiles array for each representation in each 
 *          adaptation set in the current period
 */
function derive_profiles(){
    global $mpd_features;
    
    $profiles_array = array();
    $periods = $mpd_features['Period'];
    
    foreach($periods as $period) {
        $adapts = $period['AdaptationSet'];
        $adapt_profiles = array();
        foreach($adapts as $adapt){
            $reps = $adapt['Representation'];
            $rep_profiles = array();
            foreach($reps as $rep){
                $profiles = $mpd_features['profiles'];

                if(array_key_exists('profiles', $period) && $period['profiles'])
                    $profiles = $period['profiles'];

                if(array_key_exists('profile', $adapt) && $adapt['profile'])
                    $profiles = $adapt['profiles'];

                if(array_key_exists('profile', $rep) && $rep['profile'])
                    $profiles = $rep['profiles'];

                $rep_profiles[] = $profiles;
            }
            $adapt_profiles[] = $rep_profiles;
        }
        $profiles_array[] = $adapt_profiles;
    }
    
    return $profiles_array;
}

/*
 * Compute the time in seconds.
 * The format is based on ISO 8601: PxYxMxWxDTxHxMxS.
 * @name: time_parsing
 * @input: $var - the time attribute to be calculated in seconds
 * @output: time in seconds
 */
function time_parsing($var){
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
    else
        $Mo[0] = 0;
    
    if(strpos($y, 'W') !== false){ // Week
        $W = explode("W", $y);
        $y = substr($y, strpos($y, 'W') + 1);
    }
    else
        $W[0] = 0;
    
    if(strpos($y, 'D') !== false){ // Day
        $D = explode("D", $y);
        $y = substr($y, strpos($y, 'D') + 1);
    }
    else
        $D[0] = 0;
    
    $y = str_replace("T", "", $y);
    if (strpos($y, 'H') !== false){ // Hour
        $H = explode("H", $y);
        $y = substr($y, strpos($y, 'H') + 1);
    }
    else
        $H[0] = 0;

    if (strpos($y, 'M') !== false){ // Minute
        $M = explode("M", $y);
        $y = substr($y, strpos($y, 'M') + 1);
    }
    else
        $M[0] = 0;

    $S = explode("S", $y); // Second
    
    $duration = ($Y[0] * 365 * 24 * 60 * 60) + 
                ($Mo[0] * 30 * 24 * 60 * 60) + 
                ($W[0] * 7 * 24 * 60 * 60) +
                ($D[0] * 24 * 60 * 60) + 
                ($H[0] * 60 * 60) + 
                ($M[0] * 60) + 
                $S[0]; // calculate durations in seconds

    return $duration;
}

/*
 * Compute the segment number interval available in case of dynamic MPD.
 * @name: dynamic_number
 * @input: $segmentbase - SegmentBase element for timeShiftBufferDepth information
 *         $segmentduration - Media segment duration
 *         $start - Period start time
 * @output: array of start segment and end segment number 
 */
function dynamic_number($adaptation_set_id, $representation_id, $segment_access, $segment_timings, $segmentno){
    global $mpd_features, $current_period, $period_timing_info, $low_latency_dashif_conformance, $availability_times;
    
    $bufferduration = ($mpd_features['timeShiftBufferDepth'] != NULL) ? time_parsing($mpd_features['timeShiftBufferDepth']) : INF;
    $AST = $mpd_features['availabilityStartTime'];
    if($segment_access['SegmentTimeline'] != NULL)
        $segmentduration = ($segment_timings[$segmentno-1]-$segment_timings[0])/((float)($segmentno-1));
    else
        $segmentduration = ($segment_access['duration'] != NULL) ? $segment_access['duration'] : 0;
    $timescale = ($segment_access['timescale'] != NULL) ? $segment_access['timescale'] : 1;
    $availabilityTimeOffset = ($segment_access['availabilityTimeOffset'] != NULL && $segment_access['availabilityTimeOffset'] != 'INF') ? $segment_access['availabilityTimeOffset'] : 0;
    $pto = ($segment_access['presentationTimeOffset'] != '') ? (int)($segment_access['presentationTimeOffset'])/$timescale : 0;
    
    if($segmentduration != 0)
        $segmentduration /= $timescale;
    
    $avgsum = array();
    $sumbandwidth = array();
    $adaptation_sets = $mpd_features['Period'][$current_period]['AdaptationSet'];
    for($k=0; $k<sizeof($adaptation_sets); $k++){
        $representations = $adaptation_sets[$k]['Representation'];
        $sum = 0;
        for($l=0; $l<sizeof($representations); $l++)
            $sum += $representations[$l]['bandwidth'];
        
        $sumbandwidth[] = $sum;
        $avgsum[] = $sum/sizeof($representations);
    }
    $sumbandwidth = array_sum($sumbandwidth);
    $avgsum = array_sum($avgsum) / sizeof($avgsum);
    $percent = $avgsum / $sumbandwidth;

    $buffercapacity = $bufferduration / $segmentduration; //actual buffer capacity

    date_default_timezone_set("UTC"); //Set default timezone to UTC
    $now = time(); // Get actual time
    $AST = strtotime($AST);
    $LST = $now - ($AST + $period_timing_info[0] - $pto - $availabilityTimeOffset - $segmentduration);
    $LSN = intval($LST / $segmentduration);
    $earliestsegment = $LSN - $buffercapacity * $percent;
    
    $new_array = $segment_timings;
    $new_array[] = $LST*$timescale;
    sort($new_array);
    $ind = array_search($LST*$timescale, $new_array);
    
    $SST = ($ind-1-$buffercapacity*$percent < 0) ? 0 : $ind-1-$buffercapacity*$percent;
    
    if($low_latency_dashif_conformance) {
        $ASAST = array();
        $NSAST = array();
        $count = $LSN - intval($earliestsegment);
        for($i=$count; $i>0; $i--) {
            $ASAST[] = $now - $LST - $bufferduration*$i;
            $NSAST[] = $now - ($LST - $bufferduration*$i + $availabilityTimeOffset);
        }
        $availability_times[$adaptation_set_id][$representation_id]['ASAST'] = $ASAST;
        $availability_times[$adaptation_set_id][$representation_id]['NSAST'] = $NSAST;
    }
    
    return [intval($earliestsegment), $LSN, $SST];
}
