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

function RepresentationValidation_HbbTV_DVB(){
    global $hbbtv_conformance, $dvb_conformance, $session_dir, $mpd_dom,
            $current_period, $current_adaptation_set, $current_representation, 
            $period_timing_info, $adaptation_set_template, $reprsentation_template,$subtitle_segments_location, 
            $reprsentation_error_log_template, $string_info, $progress_report, $progress_xml;
    
    $rep_error_file = str_replace(array('$AS$', '$R$'), array($current_adaptation_set, $current_representation), $reprsentation_error_log_template);
    if(!($opfile = open_file($session_dir.'/Period'.$current_period.'/'.$rep_error_file.'.txt', 'a'))){
        echo "Error opening/creating HbbTV/DVB codec validation file: "."$session_dir.'/'.$rep_error_file".'.txt';
        return;
    }
    
    ## Representation checks
    $adapt_dir = str_replace('$AS$', $current_adaptation_set, $adaptation_set_template);
    $rep_dir = str_replace(array('$AS$', '$R$'), array($current_adaptation_set, $current_representation), $reprsentation_template);
    $xml_rep = get_DOM($session_dir.'/Period'.$current_period.'/'.$adapt_dir.'/'.$rep_dir.'.xml', 'atomlist');
    if($xml_rep){
        if($dvb_conformance){
            $media_types = media_types($mpd_dom->getElementsByTagName('Period')->item($current_period));
            common_validation_DVB($opfile, $xml_rep, $media_types);
        }
        if($hbbtv_conformance){
            common_validation_HbbTV($opfile, $xml_rep);
        }
        
        seg_timing_common($opfile, $xml_rep);
        $bitrate_report_name = bitrate_report($xml_rep);
        $segment_duration_name = seg_duration_checks($opfile);        
        if ($period_timing_info[1] !== '' && $period_timing_info[1] !== 0){
            $checks = segmentToPeriodDurationCheck($xml_rep);
            if(!$checks[0]){
                fwrite($opfile, "###'HbbTV-DVB DASH Validation Requirements check violated: Section 'Periods' - The accumulated duration of the segments [".$checks[1]. "seconds] in the representation does not match the period duration[".$checks[2]."seconds].\n'");
            }
        }
    }
    
    ## For reporting
    $search = file_get_contents($session_dir . '/Period' . $current_period . '/' . $rep_error_file . '.txt'); //Search for errors within log file
    if (strpos($search, "###") === false){
        if(strpos($search, "Warning") === false && strpos($search, "WARNING") === false){
            $progress_xml->Results[0]->Period[$current_period]->Adaptation[$current_adaptation_set]->Representation[$current_representation] = "noerror";
            $file_location[] = "noerror";
        }
        else{
            $progress_xml->Results[0]->Period[$current_period]->Adaptation[$current_adaptation_set]->Representation[$current_representation] = "warning";
            $file_location[] = "warning";
        }
    }
    else{
        $progress_xml->Results[0]->Period[$current_period]->Adaptation[$current_adaptation_set]->Representation[$current_representation] = "error";
        $file_location[] = "error";
    }
    $progress_xml->asXml(trim($session_dir . '/' . $progress_report));
    
    add_remove_images('REMOVE');
    $hbbtv_string_info = "<img id=\"segmentReport\" src=\"$segment_duration_name\" width=\"650\" height=\"350\">" .
                         "<img id=\"bitrateReport\" src=\"$bitrate_report_name\" width=\"650\" height=\"350\"/>\n";
    add_remove_images('ADD', $hbbtv_string_info);
    
    return $file_location;
}

function add_remove_images($request, $hbbtv_string_info=NULL) {
    global $string_info;
    
    if($request == 'ADD') {
        $index = strpos($string_info, '</body>');
        $string_info = substr($string_info, 0, $index) . $hbbtv_string_info .substr($string_info, $index);
    }
    elseif($request == 'REMOVE') {
        $start_index = strpos($string_info, '<img');
        $end_index = strpos($string_info, '>', $start_index);
        
        while($start_index !== FALSE) {
            $string_info = substr($string_info, 0, $start_index) . substr($string_info, $end_index+1);
            $start_index = strpos($string_info, '<img');
        }
    }
}

function HbbTV_DVB_flags(){
    global $additional_flags, $mpd_features, $hbbtv_conformance, $dvb_conformance, 
            $current_period, $current_adaptation_set, $current_representation;
    
    $adapt = $mpd_features['Period'][$current_period]['AdaptationSet'][$current_adaptation_set];
    $rep = $adapt['Representation'][$current_representation];
    
    ## General
    $additional_flags .= ($hbbtv_conformance) ? ' -hbbtv' : '';
    $additional_flags .= ($hbbtv_conformance) ? ' -isolive' : '';
    $additional_flags .= ($dvb_conformance) ? ' -dvb' : '';
    
    ## Framerate checks
    if($adapt['frameRate'] == NULL && $rep['frameRate'] == NULL)
        $additional_flags .= ' -framerate 0';
    else
        $additional_flags .= ($adapt['frameRate'] == NULL) ? ' -framerate ' . $rep['frameRate'] : ' -framerate ' . $adapt['frameRate'];
    
    ## Codec checks
    $codecs = ($adapt['codecs'] == NULL) ? $rep['codecs'] : $adapt['codecs'];
    $codec_arr = explode('.', $codecs);
    if((strpos($codecs, 'hev')!==FALSE || strpos($codecs, 'hvc')!==FALSE)) {
        if(!empty($codec_arr[1]))
            $additional_flags .= " -codecprofile " . $codec_arr[1];
        if(!empty($codec_arr[3]))
            $additional_flags .= " -codectier " . substr($codec_arr[3], 0, 1);
        if(!empty($codec_arr[3]) && strlen($codec_arr[3]) > 1)
            $additional_flags .= " -codeclevel " . substr($codec_arr[3], 1);
    }
    if(strpos($codecs, 'avc')!==FALSE){
        if(!empty($codec_arr[1]) && strlen($codec_arr[1]) > 1)
            $additional_flags .= " -codecprofile " . (string)hexdec(substr($codec_arr[1], 0, 2));
        if(!empty($codec_arr[1]) && strlen($codec_arr[1]) == 6)
            $additional_flags .= " -codeclevel " . (string)hexdec(substr($codec_arr[1], -2));
    }
    
    ## Content protection checks
    $content_protection_len = (!$adapt['ContentProtection']) ? sizeof($rep['ContentProtection']) : sizeof($adapt['ContentProtection']);
    if($content_protection_len > 0)
        $additional_flags .= ' -dash264enc';
}

function is_subtitle(){
    global $mpd_features, $current_period, $current_adaptation_set, $current_representation, 
            $session_dir, $subtitle_segments_location;
    
    $subtitle_rep = false;
    $adapt = $mpd_features['Period'][$current_period]['AdaptationSet'][$current_adaptation_set];
    $rep = $adapt['Representation'][$current_representation];

    if(($adapt['mimeType'] == 'application/mp4' || $rep['mimeType'] == 'application/mp4') &&
       ($adapt['codecs'] == 'stpp' || $rep['codecs'] == 'stpp')){

        $contType = $adapt['contentType'];
        if($contType == ''){
            if(sizeof($adapt['ContentComponent']) != 0){
                $contComp = $adapt['ContentComponent'][0];
                if($contComp['contentType'] == 'text')
                    $subtitle_rep = true;
            }
            else
                $subtitle_rep = true;
        }
        elseif($contType == 'text')
            $subtitle_rep = true;
    }

    if($subtitle_rep){
        $subdir = str_replace(array('$AS$', '$R$'), array($current_adaptation_set, $current_representation), $subtitle_segments_location);
        $subtitle_dir = $session_dir . '/Period' . $current_period . '/Adapt' . $current_adaptation_set . 'rep' . $current_representation . '/Subtitles/';//$subdir;
        if (!file_exists($subtitle_dir)){
            $oldmask = umask(0);
            mkdir($subtitle_dir, 0777, true);
            umask($oldmask);
        }
    }
    
    return $subtitle_rep;
}

