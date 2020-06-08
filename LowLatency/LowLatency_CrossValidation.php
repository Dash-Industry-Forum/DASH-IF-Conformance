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

$maxSegmentDurations = array();
$first_option = array();
$second_option = array();
$presentation_times = array();
$decode_times = array();

function low_latency_validate_cross() {
    global $session_dir, $current_period, $low_latency_cross_validation_file, $progress_xml, $progress_report;
    
    if(!($opfile = open_file($session_dir. '/Period' . $current_period . '/' . $low_latency_cross_validation_file . '.txt', 'w'))){
        echo "Error opening/creating LowLatencyCrossValidation_compInfo conformance check file: "."LowLatencyCrossValidation_compInfo.txt";
        return;
    }
    
    validateAdaptationSets($opfile);
    fclose($opfile);
    
    $searchfiles = file_get_contents($session_dir.'/Period'.$current_period.'/'.$low_latency_cross_validation_file.'.txt');
    if(strpos($searchfiles, "DASH-IF IOP CR Low Latency Live check violated") !== FALSE){
        $progress_xml->Results[0]->Period[$current_period]->addChild('DASHIFLLCrossValidation', 'error');
        $file_error[] = $session_dir.'/Period' .$current_period.'/'.$low_latency_cross_validation_file.'.html';
    }
    elseif(strpos($searchfiles, "warning") !== FALSE){
        $progress_xml->Results[0]->Period[$current_period]->addChild('DASHIFLLCrossValidation', 'warning');
        $file_error[] = $session_dir.'/Period'.$current_period.'/'.$low_latency_cross_validation_file.'.html';
    }
    else{
        $progress_xml->Results[0]->Period[$current_period]->addChild('DASHIFLLCrossValidation', 'noerror');
        $file_error[] = "noerror";
    }
    $progress_xml->asXml(trim($session_dir . '/' . $progress_report));
    print_console($session_dir.'/Period'.$current_period.'/'.$low_latency_cross_validation_file.'.txt', "Period " . ($current_period+1) . " DASH-IF IOP CR Low Latency Cross Validation Results");
    tabulateResults($session_dir. '/Period' . $current_period . '/' . $low_latency_cross_validation_file . '.txt', 'Cross');
}

function validateAdaptationSets($opfile) {
    global $mpd_features, $current_period;
    
    $messages = '';
    
    $period =  $mpd_features['Period'][$current_period];
    $adaptation_sets = $period['AdaptationSet'];
    
    // At least one low latency adaptation set for each media type
    $adapts = array('video' => array(), 'audio' => array(), 'subtitle' => array());
    foreach ($adaptation_sets as $adaptation_set_index => $adaptation_set) {
        $media_type = ($adaptation_set['mimeType'] != NULL) ? $adaptation_set['mimeType'] : $adaptation_set['Representation'][0]['mimeType'];
        
        if(strpos($media_type, 'video') !== FALSE) $adapts['video'][$adaptation_set_index] = $adaptation_set;
        if(strpos($media_type, 'audio') !== FALSE) $adapts['audio'][$adaptation_set_index] = $adaptation_set;
        if(strpos($media_type, 'application') !== FALSE || strpos($media_type, 'text') !== FALSE) $adapts['subtitle'][$adaptation_set_index] = $adaptation_set;
    }
    
    foreach ($adapts as $adapt_group_index => $adapt_group) {
        foreach ($adapt_group as $adapt_index => $adapt) {
            $is_adapt_ll = array();
            $infoFileAdapt = readInfoFile($adapt, $adapt_index);
            
            $audio_present = ($adapts['audio'] != NULL);
            $is_adapt_ll[] = validate9X43($opfile, $period, $adapt, $adapt_index, $infoFileAdapt, $audio_present, $adapt_group_index);
        }
        
        if($adapt_group != NULL) {
            $conforming_adapt_ids = array_keys($is_adapt_ll, TRUE);
            if($conforming_adapt_ids == NULL) {
                $messages .= 'DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.2: "For each media type at least one Low Latency Adaptation Set SHALL be present", no Low Latency Adaptation Set is found for media type ' . $adapt_group_index . ' in Period ' . ($current_period+1) . ".\n";
                fprintf($opfile, $messages);
            }
        }
    }
}

function validate9X43($opfile, $period, $adaptation_set, $adaptation_set_id, $infoFileAdapt, $audio_present, $adapt_group_index) {
    global $mpd_features, $current_period, $utc_timing_info;
    
    $messages = '';
    $is_ll_adapt = FALSE;
    
    $segment_access_info = array();
    $segment_template_combined = get_segment_access($period['SegmentTemplate'], $adaptation_set['SegmentTemplate']);
    $producer_reference_times = $adaptation_set['ProducerReferenceTime'];
    $inband_event_streams = $adaptation_set['InbandEventStream'];
    
    $representations = $adaptation_set['Representation'];
    foreach ($representations as $representation_id => $representation) {
        $valid_rep_points[$representation_id] = 3;
        $segment_template_combined = get_segment_access($segment_template_combined, $representation['SegmentTemplate']);
        $segment_access_info[$representation_id] = $segment_template_combined;

        // Bullet 1
        if($representation['ProducerReferenceTime'] != NULL) {
            $producer_reference_times = $representation['ProducerReferenceTime'];
        }

        $valid_producer_reference_time = FALSE;
        $accepted_producer_reference_times = array();
        $producer_reference_time_ids = array();
        foreach ($producer_reference_times as $producer_reference_time) {
            $acceptance_points = 5;

            if($producer_reference_time['type'] != NULL && $producer_reference_time['type'] != 'encoder' && $producer_reference_time['type'] != 'captured')
                $acceptance_points--;

            $utc_timing_valid = FALSE;
            $utc_timing = $producer_reference_time['UTCTiming'];
            foreach ($utc_timing_info as $utc_timing_mpd) {
                if($utc_timing != NULL) {
                    if(nodes_equal($utc_timing[0], $utc_timing_mpd)) {
                        $utc_timing_valid = TRUE;
                    }
                }
            }
            if(!$utc_timing_valid)
                $acceptance_points--;

            $presentationTimeOffset = 0;
            if($segment_template_combined != NULL) {
                $presentationTimeOffset = ($segment_template_combined[0]['presentationTimeOffset'] != NULL) ? $segment_template_combined[0]['presentationTimeOffset'] : 0;
            }
            if($producer_reference_time['presentationTime'] != $presentationTimeOffset)
                $acceptance_points--;

            $availabilityStartTime = $mpd_features['availabilityStartTime'];
            if($availabilityStartTime != NULL) {
                if((time_parsing($availabilityStartTime) - time_parsing($producer_reference_time['wallClockTime'])) != (int) ($producer_reference_time['presentationTime']))
                    $acceptance_points--;
            }

            $producer_reference_time_ids[] = $producer_reference_time['id'];

            if($acceptance_points == 5)
                $accepted_producer_reference_times[] = $producer_reference_time;
        }

        foreach ($accepted_producer_reference_times as $accepted_producer_reference_time) {
            $indexes = array_keys($producer_reference_time_ids, $accepted_producer_reference_time['id']);
            $producer_reference_time_inband_present = FALSE;
            if(sizeof($indexes) == 1) {
                $valid_producer_reference_time = TRUE;
                
                if($producer_reference_time['inband'] != NULL) {
                    $producer_reference_time_inband_present = TRUE;
                }
                
                break;
            }
        }
        if(!$valid_producer_reference_time){
            $messages .= 'DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.3: "A low latency Adaptation Set SHALL include at least one ProducerReferenceTime element with a unique @id, a @type of \'encoder\' or \'captured\', a UTCTiming identical to the one in the MPD, a @wallClockTime equal to @presentationTime and a @presentationTime equal to @presentationTimeOffset if present or 0 otherwise", corresponding ProducerReferenceTime element is not found in the MPD in Period ' . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' or Represetation ' . ($representation_id+1) . ".\n";
            $valid_rep_points[$representation_id]--;
        }
        elseif($producer_reference_time_inband_present) {
            $messages .= 'Information on DASH-IF IOP CR Low Latency Live check Section 9.X.4.3: "A low latency Adaptation Set SHALL include at least one ProducerReferenceTime element where @inband may be set to TRUE or FALSE", ProducerReferenceTime element includes @inband attribute in the MPD in Period ' . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' or Represetation ' . ($representation_id+1) . ".\n";
        }
        
        // Bullet 3
        if($segment_template_combined == NULL) {
            $messages .= 'DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.3: "A low latency Adaptation Set SHALL include either SegmentTemplate@duration and SegmentTemplate@media with $Number$ or SegmentTimeline and SegmentTemplate@media with $Number$ and $Time$", neither is found in Period ' . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
            $valid_rep_points[$representation_id]--;
        }
        else {
            if(! (($segment_template_combined[0]['duration'] != NULL && strpos($segment_template_combined[0]['media'], '$Number') !== FALSE) || 
               ($segment_template_combined[0]['SegmentTimeline'] !=NULL && strpos($segment_template_combined[0]['media'], '$Number') !== FALSE && strpos($segment_template_combined[0]['media'], '$Time') !== FALSE))) {
                $messages .= 'DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.3: "A low latency Adaptation Set SHALL include either SegmentTemplate@duration and SegmentTemplate@media with $Number$ or SegmentTimeline and SegmentTemplate@media with $Number$ and $Time$", neither is found in Period ' . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
                $valid_rep_points[$representation_id]--;
            }
        }
        
        // Bullet 4
        $valid_inband_event_stream_present = FALSE;
        $inband_event_stream_messages = '';
        if($representation['InbandEventStream'] != NULL) {
            $inband_event_streams = $representation['InbandEventStream'];
        }
        if($inband_event_streams == NULL) {
            $messages .= 'DASH-IF IOP CR Low Latency Live check warning Section 9.X.4.3: "Inband Event Streams carrying MPD validity expiration events as defined in clause 4.5 SHOULD be present", corresponding Inband Event Stream is not found for Period ' . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
        }
        else {
            foreach ($inband_event_streams as $inband_event_stream) {
                if($inband_event_stream['schemeIdUri'] == 'urn:mpeg:dash:event:2012') {
                    if($inband_event_stream['value'] == '1') {
                        $valid_inband_event_stream_present = TRUE;
                        break;
                    }
                    else {
                        $inband_event_stream_messages .= 'DASH-IF IOP CR Low Latency Live check warning Section 9.X.4.3: "If Inband Event Streams carrying MPD validity expiration events as defined in clause 4.5 is used, the @value SHALL be set to 1", corresponding Inband Event Stream is not found for Period ' . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
                    }
                }
            }
            
            if(!$valid_inband_event_stream_present) {
                $inband_event_stream_messages .= 'DASH-IF IOP CR Low Latency Live check warning Section 9.X.4.3: "Inband Event Streams carrying MPD validity expiration events as defined in clause 4.5 SHOULD be present", corresponding Inband Event Stream is not found for Period ' . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
                $messages .= $inband_event_stream_messages;
            }
        }
    }
    if(sizeof(array_unique($valid_rep_points)) == 1 && $valid_rep_points[0] ==3) {
        $is_ll_adapt = TRUE;
    }
    
    $return_array1 = validate9X44($adaptation_set, $adaptation_set_id, $is_ll_adapt, $segment_access_info, $infoFileAdapt);
    $return_array2 = validate9X45($adaptation_set, $adaptation_set_id, $is_ll_adapt, $segment_access_info, $infoFileAdapt);
    
    if($return_array1[0] && $is_ll_adapt) {
        $messages .= $return_array1[1];
        fwrite($opfile, $messages);
    }
    if($return_array2[0] && $is_ll_adapt) {
        $messages .= $return_array2[1];
        fwrite($opfile, $messages);
    }
    if(!$return_array1[0] && !$return_array2[0]) {
        $messages .= 'DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.3: "A Low Latency Adaptation Set SHALL either be a Low Latency Segment Adaptation Set or a Low Latency Chunked Adaptation Set", neither Low Latency Segment Adaptation Set nor Low Latency Chunked Adaptation Set is found in Period ' . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ".\n";
        fwrite($opfile, $messages);
        fwrite($opfile, $return_array1[1]);
        fwrite($opfile, $return_array2[1]);
    }
    
    fwrite($opfile, validate9X42($adaptation_set, $adaptation_set_id, $return_array1[0]));
    
    return $is_ll_adapt;
}

