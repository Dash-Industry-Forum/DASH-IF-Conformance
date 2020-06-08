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

// All the boxes and related attributes to be checked for CMAF Table 11
$array = array("ftyp" => array("majorbrand", "version", "compatible_brands"),
               "mvhd" => array("version", "flags", "timeScale", "duration", "nextTrackID"),
               "tkhd" => array("version", "flags", "trackID", "duration", "volume"),
               "elst" => array("version", "flags", "entryCount"),
               "mdhd" => array("version", "flags", "timescale", "duration", "language"),
               "hdlr" => array("version", "flags", "handler_type", "name"),
               "vmhd" => array("version", "flags"),
               "smhd" => array("version", "flags"),
               "dref" => array("version", "flags", "entryCount"),
               "vide_sampledescription" => array("sdType"),
               "soun_sampledescription" => array("sdType"),
               "hint_sampledescription" => array("sdType"),
               "sdsm_sampledescription" => array("sdType"),
               "odsm_sampledescription" => array("sdType"),
               "stts" => array("version", "flags", "entryCount"),
               "stsc" => array("version", "flags", "entryCount"),
               "stsz" => array("version", "flags", "sampleSize", "entryCount"),
               "stco" => array("version", "flags", "entryCount"),
               "sgpd" => array("version", "flags", "groupingType", "entryCount"),
               "mehd" => array("version", "flags", "fragmentDuration"),
               "trex" => array("version", "flags", "trackID", "sampleDescriptionIndex", "sampleDuration", "sampleSize", "sampleFlags"),
               "pssh" => array("version", "flags", "systemID", "dataSize"),
               "tenc" => array("version", "flags", "default_IsEncrypted", "default_IV_size", "default_KID"),
               "cprt" => array("version", "flags", "language", "notice"),
               "kind" => array("schemeURI", "value"),
               "elng" => array("extended_languages"),
               "sinf" => array(),
               "schi" => array("comment"),
               "schm" => array("scheme", "version", "location"),
               "frma" => array("original_format"));

function createString(){
    global $array;
    $keys = array_keys($array);
    $cnt = count($keys);
    
    $str = '<compInfo>';
    for($i=0; $i<$cnt; $i++)
        $str .= '<' . $keys[$i] . '></' . $keys[$i] . '>';
    $str .= '</compInfo>';
    
    return $str;
}

function validateFileBrands($xml_att_value,$xml_comp_att_value,$infofile){
    $brands1=(string)$xml_att_value;
    $brands2=(string)$xml_comp_att_value;
    $videoCmaf1=strpos($brands1,"cfsd") || strpos($brands1,"cfhd") || strpos($brands1,"chdf");
    $videoCmaf2=strpos($brands2,"cfsd") || strpos($brands2,"cfhd") || strpos($brands2,"chdf");
    $audioCmaf1=strpos($brands1,"caac") || strpos($brands1,"caaa");
    $audioCmaf2=strpos($brands2,"caac") || strpos($brands2,"caaa");

    if($audioCmaf1 == FALSE && (($videoCmaf1!==FALSE && $videoCmaf2 == FALSE ) || ($videoCmaf2!==FALSE && $videoCmaf1 == FALSE )))
        fprintf ($infofile, "ftyp: do care\n");//When media profile brands are not subset of one another.
    else
        fprintf ($infofile, "ftyp: do not care\n");

}

function getIds($xml_atom){
    $str = $xml_atom->getAttribute("comparedIds");
    $part = explode(" ", $str);
    $firstId = explode("=", $part[0]);
    $secondId = explode("=", substr($part[1], 0, strlen($part[1])-1));
    
    return array($firstId[1], $secondId[1]);
}

