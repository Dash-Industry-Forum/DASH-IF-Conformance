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

$MediaProfileAttributesVideo = array(
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

$MediaProfileAttributesAudio = array(
        "codec" => "",
        "profile" => "",
        "level" => "",
        "channels"=>"",
        "sampleRate"=>"",
        "brand"=>"");

$MediaProfileAttributesSubtitle = array(
        "codec" => "",
        "mimeType" => "",
        "mimeSubtype"=>"",
        "brand"=>"");


function CTASelectionSet()
{
    global $mpd_features,$session_dir,$CTAselectionset_infofile,$current_period,$adaptation_set_template, $opfile, $string_info, $progress_xml, $progress_report,
            $MediaProfDatabase;
    $opfile="";
    
    
    if(!($opfile = open_file($session_dir. '/Period' . $current_period . '/' . $CTAselectionset_infofile . '.txt', 'w'))){
        echo "Error opening/creating Selection Set conformance check file: "."./SelectionSet_infofile_ctawave.txt";
        return;
    }
    //fprintf($opfile, "**Selection Set conformance check: \n\n");
    $MediaProfDatabase=array();
    $adapts = $mpd_features['Period'][$current_period]['AdaptationSet'];
    $result=CTACheckSelectionSet(sizeof($adapts),$session_dir,$adaptation_set_template,$opfile);
    $infoResult=CTACheckSingleInitSwSet(sizeof($adapts),$session_dir,$adaptation_set_template);
    fwrite($opfile,$infoResult);
    fclose($opfile);
    
    $temp_string = str_replace(array('$Template$'),array($CTAselectionset_infofile),$string_info);
    file_put_contents($session_dir.'/Period'.$current_period.'/'.$CTAselectionset_infofile.'.html',$temp_string);
    
    $searchfiles = file_get_contents($session_dir.'/Period'.$current_period.'/'.$CTAselectionset_infofile.'.txt');
    if(strpos($searchfiles, "CTAWAVE check violated") !== FALSE){
        $progress_xml->Results[0]->Period[$current_period]->addChild('CTAWAVESelectionSet', 'error');
        $file_error[] = $session_dir.'/Period'.$current_period.'/'.$CTAselectionset_infofile.'.html';
    }
    elseif(strpos($searchfiles, "Warning") !== FALSE || strpos($searchfiles, "WARNING") !== FALSE){
        $progress_xml->Results[0]->Period[$current_period]->addChild('CTAWAVESelectionSet', 'warning');
        $file_error[] = $session_dir.'/Period'.$current_period.'/'.$CTAselectionset_infofile.'.html';
    }
    else{
        $progress_xml->Results[0]->Period[$current_period]->addChild('CTAWAVESelectionSet', 'noerror');
        $file_error[] = "noerror";
    }
    
    $tempr_string = str_replace('$Template$', '/Period'.$current_period.'/'.$CTAselectionset_infofile, $string_info);
    file_put_contents($session_dir.'/Period'.$current_period.'/'.$CTAselectionset_infofile.'.html', $tempr_string);
    $progress_xml->asXml(trim($session_dir . '/' . $progress_report));
    
    print_console($session_dir.'/Period'.$current_period.'/'.$CTAselectionset_infofile.'.txt', "Period " . ($current_period+1) . " CTA WAVE Selection Set Results");
}

function CTACheckSelectionSet($adapts_count,$session_dir,$adaptation_set_template,$opfile)
{
    global $MediaProfDatabase, $current_period,$profileCommandLine;
    $waveVideoTrackFound=0; $waveVideoSwSetFound=0;$videoSelectionSetFound=0;
    $waveAudioTrackFound=0; $waveAudioSwSetFound=0;$audioSelectionSetFound=0;
    $waveSubtitleTrackFound=0; $waveSubtitleSwSetFound=0;$subtitleSelectionSetFound=0;
    $handler_type=""; $errorMsg="";
    $profileMatched=array();
    $videoMPArray=array("HD", "HHD10", "UHD10", "HLG10","HDR10");
    $audioMPArray=array("AAC_Core", "Adaptive_AAC_Core", "AAC_Multichannel", "Enhanced_AC-3","AC-4_SingleStream","MPEG-H_SingleStream");
    $subtitleMPArray=array("TTML_IMSC1_Text", "TTML_IMSC1_Image");
    for($adapt_count=0; $adapt_count<$adapts_count; $adapt_count++){

        $SwSet_MP=array();
        $adapt_dir = str_replace('$AS$', $adapt_count, $adaptation_set_template);
        $loc = $session_dir. '/Period' .$current_period. '/'. $adapt_dir.'/';
        $filecount = 0;
        $files = glob($loc . "*.xml");
        if($files)
            $filecount = count($files);
        if(!file_exists($loc))
            fprintf ($opfile, "Switching Set ".$adapt_count."-Tried to retrieve data from a location that does not exist. \n (Possible cause: Representations are not valid and no file/directory for box info is created.)");
        else{
            for($fcount=0;$fcount<$filecount;$fcount++)
            {
                $xml = get_DOM($files[$fcount], 'atomlist');
                if($xml){
                    $hdlr=$xml->getElementsByTagName("hdlr")->item(0);
                    $handler_type=$hdlr->getAttribute("handler_type");
                    $MPTrackResult=getMediaProfile($xml,$handler_type,$fcount, $adapt_count,$opfile);
                    $MPTrack=$MPTrackResult[0];
                    //Update the MP database for future checks
                    $MediaProfDatabase[$current_period][$adapt_count][$fcount]=$MPTrack;
                    fprintf($opfile, $MPTrackResult[1]);
                    if(count($profileCommandLine)>0)
                    {
                        foreach($profileCommandLine as $profile){
                            if($profile===$MPTrack || $profile===FourCCEquivalent($MPTrack)){
                                fprintf($opfile, "Information: The Media profile found in track ".$fcount." of SwitchingSet ".$adapt_count." is-".$MPTrack. "\n");
                                $profileMatched[]=$profile;
                            }
                        }

                    }
                    else
                        fprintf($opfile, "Information: The Media profile found in track ".$fcount." of SwitchingSet ".$adapt_count." is-".$MPTrack. "\n");
                    if($handler_type=="vide")
                    {
                        $videoSelectionSetFound=1;
                        if(in_array($MPTrack, $videoMPArray))
                                $waveVideoTrackFound=1;
                    }
                    if($handler_type=="soun")
                    {
                        $audioSelectionSetFound=1;
                        if(in_array($MPTrack, $audioMPArray))
                                $waveAudioTrackFound=1;
                    }
                    if($handler_type=="subt")
                    {
                        $subtitleSelectionSetFound=1;
                        if(in_array($MPTrack, $subtitleMPArray))
                                $waveSubtitleTrackFound=1;
                    }
                       array_push($SwSet_MP, $MPTrack); 
                }
            }

            if(count(array_unique($SwSet_MP)) !== 1)
                fprintf ($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.1: 'WAVE content SHALL include one or more Switching Sets conforming to at least one WAVE approved CMAF Media Profile',but Switching Set ".$adapt_count." found with Tracks of different Media Profiles. \n");
            else{
                if($handler_type==="vide" && in_array(array_unique($SwSet_MP)[0], $videoMPArray)){
                        $waveVideoSwSetFound=1;
                }elseif($handler_type=="soun" && in_array(array_unique($SwSet_MP)[0], $audioMPArray)){
                        $waveAudioSwSetFound=1;
                }
                elseif($handler_type=="subt" && in_array(array_unique($SwSet_MP)[0], $subtitleMPArray)){
                        $waveSubtitleSwSetFound=1;
                }
            }
        }
    }
    //Check if at least one wave SwSet found.
    if($videoSelectionSetFound){
        if($waveVideoTrackFound!=1){
            $errorMsg="###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.1: 'WAVE content SHALL include one or more Tracks conforming to at least one WAVE approved CMAF Media Profile',but no Tracks found conforming to WAVE video Media Profile in the video Selection Set \n";
            fprintf ($opfile, $errorMsg );
        }elseif($waveVideoSwSetFound!=1){
            $errorMsg="###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.1: 'WAVE content SHALL include one or more Switching Sets conforming to at least one WAVE approved CMAF Media Profile',but no Switching Set found conforming to WAVE video Media Profile in the video Selection Set \n";
            fprintf ($opfile, $errorMsg );
        }
    }
    if($audioSelectionSetFound){
        if($waveAudioTrackFound!=1){
            $errorMsg="###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.1: 'WAVE content SHALL include one or more Tracks conforming to at least one WAVE approved CMAF Media Profile',but no Tracks found conforming to WAVE audio Media Profile in the audio Selection Set \n";
            fprintf ($opfile, $errorMsg);
        }elseif($waveAudioSwSetFound!=1){
            $errorMsg="###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.1: 'WAVE content SHALL include one or more Switching Sets conforming to at least one WAVE approved CMAF Media Profile',but no Switching Set found conforming to WAVE audio Media Profile in the audio Selection Set \n";
            fprintf ($opfile, $errorMsg);
        }
    }
    if($subtitleSelectionSetFound){
        if($waveSubtitleTrackFound!=1){
            $errorMsg="###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.1: 'WAVE content SHALL include one or more Tracks conforming to at least one WAVE approved CMAF Media Profile',but no Tracks found conforming to WAVE subtitle Media Profile in the subtitle Selection Set \n";
            fprintf ($opfile, $errorMsg);
        }elseif($waveSubtitleSwSetFound!=1){
            $errorMsg="###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.1: 'WAVE content SHALL include one or more Switching Sets conforming to at least one WAVE approved CMAF Media Profile',but no Switching Set found conforming to WAVE subtitle Media Profile in the subtitle Selection Set \n";
            fprintf ($opfile, $errorMsg);
        }
    }
    printCommandLineProfileInfo($profileCommandLine,$profileMatched,$opfile);
    return $errorMsg;
}

function getMediaProfile($xml,$handler_type,$repCount, $adaptCount,$opfile)
{
    global $MediaProfileAttributesVideo, $MediaProfileAttributesAudio, $MediaProfileAttributesSubtitle;
    $compatible_brands=$xml->getElementsByTagName("ftyp")->item(0)->getAttribute("compatible_brands");
    if($handler_type=='vide')
    {
        $xml_MPParameters= $MediaProfileAttributesVideo;
        $videSampleDes=$xml->getElementsByTagName("vide_sampledescription")->item(0);
        $sdType=$videSampleDes->getAttribute("sdType");
        if($sdType=='avc1' || $sdType=='avc3')
        {
            $xml_MPParameters['codec']="AVC";
            $nal_unit=$xml->getElementsByTagName("NALUnit");
            if($nal_unit->length==0){
               $errorMsg= "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',AVC media profiles conformance failed.  NAL unit not found in the sample description and identification of the exact media profile not possible for track ".$repCount." of SwitchingSet ".$adaptCount.". \n";      
               return ["unknown", $errorMsg];
            }
            else{
                for($nal_count=0;$nal_count<$nal_unit->length;$nal_count++)
                {
                    if($nal_unit->item($nal_count)->getAttribute("nal_type")=="0x07")
                    {    $sps_unit=$nal_count;
                         break;
                    }  
                }
            }
          $comment=$nal_unit->item($sps_unit)->getElementsByTagName("comment")->item(0);  
          $xml_MPParameters['profile']=$comment->getAttribute("profile");
          $xml_MPParameters['level']=(float)($comment->getAttribute("level_idc"))/10;
          $xml_MPParameters['width']=$videSampleDes->getAttribute("width"); 
          $xml_MPParameters['height']=$videSampleDes->getAttribute("height"); 
          if($comment->getAttribute("vui_parameters_present_flag")=="0x1")
          {
              if($comment->getAttribute("video_signal_type_present_flag")=="0x1")
              {
                  if($comment->getAttribute("colour_description_present_flag")=="0x1")
                  {
                    $xml_MPParameters['color_primaries']=$comment->getAttribute("colour_primaries");
                    $xml_MPParameters['transfer_char']=$comment->getAttribute("transfer_characteristics");
                    $xml_MPParameters['matrix_coeff']=$comment->getAttribute("matrix_coefficients");
                  }
                  elseif($comment->getAttribute("colour_description_present_flag")=="0x0")
                  {
                    $xml_MPParameters['color_primaries']="0x1";
                    $xml_MPParameters['transfer_char']="0x1";
                    $xml_MPParameters['matrix_coeff']="0x1";
                  }
              }
              if($comment->getAttribute("timing_info_present_flag")=="0x1" )
              {
                  $num_units_in_tick=$comment->getAttribute("num_units_in_tick");
                  $time_scale=$comment->getAttribute("time_scale");
                  $xml_MPParameters['framerate']=$time_scale/(2*$num_units_in_tick);
              }
          }
          $brand_pos=strpos($compatible_brands,"cfsd") || strpos($compatible_brands,"cfhd");
          if($brand_pos!==False)
                $xml_MPParameters['brand']=substr($compatible_brands,$brand_pos,$brand_pos+3);
                  
        }
        else if($sdType=='hev1' || $sdType=='hvc1')
        {
            $xml_MPParameters['codec']="HEVC";
            $hvcC=$xml->getElementsByTagName("hvcC");
            if($hvcC->length>0)
            {
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
            if($nal_unit->length==0){
                $errorMsg= "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',HEVC media profiles conformance failed.  NAL unit not found in the sample description and identification of the exact media profile not possible for track ".$repCount." of SwitchingSet ".$adaptCount.". \n";      
                return ["unknown", $errorMsg];
            }
            else{
                for($nal_count=0;$nal_count<$nal_unit->length;$nal_count++)
                {
                    if($nal_unit->item($nal_count)->getAttribute("nal_unit_type")=="33")
                    {    $sps_unit=$nal_count;
                         break;
                    }  
                }
            } 
            $sps=$nal_unit->item($sps_unit);
            if($sps->getAttribute("vui_parameters_present_flag")=="1")
            {
              if($sps->getAttribute("video_signal_type_present_flag")=="1")
              {
                  if($sps->getAttribute("colour_description_present_flag")=="1")
                  {
                    $xml_MPParameters['color_primaries']=$sps->getAttribute("colour_primaries");
                    $xml_MPParameters['transfer_char']=$sps->getAttribute("transfer_characteristics");
                    $xml_MPParameters['matrix_coeff']=$sps->getAttribute("matrix_coeffs");
                  }
                  elseif($sps->getAttribute("colour_description_present_flag")=="0")
                  {
                    $xml_MPParameters['color_primaries']="1";
                    $xml_MPParameters['transfer_char']="1";
                    $xml_MPParameters['matrix_coeff']="1";
                  }
              }
              if($sps->getAttribute("vui_timing_info_present_flag")=="1" )
              {
                  $num_units_in_tick=$sps->getAttribute("vui_num_units_in_tick");
                  $time_scale=$sps->getAttribute("vui_time_scale");
                  $xml_MPParameters['framerate']=$time_scale/($num_units_in_tick);
              }
            }
            $brand_pos=strpos($compatible_brands,"chh1") || strpos($compatible_brands,"cud1")||strpos($compatible_brands,"clg1")||strpos($compatible_brands,"chd1");
            if($brand_pos!==False)
                $xml_MPParameters['brand']=substr($compatible_brands,$brand_pos,$brand_pos+3);

        }
        //check which profile the track conforms to.
        $MP=checkAndGetConformingVideoProfile($xml_MPParameters,$repCount, $adaptCount);
        
    }
    elseif($handler_type=='soun')
    {
        $xml_MPParameters= $MediaProfileAttributesAudio;
        $sounSampleDes=$xml->getElementsByTagName("soun_sampledescription")->item(0);
        $sdType=$sounSampleDes->getAttribute("sdType");
        if($sdType=="mp4a")
        {
            $xml_MPParameters['codec']="AAC";
            $decoderSpecInfo=$sounSampleDes->getElementsByTagName("DecoderSpecificInfo")->item(0);
            $audioObj=$decoderSpecInfo->getAttribute("audioObjectType");
            $xml_MPParameters['profile']=$audioObj;
            $channels=$decoderSpecInfo->getAttribute("channelConfig");
            $xml_MPParameters['channels']=$channels;
            $xml_MPParameters['sampleRate']=$sounSampleDes->getAttribute('sampleRate');
            $brand_pos=strpos($compatible_brands,"caaa") || strpos($compatible_brands,"caac")|| $brand_pos=strpos($compatible_brands,"camc");
            if($brand_pos!==False)
                $xml_MPParameters['brand']=substr($compatible_brands,$brand_pos,$brand_pos+3);
            
            $levelcomment=$xml->getElementsByTagName("iods_OD");
            if($levelcomment->length>0)
            {    
                $profileLevelString=$levelcomment->getAttribute("Comment");
                if($profileLevelString!==NULL)
                {
                    $profileLevel=str_replace("audio profile/level is ", "", $profileLevelString);
                    $xml_MPParameters['level']==$profileLevel;
                }
            }
            
        }
        elseif($sdType=="ec-3")
        {
            $xml_MPParameters['codec']="EAC-3";
            $xml_MPParameters['profile']="EAC-3";
            $brand_pos=strpos($compatible_brands,"ceac");
            if($brand_pos!==False)
                $xml_MPParameters['brand']=substr($compatible_brands,$brand_pos,$brand_pos+3);
        }
        elseif($sdType=="ac-3")
        {
            $xml_MPParameters['codec']="AC-3";
            $xml_MPParameters['profile']="AC-3";
            $brand_pos=strpos($compatible_brands,"ceac");
            if($brand_pos!==False)
                $xml_MPParameters['brand']=substr($compatible_brands,$brand_pos,$brand_pos+3);
        }
        elseif($sdType=="ac-4")
        {
            $xml_MPParameters['codec']="AC-4";
            $xml_MPParameters['profile']="AC-4";
            $brand_pos=strpos($compatible_brands,"ca4s");
            if($brand_pos!==False)
                $xml_MPParameters['brand']=substr($compatible_brands,$brand_pos,$brand_pos+3);
        }
         elseif($sdType=="mhm1")
        {
            $xml_MPParameters['codec']="MPEG-H";
            $xml_MPParameters['sampleRate']=$sounSampleDes->getAttribute('sampleRate');
            $brand_pos=strpos($compatible_brands,"cmhs");
            $mhaC=$sounSampleDes->getElementsByTagName("mhaC");
            if($mhaC->length>0)
            {
                $xml_MPParameters['profile']=$mhaC->getAttribute("mpegh3daProfileLevelIndication");
                $xml_MPParameters['channel']=$mhaC->getAttribute("referenceChannelLayout");
            }
            if($brand_pos!==False)
                $xml_MPParameters['brand']=substr($compatible_brands,$brand_pos,$brand_pos+3);
        }
        $MP=checkAndGetConformingAudioProfile($xml_MPParameters,$repCount,$adaptCount);
    }
    elseif($handler_type=='subt')
    {
        $xml_MPParameters= $MediaProfileAttributesSubtitle;
        $subtSampleDes=$xml->getElementsByTagName("subt_sampledescription")->item(0);
        $sdType=$subtSampleDes->getAttribute("sdType");
        if($sdType=="stpp")
        {
            $mime=$subtSampleDes->getElementsByTagName("mime");
            if($mime->length>0){   
                $contentType=$mime->getAttribute("content_type");
                $subtypePosition=strpos($contentType, "ttml+xml")|| strpos($contentType, "mp4");
                $codecPosition=strpos($contentType, "im1t")|| strpos($contentType, "im1i");
                $xml_MPParameters['mimetype']=(strpos($contentType, "application")!==False ? "application" : "");
                $xml_MPParameters['codec']=($codecPosition!==False ? substr($contentType, $codecPosition, $codecPosition+3) : "");
                if(strpos($contentType, "ttml+xml")!==False)
                    $xml_MPParameters['mimeSubtype']="ttml+xml";
                elseif(strpos($contentType, "mp4")!==False)
                    $xml_MPParameters['mimeSubtype']="mp4";
            }
            $brand_pos=strpos($compatible_brands,"im1t") || strpos($compatible_brands,"im1i");
            if($brand_pos!==False)
                $xml_MPParameters['brand']=substr($compatible_brands,$brand_pos,$brand_pos+3);
        }
        $MP=checkAndGetConformingSubtitleProfile($xml_MPParameters,$repCount,$adaptCount);
    }
    return $MP;
}

function checkAndGetConformingVideoProfile($xml_MPParameters,$repCount, $adaptCount)
{
    $videoMediaProfile="unknown"; $errorMsg="";
    if($xml_MPParameters['codec']=="AVC")
    {
        if($xml_MPParameters['profile']==="high"|| $xml_MPParameters['profile']==="main")
        {
            if($xml_MPParameters['color_primaries']== "" || $xml_MPParameters['transfer_char'] =="" || $xml_MPParameters['matrix_coeff'] == ""){
                $errorMsg= "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',AVC media profiles conformance failed. color_primaries, transfer_char, matrix_coefficients are not found and identification of the exact media profile not possible for track ".$repCount." of SwitchingSet ".$adaptCount.". \n";      
            }
            else if($xml_MPParameters['color_primaries']=== "0x1" && $xml_MPParameters['transfer_char'] ==="0x1" && $xml_MPParameters['matrix_coeff'] === "0x1")
            {
                if($xml_MPParameters['height']<=1080 && $xml_MPParameters['width']<=1920 && $xml_MPParameters['framerate']<=60)
                {
                    if($xml_MPParameters['level']<=4.0  )
                        $videoMediaProfile="HD";
                    elseif($xml_MPParameters['level']<=4.2)
                        $videoMediaProfile="AVC_HDHF";
                    else
                        $errorMsg= "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',AVC media profiles conformance failed, level- ".$xml_MPParameters['level']." not matching to any WAVE AVC media profiles (expected- 4.0) for track ".$repCount." of SwitchingSet ".$adaptCount." \n";
                }
                else
                    $errorMsg= "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',AVC media profiles conformance failed. height/width/framerete are not matching to any WAVE AVC media profiles. Expected max height|width|framerate are 1080|1920|60, found ".$xml_MPParameters['height']."|".$xml_MPParameters['width']."|".$xml_MPParameters['framerate']. " for track ".$repCount." of SwitchingSet ".$adaptCount."\n";      

            }
            else
                $errorMsg= "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',AVC media profiles conformance failed. color_primaries/transfer_char/matrix_coefficients are not matching to any WAVE AVC media profiles. Expected color_primaries|transfer_char|matrix_coeff are 1|1|1, but found ".$xml_MPParameters['color_primaries']."|".$xml_MPParameters['tranfer_char']."|".$xml_MPParameters['matrix_coeff']." for track ".$repCount." of SwitchingSet ".$adaptCount."\n";      
 
        }
        else
            $errorMsg= "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',AVC media profiles conformance failed, profile- ".$xml_MPParameters['profile']." not matching to any WAVE AVC media profiles ('High' is expected) for track ".$repCount." of SwitchingSet ".$adaptCount."\n";      


    }
    elseif($xml_MPParameters['codec']=="HEVC"){
      
        if($xml_MPParameters['tier']==0){
            if($xml_MPParameters['profile']=="Main10"){
     
                if($xml_MPParameters['level'] > 5.1)
                {
                    $errorMsg= "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',HEVC media profiles conformance failed. HEVC codec found in the track but level- ".$xml_MPParameters['level']." not conforming to any HEVC Media profiles of WAVE (expected max level- 5.1) for track ".$repCount." of SwitchingSet ".$adaptCount."\n";

                }
                elseif($xml_MPParameters['level'] <= 4.1)
                {
                    if($xml_MPParameters['height'] <= 1080 && $xml_MPParameters['width'] <= 1920 && $xml_MPParameters['framerate'] <= 60 ){
                        
                        if($xml_MPParameters['color_primaries']== "1" && $xml_MPParameters['transfer_char'] =="1" && $xml_MPParameters['matrix_coeff'] == "1")
                        {
                            //if($xml_MPParameters['branc']=="chh1"){
                                $videoMediaProfile="HHD10";
                            //}  

                        }
                        elseif($xml_MPParameters['color_primaries']== "" || $xml_MPParameters['transfer_char'] =="" || $xml_MPParameters['matrix_coeff'] == ""){
                            $errorMsg= "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',HEVC media profiles conformance failed. HEVC HHD10 Media profile constraints seems matching however color_primaries, transfer_char, matrix_coefficients are not found and identification of the exact media profile not possible. for track ".$repCount." of SwitchingSet ".$adaptCount." \n";

                        }
                        else
                            $errorMsg= "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',HEVC media profiles conformance failed. HEVC codec (Main10 profile with level 4.1) found in the track but color_primaries/transfer_char/matrix_coefficients are not conforming to o HEVC (HHD10) Media profile of WAVE. Expected color_primaries|transfer_char|matrix_coeff are 1|1|1, but found ".$xml_MPParameters['color_primaries']."|".$xml_MPParameters['tranfer_char']."|".$xml_MPParameters['matrix_coeff']."for track ".$repCount." of SwitchingSet ".$adaptCount."\n";      

                    }
                    else
                        $errorMsg= "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',HEVC media profiles conformance failed. HEVC codec (Main10 profile with level 4.1) found in the track but height/width/framerate not conforming to HEVC (HHD10) Media profile of WAVE. Expected max height|width|framerate are 1080|1920|60, found ".$xml_MPParameters['height']."|".$xml_MPParameters['width']."|".$xml_MPParameters['framerate']."for track ".$repCount." of SwitchingSet ".$adaptCount."\n";

                }
                else{
                    //Check for other HEVC Media Profiles. Level <=5.1
                    if($xml_MPParameters['height'] > 2160 || $xml_MPParameters['width'] > 3840 || $xml_MPParameters['framerate'] > 60)
                    {
                       $errorMsg= "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',HEVC media profiles conformance failed. HEVC codec (Main10 profile with level 5.1) found in the track but height/width/framerate not conforming to any HEVC Media profiles of WAVE. Expected max height|width|framerate are 2160|3840|60, found ".$xml_MPParameters['height']."|".$xml_MPParameters['width']."|".$xml_MPParameters['framerate']."for track ".$repCount." of SwitchingSet ".$adaptCount."\n";

                    }
                    else//check color characteristics.
                    {
                        if($xml_MPParameters['color_primaries']== "" || $xml_MPParameters['transfer_char'] =="" || $xml_MPParameters['matrix_coeff'] == ""){
                            $errorMsg= "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',HEVC media profiles conformance failed. color_primaries, transfer_char, matrix_coefficients are not found and identification of the exact media profile not possible.for track ".$repCount." of SwitchingSet ".$adaptCount." \n";

                        }
                        elseif(in_array($xml_MPParameters['color_primaries'], array(1,9)) && in_array($xml_MPParameters['transfer_char'], array(1,14,15)) && in_array($xml_MPParameters['matrix_coeff'], array(1,9,10)))
                        {
                            //if($xml_MPParameters['branc']=="cud1"){
                                $videoMediaProfile="UHD10";

                           // }
                        }
                        elseif($xml_MPParameters['color_primaries']=="9" && $xml_MPParameters['transfer_char']=="16" && in_array($xml_MPParameters['matrix_coeff'], array(9,10)))
                        {
                            //if($xml_MPParameters['branc']=="chd1" ){
                                $videoMediaProfile="HDR10";

                            //}
                        }
                        elseif($xml_MPParameters['color_primaries']=="9" && in_array($xml_MPParameters['transfer_char'], array(18,14)) && $xml_MPParameters['matrix_coeff']=="9")
                        {
                            //if($xml_MPParameters['brand']=="clg1"){
                                $videoMediaProfile="HLG10";

                            //}
                        }
                        else
                            $errorMsg= "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',HEVC media profiles conformance failed. HEVC codec (Main10 profile with level 5.1) found in the track but color_primaries[".$xml_MPParameters['color_primaries']."]/transfer_char[".$xml_MPParameters['tranfer_char']."]/matrix_coefficients[".$xml_MPParameters['matrix_coeff']."] are not conforming to any HEVC Media profiles of WAVE, for track ".$repCount." of SwitchingSet ".$adaptCount." \n";      

                    }
                }
            }
            elseif($xml_MPParameters['profile']=="Main")
            {
                if($xml_MPParameters['color_primaries']== "" || $xml_MPParameters['transfer_char'] =="" || $xml_MPParameters['matrix_coeff'] == ""){
                    $errorMsg= "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',HEVC media profiles conformance failed. color_primaries, transfer_char, matrix_coefficients are not found and identification of the exact media profile not possible for track ".$repCount." of SwitchingSet ".$adaptCount." \n";      
                }
                else if($xml_MPParameters['color_primaries']== "1" || $xml_MPParameters['transfer_char'] =="1" || $xml_MPParameters['matrix_coeff'] == "1")
                {
                    if($xml_MPParameters['level']<="4.1")
                    {
                        if($xml_MPParameters['height']<=1080 && $xml_MPParameters['width']<=1920 && $xml_MPParameters['framerate']<=60)
                            $videoMediaProfile="HHD10";
                        else
                            $errorMsg= "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',HEVC Main(level 4.1) media profiles conformance failed by height/width/framerete. Expected max height|width|framerate are 1080|1920|60, found ".$xml_MPParameters['height']."|".$xml_MPParameters['width']."|".$xml_MPParameters['framerate']. " for track ".$repCount." of SwitchingSet ".$adaptCount."\n";      

                    }
                    elseif($xml_MPParameters['level']<="5.0")
                    {
                        if($xml_MPParameters['height']<=2160 && $xml_MPParameters['width']<=3840 && $xml_MPParameters['framerate']<=60)
                            $videoMediaProfile="UHD10";
                        else
                            $errorMsg= "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',HEVC Main(level 5.0) media profiles conformance failed by height/width/framerete. Expected max height|width|framerate are 2160|3840|60, found ".$xml_MPParameters['height']."|".$xml_MPParameters['width']."|".$xml_MPParameters['framerate']. " for track ".$repCount." of SwitchingSet ".$adaptCount."\n";      

                    }
                    else
                        $errorMsg= "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',HEVC 'Main' media profiles conformance failed, level- ".$xml_MPParameters['level']." not matching to any WAVE HEVC media profiles (expected- 4.1/5.0) for track ".$repCount." of SwitchingSet ".$adaptCount." \n";

                }
                else
                    $errorMsg= "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',HEVC 'Main' media profiles conformance failed. color_primaries/transfer_char/matrix_coefficients are not matching to any WAVE HEVC media profiles. Expected color_primaries|transfer_char|matrix_coeff are 1|1|1, but found ".$xml_MPParameters['color_primaries']."|".$xml_MPParameters['tranfer_char']."|".$xml_MPParameters['matrix_coeff']. "for track ".$repCount." of SwitchingSet ".$adaptCount."\n";        

            }
            else
                $errorMsg= "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',HEVC media profiles conformance failed. HEVC codec found in the track but profile- ".$xml_MPParameters['profile']." not conforming to any HEVC Media profiles of WAVE (expected- Main10/Main) for track ".$repCount." of SwitchingSet ".$adaptCount." \n";
        }
        else {
            $errorMsg= "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',HEVC media profiles conformance failed. HEVC codec found in the track but tier- ".$xml_MPParameters['tier']." not conforming to any HEVC Media tiers of WAVE (expected- Main) for track ".$repCount." of SwitchingSet ".$adaptCount." \n";

        }
        
    }
    return [$videoMediaProfile,$errorMsg];
    
}

function checkAndGetConformingAudioProfile($xml_MPParameters,$repCount,$adaptCount)
{
    $audioMediaProfile="unknown"; 
    $errorMsg="";
    if($xml_MPParameters['codec']=="AAC")
    {
        if($xml_MPParameters['sampleRate']<=48000)
        {
            if($xml_MPParameters['channels']=="0x1" || $xml_MPParameters['channels']=="0x2")
            {
                //Level is checked here , however level can not be found always from the atom xml as the IODS atom is not always present in the track.
                if($xml_MPParameters['level']!=="" && strpos($xml_MPParameters['level'], "AAC@L2")===FALSE){
                        $errorMsg[]= "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.3.1: 'Each WAVE audio Media Profile SHALL conform to normative ref. listed in Table 2', audio Media profiles conformance failed. AAC codec found but the level found for track ".$repCount." of SwitchingSet ".$adaptCount."- ".$xml_MPParameters['level'].", expected level 2. \n";
                }else
                {
                    if(in_array($xml_MPParameters['profile'],array("0x02", "0x05", "0x1d")))
                    {
                        if($xml_MPParameters["brand"]=="caaa")
                            $audioMediaProfile="Adaptive_AAC_Core";
                        else
                            $audioMediaProfile="AAC_Core";
                    }
                    else
                        $errorMsg[]= "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.3.1: 'Each WAVE audio Media Profile SHALL conform to normative ref. listed in Table 2', audio Media profiles conformance failed. AAC codec found but the profiles are not among the [AAC-LC, HE-AAC, HE-AAC v2] for track ".$repCount." of SwitchingSet ".$adaptCount." \n";
                }
              
  
            }
            elseif(in_array($xml_MPParameters['channels'], array("0x5","0x6","0x7","0xc","0xe")))
            {
                if($xml_MPParameters['profile'] == "0x05" || ($xml_MPParameters['profile'] == "0x02" && $xml_MPParameters['level'] == "High Quality Audio@L6"))
                    $audioMediaProfile="AAC_Multichannel";
                else
                    $errorMsg[]= "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.3.1: 'Each WAVE audio Media Profile SHALL conform to normative ref. listed in Table 2', audio Media profiles conformance failed. AAC multichannel codec found but the profiles are not among the [AAC-LC, HE-AAC] for track ".$repCount." of SwitchingSet ".$adaptCount." \n";

            }
            else
                $errorMsg[]= "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.3.1: 'Each WAVE audio Media Profile SHALL conform to normative ref. listed in Table 2', audio Media profiles conformance failed. AAC codec found but channels are not conforming, found ".$xml_MPParameters['channels']." but expected 1,2,5,6,7,12 or 14 for track ".$repCount." of SwitchingSet ".$adaptCount." \n";

        }
        else
            $errorMsg[]= "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.3.1: 'Each WAVE audio Media Profile SHALL conform to normative ref. listed in Table 2', audio Media profiles conformance failed. AAC codec found but sampling rate is not conforming, found ".$xml_MPParameters['sampleRate']." but expected 48kHz for track ".$repCount." of SwitchingSet ".$adaptCount." \n";
             
    }
    elseif($xml_MPParameters['codec']=="EAC-3" ||$xml_MPParameters['codec']=="AC-3")
        $audioMediaProfile="Enhanced_AC-3";
    elseif($xml_MPParameters['codec']=="AC-4")
        $audioMediaProfile="AC-4_SingleStream";
    elseif($xml_MPParameters['codec']=="MPEG-H")
    {
        if($xml_MPParameters['sampleRate']<=48000)
        {
            if(in_array($xml_MPParameters['profile'], array("11","12","13"))) // "0x0B", "0x0C", "0x0D"
            {
                if(in_array($xml_MPParameters['channels'], array("1","2","3","4","5","6","7","9","10","11","12","14","15","16","17","19")))
                        $audioMediaProfile="MPEG-H_SingleStream";
                else
                    $errorMsg= "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.3.1: 'Each WAVE audio Media Profile SHALL conform to normative ref. listed in Table 2', audio Media profiles conformance failed. MPEG-H codec found but the channels are not conforming, found ".$xml_MPParameters['channels']." but expected 1-7, 9-12, 14-17, or 19  for track ".$repCount." of SwitchingSet ".$adaptCount." \n";

            }
            else
                $errorMsg= "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.3.1: 'Each WAVE audio Media Profile SHALL conform to normative ref. listed in Table 2', audio Media profiles conformance failed. MPEG-H codec found but the profile found ('".$xml_MPParameters['profile']."') are not among the Low Complexity( LC 1,2,3)  for track ".$repCount." of SwitchingSet ".$adaptCount." \n";

        }    
        else
            $errorMsg= "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.3.1: 'Each WAVE audio Media Profile SHALL conform to normative ref. listed in Table 2', audio Media profiles conformance failed. MPEG-H codec found but sampling rate is not conforming, found ".$xml_MPParameters['sampleRate']." but expected 48kHz for track ".$repCount." of SwitchingSet ".$adaptCount." \n";

    }
    else
        $errorMsg= "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.3.1: 'Each WAVE audio Media Profile SHALL conform to normative ref. listed in Table 2', audio Media profiles conformance failed. codec parameter (".$xml_MPParameters['codec'].") is not as expected and identification of the exact media profile not possible for track ".$repCount." of SwitchingSet ".$adaptCount." \n";
      
    return [$audioMediaProfile, $errorMsg];
}

function checkAndGetConformingSubtitleProfile($xml_MPParameters,$repCount,$adaptCount)
{
    $subtitleMediaProfile="unknown"; $errorMsg="";
    if($xml_MPParameters['type']=="application" && ($xml_MPParameters['subType']=="ttml+xml" || $xml_MPParameters['subType']=="mp4"))
    {
        if($xml_MPParameters['codec']=="im1t")
            $subtitleMediaProfile="TTML_IMSC1_Text";
        elseif($xml_MPParameters['codec']=="im1i")
            $subtitleMediaProfile="TTML_IMSC1_Image";
        else
            $errorMsg= "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.4.1: 'Each WAVE subtitle Media Profile SHALL conform to normative ref. listed in Table 3', subtitle Media profiles conformance failed. codec parameter not found and identification of the exact media profile not possible for track ".$repCount." of SwitchingSet ".$adaptCount." \n";

    }
    else
    {
        $errorMsg="###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.4.1: 'Each WAVE subtitle Media Profile SHALL conform to normative ref. listed in Table 3', subtitle Media profiles conformance failed. mime box parameters (type/subtype/codec) are not found/conforming and identification of the exact media profile not possible for track ".$repCount." of SwitchingSet ".$adaptCount." \n";

    }
    return [$subtitleMediaProfile,$errorMsg];
}


function FourCCEquivalent($MP)
{
    $MPNames=["HD", "HHD10","UHD10","HLG10", "HDR10","AAC_Core", "Adaptive_AAC_Core", "AAC_Multichannel", "Enhanced_AC-3","AC-4_SingleStream","MPEG-H_SingleStream", "TTML_IMSC1_Text", "TTML_IMSC1_Image","unknown"];
    $fourCC=["cfhd","chh1","cud1","clg1","chd1","caac","caaa","camc","ceac","ca4s","cmhs","im1t","im1i","unknown"];
    $key = array_search($MP, $MPNames);
    return $fourCC[$key];
    
}

function printCommandLineProfileInfo($profileCommandLine,$profileMatched,$opfile)
{
    if(count($profileCommandLine)>0)
    {
        $DiffArray=array_diff($profileCommandLine, $profileMatched);
        foreach($DiffArray as $profile)
            fprintf ($opfile, "Information: No tracks found conforming to the given media profile- ".$profile."\n");
            
    }
}


function CTACheckSingleInitSwSet($adapts_count,$session_dir,$adaptation_set_template)
{
    $InfoMsg="";
    for($adapt_count=0; $adapt_count<$adapts_count; $adapt_count++){
        $adapt_dir = str_replace('$AS$', $adapt_count, $adaptation_set_template);
        $loc = $session_dir . '/' . $adapt_dir.'/';
        $filecount = 0;
        $files = glob($loc . "*.xml");
        if($files){
            $filecount = count($files);
            for($fcount=0;$fcount<$filecount;$fcount++)
            {
                $xml = get_DOM($files[$fcount], 'atomlist');
                if($xml){
                    $hdlr=$xml->getElementsByTagName("hdlr")->item(0);
                    $handler_type=$hdlr->getAttribute("handler_type");
                    if($handler_type=="vide")
                    {   
                        $sdTypeArray=array();
                        $sampleDesc=$xml->getElementsByTagName("vide_sampledescription");
                        if($sampleDesc->length >1)
                        {
                            $InfoMsg="Information:WAVE Content Spec 2018Ed-Section 7.2.2: Switching Set May conform to CMAF Single Initialization Constraints to indicate reinitialization not req on Track switches', and found CMAF common header with multiple sample descriptions in Sw Set ".$adapt_count." with entry types- ";
                            for($i=0;$i<$sampleDesc->length;$i++)
                                $InfoMsg.=$sampleDesc->item($i)->getAttribute("sdType"). ", ";
                            $InfoMsg.=". \n";
                        }
                    }
                }
            }
        }
    }
        return $InfoMsg;
}
?>