function validate9X42($adaptation_set, $adaptation_set_id, $is_ll_segment_adapt) {
    global $session_dir, $current_period, $adaptation_set_template, $reprsentation_template;
    
    $messages = '';
    
    $eventMessageStreamsPresent = FALSE;
    $inbandEventMessageStreamsPresent = FALSE;
    $representations = $adaptation_set['Representation'];
    foreach ($representations as $representation_id => $representation) {
        $eventStreams = ($representation['EventStream']) ? $representation['EventStream'] : $adaptation_set['EventStream'];
        $inbandEventStreams = ($representation['InbandEventStream']) ? $representation['InbandEventStream'] : $adaptation_set['InbandEventStream'];
        
        $adapt_dir = str_replace('$AS$', $adaptation_set_id, $adaptation_set_template);
        $rep_xml_dir = str_replace(array('$AS$', '$R$'), array($adaptation_set_id, $representation_id), $reprsentation_template);
        $rep_xml = $session_dir . '/Period' . $current_period . '/' . $adapt_dir . '/' . $rep_xml_dir . '.xml';

        if(file_exists($rep_xml)){
            $xml = get_DOM($rep_xml, 'atomlist');
            if(!$xml)
                continue;
            
            $emsg_boxes = $xml->getElementsByTagName('emsg');
            if($emsg_boxes->length > 0 || $eventStreams != NULL || $inbandEventStreams != NULL) {
                $eventMessageStreamsPresent = TRUE;
            }
            if($emsg_boxes->length > 0 || $inbandEventStreams != NULL) {
                $inbandEventMessageStreamsPresent = TRUE;
            }
        }
    }
    
    if($eventMessageStreamsPresent) {
        $messages .= 'Information on DASH-IF IOP CR Low Latency Live check Section 9.X.4.2: "Event message streams may be used in low latency media presentations Adaptation Set or a Low Latency Chunked Adaptation Set", Low Latency Chunked Adaptation Set is found in Period ' . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ".\n";
    }
    if($inbandEventMessageStreamsPresent && !$is_ll_segment_adapt) {
        $messages .= 'DASH-IF IOP CR Low Latency Live check warning Section 9.X.4.2: "If Inband Event Streams are present, then they SHOULD be carried in Low Latency Segment Adaptation Sets", Low Latency Chunked Adaptation Set is found in Period ' . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ".\n";
    }
    
    return $messages;
}

function validate9X44($adaptation_set, $adaptation_set_id, $is_ll_adapt, $segment_access_info, $infoFileAdapt) {
    global $session_dir, $current_period, $service_description_info, $maxSegmentDurations, $adaptation_set_template, $reprsentation_template;
    
    $messages = '';
    $is_ll_segment_adapt = FALSE;
    
    $representations = $adaptation_set['Representation'];
    foreach ($representations as $representation_id => $representation) {
        $ll_segment_adapt_points[$representation_id] = 3;
        $is_smds_in_segment = FALSE;
        $is_smds_in_segment_profiles = array();
        $is_target_exceeding_50_segment = FALSE;
        $is_target_exceeding_30_segment = FALSE;
        
        $adapt_dir = str_replace('$AS$', $adaptation_set_id, $adaptation_set_template);
        $rep_xml_dir = str_replace(array('$AS$', '$R$'), array($adaptation_set_id, $representation_id), $reprsentation_template);
        $rep_xml = $session_dir . '/Period' . $current_period . '/' . $adapt_dir . '/' . $rep_xml_dir . '.xml';

        if(!file_exists($rep_xml))
            continue;
        
        $xml = get_DOM($rep_xml, 'atomlist');
        if(!$xml)
            continue;
        
        $styp_boxes = $xml->getElementsByTagName('styp');
        $segment_profiles = ($representation['segmentProfiles'] != NULL) ? $representation['segmentProfiles'] : $adaptation_set['segmentProfiles'];

        $is_segment_starts = $infoFileAdapt[$representation_id]['isSegmentStart'];
        $pres_starts = $infoFileAdapt[$representation_id]['PresStart'];
        $pres_ends = $infoFileAdapt[$representation_id]['NextPresStart'];
        
        $segment_indexes = array_keys($is_segment_starts, '1');
        $segment_count = sizeof($segment_indexes);
        $maxSegmentDuration = PHP_INT_MIN;
        $segment_durations = array();
        for($i=0; $i<$segment_count; $i++) {
            if($i != $segment_count-1) {
                $segment_index = $segment_indexes[$i];
                $next_segment_index = $segment_indexes[$i+1];
                
                $pres_start = $pres_starts[$segment_index];
                $pres_end = $pres_ends[$next_segment_index-1];
                
                $segment_duration = $pres_end - $pres_start;
                $segment_durations[] = $segment_duration;
                if($segment_duration > $maxSegmentDuration) {
                    $maxSegmentDuration = $segment_duration;
                }
                
                // Bullet 1
                if($next_segment_index - $segment_index == 1) {
                    if($styp_boxes->length > 0) {
                        $styp = $styp_boxes->item($i);
                        $major_brands = $styp->getAttribute('majorbrand');
                        $compatible_brands = $styp->getAttribute('compatible_brands');
                        
                        if(strpos($major_brands, 'smds') !== FALSE || strpos($compatible_brands, 'smds') !== FALSE) {
                            $is_smds_in_segment = TRUE;
                            if(strpos($segment_profiles, 'smds') !== FALSE) {
                                $is_smds_in_segment_profiles[] = TRUE;
                            }
                            else {
                                $is_smds_in_segment_profiles[] = FALSE;
                            }
                        }
                    }
                }
                
                // Bullet 3
                if($service_description_info != NULL) {
                    $service_description = $service_description_info[0];
                    $latency = $service_description['Latency'][0];
                    $target = $latency['target'];
                    if($segment_duration*1000 > $target*0.5) {
                        $is_target_exceeding_50_segment = TRUE;
                    }
                    if($segment_duration*1000 > $target*0.3) {
                        $is_target_exceeding_30_segment = TRUE;
                    }
                }
            }
            else {
                $segment_durations[] = PHP_INT_MAX;
            }
        }
        
        $maxSegmentDurations[$representation_id] = $maxSegmentDuration;
        
        $moofs_in_segments = checkSegment($adaptation_set_id, $representation_id, $segment_durations);
        if($moofs_in_segments != NULL) {
            for($i=0; $i<$segment_count; $i++) {
                if($moofs_in_segments[$i] > 1) {
                    $messages .= 'DASH-IF IOP CR Low Latency Live check warning Section 9.X.4.4: "Each Segment SHOULD include only a single movie fragment box "moof"", Segment with more than one "moof" box is found in Period ' . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
                }
                else {
                    if($is_smds_in_segment) {
                        $messages .= "Information on DASH-IF IOP CR Low Latency Live check Section 9.X.4.4: \" If Segments include only a single 'moof', then Segment MAY carry a 'smds' brand\", Segment with 'smds' brand is found in Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ' Segment ' . ($i+1) . ".\n";
                        if($is_smds_in_segment_profiles[$i] == FALSE) {
                            $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.4: \" If Segments include only a single 'moof' and carries a 'smds' brand, it SHALL signal this by providing the @segmentProfiles including the 'smds' brand\", Segment with 'smds' brand is found but is not signalled in @segmentProfiles in Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
                            $ll_segment_adapt_points[$representation_id]--;
                        }
                    }
                }
            }
        }
        
        // Bullet 2
        if($segment_access_info[$representation_id][0]['availabilityTimeComplete'] != NULL) {
            $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.4: \"The @availabilityTimeComplete shall be absent\", availabilityTimeComplete is found for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
            $ll_segment_adapt_points[$representation_id]--;
        }
        
        if($is_target_exceeding_50_segment) {
            $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.4: \"The Segment duration SHALL not exceed 50% of the value of the target latency\", Segment with duration larger than 50% of the target latency is found in Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
            $ll_segment_adapt_points[$representation_id]--;
        }
        if($is_target_exceeding_30_segment) {
            $messages .= "DASH-IF IOP CR Low Latency Live check warning Section 9.X.4.4: \"The Segment duration SHOULD not exceed 30% of the value of the target latency\", Segment with duration larger than 30% of the target latency is found in Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
        }
    }
    
    if(!$is_ll_adapt) {
        $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.4: \"A Low Latency Segment Adaptation Set SHALL conform to a Low Latency Adaptation Set\", Adaptation Set is not conforming to Low Latency Adaptation Set for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ".\n";
    }
    
    if(sizeof(array_unique($ll_segment_adapt_points)) == 1 && $ll_segment_adapt_points[0] == 3 && $is_ll_adapt) {
        $is_ll_segment_adapt = TRUE;
    }
    
    return [$is_ll_segment_adapt, $messages];
}