function compare($xml, $xml_comp, $id, $id_comp, $curr_adapt_dir, $index, $path){
    global $array, $infofile_template;
    
    $str = createString(); //load the comparison result xml structure
    $compXML = simplexml_load_string($str);
    $compXML->addAttribute('comparedIds', "[rep=".$id." rep=".$id_comp."]");
    
    $adapt_infofile = $curr_adapt_dir . '/' . str_replace('$Number$', $index, $infofile_template);
    $infofile = open_file($adapt_infofile , 'w');
    
    $att_val1; $att_val2;
    foreach($array as $key => $value){
        $xml_key = $xml->getElementsByTagName($key);
        $xml_comp_key = $xml_comp->getElementsByTagName($key);
        
        if($xml_key->length == $xml_comp_key->length){
            if($key == 'tkhd'){
                if($xml_key->length > 1)
                    fprintf($infofile, "elst: do not care\n");
                else
                    fprintf($infofile, "elst: do care\n");
            }
            
            foreach($xml_key as $i => $xml_key_i){
                $xml_comp_key_i = $xml_comp_key->item($i);
                
                foreach($value as $attribute){
                    $xml_att = $xml_key_i->getAttribute($attribute);
                    $xml_comp_att = $xml_comp_key_i->getAttribute($attribute);
                    
                    if($key == 'mdhd' && $attribute == 'timescale'){
                        $att_val1 = $xml_att;
                        $att_val2 = $xml_comp_att;
                    }
                    if($key == "hdlr" && $attribute == "handler_type"){
                        if($xml_att == "soun" && $xml_comp_att == "soun"){
                            if(doubleval($att_val1) % 2 == 0 && doubleval($att_val2) % 2 == 0)
                                fprintf($infofile, "mdhd: do not care\n");
                            else
                                fprintf($infofile, "mdhd: do care\n");
                        }
                        else
                            fprintf($infofile, "mdhd: do care\n");
                    }
                    
                    //For comparing file brands with media profile brands
                    if($key == 'ftyp' && $attribute == 'compatible_brands')
                        validateFileBrands($xml_att,$xml_comp_att,$infofile);
                    
                    // Check for 'sinf' box
                    $box = $key;
                    $box_att = $attribute;
                    if($key == 'frma' || $key == 'schm' || $key == 'schi'){
                        $box = 'sinf';
                        $box_att = $key;
                    }
                    
                    $string = ($xml_att == $xml_comp_att) ? 'Yes' : 'No';
                    if(isset($compXML->$box->attributes()[$box_att]))
                        $compXML->$box->attributes()->$box_att = ((string) $compXML->$box->attributes()->$box_att) . ' ' . $string;
                    else
                        $compXML->$box->addAttribute($box_att, $string);
                }
            }
        }
        else{
            $string = 'No';
            if(isset($compXML->$key->attributes()[$attribute]))
                $compXML->$key->attributes()->$attribute = ((string) $compXML->$key->attributes()->$attribute) . ' ' . $string;
            else
                $compXML->$key->addAttribute($attribute, $string);
        }
    }
    
    fclose($infofile);
    $compXML->asXml($path); //save changes
}

function getNALArray($hvcC, $type){
    $hvcC_nals = $hvcC->childNodes;
    $nal_len = $hvcC_nals->length;
    
    for($i=0; $i<$nal_len; $i++){
        $nal_unit_arr = $hvcC_nals->item($i);
        if(strpos($nal_unit_arr->nodeName, 'NAL_Unit_Array') !== FALSE){
            $nal_unit_type = $nal_unit_arr->getAttribute('nalUnitType');
            if($nal_unit_type == $type){
                return $nal_unit_arr;
            }
        }
    }
    return NULL;
}

function getNALUnit($nal_array){
    $nodes = $nal_array->childNodes;
    $nal_array_len = $nodes->length;
    
    for($i=0; $i<$nal_array_len; $i++){
        $nal_unit = $nodes->item($i);
        if(strpos($nal_unit->nodeName, 'NALUnit') !== FALSE){
            return $nal_unit;
        }
    }
    return NULL;
}

function checkMediaProfiles($opfile, $rep1, $rep2){
    global $cmaf_mediaProfiles, $current_period, $current_adaptation_set;
    
    $cmaf_profile1 = $cmaf_mediaProfiles[$current_period][$current_adaptation_set][$rep1]['cmafMediaProfile'];
    $cmaf_profile2 = $cmaf_mediaProfiles[$current_period][$current_adaptation_set][$rep2]['cmafMediaProfile'];
    if(($cmaf_profile1 == $cmaf_profile2 && $cmaf_profile1 == 'unknown') || ($cmaf_profile1 != $cmaf_profile2 || strpos($cmaf_profile1, $cmaf_profile2) === FALSE || strpos($cmaf_profile2, $cmaf_profile1) === FALSE)){
        fprintf($opfile, "**'CMAF check violated: Section 7.3.4.1- All CMAF Tracks in a CMAF Switching Set SHALL conform to one CMAF Media Profile', but not conforming for Representation ".($rep1)." with profile '".$cmaf_profile1."' and Representation ".($rep2)." with profile '".$cmaf_profile2."' in Adaptation Set ".($current_adaptation_set+1)." in Period ".($current_period+1).".\n");
    }
}