function common_validation_DVB($opfile, $xml_rep, $media_types){
    global $session_dir, $mpd_features, $current_period, $current_adaptation_set, $current_representation, $profiles,
            $sizearray, $reprsentation_template, $subtitle_segments_location;
    
    $adapt = $mpd_features['Period'][$current_period]['AdaptationSet'][$current_adaptation_set];
    $rep = $adapt['Representation'][$current_representation];
    
    ## Report on any resolutions used that are not in the tables of resoultions in 10.3 of the DVB DASH specification
    $res_result = resolutionCheck($adapt, $rep);
    if($res_result[0] == false)
        fwrite ($opfile, "Information on HbbTV-DVB DASH Validation Requirements conformance for DVB: Section 'Codec information' - Resolution value \"" . $res_result[1] . 'x' . $res_result[2] . "\" is not in the table of resolutions in 10.3 of the DVB DASH specification.\n");
    ##
    
    ## Check on the support of the provided codec
    // MPD part
    $codecs = $adapt['codecs'];
    if($codecs == ''){
        $codecs = $rep['codecs'];
    }
    
    if($codecs != ''){
        $codecs_arr = explode(',', $codecs);
        
        $str_info = '';
        foreach($codecs_arr as $codec){
            if(strpos($codec, 'avc') === FALSE && strpos($codec, 'hev1') === FALSE && strpos($codec, 'hvc1') === FALSE && 
                strpos($codec, 'mp4a') === FALSE && strpos($codec, 'ec-3') === FALSE && strpos($codec, 'ac-4') === FALSE &&
                strpos($codec, 'dtsc') === FALSE && strpos($codec, 'dtsh') === FALSE && strpos($codec, 'dtse') === FALSE && strpos($codec, 'dtsl') === FALSE &&
                strpos($codec, 'stpp') === FALSE){
                
                $str_info .= "$codec "; 
            }
        }
        
        if($str_info != '')
            fwrite($opfile, "###'HbbTV-DVB DASH Validation Requirements check violated for DVB: Section 'Codec information' - @codecs in the MPD is not supported by the specification', found $str_info.\n");
    }
    
    // Segment part
    $hdlr_type = $xml_rep->getElementsByTagName('hdlr')->item(0)->getAttribute('handler_type');
    $sdType = $xml_rep->getElementsByTagName("$hdlr_type".'_sampledescription')->item(0)->getAttribute('sdType');
    
    if(strpos($sdType, 'avc') === FALSE && strpos($sdType, 'hev1') === FALSE && strpos($sdType, 'hvc1') === FALSE && 
       strpos($sdType, 'mp4a') === FALSE && strpos($sdType, 'ec-3') === FALSE && strpos($sdType, 'ac-4') === FALSE &&
       strpos($sdType, 'dtsc') === FALSE && strpos($sdType, 'dtsh') === FALSE && strpos($sdType, 'dtse') === FALSE && strpos($sdType, 'dtsl') === FALSE &&
       strpos($sdType, 'stpp') === FALSE &&
       strpos($sdType, 'enc') === FALSE)
        fwrite($opfile, "###'HbbTV-DVB DASH Validation Requirements check violated for DVB: Section 'Codec information' - codec in the Segment is not supported by the specification', found $sdType.\n");
    
    $original_format = '';
    if(strpos($sdType, 'enc') !== FALSE){
        $sinf_boxes = $xml_rep->getElementsByTagName('sinf');
        if($sinf_boxes->length != 0){
            $original_format = $sinf_boxes->item(0)->getElementsByTagName('frma')->item(0)->getAttribute('original_format');
        }
    }
    
    if(strpos($sdType, 'avc') !== FALSE || strpos($original_format, 'avc') !== FALSE){
        $nal_units = $xml_rep->getElementsByTagName('NALUnit');
        foreach($nal_units as $nal_unit){
            if($nal_unit->getAttribute('nal_type') == '0x07'){
                if($nal_unit->getAttribute('profile_idc') != 100)
                    fwrite($opfile, "###'HbbTV-DVB DASH Validation Requirements check violated for DVB: Section 'Codec information' - profile used for the AVC codec in Segment is not supported by the specification Section 5.1.1', found " . $nal_unit->getAttribute('profile_idc') . ".\n");
            
                $level_idc = $nal_unit->getElementsByTagName('comment')->item(0)->getAttribute('level_idc');
                if($level_idc != 30 && $level_idc != 31 && $level_idc != 32 && $level_idc != 40)
                    fwrite($opfile, "###'HbbTV-DVB DASH Validation Requirements check violated for DVB: Section 'Codec information' - level used for the AVC codec in Segment is not supported by the specification Section 5.1.1', found $level_idc.\n");
            }
        }
    }
    elseif(strpos($sdType, 'hev1') !== FALSE || strpos($sdType, 'hvc1') !== FALSE || strpos($original_format, 'hev1') !== FALSE || strpos($original_format, 'hvc1') !== FALSE){
        $width = $xml_rep->getElementsByTagName("$hdlr_type".'_sampledescription')->item(0)->getAttribute('width');
        $height = $xml_rep->getElementsByTagName("$hdlr_type".'_sampledescription')->item(0)->getAttribute('height');
        $nal_units = $xml_rep->getElementsByTagName('NALUnit');
        foreach($nal_units as $nal_unit){
            $nalUnitType = $nal_unit->parentNode->getAttribute('nalUnitType');
            if($nalUnitType == '33'){
                if($nal_unit->getAttribute('gen_tier_flag') != '0')
                    fwrite($opfile, "###'HbbTV-DVB DASH Validation Requirements check violated for DVB: Section 'Codec information' - tier used for the HEVC codec in Segment is not supported by the specification Section 5.2.3', found " . $nal_unit->getAttribute('gen_tier_flag') . ".\n");
                if($nal_unit->getAttribute('bit_depth_luma_minus8') != 0 && $nal_unit->getAttribute('bit_depth_luma_minus8') != 2)
                    fwrite($opfile, "###'HbbTV-DVB DASH Validation Requirements check violated for DVB: Section 'Codec information' - bit depth used for the HEVC codec in Segment is not supported by the specification Section 5.2.3', found " . $nal_unit->getAttribute('bit_depth_luma_minus8') . ".\n");
                
                if((int)$width <= 1920 && (int)$height <= 1080){
                    if($nal_unit->getAttribute('gen_profile_idc') != '1' && $nal_unit->getAttribute('gen_profile_idc') != '2')
                        fwrite($opfile, "###'HbbTV-DVB DASH Validation Requirements check violated for DVB: Section 'Codec information' - profile used for the HEVC codec in Segment is not supported by the specification Section 5.2.3', found " . $nal_unit->getAttribute('gen_profile_idc') . ".\n");
                    if((int)($nal_unit->getAttribute('sps_max_sub_layers_minus1')) == 0 && (int)($nal_unit->getAttribute('gen_level_idc')) > 123)
                        fwrite($opfile, "###'HbbTV-DVB DASH Validation Requirements check violated for DVB: Section 'Codec information' - level used for the HEVC codec in Segment is not supported by the specification Section 5.2.3', found " . $nal_unit->getAttribute('gen_level_idc') . ".\n");
                }
                elseif((int)$width > 1920 && (int)$height > 1080){
                    if($nal_unit->getAttribute('gen_profile_idc') != '2')
                        fwrite($opfile, "###'HbbTV-DVB DASH Validation Requirements check violated for DVB: Section 'Codec information' - profile used for the HEVC codec in Segment is not supported by the specification Section 5.2.3', found " . $nal_unit->getAttribute('gen_profile_idc') . ".\n");
                    if((int)($nal_unit->getAttribute('sps_max_sub_layers_minus1')) == 0 && (int)($nal_unit->getAttribute('gen_level_idc')) > 153)
                        fwrite($opfile, "###'HbbTV-DVB DASH Validation Requirements check violated for DVB: Section 'Codec information' - level used for the HEVC codec in Segment is not supported by the specification Section 5.2.3', found " . $nal_unit->getAttribute('gen_level_idc') . ".\n");
                }
            }
        }
    }
    ##
    
    ## Subtitle checks
    if($adapt['mimeType'] == 'application/mp4' || $rep['mimeType'] == 'application/mp4'){
        if($adapt['codecs'] == 'stpp' || $rep['codecs'] == 'stpp'){
            if($hdlr_type != 'subt')
                fwrite($opfile, "###'HbbTV-DVB DASH Validation Requirements check violated for DVB: Section 'Subtitles' - For subtitle media, handler type in the Initialization Segment SHALL be \"subt\"', found \"$hdlr_type\".\n");
            
            $stpp = $xml_rep->getElementsByTagName('stpp');
            if($stpp->length == 0)
                fwrite($opfile, "###'HbbTV-DVB DASH Validation Requirements check violated for DVB: Section 'Subtitles' - For subtitle media, sample entry type SHALL be \"stpp (XMLSubtitleSampleEntry)\"', stpp not found.\n");
            else{
                if($stpp->item(0)->getAttribute('namespace') == '')
                    fwrite($opfile, "###'HbbTV-DVB DASH Validation Requirements check violated for DVB: Section 'Subtitles' - For subtitle media, namespaces SHALL be listed in the sample entry', namespace not found.\n");
            }
            
            ## EBU TECH 3381 - Section 5 - Layout check
            if(in_array('video', $media_types)){
                $tkhd = $xml_rep->getElementsByTagName('tkhd')->item(0);
                if((int)($tkhd->getAttribute('width')) != 0 || (int)($tkhd->getAttribute('height')) != 0)
                    fwrite($opfile, "Warning for HbbTV-DVB DASH Validation Requirements check for DVB: Section 'Subtitles' - EBU TECH 3381 Section 5- When the subtitle track is associated with a video object the width and height of the subtitle track SHOULD NOT be set', found width and/or height value set.\n");
            }
            ##
            
            ## Check the timing of segments and the EBU-TT-D files
            // EBU-TT-D
            $meta_str = '';
            $subt_times = array();
            $subtitle_loc = str_replace(array('$AS$', '$R$'), array($current_adaptation_set, $current_representation), $subtitle_segments_location);
            $files = glob($session_dir.'/'.$subtitle_loc.'*');
            natsort($files);
            foreach($files as $file){
                $file_loaded = simplexml_load_file($file);
                if($file_loaded){
                    $dom_abs = dom_import_simplexml($file_loaded);
                    $abs = new DOMDocument('1.0');
                    $dom_abs = $abs->importNode($dom_abs, true);
                    $dom_abs = $abs->appendChild($dom_abs);
                    $abs = $abs->getElementsByTagName('subtitle')->item(0);
                    
                    $tts = $abs = $abs->getElementsByTagName('tt');
                    $begin = '';
                    foreach($tts as $tt){
                        ##Check if metadata present; if yes, check if the profile is other than EBU-TT-D
                        if($tt->getElementsByTagName('metadata')->length != 0){
                            $metadata_children = $tt->getElementsByTagName('metadata')->item(0)->childNodes;
                            foreach($metadata_children as $metadata_child){
                                if($metadata_child->nodeType == XML_ELEMENT_NODE){
                                    if(strpos($metadata_child->nodeName, 'ebutt') === FALSE)
                                        $meta_str .= 'no '; 
                                }
                            }
                        }
                        ##
                        
                        $body = $tt->getElementsByTagName('body')->item(0);
                        $divs = $body->getElementsByTagName('div');
                        foreach($divs as $div){
                            $ps = $div->getElementsByTagName('p');
                            foreach($ps as $p){
                                $h_m_s_begin = $p->getAttribute('begin');
                                $h_m_s = explode(':', $h_m_s_begin);
                                $begin .= ' ' . (string)($h_m_s[0]*60*60 + $h_m_s[1]*60 + $h_m_s[2]);
                            }
                        }
                    }
                    
                    $subt_times[] = $begin;
                }
            }
            
            if($meta_str != '')
                fwrite($opfile, "###'HbbTV-DVB DASH Validation Requirements check violated for DVB: Section 'Subtitles' - Subtitle segments do not contain ISO-BMFF packaged EBU-TT-D but some other profile.\n");
            
            // Segments
            $type = $mpd_features['type'];
            $xml_num_moofs=$xml_rep->getElementsByTagName('moof')->length;
            $xml_trun=$xml_rep->getElementsByTagName('trun');
            $xml_tfdt=$xml_rep->getElementsByTagName('tfdt');
            
            $sidx_boxes = $xml_rep->getElementsByTagName('sidx');
            $subsegment_signaling = array();
            if($sidx_boxes->length != 0){
                foreach($sidx_boxes as $sidx_box){
                    $subsegment_signaling[] = (int)($sidx_box->getAttribute('referenceCount'));
                }
            }
            
            $xml_elst = $xml_rep->getElementsByTagName('elst');
            if($xml_elst->length == 0)
                $mediaTime = 0;
            else
                $mediaTime = (int)($xml_elst->item(0)->getElementsByTagName('elstEntry')->item(0)->getAttribute('mediaTime'));
            
            if($type != 'dynamic'){
                $timescale=$xml_rep->getElementsByTagName('mdhd')->item(0)->getAttribute('timescale');
                $sidx_index = 0;
                $cum_subsegDur=0;
                $s=0;
                for($j=0;$j<$xml_num_moofs;$j++){
                    if(empty($subsegment_signaling)){
                        $cum_subsegDur += (($xml_trun->item($j)->getAttribute('cummulatedSampleDuration'))/$timescale);
                        
                        $subt_begin = explode(' ', $subt_times[$j]);
                        for($be=1; $be<sizeof($subt_begin); $be++){
                            if($subt_begin[$be] > $cum_subsegDur)
                                fwrite($opfile, "Warning for HbbTV-DVB DASH Validation Requirements check for DVB: Section 'Subtitles' - 'For subtitle media, timing of subtitle $be with start \"" . $subt_begin[$be] . "\" lies completely outside the segment time period of the segment $j.\n");
                        }
                    }
                    else{
                        $ref_count = 1;
                        if($sidx_index < sizeof($subsegment_signaling))
                            $ref_count = $subsegment_signaling[$sidx_index];
                        
                        $cum_subsegDur += (($xml_trun->item($j)->getAttribute('cummulatedSampleDuration'))/$timescale);
                        $subsegment_signaling[$sidx_index] = $ref_count - 1;
                        if($subsegment_signaling[$sidx_index] == 0){
                            while($s <= $j){
                                $subt_begin = explode(' ', $subt_times[$s]);
                                for($be=1; $be<sizeof($subt_begin); $be++){
                                    if($subt_begin[$be] > $cum_subsegDur)
                                        fwrite($opfile, "Warning for HbbTV-DVB DASH Validation Requirements check for DVB: Section 'Subtitles' - 'For subtitle media, timing of subtitle $s with start \"" . $subt_begin[$be] . "\" lies completely outside the segment time period of the segment.\n");
                                }
                                $s++;
                            }
                            
                            $sidx_index++;
                        }
                    }
                }
            }
            ##
        }
    }
    
    ## Segment checks
    $moof_boxes = $xml_rep->getElementsByTagName('moof');
    // Section 4.3 on on-demand profile periods containing sidx boxes
    if(strpos($profiles[$current_period][$current_period][$current_adaptation_set][$current_representation], 'urn:mpeg:dash:profile:isoff-on-demand:2011') !== FALSE || strpos($profiles[$current_adaptation_set][$current_representation], 'urn:dvb:dash:profile:dvb-dash:isoff-ext-on-demand:2014') !== FALSE){
        if($xml_rep->getElementsByTagName('sidx')->length != 1)
            fwrite($opfile, "###'HbbTV-DVB DASH Validation Requirements check violated for DVB: Section 'Segments' - 'Segment includes features that are not required by the profile being validated against', found ". $xml_rep->getElementsByTagName('sidx')->length ." sidx boxes while according to Section 4.3 \"(For On Demand profile) The segment SHALL contain only one single Segment Index box ('sidx) for the entire segment\"'.\n");
        
        $seg_loc = str_replace(array('$AS$', '$R$'), array($current_adaptation_set, $current_representation), $reprsentation_template);
        if(count(glob($session_dir.'/'.$seg_loc.'/*')) - count(glob($session_dir.'/'.$seg_loc.'/*', GLOB_ONLYDIR)) != 1)
            fwrite($opfile, "###'DVB check violated: Section 4.3- (For On Demand profile) Each Representation SHALL have only one Segment', found more.\n");
    }
    
    // Section 4.3 on traf box count in moof boxes
    foreach($moof_boxes as $moof_box){
        if($moof_box->getElementsByTagName('traf')->length != 1)
            fwrite($opfile, "###'DVB check violated: Section 4.3- The movie fragment box ('moof') SHALL contain only one track fragment box ('traf')', found more than one.\n");
    }
    
    // Section 4.5 on segment and subsegment durations
    $sidx_boxes = $xml_rep->getElementsByTagName('sidx');
    $subsegment_signaling = array();
    if($sidx_boxes->length != 0){
        foreach($sidx_boxes as $sidx_box){
            $subsegment_signaling[] = (int)($sidx_box->getAttribute('referenceCount'));
        }
    }
    
    $timescale=$xml_rep->getElementsByTagName('mdhd')->item(0)->getAttribute('timescale');
    $num_moofs=$moof_boxes->length;
    $sidx_index = 0;
    $cum_subsegDur=0;
    for($j=0;$j<$num_moofs-1;$j++){
        $cummulatedSampleDuration=$xml_rep->getElementsByTagName('trun')->item($j)->getAttribute('cummulatedSampleDuration');
        $segDur=$cummulatedSampleDuration/$timescale;
        
        if(empty($subsegment_signaling) || (!empty($subsegment_signaling) && sizeof(array_unique($subsegment_signaling)) == 1 && in_array(0, $subsegment_signaling))){
            if($hdlr_type =='vide' && $segDur>15)
                fwrite($opfile, "###'DVB check violated Section 4.5: Where subsegments are not signalled, each video segment SHALL have a duration of not more than 15 seconds', segment ".($j+1)." found with duration ".$segDur." \n");
            if($hdlr_type =='soun' && $segDur>15)
                fwrite($opfile, "###'DVB check violated Section 4.5: Where subsegments are not signalled, each audio segment SHALL have a duration of not more than 15 seconds', segment ".($j+1)." found with duration ".$segDur." \n");
            
            if($segDur <1)
                fwrite($opfile, "###'DVB check violated Section 4.5: Segment duration SHALL be at least 1 second except for the last segment of a Period', segment ".($j+1)." found with duration ".$segDur." \n");
        }
        elseif(!empty($subsegment_signaling) && !in_array(0, $subsegment_signaling)){
            $ref_count = $subsegment_signaling[$sidx_index];
            $cum_subsegDur += $segDur;
            if($hdlr_type =='vide' && $segDur>15)
                fwrite($opfile, "###'DVB check violated Section 4.5: Each video subsegment SHALL have a duration of not more than 15 seconds', subsegment ".($j+1)." found with duration ".$segDur." \n");
            if($hdlr_type =='soun' && $segDur>15)
                fwrite($opfile, "###'DVB check violated Section 4.5: Each audio subsegment SHALL have a duration of not more than 15 seconds', subsegment ".($j+1)." found with duration ".$segDur." \n");
            
            $subsegment_signaling[$sidx_index] = $ref_count - 1;
            if($subsegment_signaling[$sidx_index] == 0){
                if($cum_subsegDur < 1)
                    fwrite($opfile, "###'DVB check violated Section 4.5: Segment duration SHALL be at least 1 second except for the last segment of a Period', segment ".($j+1)." found with duration ".$segDur." \n");
                
                $sidx_index++;
                $cum_subsegDur = 0;
            }
            
            // Section 5.1.2 on AVC content's SAP type
            if($hdlr_type == 'vide' && strpos($sdType, 'avc') !== FALSE){
                if($sidx_boxes->length != 0){
                    $subseg = $sidx_boxes->item($sidx_index)->getElementsByTagName('subsegment')->item(0);
                    if($subseg != NULL && $subseg->getAttribute('starts_with_SAP') == '1'){
                        $sap_type = $subseg->getAttribute('SAP_type');
                        if($sap_type != '1' && $sap_type != '2')
                            fwrite($opfile, "###'DVB check violated: Section 5.1.2- Segments SHALL start with SAP types of 1 or 2', found $sap_type.\n");
                    }
                }
            }
            //
        }
        else{
            $ref_count = $subsegment_signaling[$sidx_index];
            if($ref_count == 0){
                if($hdlr_type =='vide' && $segDur>15)
                    fwrite($opfile, "###'DVB check violated Section 4.5: Where subsegments are not signalled, each video segment SHALL have a duration of not more than 15 seconds', segment ".($j+1)." found with duration ".$segDur." \n");
                if($hdlr_type =='soun' && $segDur>15)
                    fwrite($opfile, "###'DVB check violated Section 4.5: Where subsegments are not signalled, each audio segment SHALL have a duration of not more than 15 seconds', segment ".($j+1)." found with duration ".$segDur." \n");
                
                if($segDur <1)
                    fwrite($opfile, "###'DVB check violated Section 4.5: Segment duration SHALL be at least 1 second except for the last segment of a Period', segment ".($j+1)." found with duration ".$segDur." \n");
                
                $sidx_index++;
            }
            else{
                $subsegment_signaling[$sidx_index] = $ref_count - 1;
                $cum_subsegDur += $segDur;
                if($hdlr_type =='vide' && $segDur>15)
                    fwrite($opfile, "###'DVB check violated Section 4.5: Each video subsegment SHALL have a duration of not more than 15 seconds', subsegment ".($j+1)." found with duration ".$segDur." \n");
                if($hdlr_type =='soun' && $segDur>15)
                    fwrite($opfile, "###'DVB check violated Section 4.5: Each audio subsegment SHALL have a duration of not more than 15 seconds', subsegment ".($j+1)." found with duration ".$segDur." \n");
                
                if($subsegment_signaling[$sidx_index] == 0){
                    $sidx_index++;
                    if($cum_subsegDur < 1)
                        fwrite($opfile, "###'DVB check violated Section 4.5: Segment duration SHALL be at least 1 second except for the last segment of a Period', segment ".($j+1)." found with duration ".$segDur." \n");
                    
                    $cum_subsegDur = 0;
                }
                
                // Section 5.1.2 on AVC content's SAP type
                if($hdlr_type == 'vide' && strpos($sdType, 'avc') !== FALSE){
                    if($sidx_boxes->length != 0){
                        $subseg = $sidx_boxes->item($sidx_index)->getElementsByTagName('subsegment')->item(0);
                        if($subseg != NULL && $subseg->getAttribute('starts_with_SAP') == '1'){
                            $sap_type = $subseg->getAttribute('SAP_type');
                            if($sap_type != '1' && $sap_type != '2')
                                fwrite($opfile, "###'DVB check violated: Section 5.1.2- Segments SHALL start with SAP types of 1 or 2', found $sap_type.\n");
                        }
                    }
                }
                //
            }
        }
        
        // Section 6.2 on HE_AACv2 and 6.5 on MPEG Surround audio content's SAP type
        if($hdlr_type == 'soun' && strpos($sdType, 'mp4a') !== FALSE){
            if($sidx_boxes->length != 0){
                $subsegments = $sidx_boxes->item($sidx_index)->getElementsByTagName('subsegment');
                if($subsegments->length != 0){
                    foreach($subsegments as $subsegment){
                        if($subsegment->getAttribute('starts_with_SAP') == '1'){
                            $sap_type = $subsegment->getAttribute('SAP_type');
                            if($sap_type != '1')
                                fwrite($opfile, "###'DVB check violated: Section 6.2/6.5- The content preparation SHALL ensure that each (Sub)Segment starts with a SAP type 1', found $sap_type.\n");
                        }
                    }
                }
            }
        }
        //
    }
    ##
    
    // Section 5.1.2 on AVC content's sample entry type
    if($hdlr_type == 'vide' && strpos($sdType, 'avc') !== FALSE){
        if($sdType != 'avc3' && $sdType != 'avc4')
            fwrite($opfile, "Warning for DVB check: Section 5.1.2- 'Content SHOULD be offered using Inband storage for SPS/PPS i.e. sample entries 'avc3' and 'avc4'.', found $sdType.\n");
        
        $vide_sd = $xml_rep->getElementsByTagName("$hdlr_type".'_sampledescription')->item(0);
        $nal_units = $vide_sd->getElementsByTagName('NALUnit');
        $sps_found = false;
        $pps_found = false;
        foreach($nal_units as $nal_unit){
            if($nal_unit->getAttribute('nal_type') == '0x07')
                $sps_found = true;
            if($nal_unit->getAttribute('nal_type') == '0x08')
                $pps_found = true;
        }
        
        if($sdType != 'avc3'){ // in AVC3 this data goes in the first sample of every fragment (i.e. the first sample in each mdat box).
            if(!$sps_found)
                fwrite($opfile, "###'DVB check violated: Section 5.1.2- All information necessary to decode any Segment chosen from the Representation SHALL be provided in the initialization Segment', SPS not found.\n");
            if(!$pps_found)
                fwrite($opfile, "###'DVB check violated: Section 5.1.2- All information necessary to decode any Segment chosen from the Representation SHALL be provided in the initialization Segment', PPS not found.\n");
        }
    }
    
    // Section 4.5 on subtitle segment sizes
    if($hdlr_type == 'subt'){
        $segsize_info = '';
        foreach($sizearray as $segsize){
            if($segsize > 512*1024)
                $segsize_info .= 'large ';
        }
        if($segsize_info != '')
            fwrite($opfile, "###'DVB check violated: Section 4.5- Subtitle segments SHALL have a maximum segment size of 512KB', found larger segment size.\n");
    }
}