function validate9X45($adaptation_set, $adaptation_set_id, $is_ll_adapt, $segment_access_info, $infoFileAdapt) {
    global $current_period, $service_description_info, $maxSegmentDurations, $first_option, $second_option;
    
    $messages = '';
    $is_ll_chunked_adapt = FALSE;
    
    // Bullet 2
    $dashSegCmafFrag = TRUE;
    $dashForCmaf = TRUE;
    $message_array = validateDASHProfileCMAF($adaptation_set, $adaptation_set_id, $segment_access_info, $infoFileAdapt);
    if(!(sizeof(array_unique($message_array[0])) == 1 && $message_array[0][0] == TRUE)) {
        $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"Each Segment SHALL conform to a CMAF Fragment\", non-conforming Segment found in Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ".\n";
        $dashSegCmafFrag = FALSE;
    }
    if(strpos($message_array[1], 'violated') !== FALSE) {
        $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"Each Adaptation Set SHALL conform to an Adaptation Set according to the DASH profile for CMAF content as defined in MPEG DASH 8.X.4\", non-conforming Adaptation Set found in Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ".\n";
        $dashForCmaf = FALSE;
    }
    $messages .= $message_array[1];
    
    $representations = $adaptation_set['Representation'];
    foreach ($representations as $representation_id => $representation) {
        $ll_chunked_adapt_points[$representation_id] = 3;
        
        // Bullet 3
        $is_segment_starts = $infoFileAdapt[$representation_id]['isSegmentStart'];
        $pres_starts = $infoFileAdapt[$representation_id]['PresStart'];
        $pres_ends = $infoFileAdapt[$representation_id]['NextPresStart'];
        $segment_indexes = array_keys($is_segment_starts, '1');
        foreach ($segment_indexes as $segment_index_id => $segment_index) {
            $pres_start = $pres_starts[$segment_index];
            $pres_end = $pres_ends[$segment_index];
            
            if($segment_index_id != sizeof($segment_indexes)-1) {
                $segment_durations[] = $pres_end - $pres_start;
            }
            else {
                $segment_durations[] = PHP_INT_MAX;
            }
        }
        $moofs_in_segments = checkSegment($adaptation_set_id, $representation_id, $segment_durations);
        foreach ($segment_indexes as $segment_index_id => $segment_index) {
            $moofs_in_segment = $moofs_in_segments[$segment_index_id];
            if($moofs_in_segment > 1) {
                $messages .= "Information on DASH-IF IOP CR Low Latency Live check Section 9.X.4.5: \"Each Segment MAY contain more than one CMAF chunk\", more than one CMAF chunk found in Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' or Representation ' . ($representation_id+1) . ' Segment ' . ($segment_index_id+1) . ".\n";
            }
            else {
                $messages .= "DASH-IF IOP CR Low Latency Live check warning Section 9.X.4.5: \"Each Segment typically SHOULD contain more than one CMAF chunk\", more than one CMAF chunk not found in Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' or Representation ' . ($representation_id+1) . ' Segment ' . ($segment_index_id+1) . ".\n";
            }
        }
        
        $chunkOverlapWithinRepMessage = validateTimingsWithinRepresentation($adaptation_set, $adaptation_set_id, $representation_id, $infoFileAdapt);
        $messages .= $chunkOverlapWithinRepMessage;
        
        // Bullet 4
        $resyncs = ($representation['Resync'] != NULL) ? $representation['Resync'] : $adaptation_set['Resync'];
        if($resyncs == NULL) {
            $messages .= "DASH-IF IOP CR Low Latency Live check warning Section 9.X.4.5: \"A Resync element SHOULD be assigned to each Representation (possibly defaulted)\", Resync element is not found in Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' or Representation ' . ($representation_id+1) . ".\n";
        }
        
        // Bullet 5
        if($service_description_info != NULL) {
            $service_description = $service_description_info[0];
            $latency = $service_description['Latency'][0];
            $target = $latency['target'];
            if($segment_access_info[$representation_id][0]['availabilityTimeOffset'] == NULL) {
                $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"The @availabilityTimeOffset SHALL be present\", availabilityTimeOffset is not found for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
                $ll_chunked_adapt_points[$representation_id]--;
            }
            else {
                $availability_time_offset = $segment_access_info[$representation_id][0]['availabilityTimeOffset'];

                if(($availability_time_offset <= 0) || 
                   ($availability_time_offset >= $maxSegmentDurations[$representation_id]) || 
                   (abs($availability_time_offset-$maxSegmentDurations[$representation_id]) >= $target)) {
                    $ll_chunked_adapt_points[$representation_id]--;
                    if($availability_time_offset <= 0) {
                        $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"The SegmentBase@availabilityTimeOffset SHALL be greater than zero\", availabilityTimeOffset of $availability_time_offset is found for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
                    }
                    if($availability_time_offset >= $maxSegmentDurations[$representation_id]) {
                        $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"The SegmentBase@availabilityTimeOffset SHALL be smaller than the maximum segment duration for this Representation\", availabilityTimeOffset of $availability_time_offset is not smaller than maximum segment duration of $max_segment_duration for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
                    }
                    if(abs($availability_time_offset-$maxSegmentDurations[$representation_id]) >= $target) {
                        $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"The SegmentBase@availabilityTimeOffset SHALL be such that the difference of the availabilityTimeOffset and the maximum segment duration for this Representation is smaller that the target latency\", the difference of availabilityTimeOffset of $availability_time_offset and maximum segment duration of $max_segment_duration is not smaller than the target latency of $target for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
                    }
                }
                
                if($adaptation_set['SegmentTemplate'] == NULL) {
                    $messages .= "DASH-IF IOP CR Low Latency Live check warning Section 9.X.4.5: \"The @availabilityTimeOffset SHOULD be present on Adaptation Set level\", availabilityTimeOffset is not on the AdaptationSet level for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ".\n";
                }
                elseif($adaptation_set['SegmentTemplate'][0]['availabilityTimeOffset'] == NULL) {
                    $messages .= "DASH-IF IOP CR Low Latency Live check warning Section 9.X.4.5: \"The @availabilityTimeOffset SHOULD be present on Adaptation Set level\", availabilityTimeOffset is not on the AdaptationSet level for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ".\n";
                    
                    foreach ($segment_access_info as $segment_access_info_rep) {
                        $availabilityTimeOffset_rep[] = $segment_access_info_rep[0]['availabilityTimeOffset'];
                    }
                    if(!(sizeof(array_unique($availabilityTimeOffset_rep)) == 1 && $availabilityTimeOffset_rep[0] == FALSE)) {
                        $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"The @availabilityTimeOffset, if not present on AdaptationSet, SHALL be present on Representation and SHALL be the same for each Representation\", availabilityTimeOffset is not the same for the Representations for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ".\n";
                        $ll_chunked_adapt_points[$representation_id]--;
                    }
                }
            }
        }
        else {
            $ll_chunked_adapt_points[$representation_id]--;
        }
        
        // Bullet 6
        if($segment_access_info[$representation_id][0]['availabilityTimeComplete'] == NULL) {
            $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"The @availabilityTimeComplete SHALL be present\", availabilityTimeComplete is not found for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
            $ll_chunked_adapt_points[$representation_id]--;
        }
        else {
            if($segment_access_info[$representation_id][0]['availabilityTimeComplete'] != 'false') {
                $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"The @availabilityTimeComplete SHALL be present and SHALL be set to FALSE\", availabilityTimeComplete is not set to FALSE for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
                $ll_chunked_adapt_points[$representation_id]--;
            }
            
            foreach ($segment_access_info as $segment_access_info_rep) {
                $availabilityTimeComplete_rep[] = $segment_access_info_rep[0]['availabilityTimeComplete'];
            }
            if(!(sizeof(array_unique($availabilityTimeComplete_rep)) == 1 && $availabilityTimeComplete_rep[0] == 'false')) {
                $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"The @availabilityTimeComplete, if not present on AdaptationSet, SHALL be present on Representation and SHALL be the same for each Representation\", availabilityTimeComplete is not the same for the Representations for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ".\n";
                $ll_chunked_adapt_points[$representation_id]--;
            }
            
            if($adaptation_set['SegmentTemplate'] == NULL) {
                $messages .= "DASH-IF IOP CR Low Latency Live check warning Section 9.X.4.5: \"The @availabilityTimeComplete SHOULD be present on Adaptation Set level\", availabilityTimeComplete is not found for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ".\n";
            }
            elseif($adaptation_set['SegmentTemplate'][0]['availabilityTimeComplete'] == NULL) {
                $messages .= "DASH-IF IOP CR Low Latency Live check warrning Section 9.X.4.5: \"The @availabilityTimeComplete SHOULD be present on Adaptation Set level\", availabilityTimeComplete is not found on Adaptation Set level for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ".\n";
            }
        }
        
        // Bullet 7
        $emsg_messages = validateEmsg($adaptation_set, $adaptation_set_id, $representation_id, $infoFileAdapt);
        if(strpos($emsg_messages, 'violated') !== FALSE) {
            $ll_chunked_adapt_points[$representation_id]--;
        }
        $messages .= $emsg_messages;
        
        // Bullet 8 info collect
        $first_option['maxSegmentDuration'][$representation_id] = $maxSegmentDurations[$representation_id];
        $first_option['target'][$representation_id] = $target;

        $second_option['bandwidth'][$representation_id] = $representation['bandwidth'];
        $second_option['Resync'][$representation_id] = $resyncs;
        $second_option['timescale'][$representation_id] = $segment_access_info[$representation_id][0]['timescale'];
        $second_option['target'][$representation_id] = $target;
        $second_option['qualityRanking'][$representation_id] = $representation['qualityRanking'];
        $second_option['chunkOverlapWithinRep'][$representation_id] = $chunkOverlapWithinRepMessage;
    }
    
    // Bullet 8
    if(sizeof($representations) > 1) {
        $return_array = validate9X45Extended($adaptation_set, $adaptation_set_id);
        if($return_array[0]) {
            $messages .= $return_array[1];
        }
        if($return_array[2]) {
            $messages .= $return_array[3];
        }
        if(!$return_array[0] && !$return_array[2]) {
            $messages .= "DASH-IF IOP CR Low Latency Live check warning Section 9.X.4.5: \"For any Adaptation Set that contains more than one Representation, one of two options listed in Bullet 8 in this clause SHOULD be applied\", Adaptation Set with more than one Representation is not conforming to either of the options for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ".\n";
        }
    }
    
    if(!$is_ll_adapt) {
        $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"A Low Latency Chunked Adaptation Set SHALL conform to a Low Latency Adaptation Set\", Adaptation Set is not conforming to Low Latency Adaptation Set for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ".\n";
    }
    
    if(sizeof(array_unique($ll_chunked_adapt_points)) == 1 && $ll_chunked_adapt_points[0] == 3 && $dashSegCmafFrag && $is_ll_adapt) {
        $is_ll_chunked_adapt = TRUE;
    }
    
    return [$is_ll_chunked_adapt, $messages];
}

