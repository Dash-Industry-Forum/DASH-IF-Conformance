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

function IOP_ValidateSegment() {
    global $session_dir, $current_period, $current_adaptation_set, $current_representation, 
           $adaptation_set_template, $reprsentation_template, $reprsentation_error_log_template, 
           $string_info, $progress_xml, $progress_report;
    
    $adapt_dir = str_replace('$AS$', $current_adaptation_set, $adaptation_set_template);
    $rep_dir = str_replace(array('$AS$', '$R$'), array($current_adaptation_set, $current_representation), $reprsentation_template);
    $rep_xml = $session_dir . '/Period' . $current_period . '/' . $adapt_dir . '/' . $rep_dir . '.xml';
    
    if(!file_exists($rep_xml)){
        return;
    }
    
    $xml = get_DOM($rep_xml, 'atomlist');
    if(!$xml)
        return;

    $messages = '';
    $messages .= IOP_ValidateSegment_Common($xml);
    $messages .= IOP_ValidateSegment_OnDemand($xml);

    $rep_log_file = str_replace(array('$AS$', '$R$'), array($current_adaptation_set, $current_representation), $reprsentation_error_log_template);
    if(!($opfile = open_file($session_dir.'/Period'.$current_period.'/'.$rep_log_file.'.txt', 'a'))){
        echo 'Error opening/creating DASH-IF IOP Representation conformance check file: '.$session_dir.'/'.$rep_log_file.'.txt';
        return;
    }
    
    fwrite($opfile, $messages);
    fclose($opfile);
    
    ## For reporting
    $search = file_get_contents($session_dir . '/Period' . $current_period . '/' . $rep_log_file . '.txt'); //Search for errors within log file
    if (strpos($search, "DASH-IF IOP 4.3 check violated") === FALSE){
        $progress_xml->Results[0]->Period[$current_period]->Adaptation[$current_adaptation_set]->Representation[$current_representation] = "noerror";
        $file_location[] = "noerror";
    }
    else{
        $progress_xml->Results[0]->Period[$current_period]->Adaptation[$current_adaptation_set]->Representation[$current_representation] = "error";
        $file_location[] = "error";
    }
    $progress_xml->asXml(trim($session_dir . '/' . $progress_report));
    
    return $file_location;
}