function common_validation_HbbTV($opfile, $xml_rep){
    global $session_dir, $mpd_features, $current_period, $current_adaptation_set, $current_representation, $reprsentation_template;
    
    $adapt = $mpd_features['Period'][$current_period]['AdaptationSet'][$current_adaptation_set];
    $rep = $adapt['Representation'][$current_representation];
    
    ## Check on the support of the provided codec
    // MPD part
    $codecs = $adapt['codecs'];
    if($codecs == ''){
        $codecs = $rep['codecs'];
    }
    
    if($codecs != ''){
        $codecs_arr = explode(',', $codecs);
        
        $str_info = '';
        foreach($codecs_arr as $codec){
            if(strpos($codec, 'avc') === FALSE &&
                strpos($codec, 'mp4a') === FALSE && strpos($codec, 'ec-3')){
                
                $str_info .= "$codec "; 
            }
        }
        
        if($str_info != '')
            fwrite($opfile, "###'HbbTV-DVB DASH Validation Requirements check violated for HbbTV: Section 'Codec information' - @codecs in the MPD is not supported by the specification', found $str_info.\n");
    }
    
    // Segment part
    $hdlr_type = $xml_rep->getElementsByTagName('hdlr')->item(0)->getAttribute('handler_type');
    $sdType = $xml_rep->getElementsByTagName("$hdlr_type".'_sampledescription')->item(0)->getAttribute('sdType');
    
    if($hdlr_type=='vide' || $hdlr_type=='soun'){
        if(strpos($sdType, 'avc') === FALSE && 
           strpos($sdType, 'mp4a') === FALSE && strpos($sdType, 'ec-3') === FALSE &&
           strpos($sdType, 'enc') === FALSE)
            fwrite($opfile, "###'HbbTV-DVB DASH Validation Requirements check violated for HbbTV: Section 'Codec information' - codec in Segment is not supported by the specification', found $sdType.\n");
    }
    
    $original_format = '';
    if(strpos($sdType, 'enc') !== FALSE){
        $sinf_boxes = $xml_rep->getElementsByTagName('sinf');
        if($sinf_boxes->length != 0){
            $original_format = $sinf_boxes->item(0)->getElementsByTagName('frma')->item(0)->getAttribute('original_format');
        }
    }
    
    if(strpos($sdType, 'avc') !== FALSE || strpos($original_format, 'avc') !== FALSE){
        $width = $xml_rep->getElementsByTagName("$hdlr_type".'_sampledescription')->item(0)->getAttribute('width');
        $height = $xml_rep->getElementsByTagName("$hdlr_type".'_sampledescription')->item(0)->getAttribute('height');
        $nal_units = $xml_rep->getElementsByTagName('NALUnit');
        foreach($nal_units as $nal_unit){
            if($nal_unit->getAttribute('nal_type') == '0x07'){
                if((int)$width <= 720 && (int)$height <= 576){
                    if($nal_unit->getAttribute('profile_idc') != 77 && $nal_unit->getAttribute('profile_idc') != 100)
                        fwrite($opfile, "###'HbbTV-DVB DASH Validation Requirements check violated for HbbTV: Section 'Codec information' - profile used for the codec in Segment is not supported by the specification Section 7.3.1', found " . $nal_unit->getAttribute('profile_idc') . ".\n");
                    
                    $level_idc = $nal_unit->getElementsByTagName('comment')->item(0)->getAttribute('level_idc');
                    if($level_idc != 30)
                        fwrite($opfile, "###'HbbTV-DVB DASH Validation Requirements check violated for HbbTV: Section 'Codec information' - level used for the codec in Segment is not supported by the specification Section 7.3.1', found $level_idc.\n");
                }
                elseif((int)$width >= 720 && (int)$height >= 640){
                    if($nal_unit->getAttribute('profile_idc') != 100)
                        fwrite($opfile, "###'HbbTV-DVB DASH Validation Requirements check violated for HbbTV: Section 'Codec information' - profile used for the codec in Segment is not supported by the specification Section 7.3.1', found " . $nal_unit->getAttribute('profile_idc') . ".\n");
                    
                    $level_idc = $nal_unit->getElementsByTagName('comment')->item(0)->getAttribute('level_idc');
                    if($level_idc != 30 && $level_idc != 31 && $level_idc != 32 && $level_idc != 40)
                        fwrite($opfile, "###'HbbTV-DVB DASH Validation Requirements check violated for HbbTV: Section 'Codec information' - level used for the codec in Segment is not supported by the specification Section 7.3.1', found $level_idc.\n");
                }
            }
        }
    }
    ##
    ##Segment checks.
    $stsd = $xml_rep->getElementsByTagName('stsd')->item(0);
    $vide_sample=$stsd->getElementsByTagName('vide_sampledescription');
    $soun_sample=$stsd->getElementsByTagName('soun_sampledescription');
    if($vide_sample->length>0 && $soun_sample->length>0)
        fwrite($opfile, "###'HbbTV check violated Section E.2.3: Each Representation shall contain only one media component', found both video and audio samples\n");

    if($hdlr_type =='vide'){
        $avcC = $xml_rep->getElementsByTagName('avcC');
        if($avcC->length>0){
            $nals=$xml_rep->getElementsByTagName('NALUnit');
            foreach($nals as $nal_unit){
                if($nal_unit->getAttribute('nal_type') =='0x07')
                    $sps_found=1;
                if($nal_unit->getAttribute('nal_type') =='0x08')
                    $pps_found=1;
            }
            if($sdType != 'avc3'){
                if($sps_found!=1)
                    fwrite($opfile, "###'HbbTV check violated Section E.2.3: All info necessary to decode any Segment shall be provided in Initialization Segment', for AVC video, Sequence parameter set not found\n");
                if($pps_found!=1)
                    fwrite($opfile, "###'HbbTV check violated Section E.2.3: All info necessary to decode any Segment shall be provided in Initialization Segment', for AVC video, Picture parameter set not found \n");
            }
        }
        else{
            if($sdType != 'avc3')
                fwrite($opfile, "###'HbbTV check violated Section E.2.3: All info necessary to decode any Segment shall be provided in Initialization Segment', for video, AVC decoder config record not found \n");
        }
    }
    else if($hdlr_type =='soun'){
        $soun_sample=$xml_rep->getElementsByTagName('soun_sampledescription');
        $sdType=$soun_sample->item(0)->getAttribute('sdType');
        $samplingRate=$soun_sample->item(0)->getAttribute('sampleRate');    
        $xml_audioDec=$xml_rep->getElementsByTagName('DecoderSpecificInfo');
        if($xml_audioDec->length>0)
           $channelConfig=$xml_audioDec->item(0)->getAttribute('channelConfig');
        if($sdType==NULL  )
            fwrite($opfile, "###'HbbTV check violated Section E.2.3: All info necessary to decode any Segment shall be provided in Initialization Segment', for audio, sample description type not found \n");
        if($samplingRate==NULL)
            fwrite($opfile, "###'HbbTV check violated Section E.2.3: All info necessary to decode any Segment shall be provided in Initialization Segment', for audio, sampling rate not found \n");
        if($channelConfig==NULL)
            fwrite($opfile, "###'HbbTV check violated Section E.2.3: All info necessary to decode any Segment shall be provided in Initialization Segment', for audio, channel config in decoder specific info not found \n");

    }
    
    // Segment duration except the last one shall be at least one second
    $sidx_boxes = $xml_rep->getElementsByTagName('sidx');
    $subsegment_signaling = array();
    if($sidx_boxes->length != 0){
        foreach($sidx_boxes as $sidx_box){
            $subsegment_signaling[] = (int)($sidx_box->getAttribute('referenceCount'));
        }
    }
    
    $timescale=$xml_rep->getElementsByTagName('mdhd')->item(0)->getAttribute('timescale');
    $moof_boxes = $xml_rep->getElementsByTagName('moof');
    $num_moofs=$moof_boxes->length;
    $sidx_index = 0;
    $cum_subsegDur=0;
    for($j=0;$j<$num_moofs-1;$j++){
        $cummulatedSampleDuration=$xml_rep->getElementsByTagName('trun')->item($j)->getAttribute('cummulatedSampleDuration');
        $segDur=$cummulatedSampleDuration/$timescale;
        
        if(empty($subsegment_signaling) || (!empty($subsegment_signaling) && sizeof(array_unique($subsegment_signaling)) == 1 && in_array(0, $subsegment_signaling))){
            if($hdlr_type =='vide' && $segDur>15)
                fwrite($opfile, "###'HbbTV check violated Section E.2.3: Each video segment shall have a duration of not more than 15s', segment ".($j+1)." found with duration ".$segDur." \n");
            if($hdlr_type =='soun' && $segDur>15)
                fwrite($opfile, "###'HbbTV check violated Section E.2.3: Each audio segment shall have a duration of not more than 15s', segment ".($j+1)." found with duration ".$segDur." \n");
            
            if($segDur <1)
                fwrite($opfile, "###'HbbTV check violated Section E.2.3: Segments shall be at least 1s long except last segment of Period', segment ".($j+1)." found with duration ".$segDur." \n");
        }
        elseif(!empty($subsegment_signaling) && !in_array(0, $subsegment_signaling)){
            $ref_count = $subsegment_signaling[$sidx_index];
            $cum_subsegDur += $segDur;
            if($hdlr_type =='vide' && $segDur>15)
                fwrite($opfile, "###'HbbTV check violated Section E.2.3: Each video segment shall have a duration of not more than 15s', segment ".($j+1)." found with duration ".$segDur." \n");
            if($hdlr_type =='soun' && $segDur>15)
                fwrite($opfile, "###'HbbTV check violated Section E.2.3: Each audio segment shall have a duration of not more than 15s', segment ".($j+1)." found with duration ".$segDur." \n");
            
            $subsegment_signaling[$sidx_index] = $ref_count - 1;
            if($subsegment_signaling[$sidx_index] == 0){
                if($cum_subsegDur < 1)
                    fwrite($opfile, "###'HbbTV check violated Section E.2.3: Segments shall be at least 1s long except last segment of Period', segment ".($j+1)." found with duration ".$segDur." \n");
                
                $sidx_index++;
                $cum_subsegDur = 0;
            }
        }
        else{
            $ref_count = $subsegment_signaling[$sidx_index];
            if($ref_count == 0){
                if($hdlr_type =='vide' && $segDur>15)
                    fwrite($opfile, "###'HbbTV check violated Section E.2.3: Each video segment shall have a duration of not more than 15s', segment ".($j+1)." found with duration ".$segDur." \n");
                if($hdlr_type =='soun' && $segDur>15)
                    fwrite($opfile, "###'HbbTV check violated Section E.2.3: Each audio segment shall have a duration of not more than 15s', segment ".($j+1)." found with duration ".$segDur." \n");
                
                if($segDur <1)
                    fwrite($opfile, "###'HbbTV check violated Section E.2.3: Segments shall be at least 1s long except last segment of Period', segment ".($j+1)." found with duration ".$segDur." \n");
                
                $sidx_index++;
            }
            else{
                $subsegment_signaling[$sidx_index] = $ref_count - 1;
                $cum_subsegDur += $segDur;
                if($hdlr_type =='vide' && $segDur>15)
                    fwrite($opfile, "###'HbbTV check violated Section E.2.3: Each video segment shall have a duration of not more than 15s', segment ".($j+1)." found with duration ".$segDur." \n");
                if($hdlr_type =='soun' && $segDur>15)
                    fwrite($opfile, "###'HbbTV check violated Section E.2.3: Each audio segment shall have a duration of not more than 15s', segment ".($j+1)." found with duration ".$segDur." \n");
                
                if($subsegment_signaling[$sidx_index] == 0){
                    $sidx_index++;
                    if($cum_subsegDur < 1)
                        fwrite($opfile, "###'HbbTV check violated Section E.2.3: Segments shall be at least 1s long except last segment of Period', segment ".($j+1)." found with duration ".$segDur." \n");
                    
                    $cum_subsegDur = 0;
                }
            }
        }
    }
    
    $seg_loc = str_replace(array('$AS$', '$R$'), array($current_adaptation_set, $current_representation), $reprsentation_template);
    if($mpd_features['type'] == 'dynamic' && count(glob($session_dir.'/'.$seg_loc.'/*')) == 1)
        fwrite($opfile, "###'HbbTV-DVB DASH Validation Requirements check violated for HbbTV: Section 'Segments' - 'Segment includes features that are not required by the profile being validated against', found only segment in the representation while MPD@type is dynamic.\n");
}