function validateDASHProfileCMAF($adaptation_set, $adaptation_set_id, $segment_access_info, $infoFileAdapt) {
    global $session_dir, $current_period, $adaptation_set_template, $reprsentation_template, $reprsentation_error_log_template;
    
    $messages = '';
    
    $dashConformsToCmafFrag = array();
    $representations = $adaptation_set['Representation'];
    foreach ($representations as $representation_id => $representation) {
        $adapt_dir = str_replace('$AS$', $adaptation_set_id, $adaptation_set_template);
        $rep_xml_dir = str_replace(array('$AS$', '$R$'), array($adaptation_set_id, $representation_id), $reprsentation_template);
        $rep_xml = $session_dir . '/Period' . $current_period . '/' . $adapt_dir . '/' . $rep_xml_dir . '.xml';

        if(file_exists($rep_xml)){
            $xml = get_DOM($rep_xml, 'atomlist');
            if(!$xml)
                continue;

            $contentType = $adaptation_set['contentType'];
            $mimeType = ($representation['mimeType'] != NULL) ? $representation['mimeType'] : $adaptation_set['mimeType'];
            $hdlr_type = $xml->getElementsByTagName('hdlr')->item(0)->getAttribute('handler_type');
            if($contentType != NULL){
                if(($hdlr_type == 'vide' && strpos($contentType, 'video') === FALSE) ||
                   ($hdlr_type == 'soun' && strpos($contentType, 'audio') === FALSE) ||
                   (($hdlr_type == 'text' || $hdlr_type == 'subt') && strpos($contentType, 'text') === FALSE)) {
                    $messages .= 'DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: "(As part of MPEG-DASH 8.X.4) The @contentType SHALL be set to the hdlr type of the CMAF Master Header of the Switching Set", @contentType not set accordingly for Period ' . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
                }
                
                if((strpos($contentType, 'video') !== FALSE && !($mimeType === "video/mp4" || $mimeType === "video/mp4, profiles='cmfc'")) ||
                   (strpos($contentType, 'audio') !== FALSE && !($mimeType === "audio/mp4" || $mimeType === "audio/mp4, profiles='cmfc'")) ||
                   (strpos($contentType, 'text') !== FALSE &&  !($mimeType === "text/mp4" || $mimeType === "text/mp4, profiles='cmfc'"))) {
                    $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.4) The @mimeType SHALL be compatible \"<@contentType>/mp4\" or \"<@contentType>/mp4, profiles='cmfc'\"\", @mimeType not set accordingly for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
                }
                
                if(strpos($contentType, 'video') !== FALSE) {
                    $maxWidth = ($adaptation_set['maxWidth'] != NULL) ? ((int) ($adaptation_set['maxWidth'])) : 0;
                    $maxHeight = ($adaptation_set['maxHeight'] != NULL) ? ((int) ($adaptation_set['maxWidth'])) : 0;
                    $tkhd = $xml->getElementsByTagName('tkhd')->item(0);
                    $width = (int) ($tkhd->getAttribute('width'));
                    $height = (int) ($tkhd->getAttribute('height'));
                    if($maxWidth != $width) {
                        $messages .= "DASH-IF IOP CR Low Latency Live check warning Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.4) If the @contentType is video, then @maxWidth SHOULD be set to the width in CMAF TrackHeaderBox of the CMAF Master Header\", @maxWidth of $maxWidth not set according to the width of $width for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
                    }
                    if($maxHeight != $height) {
                        $messages .= "DASH-IF IOP CR Low Latency Live check warning Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.4) If the @contentType is video, then @maxHeight SHOULD be set to the height in CMAF TrackHeaderBox of the CMAF Master Header\", @maxWidth of $maxWidth not set according to the height of $height for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
                    }
                }
            }
            
            $codecs = ($representation['codecs'] != NULL) ? $representation['codecs'] : $adaptation_set['codecs'];
            if($codecs != NULL) {
                $sample_description = $hdlr_type . '_sampledescription';
                $sdType = $xml->getElementsByTagName('stsd')->item(0)->getElementsByTagName($sample_description)->item(0)->getAttribute('sdType');
                if(strpos($codecs, $sdType) === FALSE) {
                    $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.4) The @codecs parameter SHALL be set to the sample entry of the CMAF Master Header\", @codecs of $codecs is not set according to the sample entry of $sdType for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
                }
            }
            
            $tencs = $xml->getElementsByTagName('tenc');
            if($tencs->length > 0) {
                $tenc = $tencs->item(0);
                $contentProtections = ($representation['ContentProtection'] != NULL) ? $representation['ContentProtection'] : $adaptation_set['ContentProtection'];
                
                if($contentProtections == NULL) {
                    $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.4) If the content is protected a ContentProtection element SHALL be present and set appropriately\", no ContentProtection element is found for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
                }
                else {
                    $valid_contentProtection_found = FALSE;
                    foreach ($contentProtections as $contentProtection) {
                        if($contentProtection['schemeIdUri'] == 'urn:mpeg:dash:mp4protection:2011') {
                            if(($contentProtection['value'] == 'cenc' || $contentProtection['value'] == 'cbcs')) {
                                if($contentProtection['cenc:default_KID'] == NULL) {
                                    $valid_contentProtection_found = TRUE;
                                }
                                elseif($contentProtection['cenc:default_KID'] == $tenc->getAttribute('default_KID')) {
                                    $valid_contentProtection_found = TRUE;
                                }
                            }
                        }
                        else {
                            $cenc_default_KID = $contentProtection['cenc:default_KID'];
                            $cenc_pssh = $contentProtection['cenc:pssh'];
                            $psshs = $xml->getElementsByTagName('pssh');
                            
                            if((($cenc_default_KID == NULL) || ($cenc_default_KID != NULL && $cenc_default_KID == $tenc->getAttribute('default_KID'))) &&
                               (($cenc_pssh == NULL) || ($cenc_pssh != NULL && $psshs->length > 0 && $cenc_pssh == $psshs->item(0)->getAttribute('systemID')))) {
                                $valid_contentProtection_found = TRUE;
                            }
                        }
                        
                        if($valid_contentProtection_found) {
                            break;
                        }
                    }
                    if(!$valid_contentProtection_found) {
                        $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.4) If the content is protected a ContentProtection element SHALL be present and set appropriately\", no appropriate ContentProtection element is found for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
                    }
                }
            }
            
            $segment_access_rep = $segment_access_info[$representation_id][0];
            
            $timescale_MPD = $segment_access_rep['timescale'];
            $timescale_Header = $xml->getElementsByTagName('mdhd')->item(0)->getAttribute('timescale');
            if($timescale_MPD != NULL && $timescale_MPD != $timescale_Header) {
                $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.3 referenced in 8.X.4) The @timescale in Representation SHALL be set to the timescale of Media Header Box ('mdhd') of the CMAF Track\", @timescale of $timescale_MPD is not equal to 'mdhd' timescale of $timescale_Header for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
            }
            
            if($segment_access_rep['SegmentTimeline'] != NULL) {
                $messages .= validateSegmentTimeline($adaptation_set, $adaptation_set_id, $representation, $representation_id, $segment_access_rep, $infoFileAdapt);
            }
            else {
                $messages .= validateSegmentTemplate($adaptation_set, $adaptation_set_id, $representation, $representation_id, $segment_access_rep, $infoFileAdapt);
            }
            
            if($representation['InbandEventStream'] != NULL || $adaptation_set['InbandEventStream'] != NULL) {
                $messages .= "Information on DASH-IF IOP CR Low Latency Live check Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.3 referenced in 8.X.4) Event Message Streams MAY be signaled with InbandEventStream elements\", InbandEventStream found for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
            }
            
            $self_init_seg_messages .= validateSelfInitializingSegment($adaptation_set, $adaptation_set_id, $representation, $representation_id, $segment_access_rep, $infoFileAdapt, $xml);
            
            $cmaf_seg_messages = '';
            $tfdt_boxes = $xml->getElementsByTagName('tfdt');
            for($i=1; $i<$tfdt_boxes->length; $i++) {
                $tfdt_i_prev_dec = $tfdt_boxes->item($i-1)->getAttribute('baseMediaDecodeTime');
                $tfdt_i_dec = $tfdt_boxes->item($i)->getAttribute('baseMediaDecodeTime');
                if($tfdt_i_prev_dec > $tfdt_i_dec) {
                    $cmaf_seg_messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.3 referenced in 8.X.4) Each CMAF Segment SHALL contain one or more complete and consecutive CMAF Fragments in decode order \", CMAF Fragments out of decode order found for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
                }
            }
            
            if(strpos($self_init_seg_messages, 'violated') !== FALSE && strpos($cmaf_seg_messages, 'violated') !== FALSE) {
                $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.3 referenced in 8.X.4) Each Media Segment SHALL conform to a CMAF Addressable Media Object as defined in CMAF 7.3.3\", Segment non-conforming to CMAF Addressable Media Object found for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
                $messages .= $self_init_seg_messages;
                $messages .= $cmaf_seg_messages;
            }
            
            $rep_error_file = str_replace(array('$AS$', '$R$'), array($adaptation_set_id, $representation_id), $reprsentation_error_log_template);
            $errors = file_get_contents($session_dir.'/Period'.$current_period.'/'.$rep_error_file.'.txt');
            if(strpos($errors, 'ISO/IEC 23009-1:2012(E), 6.3.4.2') !== FALSE) {
                $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.3 referenced in 8.X.4) Each Media Segment SHALL conform to a Delivery Unit Media Segment as defined in 6.3.4.2\", Segment non-conforming to Delivery Unit Media Segment found for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
            }
            if(strpos($errors, 'CMAF checks violated: Section 7.3.2.1') !== FALSE) {
                $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.3 referenced in 8.X.4) Each Initialization Segment, if present, SHALL conform to a CMAF Header as defined in CMAF 7.3.2.1\", Segment non-conforming to CMAF Header found for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
            }
            if(strpos($errors, 'ISO/IEC 23009-1:2012(E), 6.3.3') !== FALSE) {
                $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.3 referenced in 8.X.4) Each Initialization Segment, if present, SHALL conform to an Initialization Segment as defined in 6.3.3\", Segment non-conforming to CMAF Header found for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
            }
            
            $moof_boxes = $xml->getElementsByTagName('moof');
            $trun_boxes = $xml->getElementsByTagName('trun');
            $errorInTrack = FALSE;
            for($j=1;$j<$moof_boxes->length;$j++){
                $cummulatedSampleDurFragPrev=$trun_boxes->item($j-1)->getAttribute('cummulatedSampleDuration');
                $decodeTimeFragPrev=$tfdt_boxes->item($j-1)->getAttribute('baseMediaDecodeTime');
                $decodeTimeFragCurr=$tfdt_boxes->item($j)->getAttribute('baseMediaDecodeTime');

                if($decodeTimeFragCurr!=$decodeTimeFragPrev+$cummulatedSampleDurFragPrev){
                    $errorInTrack = TRUE;
                }
            }
            if($errorInTrack) {
                $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.3 referenced in 8.X.4) The Representation SHALL conform to a CMAF Track as defined in CMAF 7.3.2.2\", Representation non-conforming to CMAF Track for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
            }
            
            $startWithSAP = ($representation['startWithSAP'] != NULL) ? $representation['startWithSAP'] : $adaptation_set['startWithSAP'];
            if(strpos($errors, 'CMAF checks violated: Section 7.3.2.4') === FALSE) {
                $dashConformsToCmafFrag[$representation_id] = TRUE;
                if($startWithSAP != NULL && $startWithSAP != '1' && $startWithSAP != '2') {
                    $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.3 referenced in 8.X.4) If every DASH segment conforms to CMAF Fragment constraints and @startWithSAP is present, it SHALL be set to value 1 or 2\", @startWithSAP found $startWithSAP for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
                }
            }
        }
    }
    
    if($adaptation_set['segmentAlignment'] == NULL && $adaptation_set['subsegmentAlignment'] == NULL) {
        $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.4) Either segmentAlignment or subsegmentAlignment SHALL be set\", none found for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ".\n";
    }
    
    return [$dashConformsToCmafFrag, $messages];
}

