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

function derive_segment_URLs($urls, $period_info){
    global $mpd_features, $current_period, $segment_accesses;
    
    $period = $mpd_features['Period'][$current_period];
    $adaptation_sets = $period['AdaptationSet'];
    $adapt_segment_urls = array();
    foreach($adaptation_sets as $i => $adaptation_set){
        $segment_template_high = get_segment_access($period['SegmentTemplate'], $adaptation_set['SegmentTemplate']);
        $segment_base_high = get_segment_access($period['SegmentBase'], $adaptation_set['SegmentBase']);
        
        $representations = $adaptation_set['Representation'];
        $segment_access = array();
        $segment_urls = array();
        foreach($representations as $j => $representation){
            $segment_template_low = get_segment_access($segment_template_high, $representation['SegmentTemplate']);
            $segment_base_low = get_segment_access($segment_base_high, $representation['SegmentBase']);
            
            if($segment_template_low){
                $segment_access[] = $segment_template_low;
                $segment_info = compute_timing($period_info[1], $segment_template_low[0], 'SegmentTemplate');
                $segment_urls[] = compute_URLs($representation, $segment_template_low[0], $segment_info, $urls[$i][$j]);
            }
            elseif($segment_base_low){
                $segment_access[] = $segment_base_low;
                $segment_urls[] = array($urls[$i][$j]);
            }
            else{
                $segment_access[] = '';
                $segment_urls[] = array($urls[$i][$j]);
            }
        }
        $adapt_segment_urls[] = $segment_urls;
        $segment_accesses[] = $segment_access;
        $segment_info = array();
    }
    
    return $adapt_segment_urls;
}

function compute_URLs($representation, $segment_access, $segment_info, $rep_base_url){
    global $mpd_features;
    $startNumber = ($segment_access['startNumber'] != NULL) ? $segment_access['startNumber'] : 1;
    $initialization = $segment_access['initialization'];
    $media = $segment_access['media'];
    $bandwidth = $representation['bandwidth'];
    $id = $representation['id'];
    $segment_urls = array();
    
    if($initialization != NULL){
        $init = str_replace(array('$Bandwidth$', '$RepresentationID$'), array($bandwidth, $id), $initialization);
        if (substr($rep_base_url, -1) == '/')
            $init_url = $rep_base_url . $init;
        else
            $init_url = $rep_base_url . "/" . $init;
        $segment_urls[] = $init_url;
    }
    
    $index = 0;
    $until = $segment_info[1];
    if($mpd_features['type'] == 'dynamic'){
        list($index, $until, $time1) = dynamic_number($segment_access, $segment_info[0], $segment_info[1]);
    }
    
    $error_info = '';
    while($index < $until){
        $segmenturl = str_replace(array('$Bandwidth$', '$Number$', '$RepresentationID$', '$Time$'), array($bandwidth, $index + $startNumber, $id, $segment_info[0][$time1]), $media);
        $pos = strpos($segmenturl, '$Number');
        if ($pos !== false){
            if (substr($segmenturl, $pos + strlen('$Number'), 1) === '%'){
                $segmenturl = sprintf($segmenturl, $startNumber + $index);
                $segmenturl = str_replace('$Number', '', $segmenturl);
                $segmenturl = str_replace('$', '', $segmenturl);
            }
            else
                $error_info = "It cannot happen! the format should be either \$Number$ or \$Number%xd$!";
        }
        $pos = strpos($segmenturl, '$Time');
        if ($pos !== false){
            if (substr($segmenturl, $pos + strlen('$Time'), 1) === '%'){
                $segmenturl = sprintf($segmenturl, $segment_info[0][$index]);
                $segmenturl = str_replace('$Time', '', $segmenturl);
                $segmenturl = str_replace('$', '', $segmenturl);
            }
            else
                $error_info = "It cannot happen! the format should be either \$Time$ or \$Time%xd$!";
        }
        
        if (substr($rep_base_url, -1) == '/')
            $segmenturl = $rep_base_url . $segmenturl;
        else
            $segmenturl = $rep_base_url . "/" . $segmenturl;
        $segment_urls[] = $segmenturl;
        $index++;
        $time1++;
    }
    
    if($error_info != '')
        error_log($error_info);
    
    return $segment_urls;
}