// Report on any resolutions used that are not in the tables of resoultions in 10.3 of the DVB DASH specification
function resolutionCheck($adapt, $rep){
    $conformant = true;
    
    $progressive_width  = array('1920', '1600', '1280', '1024', '960', '852', '768', '720', '704', '640', '512', '480', '384', '320', '192', '3840', '3200', '2560');
    $progressive_height = array('1080', '900',  '720',  '576',  '540', '480', '432', '404', '396', '360', '288', '270', '216', '180', '108', '2160', '1800', '1440');
    
    $interlaced_width  = array('1920', '704', '544', '352');
    $interlaced_height = array('1080', '576', '576', '288');
    
    $scanType = $adapt['scanType'];
    if($scanType == ''){
        $scanType = $rep['scanType'];
        
        if($scanType == '')
            $scanType = 'progressive';
    }
    
    $width = $adapt['width'];
    $height = $adapt['height'];
    if($width == '' && $height == ''){
        $width = $rep['width'];
        $height = $rep['height'];
        
        if($width != '' && $height != ''){
            if($scanType == 'progressive'){
                $ind1 = array_search($width, $progressive_width);
                if($ind1 !== FALSE){
                    if($height != $progressive_height[$ind1])
                        $conformant = false;
                }
            }
            elseif($scanType == 'interlaced'){
                $ind1 = array_search($width, $interlaced_width);
                if($ind1 !== FALSE){
                    if($height != $interlaced_height[$ind1])
                        $conformant = false;
                }
            }
        }
    }
    
    return array($conformant, $width, $height);
}