function validateSelfInitializingSegment($adaptation_set, $adaptation_set_id, $representation, $representation_id, $segment_access_rep, $infoFileAdapt, $xml) {
    global $session_dir, $current_period, $reprsentation_template;
    
    $messages .= '';
    
    $rep_dir_name = str_replace(array('$AS$', '$R$'), array($adaptation_set_id, $representation_id), $reprsentation_template);
    if(!($opfile = open_file($session_dir . '/Period' .$current_period. '/' . $rep_dir_name . '.txt', 'r'))){
        echo "Error opening file: "."$session_dir.'/'.$rep_info_file".'.txt';
        return;
    }
    
    $self_initializing_segment_found = FALSE;
    $numSegments = 0;
    $line = fgets($opfile);
    while($line !== FALSE) {
        $line_info = explode(' ', $line);
        
        $numSegments++;
        $self_initializing_segment_found = ($numSegments == 1 && $line_info[1] > 0) ? TRUE : FALSE;
        
        $line = fgets($opfile);
    }
    
    if($self_initializing_segment_found) {
        $sidx_boxes = $xml->getElementsByTagName('sidx');
        $moof_boxes = $xml->getElementsByTagName('moof');
        if($sidx_boxes->length != 1) {
            $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.3 referenced in 8.X.4) If the media is contained in a Self-Initializing Segment, then exactly one sidx box SHALL be used\", multiple sidx boxes found for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
        }
        
        if($sidx_boxes->length != 0) {
            if($sidx_boxes->item(0)->getAttribute('offset') > $moof_boxes->item(0)->getAttribute('offset')) {
                $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.3 referenced in 8.X.4) If the media is contained in a Self-Initializing Segment, then sidx box SHALL be placed before any moof boxes\", sidx box placed after moof box for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
            }

            $sidx = $sidx_boxes->item(0);

            $referenceID = $sidx->getAttribute('referenceID');
            $trackID = $xml->getElementsByTagName('tkhd')->item(0)->getAttribute('trackID');
            if($referenceID != $trackID) {
                $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.3 referenced in 8.X.4) If the media is contained in a Self-Initializing Segment, then sidx box reference_ID SHALL be the trackID of the CMAF track\", reference_ID of $referenceID not equal to trackID of $trackID for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
            }

            $timescale = $sidx->getAttribute('timeScale');
            $timescale1 = $xml->getElementsByTagName('mdhd')->item(0)->getAttribute('timescale');
            if($timescale != $timescale1) {
                $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.3 referenced in 8.X.4) If the media is contained in a Self-Initializing Segment, then sidx box timescale SHALL be identical to the timescale of the mdhd box of the CMAF track\", sidx timescale of $timescale not equal to mdhd timescale of $timescale1 for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
            }

            $referenceType = $sidx->getAttribute('reference_type_1');
            if($referenceType != '0') {
                $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.3 referenced in 8.X.4) If the media is contained in a Self-Initializing Segment, then sidx box reference_type SHALL be set to 0\", sidx reference_type of $referenceType is not 0 for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
            }

            $earliestPresentationTime = $sidx->getAttribute('earliestPresentationTime');
            $is_segment_starts = $infoFileAdapt[$representation_id]['isSegmentStart'];
            $pres_starts = $infoFileAdapt[$representation_id]['PresStart'];
            $pres_ends = $infoFileAdapt[$representation_id]['NextPresStart'];
            $segment_indexes = array_keys($is_segment_starts, '1');
            $pres_start_first = $pres_starts[$segment_indexes[0]]*$timescale1;
            if($earliestPresentationTime != $pres_start_first) {
                $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.3 referenced in 8.X.4) If the media is contained in a Self-Initializing Segment, then sidx box earlieast_presentation_time SHALL be set to earlieast presentation time of the first CMAF Fragment\", sidx earlieast_presentation_time of $earliestPresentationTime is not earlieast presentation time of the first CMAF Fragment of $pres_start_first for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
            }

            $referenceCount = $sidx->getAttribute('referenceCount');
            if($referenceCount != $moof_boxes->length) {
                $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.3 referenced in 8.X.4) If the media is contained in a Self-Initializing Segment, then sidx box reference_count SHALL be set to the number of CMAF Fragments in the CMAF Track\", sidx reference_count of $referenceCount is not equal to the number of CMAF Fragments of " . ($moof_boxes->length) . " for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
            }

            $subsegments = $xml->getElementsByTagName('subsegment');
            $subsegments_count = $subsegments->length;
            for($i=0; $i<$subsegments_count; $i++) {
                $subsegment = $subsegments->item($i);
                $subsegment_duration = $subsegment->getAttribute('subsegment_duration');
                $starts_with_SAP = $subsegment->getAttribute('starts_with_SAP');
                $SAP_type = $subsegment->getAttribute('SAP_type');
                $SAP_delta_time = $subsegment->getAttribute('SAP_delta_time');

                if($starts_with_SAP != '0') {
                    $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.3 referenced in 8.X.4) If the media is contained in a Self-Initializing Segment, then sidx box starts_with_SAP SHALL be set to 1\", sidx starts_with_SAP found $starts_with_SAP for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
                }
                if($SAP_type != '1' || $SAP_type != '2') {
                    $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.3 referenced in 8.X.4) If the media is contained in a Self-Initializing Segment, then sidx box SAP_type SHALL be set to 1 or 2\", sidx SAP_type found $SAP_type for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
                }
                if($SAP_delta_time != '0') {
                    $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.3 referenced in 8.X.4) If the media is contained in a Self-Initializing Segment, then sidx box SAP_delta_time SHALL be set to 0\", sidx SAP_delta_time found $SAP_delta_time for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
                }
                if(sizeof($pres_ends) != $subsegments_count || sizeof($pres_starts) != $subsegments_count){
                    $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.3 referenced in 8.X.4) If the media is contained in a Self-Initializing Segment, then each CMAF Fragment SHALL be mapped to exactly one Subsegment with CMAF Fragment duration equal to subsegment_duration\", CMAF Fragment not mapped for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
                } 
                else {
                    $duration = $pres_ends[$i] - $pres_starts[$i];
                    if($duration != $subsegment_duration) {
                        $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.3 referenced in 8.X.4) If the media is contained in a Self-Initializing Segment, then each CMAF Fragment SHALL be mapped to exactly one Subsegment with CMAF Fragment duration equal to subsegment_duration\", CMAF Fragment duration of $duration is not equal to subsegment_duration of $subsegment_duration for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
                    }
                }
            }

            if(sizeof($segment_indexes) != 0 && $xml->getElementsByTagName('tfdt')->item(0)->getAttribute('baseMediaDecodeTime') != '0') {
                $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.3 referenced in 8.X.4) If the media is contained in a Self-Initializing Segment, then the Segment SHALL conform to CMAF Track File\", Segment not conforming to CMAF Track File for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
            }
            
            if(strpos($xml->getElementsByTagName('ftyp')->item(0)->getAttribute('compatible_brands'), 'dash') === FALSE) {
                $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.3 referenced in 8.X.4) If the media is contained in a Self-Initializing Segment, then the Segment SHALL conform to the Indexed Self-Initializing Media Segment\", Segment not conforming to Indexed Self-Initializing Media Segment for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
            }
        }
    }
    
    return $messages;
}

