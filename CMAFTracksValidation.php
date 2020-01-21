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

$CMAFMediaProfileAttributesVideo = array(
        "codec" => "",
        "profile" => "",
        "level" => "",
        "height" => "",
        "width" => "",
        "framerate" => "",
        "color_primaries" => "",
        "transfer_char" => "",
        "matrix_coeff" => "",
        "tier" => "",
        "brand"=>"");

$CMAFMediaProfileAttributesAudio = array(
        "codec" => "",
        "profile" => "",
        "level" => "",
        "channels"=>"",
        "sampleRate"=>"",
        "brand"=>"");

$CMAFMediaProfileAttributesSubtitle = array(
        "codec" => "",
        "mimeType" => "",
        "mimeSubtype"=>"",
        "brand"=>"");

function checkCMAFTracks(){
    global $session_dir, $mpd_features, $current_period, $current_adaptation_set, $current_representation, 
            $adaptation_set_template, $reprsentation_template, $reprsentation_error_log_template, $reprsentation_mdat_template, $profiles, $cmaf_mediaTypes,
            $progress_report, $progress_xml, $cmaf_mediaProfiles;
    
    $adapt_dir = str_replace('$AS$', $current_adaptation_set, $adaptation_set_template);
    $rep_xml_dir = str_replace(array('$AS$', '$R$'), array($current_adaptation_set, $current_representation), $reprsentation_template);
    $rep_xml = $session_dir . '/Period' . $current_period . '/' . $adapt_dir . '/' . $rep_xml_dir . '.xml';
    
    if(file_exists($rep_xml)){
        $xml = get_DOM($rep_xml, 'atomlist');
        
        if(!$xml)
            return;
        
        $error_file = str_replace(array('$AS$', '$R$'), array($current_adaptation_set, $current_representation), $reprsentation_error_log_template);
        if(!($opfile = open_file($session_dir.'/Period'.$current_period.'/'.$error_file.'.txt', 'a'))){
            echo 'Error opening/creating CMAF Tracks conformance check file: '.$session_dir.'/'.$error_file.'.txt';
            return;
        }
        
        # Store media type for selection set checks later
        $cmaf_media_type = $xml->getElementsByTagName('hdlr')->item(0)->getAttribute('handler_type');
        $cmaf_mediaTypes[$current_period][$current_adaptation_set][$current_representation] = $cmaf_media_type;
        
        $Adapt = $mpd_features['Period'][$current_period]['AdaptationSet'][$current_adaptation_set];
        
        $errorInTrack=0;
        $id = $Adapt['Representation'][$current_representation]['id'];
        $xml_moof=$xml->getElementsByTagName('moof');
        $xml_num_moofs=$xml_moof->length;
        $xml_tfhd=$xml->getElementsByTagName('tfhd');
        $xml_trun=$xml->getElementsByTagName('trun');
        $xml_tfdt=$xml->getElementsByTagName('tfdt');
        
        /* if($xml_trun[0]->getAttribute('version') ==1){
            $firstSampleCompTime=$xml_trun[0]->getAttribute('earliestCompositionTime');
            $firstSampleDecTime=$xml_tfdt[0]->getAttribute('baseMediaDecodeTime');
            if($firstSampleCompTime!=$firstSampleDecTime)
                fprintf($opfile, "**'CMAF check violated: Section 7.5.16- For 'trun' version 1, the composition time of 1st presented sample in a CMAF Segment SHALL be same as 1st Sample decode time, but not found in Rep ".$id." \n");
        }*/
        
        // 'trun' version check for CMAF video tracks
        $adapt_mime_type = $Adapt['mimeType'];
        $rep_mime_type = $Adapt['Representation'][$current_representation]['mimeType'];
        if(strpos($rep_mime_type, 'video') !== FALSE || strpos($adapt_mime_type, 'video') !== FALSE){
            if(strpos($profiles[$current_adaptation_set][$current_representation], 'urn:mpeg:dash:profile:isoff-live:2011') !== FALSE){
                for($j=0;$j<$xml_num_moofs;$j++){
                    $trun_version = $xml_trun->item($j)->getAttribute('version');
                    if($trun_version != "1")
                        fprintf($opfile, "**'CMAF check violated: Section 7.5.17- Version 1 SHALL be used for video CMAF tracks, except in case of a video CMAF track file', but " . $trun_version . " found for Rep ".$id." Track ".($j+1)."\n");
                }
            }
        }
        
        // 'subs' presence check for TTML image subtitle track with media profile 'im1i'
        $rep_codec_type = $Adapt['Representation'][$current_representation]['codec'];
        if(strpos($rep_codec_type, 'im1i') !== FALSE){
            for($j=0;$j<$xml_num_moofs;$j++){
                $temp_moof = $xml_moof[$j];
                $xml_subs = $temp_moof->getElementsByTagName('subs');
                if($xml_subs->length == 0)
                    fprintf($opfile, "**'CMAF check violated: Section 7.5.20- Each CMAF fragment in a TTML image subtitle track of CMAF media profile 'im1i' SHALL contain a SubSampleInformationBox in the TrackFragmentBox, but " . $xml_subs->length . " found for Rep ".$id." Fragment ".($j+1)."\n");
            }
        }
        
        for($j=1;$j<$xml_num_moofs;$j++){
            //$sampleDurFragPrev=$xml_tfhd[$j-1]->getAttribute('defaultSampleDuration');
            //$sampleCountFragPrev=$xml_trun[$j-1]->getAttribute('sampleCount');
            $cummulatedSampleDurFragPrev=$xml_trun->item($j-1)->getAttribute('cummulatedSampleDuration');
            $decodeTimeFragPrev=$xml_tfdt->item($j-1)->getAttribute('baseMediaDecodeTime');
            $decodeTimeFragCurr=$xml_tfdt->item($j)->getAttribute('baseMediaDecodeTime');
            
            if($decodeTimeFragCurr!=$decodeTimeFragPrev+$cummulatedSampleDurFragPrev){//($sampleDurFragPrev*$sampleCountFragPrev)){
                fprintf($opfile, "**'CMAF check violated: Section 7.3.2.2- Each CMAF Fragment in a CMAF Track SHALL have baseMediaDecodeTime equal to the sum of all prior Fragment durations added to the first Fragment's baseMediaDecodeTime', but not found for Rep ".$id." Fragment ".($j+1)."\n");
                $errorInTrack=1;
            }
        }
        
        $offsetinfo = 'Period' . $current_period . '/' . str_replace(array('$AS$', '$R$'), array($current_adaptation_set, $current_representation), $reprsentation_mdat_template);
        $mdat_file = open_file($session_dir . '/' . $offsetinfo . '.txt', 'r');
        for($j=0;$j<$xml_num_moofs;$j++){
            if($xml_trun->item($j)->getAttribute('version') ==1){
                $firstSampleCompTime=$xml_trun->item($j)->getAttribute('earliestCompositionTime');
                $firstDecTime=$xml_tfdt->item(0)->getAttribute('baseMediaDecodeTime');
                if($firstSampleCompTime!=$firstDecTime)
                    fprintf($opfile, "**'CMAF check violated: Section 7.5.17- For 'trun' version 1, the composition time of 1st presented sample in a CMAF Segment SHALL be same as 1st Sample decode time (baseMediaDecodeTime), but not found in Rep ".$id." \n");
            }
            
            if($mdat_file != NULL){
                $mdat_info = array();
                if(!feof($mdat_file)){
                    $mdat_info = explode(" ", fgets($mdat_file));
                }
                if(!empty($mdat_info)){
                    $moofFirstByte = $xml_moof->item($j)->getAttribute('offset');
                    $dataOffset = $xml_trun->item($j)->getAttribute('data_offset');
                    $trunSampleSize = $xml_trun->item($j)->getAttribute('sampleSizeTotal');
                    // Check that $dataOffset leads to a position within the mdat and that the total length of content in the trun doesn't, when added to this value, go beyond the end of the mdat.
                    if(!($moofFirstByte+$dataOffset >= $mdat_info[0] && $moofFirstByte+$dataOffset+$trunSampleSize <= $mdat_info[0]+$mdat_info[1] )) {
                        fprintf($opfile, "**'CMAF check violated: Section 7.3.2.3- All media samples in a CMAF Chunk SHALL be addressed by byte offsets in the TrackRunBox relative to first byte of the MovieFragmentBox', but not found for Rep ".$id." Chunk ".($j+1)."\n");
                    }
                }
            }
        }
        fclose($mdat_file);
        
        if($errorInTrack)
            fprintf($opfile, "**'CMAF check violated: Section 7.3.2.2- The concatenation of a CMAF Header and all CMAF Fragments in the CMAF Track in consecutive decode order SHALL be a valid fragmented ISOBMFF file', but not found for Rep ".$id."\n");
        
        $xml_hdlr=$xml->getElementsByTagName('hdlr')->item(0);
        $xml_handlerType=$xml_hdlr->getAttribute('handler_type');
        
        $xml_elst=$xml->getElementsByTagName('elstEntry');
        if($xml_elst->length>0 && $xml_handlerType=='vide'){
            $firstSampleCompTime=$xml_trun->item(0)->getAttribute('earliestCompositionTime');
            $mediaTime=$xml_elst->item(0)->getAttribute('mediaTime');
            if($mediaTime != $firstSampleCompTime)
                fprintf($opfile, "**'CMAF check violated: Section 7.5.13- In video CMAF track, an EditListBox shall be used to adjust the earliest video sample to movie presentation time zero, i.e., media-time equal to composition-time of earliest presented sample in the 1st Fragment', but media-time is not equal to composition-time for Rep ".$id."\n");
        }
        
        /*$ParamSetPresent=0;
        $xml_videSample=$xml->getElementsByTagName('vide_sampledescription');
        if($xml_videSample->length>0){
            $sdType=$xml_videSample->item(0)->getAttribute('sdType');
            if($sdType == "hvc1"){
                $xml_NALUnit=$xml->getElementsByTagName('NALUnit');
                if($xml_NALUnit->length==0)
                     fprintf($opfile, "**'CMAF check violated: Section B.2.1.2. - For a Visual Sample Entry with codingname 'hvc1', SHALL contain one or more decoding parameter sets(Containing VPS,SPS and PPS NALs for HEVC Video), but NALs not found in the Rep/Track ".$id."\n");
                else{ 
                    for($k=0; $k< ($xml_NALUnit->length); $k++){
                        $ParamSet=$xml_NALUnit->item($k)->getAttribute('nal_unit_type');
                        if($ParamSet ==32 || $ParamSet ==33|| $ParamSet ==34)
                            $ParamSetPresent=1;
                        }
                        if($ParamSetPresent==0)
                            fprintf($opfile, "**'CMAF check violated: Section B.2.1.2. - For a Visual Sample Entry with codingname 'hvc1', SHALL contain one or more decoding parameter sets(Containing VPS,SPS and PPS NALs for HEVC Video), but found none in the Rep/Track ".$id."\n");
                }
            }
        }*/
        $xml_videSample=$xml->getElementsByTagName('vide_sampledescription');
        if($xml_videSample->length>0){
            $sdType=$xml_videSample->item(0)->getAttribute('sdType');
            if($sdType == "hvc1" || $sdType =="hev1"){
                $xml_hvcc=$xml_videSample->item(0)->getElementsByTagName('hvcC');
                if($xml_hvcc->length!=1)
                    fprintf($opfile, "**'CMAF check violated: Section B.2.3. - The HEVCSampleEntry SHALL contain an HEVCConfigurationBox (hvcC) containing an HEVCDecoderConfigurationRecord, but found ".$xml_hvcc->length." box in the Rep/Track ".$id."\n");
            }
            if( $sdType =="hev1"){
                $vui_flag=0;
                $xml_NALUnit=$xml->getElementsByTagName('NALUnit');
                for($k=0; $k< ($xml_NALUnit->length); $k++){    
                    $ParamSet=$xml_NALUnit->item($k)->getAttribute('nal_unit_type');
                        if($ParamSet ==33)
                            $vui_flag=$xml_NALUnit->item($k)->getAttribute('vui_parameters_present_flag');    
                }
                if($vui_flag==0){
                    $colr=$xml_videSample->item(0)->getElementsByTagName('colr');
                    $pasp=$xml_videSample->item(0)->getElementsByTagName('pasp');
                    if($pasp->length==0)
                        fprintf($opfile, "**'CMAF check violated: Section B.2.3. - The HEVCSampleEntry SHALL contain PixelAspectRatioBox (pasp), but not found in the Rep/Track ".$id."\n");
                    if($colr->length==0)
                        fprintf($opfile, "**'CMAF check violated: Section B.2.3. - The HEVCSampleEntry SHALL contain ColorInformationBox (colr), but not found in the Rep/Track ".$id."\n");
                    else{
                        if($colr->item(0)->getAttribute('colrtype') !='nclx')
                            fprintf($opfile, "**'CMAF check violated: Section B.2.3. - The HEVCSampleEntry SHALL contain ColorInformationBox (colr) with colour_type 'nclx', but this colour_type ".$colr->item(0)->getAttribute('colrtype')." found in the Rep/Track ".$id."\n");

                    }
                }
            }
        }
        //Check for metadata required to decode, decrypt, display in CMAF Header.
        // $xml_hdlr=$xml->getElementsByTagName('hdlr')[0];
        // $xml_handlerType=$xml_hdlr->getAttribute('handler_type');
        if($xml_handlerType=='vide' ){
            if($sdType =='avc1' || $sdType== 'avc3'){
                $width=$xml_videSample->item(0)->getAttribute('width');
                $height=$xml_videSample->item(0)->getAttribute('height');
                $xml_NALUnit=$xml->getElementsByTagName('NALUnit');
                if($xml_NALUnit->length>0){
                    $xml_NALComment=$xml_NALUnit->item(0)->getElementsByTagName('comment');
                    $num_ticks=$xml_NALComment->item(0)->getAttribute('num_units_in_tick');
                    $time_scale=$xml_NALComment->item(0)->getAttribute('time_scale');
                    $profile_idc=$xml_NALUnit->item(0)->getAttribute('profile_idc');
                    $level_idc=$xml_NALComment->item(0)->getAttribute('level_idc');
                }
                if($width== NULL )
                    fprintf($opfile, "**'CMAF check violated: Section 7.3.2.4. - Each CMAF Fragment in combination with its associated Header SHALL contain sufficient metadata to be decoded and displayed when independently accessed, but 'width' missing in the Header of Rep/Track ".$id."\n");
                if($height==NULL)
                    fprintf($opfile, "**'CMAF check violated: Section 7.3.2.4. - Each CMAF Fragment in combination with its associated Header SHALL contain sufficient metadata to be decoded and displayed when independently accessed, but 'height' missing in the Header of Rep/Track ".$id."\n");
                if($sdType =='avc1' && $profile_idc ==NULL)
                    fprintf($opfile, "**'CMAF check violated: Section 7.3.2.4. - Each CMAF Fragment in combination with its associated Header SHALL contain sufficient metadata to be decoded and displayed when independently accessed, but 'profile_idc' missing in the Header of Rep/Track ".$id."\n");
                if($sdType =='avc1' && $level_idc==NULL)
                    fprintf($opfile, "**'CMAF check violated: Section 7.3.2.4. - Each CMAF Fragment in combination with its associated Header SHALL contain sufficient metadata to be decoded and displayed when independently accessed, but 'level_idc' missing in the Header of Rep/Track ".$id."\n");
                if($sdType =='avc1' && ($num_ticks==NULL || $time_scale==NULL))
                    fprintf($opfile, "**'CMAF check violated: Section 7.3.2.4. - Each CMAF Fragment in combination with its associated Header SHALL contain sufficient metadata to be decoded and displayed when independently accessed, but FPS info (num_ticks & time_scale) missing in the Header of Rep/Track ".$id."\n");
            }
        }
        if($xml_handlerType=='soun'){
            $xml_sounSample=$xml->getElementsByTagName('soun_sampledescription');
            $sdType=$xml_sounSample->item(0)->getAttribute('sdType');
            $samplingRate=$xml_sounSample->item(0)->getAttribute('sampleRate');    
            $xml_audioDec=$xml->getElementsByTagName('DecoderSpecificInfo');
            if($xml_audioDec->length>0)
                $channelConfig=$xml_audioDec->item(0)->getAttribute('channelConfig');
            if($sdType==NULL  )
                fprintf($opfile, "**'CMAF check violated: Section 7.3.2.4. - Each CMAF Fragment in combination with its associated Header SHALL contain sufficient metadata to be decoded and displayed when independently accessed, but audio 'sdTtype' missing in the Header of Rep/Track ".$id."\n");
            if($samplingRate==NULL)
                fprintf($opfile, "**'CMAF check violated: Section 7.3.2.4. - Each CMAF Fragment in combination with its associated Header SHALL contain sufficient metadata to be decoded and displayed when independently accessed, but audio 'samplingRate' missing in the Header of Rep/Track ".$id."\n");
            if($channelConfig==NULL)
                fprintf($opfile, "**'CMAF check violated: Section 7.3.2.4. - Each CMAF Fragment in combination with its associated Header SHALL contain sufficient metadata to be decoded and displayed when independently accessed, but audio 'channelConfig' missing in the Header of Rep/Track ".$id."\n");
        }
        
        $dash264 = false;
        if(strpos($profiles[$current_adaptation_set][$current_representation], "http://dashif.org/guidelines/dash264") !== false)
            $dash264 = true;
        
        $content_protection_len = (!$Adapt['ContentProtection']) ? sizeof($Adapt['Representation'][$current_representation]['ContentProtection']) : sizeof($Adapt['ContentProtection']);
        if($content_protection_len > 0 && $dash264 == true){
            if($xml->getElementsByTagName('tenc')->length ==0)
                fprintf($opfile, "**'CMAF check violated: Section 7.3.2.4. - Each CMAF Fragment in combination with its associated Header SHALL contain sufficient metadata to be decrypted when independently accessed, but missing in the Header of Rep/Track ".$id."\n");
            else{
                $xml_tenc=$xml->getElementsByTagName('tenc');
                $AuxInfoPresent=($xml_tenc->item(0)->getAttribute('default_IV_size')!=0);
                if($AuxInfoPresent){
                    for($j=0;$j<$xml_num_moofs;$j++){
                        $xml_traf=$xml_moof->item($j)->getElementsByTagName('traf');
                        $xml_senc=$xml_traf->item(0)->getElementsByTagName('senc');
                        if($xml_senc->length==0){
                           fprintf($opfile, "**'CMAF check violated: Section 7.4.2. - When Sample Encryption Sample Auxiliary Info is used, 'senc' SHALL be present in each CMAF Fragment, but not found in Rep/Track ".$id." Fragment ".($j+1)."\n");
                           fprintf($opfile, "**'CMAF check violated: Section 7.3.2.4. - Each CMAF Fragment in combination with its associated Header SHALL contain sufficient metadata to be decrypted when independently accessed, but missing in the Fragment ".($j+1)." of Rep/Track ".$id."\n");
                        }
                    }
                }
            }
        }
        
        //Segment Index box check.
        $sidx=$xml->getElementsByTagName('sidx');
        if($sidx->length>0){
            for($j=0; $j < $sidx->length; $j++){
                $ref_count=$sidx->item($j)->getAttribute('referenceCount');
                $syncSampleError=0;
                for($z=0; $z<$ref_count; $z++){
                    $ref_type=$sidx->item($j)->getAttribute('reference_type_'.($z+1));
                    if($ref_type!=0)
                        fprintf($opfile, "**'CMAF check violated: Section 7.3.3.3. - If SegmentIndexBoxes exist, each subsegment referenced in the SegmentIndexBox SHALL be a single CMAF Fragment contained in the CMAF Track File, but reference to Fragment not found in Rep/Track ".$id.", Segment ".($z+1)."\n");
                //Check on non_sync_sample
                 /*   if($xml_handlerType=='vide'){
                    $sap_type=intval($sidx[$j]->getAttribute('SAP_type_'.($z+1)));
                    $sample_count=$xml_trun[max($z,$j)]->getAttribute('sampleCount');
                    for($a=0;$a<$sample_count;$a++){
                        $sample_flag=intval($xml_trun[$z]->getAttribute('sample_flags_'.($a+1)));
                        // non_sync_sample is the 16th bit from MSB in 32-bit.
                        $sample_flag=$sample_flag & hexdec("00010000");//0x00010000; 
                        if($sap_type ==1 || $sap_type==2){ 
                           if($sample_flag !=0)
                               $syncSampleError=1;
                             //fprintf($opfile, "**'CMAF check violated: Section 7.5.16. - Within a video CMAF Track, TrackRunBox SHALL identify non-sync pictures with sample_is_non_sync_sample as 0 for SAP type 1 or 2, but not found in Rep/Track ".$id.", Fragment ".($z+1)."\n");
                        }else if(sample_flag!=hexdec("10000")){//0x10000
                            $syncSampleError=1;
                            //fprintf($opfile, "**'CMAF check violated: Section 7.5.16. - Within a video CMAF Track, TrackRunBox SHALL identify non-sync pictures with sample_is_non_sync_sample as 1 for SAP type other than 1 or 2, but not found in Rep/Track ".$id.", Fragment ".($z+1)."\n");
                        }
                    }  //This is to avoid printing for each sample in trun- it makes output log huge.
                        if($syncSampleError)
                            fprintf($opfile, "**'CMAF check violated: Section 7.5.16. - Within a video CMAF Track, TrackRunBox SHALL identify non-sync pictures with sample_is_non_sync_sample as 0 for SAP type 1 or 2, and 1 if not, but not found in Rep/Track ".$id.", Fragment ".(max($z,$j)+1)."\n");

                   }*/
                }
            }
        }
        
        $cmaf_mediaprofile_res = determineCMAFMediaProfiles($xml);
        $cmaf_mediaProfiles[$current_period][$current_adaptation_set][$current_representation]['cmafMediaProfile'] = $cmaf_mediaprofile_res[0];
        fprintf($opfile, $cmaf_mediaprofile_res[1]);
    }
    
    ## For reporting
    $search = file_get_contents($session_dir . '/Period' . $current_period . '/' . $error_file . '.txt'); //Search for errors within log file
    if (strpos($search, "Error") == false && strpos($search, "CMAF check violated") == false){
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
    
    return $file_location;
}

function determineCMAFMediaProfiles($xml){
    global $CMAFMediaProfileAttributesVideo, $CMAFMediaProfileAttributesAudio, $CMAFMediaProfileAttributesSubtitle;
    
    $compatible_brands=$xml->getElementsByTagName("ftyp")->item(0)->getAttribute("compatible_brands");
    $handler_type=$xml->getElementsByTagName("hdlr")->item(0)->getAttribute("handler_type");
    if($handler_type == 'vide'){
        $xml_MPParameters= $CMAFMediaProfileAttributesVideo;
        $videSampleDes=$xml->getElementsByTagName("vide_sampledescription")->item(0);
        $sdType=$videSampleDes->getAttribute("sdType");

        if($sdType=='avc1' || $sdType=='avc3'){
            $xml_MPParameters['codec'] = 'AVC';
            $nal_unit=$xml->getElementsByTagName("NALUnit");
            if($nal_unit->length!=0){
                for($nal_count=0;$nal_count<$nal_unit->length;$nal_count++){
                    if($nal_unit->item($nal_count)->getAttribute("nal_type")=="0x07"){    
                        $sps_unit=$nal_count;
                        break;
                    }
                }

                $avcC = $videSampleDes->getElementsByTagName('avcC')->item(0);
                $comment=$nal_unit->item($sps_unit)->getElementsByTagName("comment")->item(0);  
                $xml_MPParameters['profile']=$avcC->getAttribute("profile");
                $xml_MPParameters['level']=(float)($comment->getAttribute("level_idc"))/10;
                $xml_MPParameters['width']=$videSampleDes->getAttribute("width"); 
                $xml_MPParameters['height']=$videSampleDes->getAttribute("height"); 

                if($comment->getAttribute("vui_parameters_present_flag")=="0x1"){
                    if($comment->getAttribute("video_signal_type_present_flag")=="0x1"){
                        if($comment->getAttribute("colour_description_present_flag")=="0x1"){
                          $xml_MPParameters['color_primaries']=$comment->getAttribute("colour_primaries");
                          $xml_MPParameters['transfer_char']=$comment->getAttribute("transfer_characteristics");
                          $xml_MPParameters['matrix_coeff']=$comment->getAttribute("matrix_coefficients");
                        }
                        elseif($comment->getAttribute("colour_description_present_flag")=="0x0"){
                          $xml_MPParameters['color_primaries']="0x1";
                          $xml_MPParameters['transfer_char']="0x1";
                          $xml_MPParameters['matrix_coeff']="0x1";
                        }
                    }
                    if($comment->getAttribute("timing_info_present_flag")=="0x1" ){
                        $num_units_in_tick=$comment->getAttribute("num_units_in_tick");
                        $time_scale=$comment->getAttribute("time_scale");
                        $xml_MPParameters['framerate']=$time_scale/(2*$num_units_in_tick);
                    }
                }

                $brand_pos=strpos($compatible_brands,"cfsd") || strpos($compatible_brands,"cfhd");
                if($brand_pos!==False){
                    if(strpos($compatible_brands,"cfsd") !== FALSE)
                        $xml_MPParameters['brand']="cfsd";
                    if(strpos($compatible_brands,"cfhd") !== FALSE)
                        $xml_MPParameters['brand']="cfhd";
                }
            }
        }
        else if($sdType=='hev1' || $sdType=='hvc1'){
            $xml_MPParameters['codec']="HEVC";
            $hvcC=$xml->getElementsByTagName("hvcC");
            if($hvcC->length>0){
                $hvcC=$xml->getElementsByTagName("hvcC")->item(0);
                if(($hvcC->getAttribute("profile_idc")=="1") || ($hvcC->getAttribute("compatibility_flag_1"))=="1")
                    $profile="Main";
                elseif(($hvcC->getAttribute("profile_idc")=="2") || ($hvcC->getAttribute("compatibility_flag_2"))=="1")
                    $profile="Main10";
                else
                    $profile="Other";

                $tier=$hvcC->getAttribute("tier_flag");
                $xml_MPParameters['tier']=$tier;//Tier=0 is the main-tier.
                $xml_MPParameters['profile']=$profile;
                $xml_MPParameters['level']=(float)($hvcC->getAttribute("level_idc"))/30; //HEVC std defines level_idc is 30 times of actual level number.
            }
            $xml_MPParameters['width']=$videSampleDes->getAttribute("width"); 
            $xml_MPParameters['height']=$videSampleDes->getAttribute("height"); 
            $nal_unit=$xml->getElementsByTagName("NALUnit");
            if($nal_unit->length!=0){
                for($nal_count=0;$nal_count<$nal_unit->length;$nal_count++){
                    if($nal_unit->item($nal_count)->getAttribute("nal_unit_type")=="33"){    
                        $sps_unit=$nal_count;
                        break;
                    } 
                }

                $sps=$nal_unit->item($sps_unit);
                if($sps->getAttribute("vui_parameters_present_flag")=="1"){
                  if($sps->getAttribute("video_signal_type_present_flag")=="1"){
                      if($sps->getAttribute("colour_description_present_flag")=="1"){
                        $xml_MPParameters['color_primaries']=$sps->getAttribute("colour_primaries");
                        $xml_MPParameters['transfer_char']=$sps->getAttribute("transfer_characteristics");
                        $xml_MPParameters['matrix_coeff']=$sps->getAttribute("matrix_coeffs");
                      }
                      elseif($sps->getAttribute("colour_description_present_flag")=="0"){
                        $xml_MPParameters['color_primaries']="1";
                        $xml_MPParameters['transfer_char']="1";
                        $xml_MPParameters['matrix_coeff']="1";
                      }
                  }
                  if($sps->getAttribute("vui_timing_info_present_flag")=="1" ){
                      $num_units_in_tick=$sps->getAttribute("vui_num_units_in_tick");
                      $time_scale=$sps->getAttribute("vui_time_scale");
                      $xml_MPParameters['framerate']=$time_scale/($num_units_in_tick);
                  }
                }
                
                if(strpos($compatible_brands,"chhd"))
                    $xml_MPParameters['brand']="chhd";
                elseif(strpos($compatible_brands,"chh1"))
                    $xml_MPParameters['brand']="chh1";
                elseif(strpos($compatible_brands,"cud8"))
                    $xml_MPParameters['brand']="cud8";
                elseif(strpos($compatible_brands,"cud1"))
                    $xml_MPParameters['brand']="cud1";
                elseif(strpos($compatible_brands,"chd1"))
                    $xml_MPParameters['brand']="chd1";
                elseif(strpos($compatible_brands,"clg1"))
                    $xml_MPParameters['brand']="clg1";
            }
        }

        $MP = getVideoTrackMediaProfile($xml_MPParameters);
    }
    elseif($handler_type=='soun'){
        $xml_MPParameters= $CMAFMediaProfileAttributesAudio;
        $sounSampleDes=$xml->getElementsByTagName("soun_sampledescription")->item(0);
        $sdType=$sounSampleDes->getAttribute("sdType");
        if($sdType=="mp4a"){
            $xml_MPParameters['codec']="AAC";
            $decoderSpecInfo=$sounSampleDes->getElementsByTagName("DecoderSpecificInfo")->item(0);
            $xml_MPParameters['sampleRate']=$sounSampleDes->getAttribute('sampleRate');
            $xml_MPParameters['profile']=$decoderSpecInfo->getAttribute("audioObjectType");
            $xml_MPParameters['channels']=$decoderSpecInfo->getAttribute("channelConfig");

            $brand_pos=strpos($compatible_brands,"caaa") || strpos($compatible_brands,"caac");
            if($brand_pos!==False){
                if(strpos($compatible_brands,"caaa") !== FALSE){
                    $xml_MPParameters['brand']="caaa";
                }
                elseif(strpos($compatible_brands,"caac")){
                    $xml_MPParameters['brand']="caac";
                }
            }
            
            $levelcomment=$xml->getElementsByTagName("iods_OD");
            if($levelcomment->length>0){    
                $profileLevelString=$levelcomment->getAttribute("Comment");
                if($profileLevelString!==NULL){
                    $profileLevel=str_replace("audio profile/level is ", "", $profileLevelString);
                    $xml_MPParameters['level']==$profileLevel;
                }
            }
        }
        $MP = getAudioTrackMediaProfile($xml_MPParameters);
    }
    elseif($handler_type=='text'){
        $xml_MPParameters= $CMAFMediaProfileAttributesSubtitle;
        $textSampleDes=$xml->getElementsByTagName("text_sampledescription")->item(0);
        $sdType=$textSampleDes->getAttribute("sdType");
        if($sdType=='wvtt'){
            $xml_MPParameters['codec']="WebVTT";

            $brand_pos=strpos($compatible_brands,"cwvt");
            if($brand_pos!==False)
                $xml_MPParameters['brand']="cwvt";
        }
        $MP = getSubtitleTrackMediaProfile($xml_MPParameters);
    }
    elseif($handler_type=='subt'){
        $xml_MPParameters= $CMAFMediaProfileAttributesSubtitle;
        $subtSampleDes=$xml->getElementsByTagName("subt_sampledescription")->item(0);
        $sdType=$subtSampleDes->getAttribute("sdType");
        if($sdType=="stpp"){
            $mime=$subtSampleDes->getElementsByTagName("mime");
            if($mime->length>0){   
                $contentType=$mime->getAttribute("content_type");
                $subtypePosition=strpos($contentType, "ttml+xml")|| strpos($contentType, "mp4");
                $codecPosition=strpos($contentType, "im1t")|| strpos($contentType, "im1i");
                $xml_MPParameters['mimeType']=(strpos($contentType, "application")!==False ? "application" : "");
                $xml_MPParameters['codec']=($codecPosition!==False ? substr($contentType, $codecPosition, $codecPosition+3) : "");
                if(strpos($contentType, "ttml+xml")!==False)
                    $xml_MPParameters['mimeSubtype']="ttml+xml";
                elseif(strpos($contentType, "mp4")!==False)
                    $xml_MPParameters['mimeSubtype']="mp4";
            }
            $brand_pos=strpos($compatible_brands,"im1t") || strpos($compatible_brands,"im1i");
            if($brand_pos!==False){
                if(strpos($compatible_brands,"im1t") !== FALSE)
                    $xml_MPParameters['brand']="im1t";
                if(strpos($compatible_brands,"im1i") !== FALSE)
                    $xml_MPParameters['brand']="im1i";
            }
        }
        $MP = getSubtitleTrackMediaProfile($xml_MPParameters);
    }
    return $MP;
}

function getVideoTrackMediaProfile($xml_MPParameters){
    $videoMediaProfile="unknown"; $errorMsg="";
    if($xml_MPParameters['codec']=="AVC"){
        if($xml_MPParameters['profile'] <= 100){
            if($xml_MPParameters['level'] <= 3.1){
                if(($xml_MPParameters['color_primaries']=== "0x1" || $xml_MPParameters['color_primaries']=== "0x5" || $xml_MPParameters['color_primaries']=== "0x6") && 
                   ($xml_MPParameters['transfer_char'] ==="0x1" || $xml_MPParameters['transfer_char'] ==="0x6") && 
                   ($xml_MPParameters['matrix_coeff'] === "0x1" || $xml_MPParameters['matrix_coeff'] === "0x5" || $xml_MPParameters['matrix_coeff'] === "0x6")){
                    if($xml_MPParameters['height']<=576 && $xml_MPParameters['width']<=864 && $xml_MPParameters['framerate']<=60){
                        $videoMediaProfile = "AVC SD";
                        if($xml_MPParameters['brand'] != 'cfsd'){
                            //$errorMsg .= "'Warning for CMAF check: Section A.2- For a CMAF Track to comply with one of the media profiles in Table A.1, it SHOULD include the CMAF File Brandin the file its CMAF header', but not included.\n";
                        }
                    }
                    else{
                        $errorMsg .= "**'CMAF check violated: Section A.2- For a CMAF Track to comply with one of the media profiles in Table A.1, it SHALL not exceed the width, height or frame rate listed in the table, even if the AVC Level would permit higher values', but found width='".$xml_MPParameters['width']."', height='".$xml_MPParameters['height']."' and frame rate='".$xml_MPParameters['framerate']."'.\n";
                    }
                }
                else{
                    $errorMsg .= "**'CMAF check violated: Section A.2- For a CMAF Track to comply with one of the media profiles in Table A.1, it SHALL conform to the colour_primaries, transfer_characteristics and matrix_coefficients values from the options listed in the table', but found colour_primaries='".$xml_MPParameters['color_primaries']."', transfer_characteristics='".$xml_MPParameters['transfer_char']."' and matrix_coefficients='".$xml_MPParameters['matrix_coeff']."'.\n";
                }
            }
            elseif($xml_MPParameters['level'] <= 4.0){
                if($xml_MPParameters['color_primaries']=== "0x1" && $xml_MPParameters['transfer_char'] ==="0x1" && $xml_MPParameters['matrix_coeff'] === "0x1"){
                    if($xml_MPParameters['height']<=1080 && $xml_MPParameters['width']<=1920 && $xml_MPParameters['framerate']<=60){
                        $videoMediaProfile = "AVC HD";
                        if($xml_MPParameters['brand'] != 'cfhd'){
                            //$errorMsg .= "'Warning for CMAF check: Section A.2- For a CMAF Track to comply with one of the media profiles in Table A.1, it SHOULD include the CMAF File Brandin the file its CMAF header', but not included.\n";
                        }
                    }
                    else{
                        $errorMsg .= "**'CMAF check violated: Section A.2- For a CMAF Track to comply with one of the media profiles in Table A.1, it SHALL not exceed the width, height or frame rate listed in the table, even if the AVC Level would permit higher values', but found width='".$xml_MPParameters['width']."', height='".$xml_MPParameters['height']."' and frame rate='".$xml_MPParameters['framerate']."'\n";
                    }
                }
                else{
                    $errorMsg .= "**'CMAF check violated: Section A.2- For a CMAF Track to comply with one of the media profiles in Table A.1, it SHALL conform to the colour_primaries, transfer_characteristics and matrix_coefficients values from the options listed in the table', but found colour_primaries='".$xml_MPParameters['color_primaries']."', transfer_characteristics='".$xml_MPParameters['transfer_char']."' and matrix_coefficients='".$xml_MPParameters['matrix_coeff']."'.\n";
                }
            }
            elseif($xml_MPParameters['level'] <= 4.2){
                if($xml_MPParameters['color_primaries']=== "0x1" && $xml_MPParameters['transfer_char'] ==="0x1" && $xml_MPParameters['matrix_coeff'] === "0x1"){
                    if($xml_MPParameters['height']<=1080 && $xml_MPParameters['width']<=1920 && $xml_MPParameters['framerate']<=60){
                        $videoMediaProfile = "AVC HDHF";
                        if($xml_MPParameters['brand'] != 'chdf'){
                            //$errorMsg .= "'Warning for CMAF check: Section A.2- For a CMAF Track to comply with one of the media profiles in Table A.1, it SHOULD include the CMAF File Brandin the file its CMAF header', but not included.\n";
                        }
                    }
                    else{
                        $errorMsg .= "**'CMAF check violated: Section A.2- For a CMAF Track to comply with one of the media profiles in Table A.1, it SHALL not exceed the width, height or frame rate listed in the table, even if the AVC Level would permit higher values', but found width='".$xml_MPParameters['width']."', height='".$xml_MPParameters['height']."' and frame rate='".$xml_MPParameters['framerate']."'.\n";
                    }
                }
                else{
                    $errorMsg .= "**'CMAF check violated: Section A.2- For a CMAF Track to comply with one of the media profiles in Table A.1, it SHALL conform to the colour_primaries, transfer_characteristics and matrix_coefficients values from the options listed in the table', but found colour_primaries='".$xml_MPParameters['color_primaries']."', transfer_characteristics='".$xml_MPParameters['transfer_char']."' and matrix_coefficients='".$xml_MPParameters['matrix_coeff']."'.\n";
                }
            }
            else{
                $errorMsg .= "**'CMAF check violated: Section A.2- For a CMAF Track to comply with one of the media profiles in Table A.1, it SHALL not exceed the profile or level listed in the table', but level is exceeded with the value of ".$xml_MPParameters['level']."\n";
            }
        }
        else{
            $errorMsg .= "**'CMAF check violated: Section A.2- For a CMAF Track to comply with one of the media profiles in Table A.1, it SHALL not exceed the profile or level listed in the table', but profile is exceeded with the value of ".$xml_MPParameters['profile']."\n";
        }
    }
    elseif($xml_MPParameters['codec']=="HEVC"){
        if($xml_MPParameters['tier']==0){
            if($xml_MPParameters['profile'] == 'Main'){
                if($xml_MPParameters['level'] <= 4.1){
                    if($xml_MPParameters['color_primaries']=== "0x1" && $xml_MPParameters['transfer_char'] ==="0x1" && $xml_MPParameters['matrix_coeff'] === "0x1"){
                        if($xml_MPParameters['height'] <= 1080 && $xml_MPParameters['width'] <= 1920 && $xml_MPParameters['framerate'] <= 60){
                            $videoMediaProfile="HEVC HHD8";
                            if($xml_MPParameters['brand'] != 'chhd'){
                                //$errorMsg .= "'Warning for CMAF check: Section B.5- For a CMAF Track to comply with one of the media profiles in Table B.1, it SHOULD include the CMAF File Brand listed in its CMAF header', but not included.\n";
                            }
                        }
                        else{
                            $errorMsg .= "**'CMAF check violated: Section B.5- For a CMAF Track to comply with one of the media profiles in Table B.1, it SHALL not exceed the width, height or frame rate listed in the table, even if the HEVC Level would permit higher values', but found width='".$xml_MPParameters['width']."', height='".$xml_MPParameters['height']."' and frame rate='".$xml_MPParameters['framerate']."'.\n";
                        }
                    }
                    else{
                        $errorMsg .= "**'CMAF check violated: Section B.5- For a CMAF Track to comply with one of the media profiles in Table B.1, it SHALL conform to the colour_primaries, transfer_characteristics and matrix_coefficients values from the options listed in the table', but found colour_primaries='".$xml_MPParameters['color_primaries']."', transfer_characteristics='".$xml_MPParameters['transfer_char']."' and matrix_coefficients='".$xml_MPParameters['matrix_coeff']."'.\n";
                    }
                }
                elseif($xml_MPParameters['level'] <= 5.0){
                    if($xml_MPParameters['color_primaries']=== "0x1" && $xml_MPParameters['transfer_char'] ==="0x1" && $xml_MPParameters['matrix_coeff'] === "0x1"){
                        if($xml_MPParameters['height'] <= 2160 && $xml_MPParameters['width'] <= 3840 && $xml_MPParameters['framerate'] <= 60){
                            $videoMediaProfile="HEVC UHD8";
                            if($xml_MPParameters['brand'] != 'cud8'){
                                //$errorMsg .= "'Warning for CMAF check: Section B.5- For a CMAF Track to comply with one of the media profiles in Table B.1, it SHOULD include the CMAF File Brand listed in its CMAF header', but not included.\n";
                            }
                        }
                        else{
                            $errorMsg .= "**'CMAF check violated: Section B.5- For a CMAF Track to comply with one of the media profiles in Table B.1, it SHALL not exceed the width, height or frame rate listed in the table, even if the HEVC Level would permit higher values', but found width='".$xml_MPParameters['width']."', height='".$xml_MPParameters['height']."' and frame rate='".$xml_MPParameters['framerate']."'.\n";
                        }
                    }
                    else{
                        $errorMsg .= "**'CMAF check violated: Section B.5- For a CMAF Track to comply with one of the media profiles in Table B.1, it SHALL conform to the colour_primaries, transfer_characteristics and matrix_coefficients values from the options listed in the table', but found colour_primaries='".$xml_MPParameters['color_primaries']."', transfer_characteristics='".$xml_MPParameters['transfer_char']."' and matrix_coefficients='".$xml_MPParameters['matrix_coeff']."'.\n";
                    }
                }
                else{
                    $errorMsg .= "**'CMAF check violated: Section B.5- For a CMAF Track to comply with one of the media profiles in Table B.1, it SHALL conform to the colour_primaries, transfer_characteristics and matrix_coefficients values from the options listed in the table', but found colour_primaries='".$xml_MPParameters['color_primaries']."', transfer_characteristics='".$xml_MPParameters['transfer_char']."' and matrix_coefficients='".$xml_MPParameters['matrix_coeff']."'.\n";
                }
            }
            elseif($xml_MPParameters['profile'] == 'Main10'){
                if($xml_MPParameters['level'] <= 4.1){
                    if($xml_MPParameters['color_primaries']=== "0x1" && $xml_MPParameters['transfer_char'] ==="0x1" && $xml_MPParameters['matrix_coeff'] === "0x1"){
                        if($xml_MPParameters['height'] <= 1080 && $xml_MPParameters['width'] <= 1920 && $xml_MPParameters['framerate'] <= 60){
                            $videoMediaProfile="HEVC HHD10";
                            if($xml_MPParameters['brand'] != 'chh1'){
                                //$errorMsg .= "'Warning for CMAF check: Section B.5- For a CMAF Track to comply with one of the media profiles in Table B.1, it SHOULD include the CMAF File Brand listed in its CMAF header', but not included.\n";
                            }
                        }
                        else{
                            $errorMsg .= "**'CMAF check violated: Section B.5- For a CMAF Track to comply with one of the media profiles in Table B.1, it SHALL not exceed the width, height or frame rate listed in the table, even if the HEVC Level would permit higher values', but found width='".$xml_MPParameters['width']."', height='".$xml_MPParameters['height']."' and frame rate='".$xml_MPParameters['framerate']."'.\n";
                        }
                    }
                    else{
                        $errorMsg .= "**'CMAF check violated: Section B.5- For a CMAF Track to comply with one of the media profiles in Table B.1, it SHALL conform to the colour_primaries, transfer_characteristics and matrix_coefficients values from the options listed in the table', but found colour_primaries='".$xml_MPParameters['color_primaries']."', transfer_characteristics='".$xml_MPParameters['transfer_char']."' and matrix_coefficients='".$xml_MPParameters['matrix_coeff']."'.\n";
                    }
                }
                elseif($xml_MPParameters['level'] <= 5.1){
                    if(($xml_MPParameters['color_primaries']=== "0x1" || $xml_MPParameters['color_primaries']=== "0x9") && 
                       ($xml_MPParameters['transfer_char'] ==="0x1" || $xml_MPParameters['transfer_char'] ==="0x14" || $xml_MPParameters['transfer_char'] ==="0x15") && 
                       ($xml_MPParameters['matrix_coeff'] === "0x1" || $xml_MPParameters['matrix_coeff'] === "0x9" || $xml_MPParameters['matrix_coeff'] === "0x10")){
                        if($xml_MPParameters['height'] <= 2160 && $xml_MPParameters['width'] <= 3840 && $xml_MPParameters['framerate'] <= 60){
                            if($xml_MPParameters['brand'] == 'cud1'){
                                $videoMediaProfile="HEVC UHD10";
                            }
                            elseif($xml_MPParameters['brand'] == 'clg1'){
                                $videoMediaProfile="HEVC HLG10";
                            }
                            else{
                                $videoMediaProfile="HEVC UHD10, HEVC HLG10";
                                //$errorMsg .= "'Warning for CMAF check: Section B.5- For a CMAF Track to comply with one of the media profiles in Table B.1, it SHOULD include the CMAF File Brand listed in its CMAF header', but not included.\n";
                            }
                        }
                        else{
                            $errorMsg .= "**'CMAF check violated: Section B.5- For a CMAF Track to comply with one of the media profiles in Table B.1, it SHALL not exceed the width, height or frame rate listed in the table, even if the HEVC Level would permit higher values', but found width='".$xml_MPParameters['width']."', height='".$xml_MPParameters['height']."' and frame rate='".$xml_MPParameters['framerate']."'.\n";
                        }
                    }
                    elseif($xml_MPParameters['color_primaries']=== "0x9" && $xml_MPParameters['transfer_char'] ==="0x16" && 
                       ($xml_MPParameters['matrix_coeff'] === "0x9" || $xml_MPParameters['matrix_coeff'] === "0x10")){
                        if($xml_MPParameters['height'] <= 2160 && $xml_MPParameters['width'] <= 3840 && $xml_MPParameters['framerate'] <= 60){
                            $videoMediaProfile="HEVC HDR10";
                            if($xml_MPParameters['brand'] != 'chd1'){
                                //$errorMsg .= "'Warning for CMAF check: Section B.5- For a CMAF Track to comply with one of the media profiles in Table B.1, it SHOULD include the CMAF File Brand listed in its CMAF header', but not included.\n";
                            }
                        }
                        else{
                            $errorMsg .= "**'CMAF check violated: Section B.5- For a CMAF Track to comply with one of the media profiles in Table B.1, it SHALL not exceed the width, height or frame rate listed in the table, even if the HEVC Level would permit higher values', but found width='".$xml_MPParameters['width']."', height='".$xml_MPParameters['height']."' and frame rate='".$xml_MPParameters['framerate']."'.\n";
                        }
                    }
                    elseif($xml_MPParameters['color_primaries']=== "0x9" && 
                       ($xml_MPParameters['transfer_char'] ==="0x14" || $xml_MPParameters['transfer_char'] ==="0x18") && 
                       $xml_MPParameters['matrix_coeff'] === "0x9"){
                        if($xml_MPParameters['height'] <= 2160 && $xml_MPParameters['width'] <= 3840 && $xml_MPParameters['framerate'] <= 60){
                            $videoMediaProfile="HEVC HLG10";
                            if($xml_MPParameters['brand'] != 'clg1'){
                                //$errorMsg .= "'Warning for CMAF check: Section B.5- For a CMAF Track to comply with one of the media profiles in Table B.1, it SHOULD include the CMAF File Brand listed in its CMAF header', but not included.\n";
                            }
                        }
                        else{
                            $errorMsg .= "**'CMAF check violated: Section B.5- For a CMAF Track to comply with one of the media profiles in Table B.1, it SHALL not exceed the width, height or frame rate listed in the table, even if the HEVC Level would permit higher values', but found width='".$xml_MPParameters['width']."', height='".$xml_MPParameters['height']."' and frame rate='".$xml_MPParameters['framerate']."'.\n";
                        }
                    }
                    else{
                        $errorMsg .= "**'CMAF check violated: Section B.5- For a CMAF Track to comply with one of the media profiles in Table B.1, it SHALL conform to the colour_primaries, transfer_characteristics and matrix_coefficients values from the options listed in the table', but found colour_primaries='".$xml_MPParameters['color_primaries']."', transfer_characteristics='".$xml_MPParameters['transfer_char']."' and matrix_coefficients='".$xml_MPParameters['matrix_coeff']."'.\n";
                    }
                }
                else{
                    $errorMsg .= "**'CMAF check violated: Section B.5- For a CMAF Track to comply with one of the media profiles in Table B.1, it SHALL not exceed the tier, profile or level listed in the table', but level is exceeded with the value of '".$xml_MPParameters['level']."'.\n";
                }
            }
            else{
                $errorMsg .= "**'CMAF check violated: Section B.5- For a CMAF Track to comply with one of the media profiles in Table B.1, it SHALL not exceed the tier, profile or level listed in the table', but profile is exceeded with an unknown value.\n";
            }
        }
        else{
            $errorMsg .= "**'CMAF check violated: Section B.5- For a CMAF Track to comply with one of the media profiles in Table B.1, it SHALL not exceed the tier, profile or level listed in the table', but tier is exceeded with the value of '".$xml_MPParameters['tier']."'.\n";
        }
    }
    
    return [$videoMediaProfile,$errorMsg];
}

function getAudioTrackMediaProfile($xml_MPParameters){
    $audioMediaProfile="unknown"; $errorMsg="";
    if($xml_MPParameters['codec']=="AAC"){
        if(in_array($xml_MPParameters['profile'],array("0x02", "0x05", "0x1d"))){
            if($xml_MPParameters['level']!=="" && strpos($xml_MPParameters['level'], "AAC@L2")===FALSE){
                $errorMsg .= "**'CMAF check violated: Section 10.4- AAC CMAF media profile SHALL conform to the AAC CMAF track format with the following constraints: Each AAC elementary stream SHALL be encoded using MPEG-4 AAC LC, HE-AAC Level 2, or HE-AACv2 Level 2', but level information is not found or non-conforming.\n";
            }
            else{
                if($xml_MPParameters['channels']=="0x1" || $xml_MPParameters['channels']=="0x2"){
                    if($xml_MPParameters['sampleRate']<=48000){
                        if($xml_MPParameters['brand'] == 'caaa'){
                            $audioMediaProfile = "AAC Adaptive";
                        }
                        elseif($xml_MPParameters['brand'] == 'caac'){
                            $audioMediaProfile = "AAC Core";
                        }
                        elseif($xml_MPParameters['brand'] == ''){
                            $audioMediaProfile = "AAC Core";
                            //$errorMsg .= "'Warning for CMAF check: Section 10.4- AAC CMAF media profile SHALL conform to the AAC CMAF track format with the following constraints: AAC Core/Adaptive Audio FileTypeBox compatibility brand SHOULD be used to indicare CMAF tracks that conform to this (AAC Core/AAC Adaptive) media profile', but compatibility brand is not found.\n";
                        }
                        else{
                            $errorMsg .= "**'CMAF check violated: Section 10.4- AAC CMAF media profile SHALL conform to the AAC CMAF track format with the following constraints: AAC Core/Adaptive Audio FileTypeBox compatibility brand SHALL be 'caac'/'caaa', respectively', but compatibility brand is found to be '".$xml_MPParameters['brand']."'.\n";
                        }
                    }
                    else{
                        $errorMsg .= "**'CMAF check violated: Section 10.4/10.5- AAC CMAF media profile SHALL conform to the AAC CMAF track format with the following constraints: AAC Core elementary streams SHALL not exceed 48kHz sampling rate', but sampling rate is exceeded with the value of '".$xml_MPParameters['sampleRate']."'.\n";
                    }
                }
                else{
                    $errorMsg .= "**'CMAF check violated: Section 10.4- AAC CMAF media profile SHALL conform to the AAC CMAF track format with the following constraints: AAC Core CMAF tracks SHALL not exceed two audio channels', but number of channels is exceeded with the value of '".$xml_MPParameters['channels']."'.\n";
                }
            }
        }
        else{
            $errorMsg .= "**'CMAF check violated: Section 10.4- AAC CMAF media profile SHALL conform to the AAC CMAF track format with the following constraints: Each AAC elementary stream SHALL be encoded using MPEG-4 AAC LC, HE-AAC Level 2, or HE-AACv2 Level 2', but non-conforming profile is found.\n";
        }
    }
    
    return [$audioMediaProfile,$errorMsg];
}

function getSubtitleTrackMediaProfile($xml_MPParameters){
    $subtitleMediaProfile="unknown"; $errorMsg="";
    if($xml_MPParameters['codec']=="WebVTT"){
        if($xml_MPParameters['brand'] == 'cwvt'){
            $subtitleMediaProfile = 'WebVTT';
        }
    }
    elseif($xml_MPParameters['mimeType']=="application" && ($xml_MPParameters['mimeSubtype']=="ttml+xml" || $xml_MPParameters['mimeSubtype']=="mp4")){
        if($xml_MPParameters['codec']=="im1t"){
            $subtitleMediaProfile="TTML_IMSC1_Text";
        }
        elseif($xml_MPParameters['codec']=="im1i"){
            $subtitleMediaProfile="TTML_IMSC1_Image";
        }
    }
    return [$subtitleMediaProfile,$errorMsg];
}

function CMAFFalgs(){
    global $additional_flags;
    $additional_flags .= ' -cmaf';
}