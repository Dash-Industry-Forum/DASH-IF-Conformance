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

function IOP_ValidateCross() {
    global $session_dir, $mpd_features, $current_period, $adaptation_set_error_log_template;
    
    $period = $mpd_features['Period'][$current_period];
    $adaptation_sets = $period['AdaptationSet'];
    
    foreach ($adaptation_sets as $adaptation_set_id => $adaptation_set) {
        $file_path = str_replace('$AS$', $adaptation_set_id, $adaptation_set_error_log_template) . '.txt';
        $opfile = open_file($session_dir . '/Period' .$current_period.'/' . $file_path, 'a+b');

        $messages = IOP_ValidateCross_AVC_HEVC($adaptation_set, $adaptation_set_id);
        
        fwrite($opfile, $messages);
        fclose($opfile);
    }
}

function IOP_ValidateCross_AVC_HEVC($adaptation_set, $adaptation_set_id) {
    global $mpd_features, $session_dir, $current_period, $adaptation_set_template, $reprsentation_template;
    
    $messages = '';
    
    $period = $mpd_features['Period'][$current_period];
    $representations = $adaptation_set['Representation'];
    $bitstreamSwitching = ($adaptation_set['bitstreamSwitching']) ? $adaptation_set['bitstreamSwitching'] : $period['bitstreamSwitching'];
    $mimeType = ($representations[0]['mimeType']) ? $representations[0]['mimeType'] : $adaptation_set['mimeType'];
    if($bitstreamSwitching == 'true' && strpos($mimeType, 'video') !== FALSE) {
        $profiles = array();
        $levels = array();
        $elsts = array();
        foreach ($representations as $representation_id => $representation) {
            $adapt_dir = str_replace('$AS$', $adaptation_set_id, $adaptation_set_template);
            $rep_dir = str_replace(array('$AS$', '$R$'), array($adaptation_set_id, $representation_id), $reprsentation_template);
            $rep_xml = $session_dir . '/Period' . $current_period . '/' . $adapt_dir . '/' . $rep_dir . '.xml';

            if(!file_exists($rep_xml)){
                return;
            }

            $xml = get_DOM($rep_xml, 'atomlist');
            if(!$xml)
                return;

            $codecs = ($representation['codecs']) ? $representation['codecs'] : $adaptation_set['codecs'];
            if(strpos($codecs, 'avc') !== FALSE) {
                $codec_box = $xml->getElementsByTagName('avcC');
                if($codec_box->length > 0) {
                    $codec = $codec_box->item(0);
                    $profiles[] = $codec->getAttribute('profile');
                    $levels[] = $codec->getElementsByTagName('Comment')->item(0)->getAttribute('level');
                }
            }
            elseif(strpos($codecs, 'hev') !== FALSE || strpos($codecs, 'hvc') !== FALSE) {
                $codec_box = $xml->getElementsByTagName('hvcC');
                if($codec_box->length > 0) {
                    $codec = $codec_box->item(0);
                    $profiles[] = $codec->getAttribute('profile_idc');
                    $levels[] = $codec->getAttribute('level_idc');
                }
            }
            
            if(strpos($codecs, 'avc') !== FALSE || strpos($codecs, 'hev') !== FALSE || strpos($codecs, 'hvc') !== FALSE) {
                $elst = $xml->getElementsByTagName('elst');
                if($elst->length > 0) {
                    $elsts[] = $elst->item(0);
                }
            }
        }
        
        $max_profile = max($profiles);
        $max_level = max($levels);
        $codecs_adaptation_set = $adaptation_set['codecs'];
        if($adaptation_set['codecs'] != NULL) {
            $codecs_array_adaptation_set = explode(',', $codecs_adaptation_set);
            foreach ($codecs_array_adaptation_set as $codecs_array_i_adaptation_set) {
                if(strpos($codecs_array_i_adaptation_set, 'avc') !== FALSE) {
                    if((int) (substr($codecs_array_i_adaptation_set, 5, 2)) != dechex((int)($max_profile))) {
                        $messages .= "DASH-IF IOP 4.3 check violated Section 6.2.5.2: \"For AVC video data, if the @bitstreamswitching flag is set to true, the AdaptationSet@codecs attribute SHALL equal to the maximum profile and level of any Representation in the Adaptation Set\", profile is not set to the maximum profile for Period $current_period Adaptation Set $adaptation_set_id.\n";
                    }
                    if((int) (substr($codecs_array_i_adaptation_set, 9, 2)) != dechex((int)($max_level))) {
                        $messages .= "DASH-IF IOP 4.3 check violated Section 6.2.5.2: \"For AVC video data, if the @bitstreamswitching flag is set to true, the AdaptationSet@codecs attribute SHALL equal to the maximum profile and level of any Representation in the Adaptation Set\", level is not set to the maximum profile for Period $current_period Adaptation Set $adaptation_set_id.\n";
                    }
                }
                if(strpos($codecs_array_i_adaptation_set, 'hev') !== FALSE || strpos($codecs_array_i_adaptation_set, 'hvc') !== FALSE) {
                    $codecs_array_i_parts_adaptation_set = explode('.', $codecs_array_i_adaptation_set);
                    if($codecs_array_i_parts_adaptation_set[1] != $max_profile) {
                        $messages .= "DASH-IF IOP 4.3 check violated Section 6.2.5.2: \"For HEVC video data, if the @bitstreamswitching flag is set to true, the AdaptationSet@codecs attribute SHALL equal to the maximum profile and level of any Representation in the Adaptation Set\", profile is not set to the maximum profile for Period $current_period Adaptation Set $adaptation_set_id.\n";
                    }
                    if(substr($codecs_array_i_parts_adaptation_set[3], 1) != $max_level) {
                        $messages .= "DASH-IF IOP 4.3 check violated Section 6.2.5.2: \"For HEVC video data, if the @bitstreamswitching flag is set to true, the AdaptationSet@codecs attribute SHALL equal to the maximum profile and level of any Representation in the Adaptation Set\", level is not set to the maximum profile for Period $current_period Adaptation Set $adaptation_set_id.\n";
                    }
                }
            }
        }
        
        $elst_count = sizeof($elsts);
        if($elst_count > 0) {
            if($elst_count != sizeof($representations)) {
                $messages .= "DASH-IF IOP 4.3 check violated Section 6.2.5.2: \"For AVC/HEVC video data, if the @bitstreamswitching flag is set to true, the edit list, if present in any Represent in the  Adaptation Set, SHALL be identical in all Representations\", edit list is not found for all Representations for Period $current_period Adaptation Set $current_adaptation_set.\n";
            }
            else {
                for($i=0; $i<$elst_count; $i++) {
                    for($j=$i+1; $j<$elst_count; $j++) {
                        if(!nodes_equal($elsts[$i], $elsts[$j])) {
                            $messages .= "DASH-IF IOP 4.3 check violated Section 6.2.5.2: \"For AVC/HEVC video data, if the @bitstreamswitching flag is set to true, the edit list, if present in any Represent in the  Adaptation Set, SHALL be identical in all Representations\", edit lists are different for Period $current_period Adaptation Set $current_adaptation_set Representation " . ($i+1) . " and Representation " . ($j+1) . ".\n";
                        }
                    }
                }
            }
        }
    }
    
    return $messages;
}