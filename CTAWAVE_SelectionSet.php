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

define("MediaProfileAttributesVideo", array(
        "codec" => "",
        "profile" => "",
        "level" => "",
        "height" => "",
        "width" => "",
        "framerate" => "",
        "color_primaries" => "",
        "transfer_char" => "",
        "matrix_coeff" => "",
 
        "brand"=>""));


$AVC_HD = array(
    "codec" => "AVC",
    "profile" => "High",
    "level" => "40",
    "height" => "1080",
    "width" => "1920",
    "framerate" => "60",
    "color_primaries" => "1",
    "transfer_char" => "1",
    "matrix_coeff" => "1",

    "brand" => "cfhd");

$AVC_HDHF = array(
    "codec" => "AVC",
    "profile" => "High",
    "level" => "42",
    "max_height" => "1080",
    "max_width" => "1920",
    "max_rate" => "60",
    "color_primaries" => "1",
    "transfer_char" => "1",
    "matrix_coeff" => "1",
    "brand" => "chdf");

$ACC_core = array(
    "codec" => "AAC", // in soun_sampledescription box  sdType="mp4a" 
    "profiles" => array("AAC-LC", "HE-AAC", "HE-AACv2"), // in DecoderSpecificInfo box audioObjectType="0x02" (0x02=>AAC-LC, 0x05=>HE-AAC, 0x29=>HE-AACv2) 
    "levels" => "2", // can't seem to find where it is contained
    "numb_channels" => array("mono", "stereo"), //in DecoderSpecificInfo box channelConfig="0x2" (0x1 for mono, 0x2 for stereo
    //should include ObjectType="0x40" to identify the type is audio and StreamType = 0x05 (Audio Stream)?
    );

$HEVC_HHD8 = array(
    "codec" => "HEVC",//'hvc1' or 'hev1' in vide_sampledescription box
    "profile" => array("Main", "Main Tier"), // profile_idc="1" in hvcc box (find the value mapping)
    "level" => "4.1", //level_idc="93" in hvcc box
    "height" => "1080", // in vide_sampledescription box
    "width" => "1920",// in vide_sampledescription box
    "framerate" => "60", // product of vui_num_units_in_tick="1" and vui_time_scale="24" in NAL_Unit_Array
    "color_primaries" => "1",
    "transfer_char" => "1",
    "matrix_coeff" => "1",
    "brand" => "chhd");

$HEVC_HHD10 = array(
    "codec" => "HEVC",//'hvc1' or 'hev1'  
    "profile" => array("Main10", "Main Tier"),
    "level" => "4.1",
    "height" => "1080",
    "width" => "1920",
    "framerate" => "60",
    "color_primaries" => "1",
    "transfer_char" => "1",
    "matrix_coeff" => "1",
    "brand" => "chh1");

$HEVC_UHD8 = array(
    "codec" => "HEVC",//'hvc1' or 'hev1'  
    "profile" => array("Main", "Main Tier"),
    "level" => "5.0",
    "height" => "2160",
    "width" => "3840",
    "framerate" => "60",
    "color_primaries" => "1",
    "transfer_char" => "1",
    "matrix_coeff" => "1",
    "brand" => "cud8");

$HEVC_UHD10 = array(
    "codec" => "HEVC",//'hvc1' or 'hev1'  
    "profile" => array("Main10", "Main Tier", "10-bit"),
    "level" => "5.1",
    "height" => "2160",
    "width" => "3840",
    "framerate" => "60",
    "color_primaries" => array("1", "9"),
    "transfer_char" => array("1", "14", "15"),
    "matrix_coeff" => array("1", "9", "10"),
    "brand" => "cud1");

$HEVC_HDR10 = array(
    "codec" => "HEVC",//'hvc1' or 'hev1'  
    "profile" => array("Main10", "Main Tier", "10-bit"),
    "level" => "5.1",
    "height" => "2160",
    "width" => "3840",
    "framerate" => "60",
    "color_primaries" => "9",
    "transfer_char" => "16",
    "matrix_coeff" => array("9", "10"),
    "brand" => "chd1");

$HEVC_HLG10 = array(
    "codec" => "HEVC",//'hvc1' or 'hev1'  
    "profile" => array("Main10", "Main Tier", "10-bit"),
    "level" => "5.1",
    "height" => "2160",
    "width" => "3840",
    "framerate" => "60",
    "color_primaries" => "9",
    "transfer_char" => array("18", "14"),
    "matrix_coeff" => "9",
    "brand" => "clg1");

define("MediaProfileAttributesAudio", array(
        "codec" => "",
        "profile" => "",
        "level" => "",
        "channels"=>"",
        "brand"=>""));
define("MediaProfileAttributesSubtitle", array(
        "codec" => "",
        "mimeType" => "",
        "mimeSubtype"=>"",
        "brand"=>""));