function checkHeaders($opfile, $xml, $xml_comp, $id, $id_comp, $curr_adapt_dir, $index, $path){
    global $session_dir, $current_period, $current_adaptation_set, 
            $adaptation_set_template, $infofile_template;
    
    compare($xml, $xml_comp, $id, $id_comp, $curr_adapt_dir, $index, $path);
    
    $found = false;
    if(file_exists($path)){
        $infofile = str_replace('$Number$', $index, $infofile_template);
        $adapt_dir = str_replace('$AS$', $current_adaptation_set, $adaptation_set_template);
        $info_str = file_get_contents($session_dir . '/Period' . $current_period . '/' . $adapt_dir . '/' . $infofile);

        $first = true;
        $xml = get_DOM($path, 'compInfo');
        
        if($xml){
            foreach($xml->childNodes as $child){ //if any attribute in the xml file contains "No", then this will be considered as an error
                if($first){ //obtain the rep ids in the xml file. (info for $opfile)
                    $ids = getIds($xml);
                    $first = false;
                }

                $child_name = $child->nodeName;
                foreach($child->attributes as $attribute){
                    if(in_array("No", explode(" ", $attribute->nodeValue))){
                        if($child_name == "elst" && strpos($info_str, 'elst: do not care') !== FALSE)
                            continue;
                        if($child_name == "mdhd" && strpos($info_str, 'mdhd: do not care') !== FALSE)
                            continue;
                        if($child_name == "ftyp" && strpos($info_str, 'ftyp: do not care') !== FALSE)
                            continue;
                        else{
                            fprintf ($opfile, "**'CMAF check violated: Section 7.3.4- CMAF header parameters SHALL NOT differ between CMAF tracks, except as allowed in Table 11', but ".$attribute->nodeName.' in the box: '.$child_name." is different between Rep. $ids[0] and Rep. $ids[1] in Switching Set " . (string) ($current_adaptation_set+1) . " \n\n");
                            $found = true;
                        }
                    }
                }
            }

            if(!$found){ //otherwise this comparison conforms to specifications 
                $found = false;
            }
        }
    }
    else
        fprintf ($opfile, "Tried to retrieve data from a location that does not exist. \n (Possible cause: Representations are not valid and no file/directory for box info is created.)");
}