function compute_timing($presentationduration, $segment_access, $segment_access_type){
    $segment_timings = array();
    $segmentno = 0;
    $start = 0;
    
    switch ($segment_access_type) {
        case 'SegmentTemplate':
            $duration = ($segment_access['duration'] != NULL) ? $segment_access['duration'] : 0;
            $timescale = ($segment_access['timescale'] != NULL) ? $segment_access['timescale'] : 1;
            $availabilityTimeOffset = ($segment_access['availabilityTimeOffset']) ? $segment_access['availabilityTimeOffset'] : 0;
            
            if($duration != 0){
                $duration /= $timescale;
                $segmentno = ceil(($presentationduration - $start) / $duration); 
            }

            $segmentbase = $segment_access['SegmentBase'];
            $segment_timeline = $segment_access['SegmentTimeline'];
            if($segmentbase != NULL){
                $presentationduration = ($segmentbase['presentationDuration'] != NULL) ? $segmentbase['presentationDuration'] : $presentationduration;
                $timescale = ($segmentbase['timescale'] != NULL) ? $segmentbase['timescale'] : $timescale;
                
                $segment_timing = $duration;
                while($segment_timing < $presentationduration * $timescale){
                    $segment_timings[] = $segment_timing;
                    $segment_timing += $duration;
                }
            }
            elseif($segment_timeline != NULL){
                $S_array = $segment_timeline[0]['S'];

                if($S_array != NULL){
                    $segment_time = ($S_array[0]['t']) ? $S_array[0]['t'] : 0;
                    
                    foreach($S_array as $index => $S){
                        $d = $S['d'];
                        $r = ($S['r']) ? $S['r'] : 0;
                        $t = ($S['t']) ? $S['t'] : 0;

                        if($r == 0){
                            $segment_timings[] = (float) $segment_time;
                            $segment_time += $d; 
                        }
                        elseif($r < 0){
                            if(!isset($S_array[$index+1]))
                                $end_time = $presentationduration * $timescale;
                            else
                                $end_time = ($S_array[$index+1]['t']);

                            while ($segment_time < $end_time){
                                $segment_timings[] = (float) $segment_time;
                                $segment_time += $d;
                            }
                        }
                        else{
                            for ($st = 0; $st <= $r; $st++){
                                $segment_timings[] = (float) $segment_time;
                                $segment_time += $d;
                            }
                        }
                    }
                }
                
                $startnumber = 1;
                $segmentno = sizeof($segment_timings);
            }
            else{
                $index = 0;
                $segment_time = $start;
                while($index < $segmentno){
                    $segment_timings[] = $segment_time;
                    $segment_time += $duration;
                    $index++;
                }
            }
            break;
        default:
            break;
    }
    
    return [$segment_timings, $segmentno];
}

function form_segment_access($high, $low){
    foreach($high as $index => $high_i){
        $low_i = $low[$index];
        foreach($high_i as $high_key => $high_value){
            if(!$low_i[$high_key])
                $low_i[$high_key] = $high_value;
            else{
                if(gettype($low_i[$high_key]) == 'array')
                    $low_i[$high_key] = form_segment_access($high_i[$high_key], $low_i[$high_key]);
            }
        }
        $low[$index] = $low_i;
    }
    
    return $low;
}

function get_segment_access($high_level, $low_level){
    $high_level_exists = !empty($high_level);
    $low_level_exists = !empty($low_level);
    
    if(!$high_level_exists && !$low_level_exists)
        return NULL;
    elseif($high_level_exists && !$low_level_exists)
        return $high_level;
    elseif(!$high_level_exists && $low_level_exists)
        return $low_level;
    else
        return form_segment_access($high_level, $low_level);
}

function isAbsoluteURL($URL){
    $parsedURL = parse_url($URL);
    return $parsedURL['scheme'] && $parsedURL['host'];
}

function process_base_url(){
    global $mpd_url, $mpd_features, $current_period;
    
    $base_url_used = false;
    $mpd_base = $mpd_features['BaseURL'];
    
    $period = $mpd_features['Period'][$current_period];
    $period_base = $period['BaseURL'];
    
    $adapts = $period['AdaptationSet'];
    foreach($adapts as $adapt){
        $adapt_base = $adapt['BaseURL'];
        
        $reps = $adapt['Representation'];
        foreach($reps as $rep){
            $rep_base = $rep['BaseURL'];
            
            if($mpd_base || $period_base || $adapt_base || $rep_base){
                $base_url_used = true;
                
                $dir = '';
                $array = array($mpd_base, $period_base, $adapt_base, $rep_base);
                foreach($array as $item){
                    if($item){
                        $base = $item[0]['anyURI'];
                        if(isAbsoluteURL($base))
                            $dir = $base;
                        else
                            $dir = $dir . $base;
                    }
                    
                    $rep_url = $dir;
                }
                if(!isset($rep_url))
                    $rep_url = dirname($mpd_url) . '/';
            }
            else
                $rep_url = dirname($mpd_url) . '/';
            
            if(!isAbsoluteURL($rep_url))
                $rep_url = dirname($mpd_url) . '/' . $rep_url;
            
            $rep_urls[] = $rep_url;
        }
        $adapt_urls[] = $rep_urls;
        $rep_urls = array();
    }
    return $adapt_urls;
}