function seg_timing_common($opfile, $xml_rep){
    global $mpd_features;
    
    $xml_num_moofs=$xml_rep->getElementsByTagName('moof')->length;
    $xml_trun=$xml_rep->getElementsByTagName('trun');
    $xml_tfdt=$xml_rep->getElementsByTagName('tfdt');
    
    ## Consistency check of the start times within the segments with the timing indicated by the MPD
    // MPD information
    $mpd_timing = mdp_timing_info();
    
    // Segment information
    $type = $mpd_features['type'];
    
    $sidx_boxes = $xml_rep->getElementsByTagName('sidx');
    $subsegment_signaling = array();
    if($sidx_boxes->length != 0){
        foreach($sidx_boxes as $sidx_box){
            $subsegment_signaling[] = (int)($sidx_box->getAttribute('referenceCount'));
        }
    }
    
    $xml_elst = $xml_rep->getElementsByTagName('elst');
    if($xml_elst->length == 0){
        $mediaTime = 0;
    }
    else{
        $mediaTime = (int)($xml_elst->item(0)->getElementsByTagName('elstEntry')->item(0)->getAttribute('mediaTime'));
    }
    
    $timescale=$xml_rep->getElementsByTagName('mdhd')->item(0)->getAttribute('timescale');
    $sidx_index = 0;
    $cum_subsegDur=0;
    for($j=0;$j<$xml_num_moofs;$j++){
        ## Checking for gaps
        if($j > 0){
            $cummulatedSampleDurFragPrev=$xml_trun->item($j-1)->getAttribute('cummulatedSampleDuration');
            $decodeTimeFragPrev=$xml_tfdt->item($j-1)->getAttribute('baseMediaDecodeTime');
            $decodeTimeFragCurr=$xml_tfdt->item($j)->getAttribute('baseMediaDecodeTime');
            
            if($decodeTimeFragCurr!=$decodeTimeFragPrev+$cummulatedSampleDurFragPrev){
                fprintf($opfile, "###'HbbTV-DVB DASH Validation Requirements check violated: Section 'Segments' - A gap in the timing within the segments of the Representation found at segment number ".($j+1)."\n");
            }
        }
        ##
        
        if(!empty($mpd_timing) && $type != 'dynamic'){ //Empty means that there is no mediaPresentationDuration attribute in which case the media presentation duration is unknown.
            $decodeTime = $xml_tfdt->item($j)->getAttribute('baseMediaDecodeTime');
            $compTime = $xml_trun->item($j)->getAttribute('earliestCompositionTime');
            
            $segmentTime = ($decodeTime + $compTime - $mediaTime)/$timescale;
            if(empty($subsegment_signaling)){
                if($j < sizeof($mpd_timing)){
                    if(abs(($segmentTime - $mpd_timing[$j])/$mpd_timing[$j]) > 0.00001)
                        fprintf($opfile, "###'HbbTV-DVB DASH Validation Requirements check violated: Section 'Segments' - Start time \"$segmentTime\" within the segment " . ($j+1) . " is not consistent with the timing indicated by the MPD \"$mpd_timing[$j]\".\n");
                }
            }
            else{
                $ref_count = 1;
                if($sidx_index < sizeof($subsegment_signaling))
                    $ref_count = $subsegment_signaling[$sidx_index];
                
                if($cum_subsegDur == 0 && $sidx_index < sizeof($mpd_timing)){
                    if(abs(($segmentTime - $mpd_timing[$sidx_index])/$mpd_timing[$sidx_index]) > 0.00001)
                        fprintf($opfile, "###'HbbTV-DVB DASH Validation Requirements check violated: Section 'Segments' - Start time \"$segmentTime\" within the segment " . ($sidx_index+1) . " is not consistent with the timing indicated by the MPD \"$mpd_timing[$sidx_index]\".\n");
                }
            
                $cummulatedSampleDuration=$xml_trun->item($j)->getAttribute('cummulatedSampleDuration');
                $segDur=$cummulatedSampleDuration/$timescale;
                $cum_subsegDur += $segDur;
                $subsegment_signaling[$sidx_index] = $ref_count - 1;
                if($subsegment_signaling[$sidx_index] == 0){
                    $sidx_index++;
                    $cum_subsegDur = 0;
                }
            }
        }
    }
    ##
}