function compareHevc($opfile, $xml, $xml_comp, $id, $id_comp){
    $att_names_sps = array('vui_parameters_present_flag', 'video_signal_type_present_flag', 'colour_description_present_flag',
        'colour_primaries', 'transfer_characteristics', 'matrix_coeffs', 'chroma_loc_info_present_flag',
        'chroma_sample_loc_type_top_field', 'chroma_sample_loc_type_bottom_field', 'neutral_chroma_indication_flag', 
        'sps_extension_present_flag', 'sps_range_extension_flag', 'extended_precision_processing_flag');
    $att_names_sei = array('length', 'zero-bit', 'nuh_layer_id', 'nuh_temporal_id_plus1');
    
    $xml_hvcC=$xml->getElementsByTagName('hvcC')->item(0);
    $xml_comp_hvcC = $xml_comp->getElementsByTagName('hvcC')->item(0);
    
    $xml_SPS = getNALArray($xml_hvcC, '33');
    $xml_comp_SPS = getNALArray($xml_comp_hvcC, '33');
    if($xml_SPS != NULL && $xml_comp_SPS != NULL){
        $sps_unit = getNALUnit($xml_SPS);
        $sps_unit_comp = getNALUnit($xml_comp_SPS);
        
        foreach ($att_names_sps as $att_name) {
            $nal_unit_att = $sps_unit->getAttribute($att_name);
            $comp_nal_unit_att = $sps_unit_comp->getAttribute($att_name);
            
            if($nal_unit_att != $comp_nal_unit_att)
                fprintf($opfile, "**'CMAF check violated: Section B.2.4- CMAF Switching Sets SHALL be constrained to include identical SPS VUI color mastering and dynamic range information in the first sample entry of every CMAF header in the CMAF switching set to provide consistent initialization and calibration', but $att_name is $nal_unit_att for Rep. $id and $comp_nal_unit_att for Rep. $id_comp.\n");
        }
    }
    elseif(($xml_SPS != NULL && $xml_comp_SPS == NULL) || ($xml_SPS == NULL && $xml_comp_SPS != NULL)){
        fprintf($opfile, "**'CMAF check violated: Section B.2.4- CMAF Switching Sets SHALL be constrained to include identical SPS VUI color mastering and dynamic range information in the first sample entry of every CMAF header in the CMAF switching set to provide consistent initialization and calibration', but Rep. $id and Rep. $id_comp are not symmetric in SPS NAL presence.\n");
    }
    
    $xml_PRESEI = getNALArray($xml_hvcC, '39');
    $xml_comp_PRESEI = getNALArray($xml_comp_hvcC, '39');
    if($xml_PRESEI != NULL && $xml_comp_PRESEI != NULL){
        $presei_unit = getNALUnit($xml_PRESEI);
        $presei_unit_comp = getNALUnit($xml_comp_PRESEI);
        
        foreach ($att_names_sei as $att_name) {
            $nal_unit_att = $presei_unit->getAttribute($att_name);
            $comp_nal_unit_att = $presei_unit_comp->getAttribute($att_name);
            
            if($nal_unit_att != $comp_nal_unit_att)
                fprintf($opfile, "**'CMAF check violated: Section B.2.4- CMAF Switching Sets SHALL be constrained to include identical SEI NALS in the first sample entry of every CMAF header in the CMAF switching set to provide consistent initialization and calibration', but $att_name is $nal_unit_att for Rep. $id and $comp_nal_unit_att for Rep. $id_comp. \n");
            }
    }
    elseif(($xml_PRESEI != NULL && $xml_comp_PRESEI == NULL) || ($xml_PRESEI == NULL && $xml_comp_PRESEI != NULL)){
        fprintf($opfile, "**'CMAF check violated: Section B.2.4- CMAF Switching Sets SHALL be constrained to include identical SPS VUI color mastering and dynamic range information in the first sample entry of every CMAF header in the CMAF switching set to provide consistent initialization and calibration', but Rep. $id and Rep. $id_comp are not symmetric in SEI NAL presence.\n");
    }
    
    $xml_SUFSEI = getNALArray($xml_hvcC, '40');
    $xml_comp_SUFSEI = getNALArray($xml_comp_hvcC, '40');
    if($xml_SUFSEI != NULL && $xml_comp_SUFSEI != NULL){
        $sufsei_unit = getNALUnit($xml_SUFSEI);
        $sufsei_unit_comp = getNALUnit($xml_comp_SUFSEI);
        
        foreach ($att_names_sei as $att_name) {
            $nal_unit_att = $sufsei_unit->getAttribute($att_name);
            $comp_nal_unit_att = $sufsei_unit_comp->getAttribute($att_name);
            
            if($nal_unit_att != $comp_nal_unit_att)
                fprintf($opfile, "**'CMAF check violated: Section B.2.4- CMAF Switching Sets SHALL be constrained to include identical SEI NALS in the first sample entry of every CMAF header in the CMAF switching set to provide consistent initialization and calibration', but $att_name is $nal_unit_att for Rep. $id and $comp_nal_unit_att for Rep. $id_comp. \n");
            }
    }
    elseif(($xml_SUFSEI != NULL && $xml_comp_SUFSEI == NULL) || ($xml_SUFSEI == NULL && $xml_comp_SUFSEI != NULL)){
        fprintf($opfile, "**'CMAF check violated: Section B.2.4- CMAF Switching Sets SHALL be constrained to include identical SPS VUI color mastering and dynamic range information in the first sample entry of every CMAF header in the CMAF switching set to provide consistent initialization and calibration', but Rep. $id and Rep. $id_comp are not symmetric in SEI NAL presence.\n");
    }
}