function validateSegmentTemplate($adaptation_set, $adaptation_set_id, $representation, $representation_id, $segment_access_rep, $infoFileAdapt) {
    global $current_period;
    
    $messages = '';
    
    $is_segment_starts = $infoFileAdapt[$representation_id]['isSegmentStart'];
    $pres_starts = $infoFileAdapt[$representation_id]['PresStart'];

    $segment_indexes = array_keys($is_segment_starts, '1');
    $segment_count = sizeof($segment_indexes);
    
    $duration = $segment_access_rep['duration'];
    $timescale = ($segment_access_rep['timescale']) ? $segment_access_rep['timescale'] : 1;
    
    $pres_start_first = $pres_starts[$segment_indexes[0]]*$timescale;
    for($i=1; $i<$segment_count; $i++) {
        $pres_start_i = $pres_starts[$segment_indexes[$i]]*$timescale;
        $diff = $pres_start_i - $pres_start_first;
        $lower_bound = (($i-1)+0.5)*$duration;
        $upper_bound = ($i+0.5)*$duration;
        if(!($diff >= $lower_bound && $diff <= $upper_bound)) {
            $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.3 referenced in 8.X.4) If @duration attribute is present then for every CMAF Fragment the tolerance for the earliest presentation time of the CMAF Fragment relative to the earliest presentation time of the first CMAF Fragment SHALL not exceed 50%\", CMAF Fragment exceeding the 50% tolerance found for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ' Segment ' . ($i+1) . ".\n";
        }
    }
    
    return $messages;
}

function validateSegmentTimeline($adaptation_set, $adaptation_set_id, $representation, $representation_id, $segment_access_rep, $infoFileAdapt) {
    global $current_period, $period_timing_info;
    
    $messages = '';
    
    $is_segment_starts = $infoFileAdapt[$representation_id]['isSegmentStart'];
    $pres_starts = $infoFileAdapt[$representation_id]['PresStart'];
    $pres_ends = $infoFileAdapt[$representation_id]['NextPresStart'];

    $segment_indexes = array_keys($is_segment_starts, '1');
    $segment_count = sizeof($segment_indexes);
    $segment_index = 0;
    
    $timescale = ($segment_access_rep['timescale']) ? $segment_access_rep['timescale'] : 1;
    $presentationTimeOffset = ($segment_access_rep['presentationTimeOffset']) ? $segment_access_rep['presentationTimeOffset'] : 0;
    
    $s_elements = $segment_access_rep['SegmentTimeline'][0]['S'];
    $s_count = sizeof($s_elements);
    foreach($s_elements as $s_i => $s) {
        $t = ($s['t']) ? $s['t'] : 0; $t -= $presentationTimeOffset;
        $d = $s['d'];
        $r = $s['r'];
        $k = $s['k'];
        
        if($segment_index != $segment_count-1) {
            $numberOfChunks = $segment_indexes[$segment_index+1] - $segment_indexes[$segment_index];
            if($numberOfChunks > 1 && ($k == NULL || $k = $numberOfChunks)) {
                $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.3 referenced in 8.X.4) If the SegmentTimeline element is present and if each chunk is an addressable object then for every CMAF Fragment an entry in S element SHALL be present with @k as set to the number of chunks in the corresponding CMAF Fragment\", @k is not set to number of chunks in the CMAF Fragment for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ' Segment ' . ($segment_index+1) . ".\n";
            }
        }
        
        if($r < 0) {
            $until = ($s_i != $s_count-1) ? $s_elements[$s_i+1]['t'] : $period_timing_info[1]*$timescale;
            while($t < $until) {
                if($t/$timescale != $pres_starts[$segment_index]) {
                    $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.3 referenced in 8.X.4) If the SegmentTimeline element is present then for every CMAF Fragment an entry in S element SHALL be present with @t as set to earliest presentation time\", @t of $t is not equal to earliest presentation time of " . $pres_starts[$segment_index] . "  for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ' Segment ' . ($segment_index+1) . ".\n";
                }
                if($d/$timescale != $pres_ends[$segment_index] - $pres_starts[$segment_index]) {
                    $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.3 referenced in 8.X.4) If the SegmentTimeline element is present then for every CMAF Fragment an entry in S element SHALL be present with @d as set to CMAF Fragment duration\", @d of $d is not equal to duration of " . $pres_ends[$segment_index] - $pres_starts[$segment_index] . "  for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ' Segment ' . ($segment_index+1) . ".\n";
                }
                $t += $d;
                $segment_index++;
            }
        }
        else {
            for($j=0; $j<$r+1; $j++) {
                if($t/$timescale != $pres_starts[$segment_index]) {
                    $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.3 referenced in 8.X.4) If the SegmentTimeline element is present then for every CMAF Fragment an entry in S element SHALL be present with @t as set to earliest presentation time\", @t of $t is not equal to earliest presentation time of " . $pres_starts[$segment_index] . "  for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ' Segment ' . ($segment_index+1) . ".\n";
                }
                if($d/$timescale != $pres_ends[$segment_index] - $pres_starts[$segment_index]) {
                    $messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.3 referenced in 8.X.4) If the SegmentTimeline element is present then for every CMAF Fragment an entry in S element SHALL be present with @d as set to CMAF Fragment duration\", @d of $d is not equal to duration of " . $pres_ends[$segment_index] - $pres_starts[$segment_index] . "  for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ' Segment ' . ($segment_index+1) . ".\n";
                }
                $t += $d;
                $segment_index++;
            }
        }
        
        if($s_i != 0) {
            $s_prev = $s_elements[$s_i-1];
            if($s_prev['d'] == $d) {
                $messages .= "DASH-IF IOP CR Low Latency Live check warning Section 9.X.4.5: \"(As part of MPEG-DASH 8.X.3 referenced in 8.X.4) If the SegmentTimeline element is present and if consecutive CMAF Fragments have the same duration then their corresponding S element SHOULD be combined to a single S element\", S elements at indexes " . $s_i . " and "  ($s_i+1) . " signal the same duration of $d but are not combined into a single S element for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
            }
        }
    }
    
    return $messages;
}