function bitrate_report($xml_rep){
    global $session_dir, $mpd_features, $current_period, $current_adaptation_set, $current_representation, 
            $sizearray, $segment_duration_array, $reprsentation_template;
    
    $bandwidth = $mpd_features['Period'][$current_period]['AdaptationSet'][$current_adaptation_set]['Representation'][$current_representation]['bandwidth'];
    
    $sidx_boxes = $xml_rep->getElementsByTagName('sidx');
    $subsegment_signaling = array();
    if($sidx_boxes->length != 0){
        foreach($sidx_boxes as $sidx_box){
            $subsegment_signaling[] = (int)($sidx_box->getAttribute('referenceCount'));
        }
    }
    
    $timescale=$xml_rep->getElementsByTagName('mdhd')->item(0)->getAttribute('timescale');
    $num_moofs=$xml_rep->getElementsByTagName('moof')->length;
    $bitrate_info = '';
    $segment_duration_array = array();
    $sidx_index = 0;
    $cum_subsegDur = 0;
    // Here 2 possible cases are considered for sidx -subsegment signalling.
    //First case is for no sidx box.
    if(empty($subsegment_signaling)){
        for($j=0;$j<$num_moofs;$j++){
            $cummulatedSampleDuration=$xml_rep->getElementsByTagName('trun')->item($j)->getAttribute('cummulatedSampleDuration');
            $segDur=$cummulatedSampleDuration/$timescale;
            $segSize = $sizearray[$j];
            $segment_duration_array[] = round($segDur, 2);
            $bitrate_info = $bitrate_info . (string)($segSize*8/$segDur) . ',';
        }
    }
    //Secondly, sidx exists with non-zero reference counts- 1) all segments have subsegments (referenced by some sidx boxes) 2) only some segments have subsegments. 
    else{
        for($j=0;$j<$num_moofs;$j++){
            if($sidx_index>sizeof($subsegment_signaling)-1)
                $ref_count=1;// This for case 2 of case 2.
            else
                $ref_count = $subsegment_signaling[$sidx_index];

            $cummulatedSampleDuration=$xml_rep->getElementsByTagName('trun')->item($j)->getAttribute('cummulatedSampleDuration');
            $segDur=$cummulatedSampleDuration/$timescale;
            $cum_subsegDur += $segDur;
            
            $subsegment_signaling[$sidx_index] = $ref_count - 1;
            if($subsegment_signaling[$sidx_index] == 0){
                $segSize = $sizearray[$sidx_index];
                $bitrate_info = $bitrate_info . (string)($segSize*8/$cum_subsegDur) . ',';
                $segment_duration_array[] = round($cum_subsegDur);
                $sidx_index++;
                $cum_subsegDur = 0;
            }
        }
    }
    
    $bitrate_info = substr($bitrate_info, 0, strlen($bitrate_info)-2);
    $bitrate_report_name = str_replace(array('$AS$', '$R$'), array($current_adaptation_set, $current_representation), $reprsentation_template) . '.png';
    $location = $session_dir. '/Period' . $current_period . '/' . $bitrate_report_name;
    $command="cd $session_dir && python bitratereport.py $bitrate_info $bandwidth $location";
    exec($command);
    //chmod($session_dir.'/'.$bitrate_report_name, 777);
    
    return $bitrate_report_name;
}