function compareRest($opfile, $xml, $xml_comp, $id, $id_comp){
    //Check all Tracks are of same media type.
    $xml_hdlr=$xml->getElementsByTagName('hdlr')->item(0);
    $xml_handlerType=$xml_hdlr->getAttribute('handler_type');
    $xml_comp_hdlr=$xml_comp->getElementsByTagName('hdlr')->item(0);
    $xml_comp_handlerType=$xml_comp_hdlr->getAttribute('handler_type');

    if($xml_handlerType!=$xml_comp_handlerType)
        fprintf($opfile, "**'CMAF check violated: Section 7.3.4.1- A CMAF Switching Set SHALL contain CMAF Tracks of only one media type', but not matching between Rep". $id." (".$xml_handlerType.") and Rep".$id_comp." (".$xml_comp_handlerType.") \n");

    //Check Tracks have same number of moofs.
    $xml_num_moofs=$xml->getElementsByTagName('moof')->length;
    $xml_comp_num_moofs=$xml_comp->getElementsByTagName('moof')->length;

    if($xml_num_moofs!=$xml_comp_num_moofs)
        fprintf($opfile, "**'CMAF check violated: Section 7.3.4.1- All CMAF Tracks in a CMAF Switching Set SHALL contain the same number of CMAF Fragments', but not matching between Rep". $id." (fragments=".$xml_num_moofs.") and Rep".$id_comp." (fragments=".$xml_comp_num_moofs.") \n");

    //Check all Tracks have same ISOBMFF defined duration.
    if($xml->getElementsByTagName('mehd')->length >0 && $xml_comp->getElementsByTagName('mehd')->length >0 ){
        $xml_mehd=$xml->getElementsByTagName('mehd')->item(0);
        $xml_mehdDuration=$xml_mehd->getAttribute('fragmentDuration');
        $xml_comp_mehd=$xml_comp->getElementsByTagName('mehd')->item(0);
        $xml_comp_mehdDuration=$xml_comp_mehd->getAttribute('fragmentDuration');

        if($xml_mehdDuration!=$xml_comp_mehdDuration)
            fprintf($opfile, "**'CMAF check violated: Section 7.3.4.1- All CMAF Tracks in a CMAF Switching Set SHALL have the same duration', but not matching between Rep". $id." (duration=".$xml_mehdDuration.") and Rep".$id_comp." (duration=".$xml_comp_mehdDuration.") \n");
    }
    else{ //added according to change in FDIS.
        $xml_lasttfdt=$xml->getElementsByTagName('tfdt')->item($xml_num_moofs-1);
        $xml_comp_lasttfdt=$xml_comp->getElementsByTagName('tfdt')->item($xml_comp_num_moofs-1);

        $xml_lastDecodeTime=$xml_lasttfdt->getAttribute('baseMediaDecodeTime');
        $xml_comp_lastDecodeTime=$xml_comp_lasttfdt->getAttribute('baseMediaDecodeTime');

        $xml_lasttrun=$xml->getElementsByTagName('trun')->item($xml_num_moofs-1);
        $xml_comp_lasttrun=$xml_comp->getElementsByTagName('trun')->item($xml_comp_num_moofs-1);

        $xml_cumSampleDur=$xml_lasttrun->getAttribute('cummulatedSampleDuration');
        $xml_comp_cumSampleDur=$xml_comp_lasttrun->getAttribute('cummulatedSampleDuration');

        if($xml_lastDecodeTime+$xml_cumSampleDur != $xml_comp_lastDecodeTime+$xml_comp_cumSampleDur)
            fprintf($opfile, "**'CMAF check violated: Section 7.3.4.1- All CMAF Tracks in a CMAF Switching Set SHALL have the same duration', but not matching between Rep". $id." (duration=".$xml_lastDecodeTime+$xml_cumSampleDur.") and Rep".$id_comp." (duration=".$xml_comp_lastDecodeTime+$xml_comp_cumSampleDur.") \n");
    }

    //Check base decode time of Tracks.
    $xml_tfdt=$xml->getElementsByTagName('tfdt');    
    $xml_baseDecodeTime=$xml_tfdt->item(0)->getAttribute('baseMediaDecodeTime');
    $xml_comp_tfdt=$xml_comp->getElementsByTagName('tfdt');    
    $xml_comp_baseDecodeTime=$xml_comp_tfdt->item(0)->getAttribute('baseMediaDecodeTime');

    if($xml_baseDecodeTime!=$xml_comp_baseDecodeTime)
         fprintf($opfile, "**'CMAF check violated: Section 7.3.4.1- All CMAF tracks in a CMAF Switching Set SHALL have the same value of baseMediaDecodeTime in the 1st CMAF fragment's tfdt box, measured from the same timeline origin', but not matching between Rep". $id." (decode time=".$xml_baseDecodeTime.") and Rep".$id_comp." (decode time=".$xml_comp_baseDecodeTime.") \n");

    //Check for Fragments with same decode time.
    for($y=0; $y<$xml_num_moofs;$y++){
        $xml_baseDecodeTime=$xml_tfdt->item($y)->getAttribute('baseMediaDecodeTime');
        for($z=0;$z<$xml_comp_num_moofs; $z++){
            $xml_comp_baseDecodeTime=$xml_comp_tfdt->item($z)->getAttribute('baseMediaDecodeTime');
            if($xml_baseDecodeTime==$xml_comp_baseDecodeTime)
                break;
            elseif($z==$xml_comp_num_moofs-1)
                fprintf($opfile, "**'CMAF check violated: Section 7.3.4.1- For any CMAF Fragment in one CMAF Track in a CMAF Switching Set there SHALL be a CMAF Fragment with same decode time in all other CMAF Tracks', but not found for Rep ".$id." Fragment ".($y+1)." in Rep ".$id_comp."\n");
        }
    }

    //Check tenc encryption parameters.
    /*if($xml->getElementsByTagName('tenc')->length >0 && $xml_comp->getElementsByTagName('tenc')->length >0){
        $xml_tenc=$xml->getElementsByTagName('tenc');    
        $xml_KID=$xml_tenc->item(0)->getAttribute('default_KID');
        $xml_comp_tenc=$xml_comp->getElementsByTagName('tenc');    
        $xml_comp_KID=$xml_comp_tenc->item(0)->getAttribute('default_KID');

        $xml_IVSize=$xml_tenc->item(0)->getAttribute('default_IV_size');
        $xml_comp_IVSize=$xml_comp_tenc->item(0)->getAttribute('default_IV_size');

        if($xml_KID!=$xml_comp_KID)
            fprintf($opfile, "**'CMAF check violated: Section 7.3.3- CMAF Header contained default_KID SHALL be identical for all CMAF Tracks in a Switching Set', but not found for Rep ".$id." (KID=".$xml_KID.") and Rep ".$id_comp." (KID=".$xml_comp_KID.") \n");

        if($xml_IVSize!=$xml_comp_IVSize)
            fprintf($opfile, "**'CMAF check violated: Section 7.3.3- CMAF Header contained default_IV_size SHALL be identical for all CMAF Tracks in a Switching Set', but not found for Rep ".$id." (IV_size=".$xml_IVSize.") and Rep ".$id_comp." (IV_size=".$xml_comp_IVSize.") \n");
    }*/

    //Check new presentation time check from FDIS on SwSet
    $xml_hdlr=$xml->getElementsByTagName('hdlr')->item(0);
    $xml_handlerType=$xml_hdlr->getAttribute('handler_type');
    $xml_trun=$xml->getElementsByTagName('trun')->item(0);
    $xml_comp_trun=$xml_comp->getElementsByTagName('trun')->item(0);
    $xml_earlyCompTime=$xml_trun->getAttribute('earliestCompositionTime');
    $xml_comp_earlyCompTime=$xml_comp_trun->getAttribute('earliestCompositionTime');

    if($xml_handlerType=='vide'){ 
      if($xml_earlyCompTime!=$xml_comp_earlyCompTime)
         fprintf($opfile, "**'CMAF check violated: Section 7.3.4.1- The presentation time of earliest media sample of the earliest CMAF fragment in each CMAF track shall be equal', but unequal presentation-times found between Rep ".$id." and Rep ".$id_comp." \n");
    }
    else if($xml_handlerType=='soun'){
         $xml_elst=$xml->getElementsByTagName('elstEntry');
         $xml_comp_elst=$xml_comp->getElementsByTagName('elstEntry');
         $mediaTime=0;
         if($xml_elst->length>0 ){
         $mediaTime=$xml_elst->item(0)->getAttribute('mediaTime');
         }
         $mediaTime_comp=0;
         if($xml_comp_elst->length>0 ){
         $mediaTime_comp=$xml_comp_elst->item(0)->getAttribute('mediaTime');
         }
         if($xml_earlyCompTime+$mediaTime != $xml_comp_earlyCompTime+$mediaTime_comp)
             fprintf($opfile, "**'CMAF check violated: Section 7.3.4.1- The presentation time of earliest media sample of the earliest CMAF fragment in each CMAF track shall be equal', but unequal presentation-times found between Rep ".$id." and Rep ".$id_comp." \n");
    } //
}