function checkSelectionSet()
{
    global $mpd_features,$session_dir,$selectionset_infofile,$current_period,$adaptation_set_template, $opfile ;
    $opfile="";
    $waveVideoTrackFound=0; $waveVideoSwSetFound=0;
    $waveAudioTrackFound=0; $waveAudioSwSetFound=0;
    $waveSubtitleTrackFound=0; $waveSubtitleSwSetFound=0;
    $handler_type="";
    
    if(!($opfile = open_file($session_dir. '/' . $selectionset_infofile . '.txt', 'w'))){
        echo "Error opening/creating Selection Set conformance check file: "."./SelectionSet_infofile.txt";
        return;
    }
    //fprintf($opfile, "**Selection Set conformance check: \n\n");
    
    $adapts = $mpd_features['Period'][$current_period]['AdaptationSet'];
    for($adapt_count=0; $adapt_count<sizeof($adapts); $adapt_count++){
        $Adapt = $adapts[$adapt_count];
        $SwSet_MP=array();
        $adapt_dir = str_replace('$AS$', $adapt_count, $adaptation_set_template);
        $loc = $session_dir . '/' . $adapt_dir.'/';
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
                $hdlr=$xml->getElementsByTagName("hdlr")->item(0);
                $handler_type=$hdlr->getAttribute("handler_type");
                $MPTrack=getMediaProfile($xml,$handler_type,$fcount, $adapt_count);
                fprintf($opfile, "Information: The Media profile found in track ".$fcount." of SwitchingSet ".$adapt_count." is-".$MPTrack. "\n");
                if($handler_type=="vide")
                {
                    $videoSelectionSetFound=1;
                                        fprintf($opfile, in_array(array_unique($SwSetMP), ["AAC_Core", "Adaptive_AAC_Core", "AAC_Multichannel", "Enhanced_AC-3","AC-4_SingleStream","MPEG-H_SingleStream"]));

                    if(in_array($MPTrack, ["AVC_HD", "HHD10", "UHD_10", "HLG_10","HDR10"]))
                            $waveVideoTrackFound=1;
                }
                if($handler_type=="soun")
                {
                    $audioSelectionSetFound=1;
                    if(in_array($MPTrack, ["AAC_Core", "Adaptive_AAC_Core", "AAC_Multichannel", "Enhanced_AC-3","AC-4_SingleStream","MPEG-H_SingleStream"]))
                            $waveAudioTrackFound=1;
                }
                if($handler_type=="subt")
                {
                    $subtitleSelectionSetFound=1;
                    if(in_array($MPTrack, ["TTML_IMSC1_Text", "TTML_IMSC1_Image"]))
                            $waveSubtitleTrackFound=1;
                }
                   array_push($SwSet_MP, $MPTrack); 
            }
            if(count(array_unique($SwSet_MP)) !== 1)
                fprintf ($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'WAVE content SHALL include one or more Switching Sets conforming to at least one WAVE approved CMAF Media Profile',but Switching Set ".$adapt_count." found with Tracks of different Media Profiles. \n");
            else{
                if($handler_type==="vide" && in_array(array_unique($SwSetMP), ["AVC_HD", "HHD10", "UHD_10", "HLG_10","HDR10"])){
                        $waveVideoSwSetFound=1;
                }elseif($handler_type=="soun" && in_array(array_unique($SwSetMP), ["AAC_Core", "Adaptive_AAC_Core", "AAC_Multichannel", "Enhanced_AC-3","AC-4_SingleStream","MPEG-H_SingleStream"])){
                        $waveAudioSwSetFound=1;
                }
                elseif($handler_type=="subt" && in_array(array_unique($SwSetMP), ["TTML_IMSC1_Text", "TTML_IMSC1_Image"])){
                        $waveSubtitleSwSetFound=1;
                }
            }
            
            
        }
    }
    //Check if at least one wave SwSet found.
    if($videoSelectionSetFound){
        if($waveVideoTrackFound!=1)
            fprintf ($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'WAVE content SHALL include one or more Tracks conforming to at least one WAVE approved CMAF Media Profile',but no Tracks found conforming to WAVE video Media Profile in the video Selection Set \n");
        elseif($waveVideoSwSetFound!=1)
            fprintf ($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'WAVE content SHALL include one or more Switching Sets conforming to at least one WAVE approved CMAF Media Profile',but no Switching Set found conforming to WAVE video Media Profile in the video Selection Set \n");

    }
    if($audioSelectionSetFound){
        if($waveAudioTrackFound!=1)
            fprintf ($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'WAVE content SHALL include one or more Tracks conforming to at least one WAVE approved CMAF Media Profile',but no Tracks found conforming to WAVE audio Media Profile in the audio Selection Set \n");
        elseif($waveAudioSwSetFound!=1)
            fprintf ($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'WAVE content SHALL include one or more Switching Sets conforming to at least one WAVE approved CMAF Media Profile',but no Switching Set found conforming to WAVE audio Media Profile in the audio Selection Set \n");

    }
    if($subtitleSelectionSetFound){
        if($waveSubtitleTrackFound!=1)
            fprintf ($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'WAVE content SHALL include one or more Tracks conforming to at least one WAVE approved CMAF Media Profile',but no Tracks found conforming to WAVE subtitle Media Profile in the subtitle Selection Set \n");
        elseif($waveSubtitleSwSetFound!=1)
            fprintf ($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'WAVE content SHALL include one or more Switching Sets conforming to at least one WAVE approved CMAF Media Profile',but no Switching Set found conforming to WAVE subtitle Media Profile in the subtitle Selection Set \n");
    }
    fclose($opfile);
}
function getMediaProfile($xml,$handler_type,$repCount, $adaptCount)
{
    global $opfile;
    $compatible_brands=$xml->getElementsByTagName("ftyp")->item(0)->getAttribute("compatible_brands");
    if($handler_type=='vide')
    {
        $xml_MPParameters= MediaProfileAttributesVideo;
        $videSampleDes=$xml->getElementsByTagName("vide_sampledescription")->item(0);
        $sdType=$videSampleDes->getAttribute("sdType");
        if($sdType=='avc1')
        {
            $xml_MPParameters['codec']="AVC";
            $nal_unit=$xml->getElementsByTagName("NALUnit");
            if($nal_unit->length==0){
                fprintf ($opfile, "###NAL unit not found in the sample description");
                return;
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
          $xml_MPParameters['level']=$comment->getAttribute("level_idc");
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
                    $xml_MPParameters['color_primaries']="1";
                    $xml_MPParameters['transfer_char']="1";
                    $xml_MPParameters['matrix_coeff']="1";
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
            $hvcC=$xml->getElementsByTagName("hvcC")->item(0);
            if(($hvcC->getAttribute("profile_idc")==1) || ($hvcC->getAttribute("compatibility_flag_1"))==1)
                $profile="Main";
            elseif(($hvcC->getAttribute("profile_idc")==2) || ($hvcC->getAttribute("compatibility_flag_2"))==1)
                $profile="Main10";
            else
                $profile="Other";
            
            $tier=$hvcC->getAttribute("tier_flag");
            /*array_push(*/$xml_MPParameters['profile']=$profile;//, $tier);//Tier=0 is the main-tier.
            $xml_MPParameters['level']=(float)($hvcC->getAttribute("level_idc"))/30; //HEVC std defines level_idc is 30 times of actual level number.
            $xml_MPParameters['width']=$videSampleDes->getAttribute("width"); 
            $xml_MPParameters['height']=$videSampleDes->getAttribute("height"); 
            $nal_unit=$xml->getElementsByTagName("NALUnit");
            if($nal_unit->length==0){
                fprintf ($opfile, "### NAL unit not found in the sample description \n");
                return;
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
            if($sps->getAttribute("vui_parameters_present_flag")=="0x1")
            {
              if($sps->getAttribute("video_signal_type_present_flag")=="0x1")
              {
                  if($sps->getAttribute("colour_description_present_flag")=="0x1")
                  {
                    $xml_MPParameters['color_primaries']=$sps->getAttribute("colour_primaries");
                    $xml_MPParameters['transfer_char']=$sps->getAttribute("transfer_characteristics");
                    $xml_MPParameters['matrix_coeff']=$sps->getAttribute("matrix_coeffs");
                  }
                  elseif($sps->getAttribute("colour_description_present_flag")=="0x0")
                  {
                    $xml_MPParameters['color_primaries']="1";
                    $xml_MPParameters['transfer_char']="1";
                    $xml_MPParameters['matrix_coeff']="1";
                  }
              }
              if($comment->getAttribute("vui_timing_info_present_flag")=="0x1" )
              {
                  $num_units_in_tick=$comment->getAttribute("vui_num_units_in_tick");
                  $time_scale=$comment->getAttribute("vui_time_scale");
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
        $xml_MPParameters= MediaProfileAttributesAudio;
        $sounSampleDes=$xml->getElementsByTagName("soun_sampledescription")->item(0);
        $sdType=$sounSampleDes->getAttribute("sdType");
        if($sdType=="mp4a")
        {
            $xml_MPParameters['codec']="AAC";
            $decoderSpecInfo=$sounSampleDes->getElementsByTagName("DecoderSpecificInfo")->item(0);
            //$audioObj=$decoderSpecInfo->getAttribute("audioObjectType");
            $channels=$decoderSpecInfo->getAttribute("channelConfig");
            $xml_MPParameters['channels']=$channels;
            //Todo , extract profile and level.
            $brand_pos=strpos($compatible_brands,"caaa") || strpos($compatible_brands,"caac")|| $brand_pos=strpos($compatible_brands,"camc");
             if($brand_pos!==False)
                $xml_MPParameters['brand']=substr($compatible_brands,$brand_pos,$brand_pos+3);
            
        }
        elseif($sdType=="ec-3")
        {
            $xml_MPParameters['codec']="EC-3";
            $brand_pos=strpos($compatible_brands,"ceac");
             if($brand_pos!==False)
                $xml_MPParameters['brand']=substr($compatible_brands,$brand_pos,$brand_pos+3);
        }
        elseif($sdType=="ac-4")
        {
            $xml_MPParameters['codec']="AC-4";
            $brand_pos=strpos($compatible_brands,"ca4s");
             if($brand_pos!==False)
                $xml_MPParameters['brand']=substr($compatible_brands,$brand_pos,$brand_pos+3);
        }
         elseif($sdType=="mhm1")
        {
            $xml_MPParameters['codec']="MPEG-H";
            $brand_pos=strpos($compatible_brands,"cmhs");
             if($brand_pos!==False)
                $xml_MPParameters['brand']=substr($compatible_brands,$brand_pos,$brand_pos+3);
        }
        $MP=checkAndGetConformingAudioProfile($xml_MPParameters,$repCount,$adaptCount);
    }
    elseif($handler_type=='subt')
    {
        $xml_MPParameters= MediaProfileAttributesSubtitle;
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
    $errorFlag=0; $videoMediaProfile="unknown";
    global $opfile;
    if($xml_MPParameters['codec']=="AVC")
    {
        if($xml_MPParameters['profile']=="high"|| $xml_MPParameters['profile']=="main")
        {
            if($xml_MPParameters['color_primaries']== "" || $xml_MPParameters['transfer_char'] =="" || $xml_MPParameters['matrix_coeff'] == ""){
                fprintf ($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',AVC media profiles conformance failed. color_primaries, transfer_char, matrix_coefficients are not found and identification of the exact media profile not possible for track ".$repCount." of SwitchingSet ".$adaptCount.". \n");      
            }
            else if($xml_MPParameters['color_primaries']== "1" || $xml_MPParameters['transfer_char'] =="1" || $xml_MPParameters['matrix_coeff'] == "1")
            {
                if($xml_MPParameters['height']<=1080 && $xml_MPParameters['width']<=1920 && $xml_MPParameters['framerate']<=60)
                {
                    if($xml_MPParameters['level']<="4.0"  )
                        $videoMediaProfile="AVC_HD";
                    elseif($xml_MPParameters['level']<="4.2")
                        $videoMediaProfile="AVC_HDHF";
                    else
                        fprintf ($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',AVC media profiles conformance failed, level- ".$xml_MPParameters['level']." not matching to any WAVE AVC media profiles (expected- 4.0) for track ".$repCount." of SwitchingSet ".$adaptCount." \n");
                }
                else
                    fprintf ($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',AVC media profiles conformance failed. height/width/framerete are not matching to any WAVE AVC media profiles. Expected max height|width|framerate are 1080|1920|60, found ".$xml_MPParameters['height']."|".$xml_MPParameters['width']."|".$xml_MPParameters['framerate']. " for track ".$repCount." of SwitchingSet ".$adaptCount."\n");      

            }
            else
                fprintf ($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',AVC media profiles conformance failed. color_primaries/transfer_char/matrix_coefficients are not matching to any WAVE AVC media profiles. Expected color_primaries|transfer_char|matrix_coeff are 1|1|1, but found ".$xml_MPParameters['color_primaries']."|".$xml_MPParameters['tranfer_char']."|".$xml_MPParameters['matrix_coeff']." for track ".$repCount." of SwitchingSet ".$adaptCount."\n");      
 
        }
        else
            fprintf ($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',AVC media profiles conformance failed, profile- ".$xml_MPParameters['profile']." not matching to any WAVE AVC media profiles ('High' is expected). for track ".$repCount." of SwitchingSet ".$adaptCount."\n");      


    }
    elseif($xml_MPParameters['codec']=="HEVC"){
     
        if($xml_MPParameters['profile']=="Main10"){
            
            if($xml_MPParameters['level'] > 5.1)
            {
                fprintf ($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',HEVC media profiles conformance failed. HEVC codec found in the track but level- ".$xml_MPParameters['level']." not conforming to any HEVC Media profiles of WAVE (expected max level- 5.1) for track ".$repCount." of SwitchingSet ".$adaptCount."\n");
                //displayMPparameters($xml_MPParameters);
            }
            elseif($xml_MPParameters['level'] <= 4.1)
            {
                if($xml_MPParameters['height'] < 1080 && $xml_MPParameters['width'] < 1920 && $xml_MPParameters['framerate'] < 60 ){
                    if($xml_MPParameters['color_primaries']== 1 && $xml_MPParameters['transfer_char'] ==1 && $xml_MPParameters['matrix_coeff'] == 1)
                    {
                        if($xml_MPParameters['branc']=="chh1" || $xml_MPParameters['brand']==""){
                            $videoMediaProfile=="HHD10";
                        }  
                    }
                    elseif($xml_MPParameters['color_primaries']== "" || $xml_MPParameters['transfer_char'] =="" || $xml_MPParameters['matrix_coeff'] == ""){
                        fprintf ($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',HEVC media profiles conformance failed. HEVC HHD10 Media profile constraints seems matching however color_primaries, transfer_char, matrix_coefficients are not found and identification of the exact media profile not possible. for track ".$repCount." of SwitchingSet ".$adaptCount." \n");
 
                    }
                    else
                        fprintf ($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',HEVC media profiles conformance failed. HEVC codec (Main10 profile with level 4.1) found in the track but color_primaries/transfer_char/matrix_coefficients are not conforming to o HEVC (HHD10) Media profile of WAVE. Expected color_primaries|transfer_char|matrix_coeff are 1|1|1, but found ".$xml_MPParameters['color_primaries']."|".$xml_MPParameters['tranfer_char']."|".$xml_MPParameters['matrix_coeff']."for track ".$repCount." of SwitchingSet ".$adaptCount."\n");      

                }
                else
                    fprintf ($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',HEVC media profiles conformance failed. HEVC codec (Main10 profile with level 4.1) found in the track but height/width/framerate not conforming to HEVC (HHD10) Media profile of WAVE. Expected max height|width|framerate are 1080|1920|60, found ".$xml_MPParameters['height']."|".$xml_MPParameters['width']."|".$xml_MPParameters['framerate']."for track ".$repCount." of SwitchingSet ".$adaptCount."\n");

            }
            else{
                //Check for other HEVC Media Profiles. Level <=5.1
                if($xml_MPParameters['height'] > 2160 || $xml_MPParameters['width'] > 3840 || $xml_MPParameters['framerate'] > 60)
                {
                    fprintf ($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',HEVC media profiles conformance failed. HEVC codec (Main10 profile with level 5.1) found in the track but height/width/framerate not conforming to any HEVC Media profiles of WAVE. Expected max height|width|framerate are 2160|3840|60, found ".$xml_MPParameters['height']."|".$xml_MPParameters['width']."|".$xml_MPParameters['framerate']."for track ".$repCount." of SwitchingSet ".$adaptCount."\n");

                }
                else//check color characteristics.
                {
                    if($xml_MPParameters['color_primaries']== "" || $xml_MPParameters['transfer_char'] =="" || $xml_MPParameters['matrix_coeff'] == ""){
                        fprintf ($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',HEVC media profiles conformance failed. color_primaries, transfer_char, matrix_coefficients are not found and identification of the exact media profile not possible.for track ".$repCount." of SwitchingSet ".$adaptCount." \n");

                    }
                    elseif(in_array($xml_MPParameters['color_primaries'], [1,9]) && in_array($xml_MPParameters['transfer_char'], [1,14,15] && in_array($xml_MPParameters['matrix_coeff'], [1,9,10])))
                    {
                        //if($xml_MPParameters['branc']=="cud1" || $xml_MPParameters['brand']==""){
                            $videoMediaProfile=="UHD10";

                       // }
                    }
                    elseif($xml_MPParameters['color_primaries']=="9" && $xml_MPParameters['transfer_char']=="16" && in_array($xml_MPParameters['matrix_coeff'], [9,10]))
                    {
                        //if($xml_MPParameters['branc']=="chd1" || $xml_MPParameters['brand']==""){
                            $videoMediaProfile=="HDR10";

                        //}
                    }
                    elseif($xml_MPParameters['color_primaries']=="9" && in_array($xml_MPParameters['transfer_char'], [18,14]) && $xml_MPParameters['matrix_coeff']=="9")
                    {
                        //if($xml_MPParameters['brand']=="clg1" || $xml_MPParameters['brand']==""){
                            $videoMediaProfile=="HLG10";

                        //}
                    }
                    else
                        fprintf ($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',HEVC media profiles conformance failed. HEVC codec (Main10 profile with level 5.1) found in the track but color_primaries[".$xml_MPParameters['color_primaries']."]/transfer_char[".$xml_MPParameters['tranfer_char']."]/matrix_coefficients[".$xml_MPParameters['matrix_coeff']."] are not conforming to any HEVC Media profiles of WAVE, for track ".$repCount." of SwitchingSet ".$adaptCount." \n");      

                }
            }
        }
        elseif($xml_MPParameters['profile']=="Main")
        {
            if($xml_MPParameters['color_primaries']== "" || $xml_MPParameters['transfer_char'] =="" || $xml_MPParameters['matrix_coeff'] == ""){
                fprintf ($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',HEVC media profiles conformance failed. color_primaries, transfer_char, matrix_coefficients are not found and identification of the exact media profile not possible for track ".$repCount." of SwitchingSet ".$adaptCount." \n");      
            }
            else if($xml_MPParameters['color_primaries']== "1" || $xml_MPParameters['transfer_char'] =="1" || $xml_MPParameters['matrix_coeff'] == "1")
            {
                if($xml_MPParameters['level']<="4.1")
                {
                    if($xml_MPParameters['height']<=1080 && $xml_MPParameters['width']<=1920 && $xml_MPParameters['framerate']<=60)
                        $videoMediaProfile="HHD8";
                    else
                        fprintf ($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',HEVC Main(level 4.1) media profiles conformance failed by height/width/framerete. Expected max height|width|framerate are 1080|1920|60, found ".$xml_MPParameters['height']."|".$xml_MPParameters['width']."|".$xml_MPParameters['framerate']. " for track ".$repCount." of SwitchingSet ".$adaptCount."\n");      

                }
                elseif($xml_MPParameters['level']<="5.0")
                {
                    if($xml_MPParameters['height']<=2160 && $xml_MPParameters['width']<=3840 && $xml_MPParameters['framerate']<=60)
                        $videoMediaProfile="UHD8";
                    else
                        fprintf ($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',HEVC Main(level 5.0) media profiles conformance failed by height/width/framerete. Expected max height|width|framerate are 2160|3840|60, found ".$xml_MPParameters['height']."|".$xml_MPParameters['width']."|".$xml_MPParameters['framerate']. " for track ".$repCount." of SwitchingSet ".$adaptCount."\n");      

                }
                else
                    fprintf ($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',HEVC 'Main' media profiles conformance failed, level- ".$xml_MPParameters['level']." not matching to any WAVE HEVC media profiles (expected- 4.1/5.0) for track ".$repCount." of SwitchingSet ".$adaptCount." \n");

            }
            else
                fprintf ($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',HEVC 'Main' media profiles conformance failed. color_primaries/transfer_char/matrix_coefficients are not matching to any WAVE HEVC media profiles. Expected color_primaries|transfer_char|matrix_coeff are 1|1|1, but found ".$xml_MPParameters['color_primaries']."|".$xml_MPParameters['tranfer_char']."|".$xml_MPParameters['matrix_coeff']. "for track ".$repCount." of SwitchingSet ".$adaptCount."\n");        

        }
        else
            fprintf ($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.2.1: 'Each WAVE Video Media Profile SHALL conform to normative ref. listed in Table 1',HEVC media profiles conformance failed. HEVC codec found in the track but profile- ".$xml_MPParameters['profile']." not conforming to any HEVC Media profiles of WAVE (expected- Main10/Main) for track ".$repCount." of SwitchingSet ".$adaptCount." \n");

        
    }
    return $videoMediaProfile;
    
}

function checkAndGetConformingAudioProfile($xml_MPParameters)
{
    if($xml_MPParameters['codec']=="AAC")
    {
        if($xml_MPParameters['channels']=="0x1" || $xml_MPParameters['channels']=="0x2")
        {
            if($xml_MPParameters["brand"]=="caac")
                $audioMediaProfile="AAC_Core";
            elseif($xml_MPParameters['brand']=="caaa")
                $audioMediaProfile="Adaptive_AAC_Core";
            else
                $audioMediaProfile="AAC_Core";
        }
        elseif($xml_MPParameters['brand']=="camc")
            $audioMediaProfile="AAC_Multichannel";
            
    }
    elseif($xml_MPParameters['codec']=="EC-3")
        $audioMediaProfile="EC-3";
    elseif($xml_MPParameters['codec']=="AC-4")
        $audioMediaProfile="AC-4";
    elseif($xml_MPParameters['codec']=="MPEG-H")
        $audioMediaProfile="MPEG-H";
    else
        $audioMediaProfile="unknown";
      
    return $audioMediaProfile;
}

function checkAndGetConformingSubtitleProfile($xml_MPParameters)
{
    $subtitleMediaProfile="unknown";
    if($xml_MPParameters['type']=="application" && ($xml_MPParameters['subType']=="ttml+xml" || $xml_MPParameters['subType']=="mp4"))
    {
        if($xml_MPParameters['codec']=="im1t")
            $subtitleMediaProfile="TTML_IMSC1_Text";
        elseif($xml_MPParameters['codec']=="im1i")
            $subtitleMediaProfile="TTML_IMSC1_Image";
        else
            fprintf ($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.4.1: 'Each WAVE subtitle Media Profile SHALL conform to normative ref. listed in Table 3', subtitle Media profiles conformance failed. codec parameter not found and identification of the exact media profile not possible for track ".$repCount." of SwitchingSet ".$adaptCount." \n");

    }
    else
    {
        fprintf ($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 4.4.1: 'Each WAVE subtitle Media Profile SHALL conform to normative ref. listed in Table 3', subtitle Media profiles conformance failed. mime box parameters (type/subtype/codec) are not found/conforming and identification of the exact media profile not possible for track ".$repCount." of SwitchingSet ".$adaptCount." \n");
        displayMPparameters($xml_MPParameters);

    }
    return $subtitleMediaProfile;
}

function displayMPparameters($MPArray)
{   
    global $opfile;
    fprintf($opfile, "The media profile parameters= ");
    foreach ($MPArray as $attribute => $value)
    {
        fprintf($opfile, $attribute."=".$value." ");
    }
    return;
}
function checkCMAFPresentation()
{   
    global $Period_arr,$locate,$profiles, $cfhd_SwSetFound,$caac_SwSetFound, $encryptedSwSetFound,$MPD;
    //Assuming one of the CMAF profiles will be present.
    $profile_cmfhd=strpos($profiles, 'urn:mpeg:cmaf:presentation_profile:cmfhd:2017');
    $profile_cmfhdc=strpos($profiles, 'urn:mpeg:cmaf:presentation_profile:cmfhdc:2017');
    $profile_cmfhds=strpos($profiles, 'urn:mpeg:cmaf:presentation_profile:cmfhds:2017');
    $videoFound=0;
    $audioFound=0;
    $im1t_SwSetFound=0;
    $subtitle_array=array();
    $subtitleFound=0;
    $trackDurArray=array();
    $maxFragDur=0;
   // $lang_count=0;
    if(!($opfile = fopen($locate."/Presentation_infofile.txt", 'w'))){
            echo "Error opening/creating Presentation profile conformance check file: "."./Presentation_infofile.txt";
            return;
        }
    
    $mediaPresentationDuration = $MPD->getAttribute('mediaPresentationDuration');
    $PresentationDur=timeparsing($mediaPresentationDuration);
    $videoFragDur=0;
    for($adapt_count=0; $adapt_count<sizeof($Period_arr); $adapt_count++){   
        $loc = $locate . '/Adapt' . $adapt_count.'/';
            
        $Adapt=$Period_arr[$adapt_count];
        $filecount = 0;
        $files = glob($loc . "*.xml");
        if($files)
            $filecount = count($files);
            
            $video_counter=0;
            $audio_counter=0;
            $enc_counter=0;
            if(!file_exists($loc))
                    fprintf ($opfile, "Switching Set ".$adapt_count."-Tried to retrieve data from a location that does not exist. \n (Possible cause: Representations are not valid and no file/directory for box info is created.)");
                else{
        for($i=0; $i<$filecount; $i++){     
        
            $filename = $files[$i];                 //load file
            $xml = xmlFileLoad($filename);
            $id = $Adapt['Representation']['id'][$i];
            //Check Section 7.3.4 conformance
            $xml_tfdt=$xml->getElementsByTagName('tfdt')->item(0);
            $xml_baseDecodeTime=$xml_tfdt->getAttribute('baseMediaDecodeTime');
            $xml_trun=$xml->getElementsByTagName('trun');
            $xml_earliestCompTime=$xml_trun->item(0)->getAttribute('earliestCompositionTime');
            $xml_hdlr=$xml->getElementsByTagName('hdlr')->item(0);
            $xml_handlerType=$xml_hdlr->getAttribute('handler_type');
            $xml_elst=$xml->getElementsByTagName('elstEntry');
            $xml_mdhd=$xml->getElementsByTagName('mdhd');
            $timescale=$xml_mdhd->item(0)->getAttribute('timescale');
            
            $mediaTime=0;
            if($xml_elst->length>0 ){
                $mediaTime=$xml_elst->item(0)->getAttribute('mediaTime');
            }
                        
            
            //Check profile conformance
            //$xml_ftyp=$xml->getElementsByTagName('ftyp')[0];
            //$brands=(string)$xml_ftyp->getAttribute('compatible_brands');
            if($profile_cmfhd)
            {
                if($xml_handlerType=='vide')
                {
                    $videoFound=1;
                    if(cfhd_MediaProfileConformance($xml)==false)
                        break;
                    else
                        $video_counter=$video_counter+1;
                    
                    if($cfhd_SwSetFound=0 && $video_counter ==$filecount)
                        $cfhd_SwSetFound=1;
                    
                }
                else if($xml_handlerType=='soun')
                {
                    $audioFound=1;
                    if(caac_mediaProfileConformance($xml)==false)
                        break;
                    else
                    $audio_counter=$audio_counter+1;
                    
                    if($caac_SwSetFound=0 && $audio_counter == $filecount)
                        $caac_SwSetFound=1;
                }
                
                if($xml->getElementsByTagName('tenc')->length >0)
                {
                    fprintf($opfile, "**'CMAF check violated: Section A.1.2 - 'All CMAF Tracks SHALL NOT contain encrypted Samples or a TrackEncryptionBox', but found in Switching Set ".$adapt_count." Rep ".$id." \n");
                }
                    

            }
            if($profile_cmfhdc)
            {
                if($xml_handlerType=='vide')
                {
                    $videoFound=1;
                    if(cfhd_MediaProfileConformance($xml)==false)
                        break;
                    else
                        $video_counter=$video_counter+1;
                    
                    if($cfhd_SwSetFound=0 && $video_counter ==$filecount)
                        $cfhd_SwSetFound=1;
                }
                else if($xml_handlerType=='soun')
                {
                    $audioFound=1;
                    if(caac_mediaProfileConformance($xml)==false)
                        break;
                    else
                    $audio_counter=$audio_counter+1;
                        
                    if($caac_SwSetFound=0 && $audio_counter == $filecount)
                        $caac_SwSetFound=1;
                }
                
                if($xml->getElementsByTagName('tenc')->length >0)
                {   
                    $enc_counter=$enc_counter+1;
                    $schm=$xml->getElementsByTagName('schm');
                    if($schm->length>0)
                        if($schm->item(0)->getAttribute('scheme')!='cenc')
                            fprintf($opfile, "**'CMAF check violated: Section A.1.3 - 'Any CMAF Switching Set that is encrypted SHALL be available in 'cenc' Common Encryption scheme', but found scheme ".$schm->item(0)->getAttribute('scheme')." \n");
                    if($encryptedSwSetFound=0 && $enc_counter == $filecount)
                        $encryptedSwSetFound=1;
                }

            }
            if($profile_cmfhds)
            {
                if($xml_handlerType=='vide')
                {
                    $videoFound=1;
                    if(cfhd_MediaProfileConformance($xml)==false)
                        break;
                    else
                        $video_counter=$video_counter+1;
                    
                    if($cfhd_SwSetFound=0 && $video_counter ==$filecount)
                        $cfhd_SwSetFound=1;
                }
                else if($xml_handlerType=='soun')
                {
                    $audioFound=1;
                    if(caac_mediaProfileConformance($xml)==false)
                        break;
                    else
                    $audio_counter=$audio_counter+1;
                        
                    if($caac_SwSetFound=0 && $audio_counter == $filecount)
                        $caac_SwSetFound=1;
                }
                
                 if($xml->getElementsByTagName('tenc')->length >0)
                {   
                    $enc_counter=$enc_counter+1;
                    $schm=$xml->getElementsByTagName('schm');
                    if($schm->length>0)
                        if($schm->item(0)->getAttribute('scheme')!='cbcs')
                            fprintf($opfile, "**'CMAF check violated: Section A.1.4 - 'Any CMAF Switching Set that is encrypted SHALL be available in 'cbcs' Common Encryption scheme', but found scheme ".$schm->item(0)->getAttribute('scheme')." \n");
                    if($encryptedSwSetFound=0 && $enc_counter == $filecount)
                        $encryptedSwSetFound=1;
                }

            }
        
          }
        }
        //Check for subtitle conformance of Section A.1
        if($profile_cmfhd || $profile_cmfhdc || $profile_cmfhds){
            if(strpos($Adapt['mimeType'],"application/ttml+xml"))
            {
                $subtitleFound=1;
                $codecs_Adapt=$Adapt['codecs'];
                $lang=$Adapt['language'];
                $role_scheme=$Adapt['Role']['scheme'];
                if($lang!=0){
                    if(empty($subtitle_array))
                        $subtitle_array=array($lang => 0);
                    else{
                        if(!array_key_exists($lang, $subtitle_array))
                            $subtitle_array[$lang]=0;//array_push($subtitle_array, $lang=>0);
                    }
                    //$lang_count++;
                    if(strpos($codecs_Adapt, "im1t"))
                        $subtitle_array[$lang]=1;
                }
                
            }
        }
        //
    }
    
    
    if(($profile_cmfhd || $profile_cmfhdc ||$profile_cmfhds) && $videoFound && $cfhd_SwSetFound!=1)
        fprintf($opfile, "**'CMAF check violated: Section A.1.2/A.1.3/A.1.4 - 'If containing video, SHALL include at least one Switching Set constrained to the 'cfhd' Media Profile', but found none \n");
    if(($profile_cmfhd || $profile_cmfhdc ||$profile_cmfhds) && $audioFound && $caac_SwSetFound!=1)
        fprintf($opfile, "**'CMAF check violated: Section A.1.2/A.1.3/A.1.4 - 'If containing audio, SHALL include at least one Switching Set constrained to the 'caac' Media Profile', but found none \n");
    if($profile_cmfhdc && $encryptedSwSetFound!=1)
        fprintf($opfile, "**'CMAF check violated: Section A.1.3 - 'At least one CMAF Switching Set SHALL be encrypted', but found none. \n");
    if($profile_cmfhds && $encryptedSwSetFound!=1)
        fprintf($opfile, "**'CMAF check violated: Section A.1.4 - 'At least one CMAF Switching Set SHALL be encrypted', but found none. \n");
    if(($profile_cmfhd || $profile_cmfhdc ||$profile_cmfhds) && $subtitleFound){
        $count_subtitleLang=count(subtitle_array);
        for($z=0;$z<$count;$z++)
        {
            if($subtitle_array[$z]!=1)
                fprintf($opfile, "**'CMAF check violated: Section A.1.2/A.1.3/A.1.4 - 'If containing subtitles, SHALL include at least one Switching Set for each language and role in the 'im1t' Media Profile', but found none \n");
        }
    }
    fclose($opfile);
}

?>