function IOP_ValidateSegment_Common($xml) {
    global $mpd_features, $profiles, $current_period, $current_adaptation_set, $current_representation;
    
    $messages = '';
    
    $period = $mpd_features['Period'][$current_period];
    $adaptation_set = $period['AdaptationSet'][$current_adaptation_set];
    $representation = $adaptation_set['Representation'][$current_representation];
    $codecs = ($representation['codecs']) ? $representation['codecs'] : $adaptation_set['codecs'];
    $mimeType = ($representation['mimeType']) ? $representation['mimeType'] : $adaptation_set['mimeType'];
    $bitstreamSwitching = ($adaptation_set['bitstreamSwitching']) ? $adaptation_set['bitstreamSwitching'] : $period['bitstreamSwitching'];
    
    if($bitstreamSwitching == 'true') {
        if(strpos($mimeType, 'video') !== FALSE) {
            if(strpos($codecs, 'avc') !== FALSE) {
                if(strpos($codecs, 'avc3') === FALSE) {
                    $messages .= "DASH-IF IOP 4.3 check violated Section 6.2.5.2: \"For AVC video data, if the @bitstreamswitching flag is set to true, all Representations SHALL be encoded using avc3\", content encoded with $codecs for Period $current_period Adaptation Set $current_adaptation_set Representation $current_representation.\n";
                }
                
                $codec_box = $xml->getElementsByTagName('avcC');
                if($codec_box->length == 0) {
                    $messages .= "DASH-IF IOP 4.3 check violated Section 6.2.5.2: \"For AVC video data, if the @bitstreamswitching flag is set to true, all Representations SHALL include Initialitization Segment containing 'avcC' box\", avcC not found for Period $current_period Adaptation Set $current_adaptation_set Representation $current_representation.\n";
                }
                else {
                    $sps_found = FALSE;
                    $pps_found = FALSE;
                    $nal_boxes = $codec_box->item(0)->getElementsByTagName('NALUnit');
                    for($i=0; $i<$nal_boxes->length; $i++) {
                        $nal_box = $nal_boxes->item($i);
                        if($nal_box->getAttribute('nal_type') == '0x07') {
                            $sps_found = TRUE;
                        }
                        elseif($nal_box->getAttribute('nal_type') == '0x08') {
                            $pps_found = TRUE;
                        }
                    }
                    if(!$sps_found || !$pps_found) {
                        $messages .= "DASH-IF IOP 4.3 check violated Section 6.2.5.2: \"For AVC video data, if the @bitstreamswitching flag is set to true, all Representations SHALL include Initialitization Segment containing 'avcC' box containing Decoder Configuration Record containing SPS and PPS NALs\", SPS and/or PPS not found for Period $current_period Adaptation Set $current_adaptation_set Representation $current_representation.\n";
                    }
                }
            }
            elseif(strpos($codecs, 'hev') !== FALSE || strpos($codecs, 'hvc') !== FALSE) {
                if(strpos($codecs, 'hev1') === FALSE) {
                    $messages .= "DASH-IF IOP 4.3 check violated Section 6.2.5.2: \"For HEVC video data, if the @bitstreamswitching flag is set to true, all Representations SHALL be encoded using hev1\", content encoded with $codecs for Period $current_period Adaptation Set $current_adaptation_set Representation $current_representation.\n";
                }
                
                $codec_box = $xml->getElementsByTagName('hvcC');
                if($codec_box->length == 0) {
                    $messages .= "DASH-IF IOP 4.3 check violated Section 6.2.5.2: \"For HEVC video data, if the @bitstreamswitching flag is set to true, all Representations SHALL include Initialitization Segment containing 'hvcC' box\", hvcC not found for Period $current_period Adaptation Set $current_adaptation_set Representation $current_representation.\n";
                }
                else {
                    $vps_found = FALSE;
                    $sps_found = FALSE;
                    $pps_found = FALSE;
                    $nal_boxes = $codec_box->item(0)->getElementsByTagName('NALUnit');
                    for($i=0; $i<$nal_boxes->length; $i++) {
                        $nal_box = $nal_boxes->item($i);
                        if($nal_box->getAttribute('nal_unit_type') == 32) {
                            $vps_found = TRUE;
                        }
                        if($nal_box->getAttribute('nal_unit_type') == 33) {
                            $sps_found = TRUE;
                        }
                        elseif($nal_box->getAttribute('nal_unit_type') == 34) {
                            $pps_found = TRUE;
                        }
                    }
                    if(!$sps_found || !$pps_found || !$vps_found) {
                        $messages .= "DASH-IF IOP 4.3 check violated Section 6.2.5.2: \"For HEVC video data, if the @bitstreamswitching flag is set to true, all Representations SHALL include Initialitization Segment containing 'hvcC' box containing Decoder Configuration Record containing SPS, PPS and VPS NALs\", SPS and/or PPS and/or VPS not found for Period $current_period Adaptation Set $current_adaptation_set Representation $current_representation.\n";
                    }
                }
            }
            
            if(strpos($codecs, 'avc') != FALSE || strpos($codecs, 'hev') != FALSE || strpos($codecs, 'hvc') != FALSE) {
                $elst_boxes = $xml->getElementsByTagName('elst');
                if(!(strpos($profiles[$current_period][$current_adaptation_set][$current_representation], 'http://dashif.org/guidelines/dash-if-ondemand') !== FALSE ||
                   (strpos($profiles[$current_period][$current_adaptation_set][$current_representation], 'http://dashif.org/guidelines/dash') !== FALSE &&
                   strpos($profiles[$current_period][$current_adaptation_set][$current_representation], 'urn:mpeg:dash:profile:isoff-on-demand:2011') !== FALSE))) {
                    if($elst_boxes->length != 0) {
                        $messages .= "DASH-IF IOP 4.3 check violated Section 6.2.5.2: \"Edit lists SHALL NOT be present in video Adaptation Sets unless they are offered in On-Demand profile.\", edit list found for Period $current_period Adaptation Set $current_adaptation_set Representation $current_representation.\n";
                    }
                }
                
                $xml_trun=$xml->getElementsByTagName('trun');
                $xml_tfdt=$xml->getElementsByTagName('tfdt');
                $firstSampleCompTime=$xml_trun[0]->getAttribute('earliestCompositionTime');
                $firstSampleDecTime=$xml_tfdt[0]->getAttribute('baseMediaDecodeTime');
                if($firstSampleCompTime!=$firstSampleDecTime) {
                    $messages .= "DASH-IF IOP 4.3 check violated Section 6.2.5.2: \"Video media Segments SHALL set the first presented sample's composition time equal to the first decoded sample's decode time.\", not equal for Period $current_period Adaptation Set $current_adaptation_set Representation $current_representation.\n";
                }
            }
        }
    }
    
    return $messages;
}

function IOP_ValidateSegment_OnDemand($xml) {
    global $session_dir, $profiles, $current_period, $current_adaptation_set, $current_representation, $reprsentation_template;
    
    $messages = '';
    if(strpos($profiles[$current_period][$current_adaptation_set][$current_representation], 'http://dashif.org/guidelines/dash-if-ondemand') === FALSE) {
        return $messages;
    }
    
    $sidx_boxes = $xml->getElementsByTagName('sidx');
    $sidx_count = $sidx_boxes->length;
    if($sidx_count != 1) {
        $messages .= "DASH-IF IOP 4.3 check violated Section 3.10.3.2: \"Only a single 'sidx' SHALL be present\", $sidx_count sidx boxes found for Period $current_period Adaptation Set $current_adaptation_set Representation $current_representation.\n";
    }
    
    $rep_dir_name = str_replace(array('$AS$', '$R$'), array($current_adaptation_set, $current_representation), $reprsentation_template);
    if(!($opfile = open_file($session_dir . '/Period' .$current_period. '/' . $rep_dir_name . '.txt', 'r'))){
        echo "Error opening file: "."$session_dir.'/'.$rep_dir_name".'.txt';
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
    
    $segment_count = count(glob($session_dir.'/'.$rep_dir_name.'/*')) - count(glob($session_dir.'/'.$rep_dir_name.'/*', GLOB_ONLYDIR));
    if(!($self_initializing_segment_found && $segment_count == 1)) {
        fwrite($opfile, "DASH-IF IOP 4.3 check violated Section 3.10.3.2 \"Each Representation SHALL have one Segment that complies with Indexed Self-Initializing Media Segment\", found $segment_count Segment(s) and Indexed Self-Initializing Media Segment to be $self_initializing_segment_found.\n");
    }
    
    return $messages;
}