function checkSwitchingSets(){
    global $session_dir, $mpd_features, $current_period, $current_adaptation_set, $string_info,
            $adaptation_set_template, $comparison_folder, $compinfo_file, $progress_xml, $progress_report;
    
    $adaptation_set = $mpd_features['Period'][$current_period]['AdaptationSet'][$current_adaptation_set];
    $curr_adapt_dir = $session_dir . '/Period' . $current_period . '/' . str_replace('$AS$', $current_adaptation_set, $adaptation_set_template);
    
    $compinfo = str_replace('$AS$', $current_adaptation_set, $compinfo_file);
    if(!($opfile = open_file($session_dir.'/Period'.$current_period.'/'.$compinfo.'.txt', 'w'))){
        echo "Error opening/creating compared representations' conformance check file: ". $session_dir.'/'.$compinfo.'.txt';
        return;
    }
    
    $filecount = 0;
    $files = glob($curr_adapt_dir . "/*.xml");
    if($files)
        $filecount = count($files);
    
    $ind = 0;
    for($i=0; $i<$filecount-1; $i++){ //iterate over files
        if($i >= $filecount-1)
            break;
        
        for($j=$i+1; $j<$filecount; $j++){ //iterate over remaining files
            $filename = $files[$i]; //load file
            $xml = get_DOM($filename, 'atomlist');
            $id = $adaptation_set['Representation'][$i]['id'];
            
            $filename_comp = $files[$j]; //load file to be compared
            $xml_comp = get_DOM($filename_comp, 'atomlist');
            $id_comp = $adaptation_set['Representation'][$j]['id'];
            
            create_folder_in_session($curr_adapt_dir  . '/' . $comparison_folder);
            $name_part = explode('.', basename($filename))[0];
            $name_comp_part = explode('.', basename($filename_comp))[0];
            $path = $curr_adapt_dir  . '/' . $comparison_folder . $name_part . "_vs_" . $name_comp_part . ".xml";
            
            if($xml && $xml_comp){
                checkHeaders($opfile, $xml, $xml_comp, $id, $id_comp, $curr_adapt_dir, $ind, $path); //start comparing
                compareHevc($opfile, $xml, $xml_comp, $id, $id_comp);
                checkMediaProfiles($opfile, $i, $j);
                compareRest($opfile, $xml, $xml_comp, $id, $id_comp);
            }
            
            $ind++;
        }
    }
    
    fclose($opfile);
    
    if(file_exists($session_dir.'/Period'.$current_period.'/'.$compinfo.'.txt')){
        $searchfiles = file_get_contents($session_dir.'/Period'.$current_period.'/'.$compinfo.'.txt');
        if(strpos($searchfiles, "Error") == false && strpos($searchfiles, "CMAF check violated") == false){
            $progress_xml->Results[0]->Period[$current_period]->Adaptation[$current_adaptation_set]->addChild('ComparedRepresentations', 'noerror');
            $file_error[] = "noerror"; // no error found in text file
        }
        else{
            $progress_xml->Results[0]->Period[$current_period]->Adaptation[$current_adaptation_set]->addChild('ComparedRepresentations', 'error');
            $file_error[] = $session_dir.'/Period'.$current_period.'/'.$compinfo.'.html'; // add error file location to array
        }
        $progress_xml->Results[0]->Period[$current_period]->Adaptation[$current_adaptation_set]->ComparedRepresentations->addAttribute('url', str_replace($_SERVER['DOCUMENT_ROOT'], 'http://' . $_SERVER['SERVER_NAME'], $session_dir.'/Period'.$current_period.'/'.$compinfo.'.txt'));
        $progress_xml->asXml(trim($session_dir . '/' . $progress_report));
    }
    
    err_file_op(2);
    print_console($session_dir.'/Period'.$current_period.'/'.$compinfo.'.txt', "Period " . ($current_period+1) . " Adaptation Set " . ($current_adaptation_set+1) . " CMAF Switching Set Results");
    tabulateResults($session_dir.'/Period'.$current_period.'/'.$compinfo.'.txt', 'Cross');
    
    return $file_error;
}