function validateTimingsWithinRepresentation($adaptation_set, $adaptation_set_id, $representation_id, $infoFileAdapt) {
    global $session_dir, $current_period, $presentation_times, $decode_times, $adaptation_set_template, $reprsentation_template;
    
    $messages = '';
    
    $representations = $adaptation_set['Representation'];
    
    $presStarts = $infoFileAdapt[$representation_id]['PresStart'];
    $presEnds = $infoFileAdapt[$representation_id]['PresEnd'];
    $earliest_pres_time_prev = 0;
    $latest_pres_time_prev = 0;
    for($i=0; $i<sizeof($presStarts); $i++) {
        $earliest_pres_time = $presStarts[$i];
        $latest_pres_time = $presEnds[$i];

        if($i > 0) {
            if($earliest_pres_time_prev > $earliest_pres_time || $latest_pres_time < $earliest_pres_time_prev) {
                $messages .= 'DASH-IF IOP CR Low Latency Live check warning Section 9.X.4.5: "CMAF chunks SHOULD be generated such that the range of presentation times contained in any CMAF chunk of the CMAF Track do not overlap with the range of presentation times in any other CMAF chunk of the same CMAF Track", overlapping CMAF chunk ' . ($i-$start_line+1) . ".\n";
            }
        }

        $earliest_pres_time_prev = $earliest_pres_time;
        $latest_pres_time_prev = $latest_pres_time;

        $presentation_times[$current_period][$adaptation_set_id][$representation_id][] = $earliest_pres_time;
    }
    
    $adapt_dir = str_replace('$AS$', $adaptation_set_id, $adaptation_set_template);
    $rep_xml_dir = str_replace(array('$AS$', '$R$'), array($adaptation_set_id, $representation_id), $reprsentation_template);
    $rep_xml = $session_dir . '/Period' . $current_period . '/' . $adapt_dir . '/' . $rep_xml_dir . '.xml';

    if(file_exists($rep_xml)){
        $xml = get_DOM($rep_xml, 'atomlist');

        if(!$xml)
            return;

        $timescale = $xml->getElementsByTagName('mdhd')->item(0)->getAttribute('timescale');
        $tfdts = $xml->getElementsByTagName('tfdt');
        foreach ($tfdts as $tfdt) {
            $decode_times[$current_period][$adaptation_set_id][$representation_id][] = $tfdt->getAttribute('baseMediaDecodeTime') / $timescale;
        }
    }
    
    return $messages;
}

function validateEmsg($adaptation_set, $adaptation_set_id, $representation_id, $infoFileAdapt) {
    global $session_dir, $current_period, $adaptation_set_template, $reprsentation_template, $reprsentation_mdat_template;
    
    $messages = '';
    
    $adapt_dir = str_replace('$AS$', $adaptation_set_id, $adaptation_set_template);
    $rep_xml_dir = str_replace(array('$AS$', '$R$'), array($adaptation_set_id, $representation_id), $reprsentation_template);
    $rep_xml = $session_dir . '/Period' . $current_period . '/' . $adapt_dir . '/' . $rep_xml_dir . '.xml';

    if(file_exists($rep_xml)){
        $xml = get_DOM($rep_xml, 'atomlist');
        if(!$xml)
            return $messages;
        
        $is_segment_starts = $infoFileAdapt[$representation_id]['isSegmentStart'];
        $pres_starts = $infoFileAdapt[$representation_id]['PresStart'];
        $pres_ends = $infoFileAdapt[$representation_id]['NextPresStart'];
        
        $segment_indexes = array_keys($is_segment_starts, '1');
        $segment_count = sizeof($segment_indexes);
        
        $mdat_file = 'Period' . $current_period .'/' . str_replace(array('$AS$', '$R$'), array($adaptation_set_id, $representation_id), $reprsentation_mdat_template);
        $mdat_info = explode("\n", file_get_contents($mdat_file));
        
        $moof_boxes = $xml->getElementsByTagName('moof');
        $moof_count = $moof_boxes->length;
        $emsg_boxes = $xml->getElementsByTagName('emsg');
        $emsg_count = $emsg_boxes->length;
        foreach ($emsg_boxes as $emsg_index => $emsg_box) {
            $emsg_conforms = FALSE;
            $emsg_offset = $emsg_box->getAttribute('offset');
            $first_moof_offset = $moof_boxes->item(0)->getAttribute('offset');
            $last_mdat_info = explode(' ', $mdat_info[sizeof($mdat_info)-1]);
            if($emsg_offset < $first_moof_offset) {
                $emsg_conforms = TRUE;
            }
            else {
                $location_found_before_moof = FALSE;
                $location_found_before_mdat = FALSE;
                for($i=0; $i<$moof_count; $i++) {
                    $moof = $moof_boxes->item($i);
                    $moof_offset = $moof->getAttribute('offset');
                    $moof_size = $moof->getAttribute('size');

                    $mdat = $mdat_info[$i];
                    $mdat_offset = explode(' ', $mdat)[0];
                    $mdat_size = explode(' ', $mdat)[1];

                    if($emsg_offset < $moof_offset) {
                        $location_found_before_moof = TRUE;
                        break;
                    }
                    elseif(($emsg_offset > $moof_offset + $moof_size) && ($emsg_offset < $mdat_offset)) {
                        $location_found_before_mdat = TRUE;
                        break;
                    }
                }
                
                $segment_number = NULL;
                if($location_found_before_moof) {
                    foreach ($segment_indexes as $segment_index_id => $segment_index) {
                        if($segment_index_id != $segment_count-1) {
                            if($i >= $segment_index && $i < $segment_indexes[$segment_index_id+1]) {
                                $segment_number = $segment_index_id;
                            }
                        }
                    }
                }
                elseif($location_found_before_mdat) {
                    
                }
                
                $equivalent_emsg_found = FALSE;
                if($segment_number != NULL) {
                    $mdat_last_curr_seg = explode(' ', $mdat_info[$segment_indexes[$segment_number+1]-1]);
                    $moof_first_next_seg = $moof_boxes->item($segment_indexes[$segment_number+1]);
                    
                    $mdat_last_curr_seg_offset = $mdat_last_curr_seg[0];
                    $mdat_last_curr_seg_size = $mdat_last_curr_seg[1];
                    $moof_first_next_seg_offset = $moof_first_next_seg->getAttribute('offset');
                    $moof_first_next_seg_size = $moof_first_next_seg->getAttribute('size');
                    
                    for($e=$emsg_index+1; $e<$emsg_count; $e++) {
                        $e_comp = $emsg_boxes->item($e);
                        $e_offset = $e_comp->getAttribute('offset');
                        
                        if($e_offset >= $mdat_last_curr_seg_offset+$mdat_last_curr_seg_size && $e_offset < $moof_first_next_seg_offset) {
                            if(nodes_equal($emsg_box, $e_comp)) {
                                $equivalent_emsg_found = TRUE;
                                $emsg_conforms = TRUE;
                                break;
                            }
                        }
                    }
                }
                if(!$equivalent_emsg_found) {
                    $messages .= 'DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: "Any emsg box if not placed before the moof box, an equivalent emsg with the same id valu SHALL be present before the first moof box of the next Segment", emsg box at index ' . ($emsg_index+1) . ' is not placed before the first moof box and could not be found before the first moof box of the next Segment' . ".\n";
                }
            }
            
            if($emsg_conforms) {
                $messages .= 'Information on DASH-IF IOP CR Low Latency Live check Section 9.X.4.5: "Any emsg box MAY be placed in between any mdat and moof boxes or before the first moof box", emsg box at index ' . ($emsg_index+1) . ' is placed before according to one the two options' . ".\n";
            }
        }
    }
    
    return $messages;
}