function seg_duration_checks($opfile){
    global $session_dir, $mpd_features, $current_period, $current_adaptation_set, $current_representation, $segment_duration_array, $adaptation_set_template, $reprsentation_template;

    $period = $mpd_features['Period'][$current_period];
    $adapt_set = $period['AdaptationSet'][$current_adaptation_set];
    $rep = $adapt_set['Representation'][$current_representation];
    $adapt_id = $current_adaptation_set + 1;
    $rep_id = ($rep['id'] != NULL) ? $rep['id'] : $current_representation+1;

    if(sizeof($rep['SegmentTemplate']) != 0)//only if there is a segment template in the representation get the timescale and duration
        $seg_template = $rep['SegmentTemplate'][0];
    elseif(sizeof($adapt_set['SegmentTemplate']) != 0)
        $seg_template = $adapt_set['SegmentTemplate'][0];
    elseif (sizeof($period['SegmentTemplate']) != 0)
            $seg_template = $period['SegmentTemplate'][0];
    else
        $MPD_duration_sec = 'Not_Set';

    if($MPD_duration_sec != 'Not_Set'){ //if there is a segment template
        $duration_diff_array = array(); //array to hold the duration of segments present in atom file that are different from that advertised in the MPD
        $duration = $seg_template['duration'];
        $timescale = $seg_template['timescale'];
        if(($duration != '') && ($timescale != '')){
            $MPD_duration_sec = round(($duration / $timescale), 2);
            $ind = 0;
            foreach ($segment_duration_array as $atom_seg_duration){
                $ind++;
                if($atom_seg_duration != $MPD_duration_sec){
                    $duration_diff_array[$ind] = $atom_seg_duration;
                }
            }
        }
        else{
            if(sizeof($seg_template['SegmentTimeline']) != 0){
                $MPD_duration_sec_array = array();
                $seg_tline = $seg_template['SegmentTimeline'][0];
                $seg_tline_num = sizeof($seg_tline['S']);
                for ($i = 0; $i < $seg_tline_num; $i++ ){
                    $seg_instance = $seg_tline['S'][$i];
                    $repetition = $seg_instance['r'];
                    $duration = $seg_instance['d'];
                    if($repetition == -1){
                        $MPD_duration_sec = round(($duration / $timescale), 2);
                        $ind = 0;
                        foreach ($segment_duration_array as $atom_seg_duration){
                            $ind++;
                            if($atom_seg_duration != $MPD_duration_sec){
                                $duration_diff_array[$ind] = $atom_seg_duration;
                            }
                        }
                    }
                    else{
                        if($repetition == ''){
                            $repetition = 1;
                        }
                        for($i = 0; $i< $repetition; $i++){
                            $MPD_duration_sec_array[] = round(($duration / $timescale), 2);
                        }
                    }
                }
                for($j = 0; $j < count($MPD_duration_sec_array); $j++ ){
                    if($MPD_duration_sec_array[$j] != $segment_duration_array[$j]){
                        $duration_diff_array[$j] = $segment_duration_array[$j];
                    }
                }
            }
            else{
                $MPD_duration_sec = 'Not_Set';
            }
        }
    }
    
    $total_seg_duration = array_sum($segment_duration_array);
    if(!empty($duration_diff_array)){
        if(empty($MPD_duration_sec_array)){
            $duration_diff_k_v  = implode(' ', array_map(function ($v, $k) { return sprintf(" seg: '%s' -> duration: '%s' sec \n", $k, $v); },
            $duration_diff_array,array_keys($duration_diff_array)));
            fwrite($opfile, "Information on HbbTV-DVB DASH Validation Requirements: Section 'Duration Self consistency' - In Adaptation Set ".$adapt_id.", Representation with 'id' : ".$rep_id." the following segments were found to have a different"
                    . " duration from the one advertised in the MPD (".$MPD_duration_sec." sec) :\n".$duration_diff_k_v.".\n");
        }
        else{
            $duration_diff_k_v  = implode(' ', array_map(function ($v, $k) { return sprintf(" seg: '%s' -> duration: '%s' sec \n", $k, $v); },
            $duration_diff_array,array_keys($duration_diff_array)));
            fwrite($opfile, "Information on HbbTV-DVB DASH Validation Requirements: Section 'Duration Self consistency' - In Adaptation Set ".$adapt_id.", Representation with 'id' : ".$rep_id." the following segments were found to have a different"
                    . " duration from the one advertised in the MPD:\n".$duration_diff_k_v.".\n");
        }
    }
    
    //load the atom xml file into a dom Document
    $adapt_loc = str_replace('$AS$', $current_adaptation_set, $adaptation_set_template);
    $rep_loc = str_replace(array('$AS$', '$R$'), array($current_adaptation_set, $current_representation), $reprsentation_template);
    $xml_file_location = $session_dir.'/'.$adapt_loc.'/'.$rep_loc.'.xml'; 
    $abs = get_DOM($xml_file_location, 'atomlist'); // load mpd from url
    if($abs){
        if(($abs->getElementsByTagName('mehd')->length != 0) && ($abs->getElementsByTagName('mvhd')->length != 0)){
            $fragment_duration = $abs->getElementsByTagName('mehd')->item(0)->getAttribute('fragmentDuration');
            $fragment_duration_sec = (float)($fragment_duration) / (float)($abs->getElementsByTagName('mvhd')->item(0)->getAttribute('timeScale'));

            if($abs->getElementsByTagName('hdlr')->length != 0)
                $handler_type = $abs->getElementsByTagName('hdlr')->item(0)->getAttribute('handler_type');
            else
                $handler_type = 'missing';

            if(abs(($fragment_duration_sec-$total_seg_duration)/$total_seg_duration) > 0.00001){
                if($handler_type == 'vide'){
                    fwrite($opfile, "Warning on HbbTV-DVB DASH Validation Requirements: Section 'Duration Self consistency' - The fragment duration of video type (".$fragment_duration_sec." sec) is different from the sum of all segment durations (".$total_seg_duration." sec) in Adaptation Set: "
                            .$adapt_id." Representation with 'id' : ".$rep_id. ".\n");
                }
                elseif($handler_type == 'soun'){
                    fwrite($opfile, "###'HbbTV-DVB DASH Validation Requirements check violated: Section 'Duration Self consistency' - The fragment duration of audio type (".$fragment_duration_sec." sec) is different from the sum of all segment durations (".$total_seg_duration." sec) in Adaptation Set: "
                            .$adapt_id." Representation with 'id' : ".$rep_id. ".'\n");
                }
                elseif ($handler_type == 'missing'){
                    fwrite($opfile, "Warning on HbbTV-DVB DASH Validation Requirements: Section 'Duration Self consistency' - The fragment duration of 'unknown' type (".$fragment_duration_sec." sec) is different from the sum of all segment durations (".$total_seg_duration." sec) in Adaptation Set: "
                            .$adapt_id." Representation with 'id' : ".$rep_id. ".\n");
                }
            }
        }
    }
    
    if(!empty($MPD_duration_sec_array)){
        $MPD_duration_sec = 'Not_Set'; //to avoid giving an array to the python code as an argument
    }
    $atm_duration_array_str = implode(',', $segment_duration_array);
    $location = $session_dir.'/Period' . $current_period . '/' . $rep_loc . '_.png';
    $command = "cd $session_dir && python seg_duration.py  $atm_duration_array_str $MPD_duration_sec $location";
    exec($command);

    // Check if the average segment duration is consistent with that of the duration information in the MPD
    $num_segments = sizeof($segment_duration_array);
    $average_segment_duration = (array_sum($segment_duration_array) ) / ($num_segments);
    if($MPD_duration_sec != 'Not_Set'){
        if(abs((round($average_segment_duration, 2)-round($MPD_duration_sec, 2)) / round($MPD_duration_sec, 2)) > 0.00001)
            fwrite($opfile, "###'HbbTV-DVB DASH Validation Requirements check violated: Section 'Duration Self consistency' - The average segment duration is not consistent with the durations advertised by the MPD " . round($average_segment_duration, 2) . ' vs. ' . round($MPD_duration_sec, 2) . ".'\n");
    }
    
    return $rep_loc . '_.png';
}

function segmentToPeriodDurationCheck($xml_rep){
    global $period_timing_info;
    
    $mdhd=$xml_rep->getElementsByTagName('mdhd')->item(0);
    $timescale=$mdhd->getAttribute('timescale');
    $num_moofs=$xml_rep->getElementsByTagName('moof')->length;
    $totalSegmentDuration = 0;
    for ( $j = 0; $j < $num_moofs ; $j++ ){
        $trun = $xml_rep->getElementsByTagName('trun')->item($j);
        $cummulatedSampleDuration = $trun->getAttribute('cummulatedSampleDuration');
        $segDur = ( $cummulatedSampleDuration * 1.00 ) / $timescale;      
        $totalSegmentDuration += $segDur;
    }
    
    $period_duration = (float)$period_timing_info[1];
    if(abs((round($totalSegmentDuration, 2) - round($period_duration, 2)) / round($period_duration, 2)) > 0.00001)
        return [false, round($totalSegmentDuration, 2), round($period_duration, 2)];
    else
        return [true, round($totalSegmentDuration, 2), round($period_duration, 2)];
}