function validate9X45Extended($adaptation_set, $adaptation_set_id) {
    global $current_period, $first_option, $second_option, $presentation_times, $decode_times;
    
    $representations = $adaptation_set['Representation'];
    
    // Test first option
    $first_option_messages = '';
    $valid_first_option = FALSE;
    foreach ($representations as $representation_id => $representation) {
        $first_option_points[$representation_id] = 1;
        $maxSegmentDuration = $first_option['maxSegmentDuration'][$representation_id];
        $target = $first_option['target'][$representation_id];
        if($target == NULL) {
            $first_option_messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"For Adaptation Set that contains more than one Representation, the maximum segment duration SHALL be smaller than the signaled target latency\", target latency not found for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
            $first_option_points[$representation_id]--;
            continue;
        }
        if($maxSegmentDuration >= $target) {
            $first_option_messages .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"For Adaptation Set that contains more than one Representation, the maximum segment duration SHALL be smaller than the signaled target latency\", maximum segment duration of $max_segment_duration is not smaller than the signaled target latency (signaled target latency: $target) for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
            $first_option_points[$representation_id]--;
        }
        if($max_segment_duration >= $target*0.5) {
            $first_option_messages .= "DASH-IF IOP CR Low Latency Live check warning Section 9.X.4.5: \"For Adaptation Set that contains more than one Representation, the maximum segment duration SHOULD be smaller than half of the signaled target latency\", maximum segment duration of $max_segment_duration is not smaller than half of the signaled target latency (signaled target latency: $target) for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
        }
    }
    if(sizeof(array_unique($first_option_points)) == 1 && $first_option_points[0] == 1) {
        $valid_first_option = TRUE;
    }
    
    
    // Test second option
    $second_option_messages = '';
    $valid_second_option = FALSE;
    
    // Lowest bw Representations
    $valid_lowest_bw_found = FALSE;
    $lowest_bw_rep_ids = array_keys($second_option['bandwidth'], min($second_option['bandwidth']));
    foreach ($lowest_bw_rep_ids as $lowest_bw_rep_id) {
        $lowest_bw_points[$lowest_bw_rep_id] = 1;
        
        $representation = $representations[$lowest_bw_rep_id];
        $resyncs = $second_option['Resync'][$lowest_bw_rep_id];
        $timescale = $second_option['timescale'][$lowest_bw_rep_id];
        $target = $second_option['target'][$lowest_bw_rep_id];
        $qualityRanking = $second_option['qualityRanking'][$lowest_bw_rep_id];
        
        // Check the Resync
        $valid_resync_found = FALSE;
        foreach ($resyncs as $resync) {
            $valid_resync_warning = FALSE;
            if($resync['type'] == '1' || $resync['type'] == '2') {
                if($target == NULL) {
                    continue;
                }
                if($resync['dT']/$timescale <= $target) {
                    if($resync['dT']/$timescale >= $target*0.5) {
                        $valid_resync_warning = TRUE;
                    }
                    if($resync['marker'] == 'TRUE') {
                        $valid_resync_found = TRUE;
                        break;
                    }
                }
            }
        }
        if(!$valid_resync_found) {
            $lowest_bw_rep_messages[$lowest_bw_rep_id] .= "DASH-IF IOP CR Low Latency Live check violated Section 9.X.4.5: \"For Adaptation Set that contains more than one Representation, at least one Representation is present with a Resync element with @type set to 1 or 2, @dT normalized by @timescale is at most the signaled target latency, and @marker set to 'TRUE'\", appropriate Resync element not found for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($lowest_bw_rep_id+1) . ".\n";
            $lowest_bw_points[$lowest_bw_rep_id]--;
        }
        elseif($valid_resync_warning) {
            $lowest_bw_rep_messages[$lowest_bw_rep_id] .= "DASH-IF IOP CR Low Latency Live check warning Section 9.X.4.5: \"For Adaptation Set that contains more than one Representation, at least one Representation is present with @bandwidth value is the lowest in the Adaptation Set and it contains a Resync element with @dT normalized by @timescale should be smaller than half of the signaled target latency\", @dT of " . $resync['dT'] . " normalized by timescale of $timescale is not less than half of the target latency (target latency of $target) for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($lowest_bw_rep_id+1) . ".\n";
        }
        
        // Check the qualityRanking
        if($qualityRanking == NULL) {
            $lowest_bw_rep_messages[$lowest_bw_rep_id] .= "DASH-IF IOP CR Low Latency Live check warning Section 9.X.4.5: \"For Adaptation Set that contains more than one Representation, at least one Representation is present with @bandwidth value is the lowest in the Adaptation Set and @qualityRanking SHOULD be used\", @qualityRanking is not found for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($lowest_bw_rep_id+1) . ".\n";
        }
        
        // Analyze the findings for this low bw rep
        if($lowest_bw_points[$lowest_bw_rep_id] == 1) {
            $valid_lowest_bw_found = TRUE;
            break;
        }
    }
    if($valid_lowest_bw_found) {
        $second_option_messages .= $lowest_bw_rep_messages[$lowest_bw_rep_id];
    }
    else{
        foreach ($lowest_bw_rep_ids as $lowest_bw_rep_id) {
            $second_option_messages .= $lowest_bw_rep_messages[$lowest_bw_rep_id];
        }
    }
    
    // Rest of the Representations
    $other_rep_messages = '';
    foreach ($representations as $representation_id => $representation) {
        if(!in_array($representation_id, $lowest_bw_rep_ids)) {
            $resyncs = $second_option['Resync'][$representation_id];
            $timescale = $second_option['timescale'][$representation_id];
            $target = $second_option['target'][$representation_id];
            $qualityRanking = $second_option['qualityRanking'][$representation_id];
            
            // Check the Resync
            $valid_resync_found = FALSE;
            foreach ($resyncs as $resync) {
                if($resync['type'] == '1' || $resync['type'] == '2' || $resync['type'] == '3') {
                    if($resync['dT']/$timescale <= $target) {
                        if($resync['marker'] == 'TRUE') {
                            $valid_resync_found = TRUE;
                            break;
                        }
                    }
                }
            }
            if($valid_resync_found) {
                $other_rep_messages .= "Information on DASH-IF IOP CR Low Latency Live check Section 9.X.4.5: \"For Adaptation Set that contains more than one Representation, additional Representations with higher values for @bandwidth MAY be present with Resync set as above\", such Representation is found for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
                // Check the qualityRanking
                if($representation['qualityRanking'] == NULL) {
                    $other_rep_messages .= "DASH-IF IOP CR Low Latency Live check warning Section 9.X.4.5: \"For Adaptation Set that contains more than one Representation, additional Representations with higher values should use @qualityRanking\", @qualityRanking is not found for Period " . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($representation_id+1) . ".\n";
                }
            }
        }
    }
    $second_option_messages .= $other_rep_messages;
    
    // All of the Representations
    $all_rep_messages = '';
    $valid_all_rep_found = FALSE;
    for($i=0; $i<sizeof($representations); $i++) {
        $all_rep_points[$i] = 1;
        
        $chunkOverlapWithinRep = $second_option['chunkOverlapWithinRep'][$i];
        if($chunkOverlapWithinRep != '') {
            $all_rep_points[$i]--;
            $all_rep_messages .= str_replace("SHOULD", "SHALL", str_replace("warning", "violated", $chunkOverlapWithinRep));
        }
        
        $presentation_time_i = $presentation_times[$current_period][$adaptation_set_id][$i];
        $decode_time_i = $decode_times[$current_period][$adaptation_set_id][$i];
        $all_rep_cross_messages = '';
        for($j=1; $j<sizeof($representations); $j++) {
            $presentation_time_j = $presentation_times[$current_period][$adaptation_set_id][$j];
            $decode_time_j = $decode_times[$current_period][$adaptation_set_id][$j];

            if(!empty(array_diff($presentation_time_i, $presentation_time_j))) {
                $all_rep_cross_messages .= 'DASH-IF IOP CR Low Latency Live check warning Section 9.X.4.5: "CMAF chunks SHALL be aligned in presentation time across all Representations", presentation time not aligned for Period ' . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($i+1) . ' and Representation ' . ($j+1) . ".\n";
            }
            if(!empty(array_diff($decode_time_i, $decode_time_j))) {
                $all_rep_cross_messages .= 'DASH-IF IOP CR Low Latency Live check warning Section 9.X.4.5: "CMAF chunks SHALL be aligned in decode time across all Representations", decode time not aligned for Period ' . ($current_period+1) . ' Adaptation Set ' . ($adaptation_set_id+1) . ' Representation ' . ($i+1) . ' and Representation ' . ($j+1) . ".\n";
            }
        }
        if($all_rep_cross_messages != '') {
            $all_rep_points[$i]--;
            $all_rep_messages .= $all_rep_cross_messages;
        }
    }
    if(sizeof(array_unique($all_rep_points)) == 1 && $all_rep_points[0] == 1) {
        $valid_all_rep_found = TRUE;
    }
    $second_option_messages .= $all_rep_messages;
    
    
    if($valid_lowest_bw_found && $valid_all_rep_found) {
        $valid_second_option = TRUE;
    }
    
    return [$valid_first_option, $first_option_messages, $valid_second_option, $second_option_messages];
}

function readInfoFile($adaptation_set, $adaptation_set_id) {
    global $session_dir, $current_period, $reprsentation_info_log_template;
    
    $infoFileInfoAdaptationSet = array();
    $representations = $adaptation_set['Representation'];
    foreach ($representations as $representation_id => $representation) {
        $rep_info_file = str_replace(array('$AS$', '$R$'), array($adaptation_set_id, $representation_id), $reprsentation_info_log_template);
    
        if(!($opfile = open_file($session_dir.'/Period'.$current_period.'/'.$rep_info_file.'.txt', 'r'))){
            echo "Error opening file: "."$session_dir.'/'.$rep_info_file".'.txt';
            return;
        }

        $infoFileInfo = array('isSegmentStart'=>array(), 'PresStart'=>array(), 'PresEnd'=>array(), 'NextPresStart'=>array());
        while(($line = fgets($opfile)) !== FALSE) {
            $line_info = explode(' ', $line);
            if(sizeof($line_info) < 3)
                continue;
            
            $is_segment_start = $line_info[0];
            $pres_start = $line_info[1];
            $pres_end = $line_info[2];
            $next_pres_start = (sizeof($line_info) > 3) ? explode("\n", $line_info[3])[0] : PHP_INT_MAX;

            $infoFileInfo['isSegmentStart'][] = $is_segment_start;
            $infoFileInfo['PresStart'][] = $pres_start;
            $infoFileInfo['PresEnd'][] = $pres_end;
            $infoFileInfo['NextPresStart'][] = $next_pres_start;
        }
        fclose($opfile);
        
        $infoFileInfoAdaptationSet[$representation_id] = $infoFileInfo;
    }
    
    return $infoFileInfoAdaptationSet;
}

function checkSegment($adaptation_set_id, $representation_id, $segment_durations) {
    global $session_dir, $current_period, $adaptation_set_template, $reprsentation_template, $reprsentation_mdat_template;
    
    $adapt_dir = str_replace('$AS$', $adaptation_set_id, $adaptation_set_template);
    $rep_xml_dir = str_replace(array('$AS$', '$R$'), array($adaptation_set_id, $representation_id), $reprsentation_template);
    $rep_xml = $session_dir . '/Period' . $current_period . '/' . $adapt_dir . '/' . $rep_xml_dir . '.xml';

    if(!file_exists($rep_xml))
        return NULL;
    
    $xml = get_DOM($rep_xml, 'atomlist');
    if(!$xml)
        return NULL;
    
    $segment_index = 0;
    $moofs_per_segments = array();
    
    $timescale = $xml->getElementsByTagName('mdhd')->item(0)->getAttribute('timescale');
    $moofs = $xml->getElementsByTagName('moof');
    $truns = $xml->getElementsByTagName('trun');
    $cumulated_duration = 0;
    for($i=0; $i<$moofs->length; $i++) {
        $trun = $truns->item($i);
        
        $segment_duration = $segment_durations[$segment_index];
        $cumulated_duration += $trun->getAttribute('cummulatedSampleDuration') / $timescale;
        
        if($segment_duration == PHP_INT_MAX) {
            $moofs_per_segments[] = $moofs->length - $i + 1;
            break;
        }
        
        if($cumulated_duration > $segment_duration) {
            $moofs_per_segments[] = $i;
            $segment_index++;
            $cumulated_duration = 0;
        }
    }
    
    return $moofs_per_segments;
}