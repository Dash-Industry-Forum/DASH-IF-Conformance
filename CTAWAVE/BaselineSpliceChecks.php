<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
include(dirname(__FILE__)."/../../Utils/Load.php");
function CTABaselineSpliceChecks()
{
    global $MediaProfDatabase, $session_dir, $adaptation_set_template,$reprsentation_template;
    $error=checkSequentialSwSetMProfile($MediaProfDatabase);
    fwrite($opfile, $error);
    $error=checkDiscontinuousSplicePoints($session_dir,$MediaProfDatabase, $adaptation_set_template,$reprsentation_template);
    fwrite($opfile, $error);
    $error=checkEncryptionChangeSplicePoint($session_dir,$MediaProfDatabase, $adaptation_set_template,$reprsentation_template);
    fwrite($opfile, $error);
    $error=checkSampleEntryChangeSplicePoint($session_dir,$MediaProfDatabase, $adaptation_set_template,$reprsentation_template);
    fwrite($opfile, $error);
    $error=checkDefaultKIDChangeSplicePoint($session_dir,$MediaProfDatabase, $adaptation_set_template,$reprsentation_template);
    fwrite($opfile, $error);
    $error=checkPicAspectRatioSplicePoint($session_dir,$MediaProfDatabase, $adaptation_set_template,$reprsentation_template);
    fwrite($opfile, $error);
    $error=checkFrameRateSplicePoint($session_dir,$MediaProfDatabase, $adaptation_set_template,$reprsentation_template);
    fwrite($opfile, $error);
    $error=checkAudioChannelSplicePoint($session_dir,$MediaProfDatabase, $adaptation_set_template,$reprsentation_template);
    fwrite($opfile, $error);

}

function checkSequentialSwSetMProfile($MediaProfDatabase)
{
    $errorMsg="";
    $period_count=sizeof($MediaProfDatabase);
    //Create an array of media profiles at Sw set level.
    //If all reps doesnt have same MP, then "unknown" is assigned.
    for($i=0;$i<$period_count;$i++)
    {
        $adapt_count=sizeof($MediaProfDatabase[$i]);
        for($j=0;$j<$adapt_count;$j++)
        {
            $rep_count=sizeof($MediaProfDatabase[$i][$j]);
            $MediaProf_PeriodAdSet[$i][$j]=$MediaProfDatabase[$i][$j][0];
            for($k=0;$k<($rep_count-1);$k++)
            {
                if($MediaProfDatabase[$i][$j][$k]!==$MediaProfDatabase[$i][$j][$k+1])
                {
                    $MediaProf_PeriodAdSet[$i][$j]="unknown";
                    break;
                }       
            }
        }
    }
    
   //Check the MP at the Sw Set level and raise conformance error.
    $adapt_count=sizeof($MediaProf_PeriodAdSet[0]);
    for($i=0;$i<($period_count-1);$i++)
    {
        for($j=0;$j<$adapt_count;$j++)
        {
            if($MediaProf_PeriodAdSet[$i][$j]!==$MediaProf_PeriodAdSet[$i+1][$j])
                $errorMsg="###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 7.2.2: Sequential Switching Sets SHALL conform to the same CMAF Media Profile, voilated for Sw set ".$j." between CMAF Presentations ".$i." and  ".($i+1).".\n";
                $errorMsg.="###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 7.2.2: Encoding parameters Shall be constrained such that CMAF Fragments of following Switching Set can be decoded by a decoder configured for previous Switching Set without reinitialization, voilated for Sw set ".$j." between CMAF Presentations ".$i." and  ".($i+1)." as Media Profile found are ".$MediaProf_PeriodAdSet[$i][$j]." and ".$MediaProf_PeriodAdSet[$i+1][$j]." respectively.\n";

        }
    }
    return $errorMsg;
}

function checkDiscontinuousSplicePoints($session_dir, $MediaProfDatabase, $adaptation_set_template,$reprsentation_template)
{
    $errorMsg="";
    $period_count=sizeof($MediaProfDatabase);
    $adapt_count=sizeof($MediaProfDatabase[0]);
    for($i=0;$i<($period_count-1);$i++)
    {
       for($adapt=0;$adapt<$adapt_count;$adapt++)
        {
            $adapt_dir = str_replace('$AS$', $adapt, $adaptation_set_template);
            $rep_dir = str_replace(array('$AS$', '$R$'), array($adapt, 0), $reprsentation_template);
            $xml_rep_P1 = get_DOM($session_dir.'/Period'.$i.'/'.$adapt_dir.'/'.$rep_dir.'.xml', 'atomlist');
            if($xml_rep_P1){
                $moof_len=$xml_rep_P1->getElementsByTagName("moof")->length;
                if($moof_len>0)
                {
                    $tfdt=$xml_rep_P1->getElementsByTagName("tfdt")->item($moof_len-1);
                    $baseMediaDecodeTime_p1=$tfdt->getAttribute("baseMediaDecodeTime");
                    $trun=$xml_rep_P1->getElementsByTagName("trun")->item($moof_len-1);
                    $cummulatedSampleDuration_p1=$trun->getAttribute("cummulatedSampleDuration");
                }
            }
            $xml_rep_P2 = get_DOM($session_dir.'/Period'.($i+1).'/'.$adapt_dir.'/'.$rep_dir.'.xml', 'atomlist');
            if($xml_rep_P2){
                $moof_len=$xml_rep_P2->getElementsByTagName("moof")->length;
                if($moof_len>0)
                {
                    $tfdt=$xml_rep_P2->getElementsByTagName("tfdt")->item(0);
                    $baseMediaDecodeTime_p2=$tfdt->getAttribute("baseMediaDecodeTime");
                    
                }
                if($baseMediaDecodeTime_p2!=$baseMediaDecodeTime_p1+$cummulatedSampleDuration_p1)
                    $errorMsg="###Information: WAVE Content Spec 2018Ed-Section 7.2.2: Sequential Switching Sets can be discontinuous, and it is observed for Sw set ".$adapt." between CMAF Presentations ".$i." and  ".($i+1).".\n";

                
            }
        }
       
    }
    return $errorMsg;
}

function checkEncryptionChangeSplicePoint($session_dir,$MediaProfDatabase, $adaptation_set_template,$reprsentation_template)
{
    $errorMsg="";
    $period_count=sizeof($MediaProfDatabase);
    $adapt_count=sizeof($MediaProfDatabase[0]);
    for($i=0;$i<($period_count-1);$i++)
    {
       for($adapt=0;$adapt<$adapt_count;$adapt++)
        {
            $adapt_dir = str_replace('$AS$', $adapt, $adaptation_set_template);
            $rep_dir = str_replace(array('$AS$', '$R$'), array($adapt, 0), $reprsentation_template);
            $xml_rep_P1 = get_DOM($session_dir.'/Period'.$i.'/'.$adapt_dir.'/'.$rep_dir.'.xml', 'atomlist');
            if($xml_rep_P1){
                $encScheme_p1=getEncrytionScheme($xml_rep_P1);

            }
           $xml_rep_P2 = get_DOM($session_dir.'/Period'.($i+1).'/'.$adapt_dir.'/'.$rep_dir.'.xml', 'atomlist');
            if($xml_rep_P2){
                $encScheme_p2=getEncrytionScheme($xml_rep_P2);

            }
            if($encScheme_p1!=$encScheme_p2 && ($encScheme_p1===0 || $encScheme_p2===0))
                    $errorMsg="###Information: WAVE Content Spec 2018Ed-Section 7.2.2: Sequential Switching Sets can change between unencrypted/encrypted at Splice points, it is observed for Sw set ".$adapt." between CMAF Presentations ".$i." and  ".($i+1).".\n";

                
        }   
    }
    $encSchemePeriod=array();
    for($i=0;$i<($period_count);$i++)
    {
       $encSchemeAdapt=array();
       for($adapt=0;$adapt<$adapt_count;$adapt++)
        {
            $adapt_dir = str_replace('$AS$', $adapt, $adaptation_set_template);
            $rep_dir = str_replace(array('$AS$', '$R$'), array($adapt, 0), $reprsentation_template);
            $xml_rep_P1 = get_DOM($session_dir.'/Period'.$i.'/'.$adapt_dir.'/'.$rep_dir.'.xml', 'atomlist');
            if($xml_rep_P1){
                $encScheme_p1=getEncrytionScheme($xml_rep_P1);
                if($encScheme_p1!==0)
                   array_push($encSchemeAdapt,$encScheme_p1);
            }       
        }
        if(count($encSchemeAdapt)==0)
            array_push ($encSchemePeriod, 0);
        else
            array_push ($encSchemePeriod, array_unique($encSchemeAdapt)[0]);
    }
    for($i=0;$i<($period_count-1);$i++)
    {
        if($encSchemePeriod[$i]!==$encSchemePeriod[$i+1] && $encSchemePeriod[$i]!==0 && $encSchemePeriod[$i+1]!==0)
            $errorMsg.="###CTA WAVE check violated: WAVE Content Spec 2018Ed-Section 7.2.2: 'WAVE content SHALL contain one CENC Scheme per program', violated between CMAF Presentations ".$i." and  ".($i+1)." contains ".$encSchemePeriod[$i]." and ".$encSchemePeriod[$i+1]." respectively.\n";

    }
    return $errorMsg;
}

function getEncrytionScheme($xml)
{
     //Check for encrypted tracks
    if($xml->getElementsByTagName('tenc')->length >0)
    {   
        $schm=$xml->getElementsByTagName('schm');
        if($schm->length>0)
             return $schm->item(0)->getAttribute('scheme');
    }
    else
        return 0;
}

function checkSampleEntryChangeSplicePoint($session_dir,$MediaProfDatabase, $adaptation_set_template,$reprsentation_template)
{
    $errorMsg="";
    $period_count=sizeof($MediaProfDatabase);
    $adapt_count=sizeof($MediaProfDatabase[0]);
    for($i=0;$i<($period_count-1);$i++)
    {
       for($adapt=0;$adapt<$adapt_count;$adapt++)
        {
            $adapt_dir = str_replace('$AS$', $adapt, $adaptation_set_template);
            $rep_dir = str_replace(array('$AS$', '$R$'), array($adapt, 0), $reprsentation_template);
            $xml_rep_P1 = get_DOM($session_dir.'/Period'.$i.'/'.$adapt_dir.'/'.$rep_dir.'.xml', 'atomlist');
            if($xml_rep_P1){
                $sdType_p1=getSdType($xml_rep_P1);

            }
           $xml_rep_P2 = get_DOM($session_dir.'/Period'.($i+1).'/'.$adapt_dir.'/'.$rep_dir.'.xml', 'atomlist');
            if($xml_rep_P2){
                  $sdType_p2=getSdType($xml_rep_P2);

            }
            if($sdType_p1!=$sdType_p2 )
                    $errorMsg="###CTA WAVE check violated: WAVE Content Spec 2018Ed-Section 7.2.2: 'Sample entries in Sequential Switching Sets Shall not change sample type at Splice points', but different sample types observed for Sw set ".$adapt." between CMAF Presentations ".$i." and  ".($i+1).".\n";

                
        }   
    }
    return $errorMsg;
}

function getSdType($xml)
{
    $sdType=0;$SampleDescr="";
    $hdlr=$xml->getElementsByTagName("hdlr")->item(0);
    $handler_type=$hdlr->getAttribute("handler_type");
    if($handler_type=="vide")
        $SampleDescr=$xml->getElementsByTagName("vide_sampledescription")->item(0);
    elseif($handler_type=="soun")
        $SampleDescr=$xml->getElementsByTagName("soun_sampledescription")->item(0);
    if($SampleDescr!=="")
        $sdType=$SampleDescr->getAttribute("sdType");
    
    return $sdType;
}

function checkDefaultKIDChangeSplicePoint($session_dir,$MediaProfDatabase, $adaptation_set_template,$reprsentation_template)
{
    $errorMsg="";
    $period_count=sizeof($MediaProfDatabase);
    $adapt_count=sizeof($MediaProfDatabase[0]);
    $defaultKID_p1=0;$defaultKID_p2=0;
    $errorMsg="";
    for($i=0;$i<($period_count-1);$i++)
    {
       for($adapt=0;$adapt<$adapt_count;$adapt++)
        {
            $adapt_dir = str_replace('$AS$', $adapt, $adaptation_set_template);
            $rep_dir = str_replace(array('$AS$', '$R$'), array($adapt, 0), $reprsentation_template);
            $xml_rep_P1 = get_DOM($session_dir.'/Period'.$i.'/'.$adapt_dir.'/'.$rep_dir.'.xml', 'atomlist');
            if($xml_rep_P1){
                $tenc=$xml_rep_P1->getElementsByTagName("tenc");
                if($tenc->length>0)
                  $defaultKID_p1=$tenc->item(0)->getAttribute("default_KID");
               
            }
           $xml_rep_P2 = get_DOM($session_dir.'/Period'.($i+1).'/'.$adapt_dir.'/'.$rep_dir.'.xml', 'atomlist');
            if($xml_rep_P2){
                $tenc=$xml_rep_P2->getElementsByTagName("tenc");
                if($tenc->length>0)
                  $defaultKID_p2=$tenc->item(0)->getAttribute("default_KID");

            }
            if($defaultKID_p1!=$defaultKID_p2 )
                    $errorMsg="###Information: WAVE Content Spec 2018Ed-Section 7.2.2: 'Default KID can change at Splice points', change is observed for Sw set ".$adapt." between CMAF Presentations ".$i." and  ".($i+1).".\n";

                
        }   
    }
    return $errorMsg;
}
function checkTrackIDChangeSplicePoint($session_dir,$MediaProfDatabase, $adaptation_set_template,$reprsentation_template)
{
    $errorMsg="";
    $period_count=sizeof($MediaProfDatabase);
    $adapt_count=sizeof($MediaProfDatabase[0]);
    for($i=0;$i<($period_count-1);$i++)
    {
       for($adapt=0;$adapt<$adapt_count;$adapt++)
        {
            $adapt_dir = str_replace('$AS$', $adapt, $adaptation_set_template);
            $rep_dir = str_replace(array('$AS$', '$R$'), array($adapt, 0), $reprsentation_template);
            $xml_rep_P1 = get_DOM($session_dir.'/Period'.$i.'/'.$adapt_dir.'/'.$rep_dir.'.xml', 'atomlist');
            if($xml_rep_P1){
                $tkhd=$xml_rep_P1->getElementsByTagName("tkhd");
                if($tkhd->length>0)
                  $trackID_p1=$tkhd->item(0)->getAttribute("trackID");
               
            }
           $xml_rep_P2 = get_DOM($session_dir.'/Period'.($i+1).'/'.$adapt_dir.'/'.$rep_dir.'.xml', 'atomlist');
            if($xml_rep_P2){
                $tkhd=$xml_rep_P2->getElementsByTagName("tkhd");
                if($tkhd->length>0)
                  $trackID_p2=$tkhd->item(0)->getAttribute("trackID");

            }
            if($trackID_p1!=$trackID_p2 )
                    $errorMsg="###Information: WAVE Content Spec 2018Ed-Section 7.2.2: 'Track_ID can change at Splice points', change is observed for Sw set ".$adapt." between CMAF Presentations ".$i." and  ".($i+1).".\n";

                
        }   
    }
    return $errorMsg;
}

function checkTimeScaleChangeSplicePoint($session_dir,$MediaProfDatabase, $adaptation_set_template,$reprsentation_template)
{
    $period_count=sizeof($MediaProfDatabase);
    $adapt_count=sizeof($MediaProfDatabase[0]);
    $errorMsg="";
    for($i=0;$i<($period_count-1);$i++)
    {
       for($adapt=0;$adapt<$adapt_count;$adapt++)
        {
            $adapt_dir = str_replace('$AS$', $adapt, $adaptation_set_template);
            $rep_dir = str_replace(array('$AS$', '$R$'), array($adapt, 0), $reprsentation_template);
            $xml_rep_P1 = get_DOM($session_dir.'/Period'.$i.'/'.$adapt_dir.'/'.$rep_dir.'.xml', 'atomlist');
            if($xml_rep_P1){
                $mvhd=$xml_rep_P1->getElementsByTagName("mvhd");
                if($mvhd->length>0)
                  $timescale_p1=$mvhd->item(0)->getAttribute("timeScale");
               
            }
            $xml_rep_P2 = get_DOM($session_dir.'/Period'.($i+1).'/'.$adapt_dir.'/'.$rep_dir.'.xml', 'atomlist');
            if($xml_rep_P2){
                $mvhd=$xml_rep_P2->getElementsByTagName("mvhd");
                if($mvhd->length>0)
                  $timescale_p2=$mvhd->item(0)->getAttribute("timeScale");

            }
            if($timescale_p1!=$timescale_p2 )
                    $errorMsg="###Information: WAVE Content Spec 2018Ed-Section 7.2.2: 'Timescale can change at Splice points', change is observed for Sw set ".$adapt." between CMAF Presentations ".$i." and  ".($i+1).".\n";

                
        }   
    }
    return $errorMsg;
}

function checkFragrmentOverlapSplicePoint($session_dir,$MediaProfDatabase, $adaptation_set_template,$reprsentation_template)
{
    $period_count=sizeof($MediaProfDatabase);
    $adapt_count=sizeof($MediaProfDatabase[0]);
    $errorMsg="";
    for($i=0;$i<($period_count-1);$i++)
    {
       for($adapt=0;$adapt<$adapt_count;$adapt++)
        {
            $adapt_dir = str_replace('$AS$', $adapt, $adaptation_set_template);
            $rep_dir = str_replace(array('$AS$', '$R$'), array($adapt, 0), $reprsentation_template);
            $xml_rep_P1 = get_DOM($session_dir.'/Period'.$i.'/'.$adapt_dir.'/'.$rep_dir.'.xml', 'atomlist');
            if($xml_rep_P1){
                $trun=$xml_rep_P1->getElementsByTagName('trun')->item(0);
                $earlyCompTime_p1=$trun->getAttribute('earliestCompositionTime');
                $xml_elst=$xml_rep_P1->getElementsByTagName('elstEntry');
                $mediaTime_p1=0;
                if($xml_elst->length>0 ){
                    $mediaTime_p1=$xml_elst->item(0)->getAttribute('mediaTime');
                }
                $trun=$xml_rep_P1->getElementsByTagName('trun');
                $sumSampleDur=0;
                for($i=0;i<$trun->length;$i++)
                {
                    $sumSampleDur+=$trun->item($i)->getAttribute("cummulatedSampleDuration");
                }
            }
            $xml_rep_P2 = get_DOM($session_dir.'/Period'.($i+1).'/'.$adapt_dir.'/'.$rep_dir.'.xml', 'atomlist');
            if($xml_rep_P2){
                $trun=$xml_rep_P2->getElementsByTagName('trun')->item(0);
                $earlyCompTime_p2=$trun->getAttribute('earliestCompositionTime');
                $xml_elst=$xml_rep_P2->getElementsByTagName('elstEntry');
                $mediaTime_p2=0;
                if($xml_elst->length>0 ){
                    $mediaTime_p2=$xml_elst->item(0)->getAttribute('mediaTime');
                }

            }
            if(($earlyCompTime_p1+$mediaTime_p1+$sumSampleDur) >($earlyCompTime_p2+$mediaTime_p2) )
                    $errorMsg="###CTA WAVE check violated: WAVE Content Spec 2018Ed-Section 7.2.2: 'CMAF Fragments Shall not overlap the same WAVE Program presentation time at the Splice point', overlap is observed for Sw set ".$adapt." between CMAF Presentations ".$i." and  ".($i+1).".\n";
            elseif(($earlyCompTime_p1+$mediaTime_p1+$sumSampleDur) <($earlyCompTime_p2+$mediaTime_p2) )
                    $errorMsg.="###CTA WAVE check violated: WAVE Content Spec 2018Ed-Section 7.2.2: 'CMAF Fragments Shall not have gaps in WAVE Program presentation time at the Splice point', gap is observed for Sw set ".$adapt." between CMAF Presentations ".$i." and  ".($i+1).".\n";

                
        }   
    }
    return $errorMsg;
}

function checkPicAspectRatioSplicePoint($session_dir,$MediaProfDatabase, $adaptation_set_template,$reprsentation_template)
{
    $period_count=sizeof($MediaProfDatabase);
    $adapt_count=sizeof($MediaProfDatabase[0]);
    $errorMsg="";
    for($i=0;$i<($period_count-1);$i++)
    {
       for($adapt=0;$adapt<$adapt_count;$adapt++)
        {
            $adapt_dir = str_replace('$AS$', $adapt, $adaptation_set_template);
            $rep_dir = str_replace(array('$AS$', '$R$'), array($adapt, 0), $reprsentation_template);
            $xml_rep_P1 = get_DOM($session_dir.'/Period'.$i.'/'.$adapt_dir.'/'.$rep_dir.'.xml', 'atomlist');
            if($xml_rep_P1){
                $tkhd=$xml_rep_P1->getElementsByTagName("tkhd")->item(0);
                $par_p1=$tkhd->getAttribute("width")/($tkhd->getAttribute("height"));
                    
            }
            $xml_rep_P2 = get_DOM($session_dir.'/Period'.($i+1).'/'.$adapt_dir.'/'.$rep_dir.'.xml', 'atomlist');
            if($xml_rep_P2){
                $tkhd=$xml_rep_P2->getElementsByTagName("tkhd")->item(0);
                $par_p2=$tkhd->getAttribute("width")/($tkhd->getAttribute("height"));

            }
            if($par_p1!=$par_p2)
                $errorMsg="###Warning: WAVE Content Spec 2018Ed-Section 7.2.2: 'Pictrure Aspect Ratio Should be the same between Sequential Sw Sets at the Splice point', violated for Sw set ".$adapt." between CMAF Presentations ".$i." and  ".($i+1).".\n";

              
        }   
    }
    return $errorMsg;
}
function checkFrameRateSplicePoint($session_dir,$MediaProfDatabase, $adaptation_set_template,$reprsentation_template)
{
    $period_count=sizeof($MediaProfDatabase);
    $adapt_count=sizeof($MediaProfDatabase[0]);
    $errorMsg="";
    for($i=0;$i<($period_count-1);$i++)
    {
       for($adapt=0;$adapt<$adapt_count;$adapt++)
        {
            $adapt_dir = str_replace('$AS$', $adapt, $adaptation_set_template);
            $rep_dir = str_replace(array('$AS$', '$R$'), array($adapt, 0), $reprsentation_template);
            $xml_rep_P1 = get_DOM($session_dir.'/Period'.$i.'/'.$adapt_dir.'/'.$rep_dir.'.xml', 'atomlist');
            if($xml_rep_P1){
                $hdlr=$xml_rep_P1->getElementsByTagName("hdlr")->item(0)->getAttribute("handler_type");
                if($hdlr=="vide")
                    $framerate_p1=getFrameRate($xml_rep_P1);
                    
            }
            $xml_rep_P2 = get_DOM($session_dir.'/Period'.($i+1).'/'.$adapt_dir.'/'.$rep_dir.'.xml', 'atomlist');
            if($xml_rep_P2){
                $hdlr=$xml_rep_P2->getElementsByTagName("hdlr")->item(0)->getAttribute("handler_type");
                if($hdlr=="vide"){
                    $framerate_p2=getFrameRate($xml_rep_P2);

            
                    $remainder=($framerate_p1>$framerate_p2 ? ($framerate_p1 % $framerate_p2): ($framerate_p2 % $framerate_p1));
                    if($remainder !=0)
                        $errorMsg="###Warning: WAVE Content Spec 2018Ed-Section 7.2.2: 'Frame rate Should be the same family of multiples between Sequential Sw Sets at the Splice point', violated for Sw set ".$adapt." between CMAF Presentations ".$i." and  ".($i+1)." with framerates of ".$framerate_p1." and ".$framerate_p2." respectively.\n";
                    }
            }
              
        }   
    }
    return $errorMsg;
}

function getFrameRate($xml)
{
    $videSampleDes=$xml->getElementsByTagName("vide_sampledescription")->item(0);
    $sdType=$videSampleDes->getAttribute("sdType");
    if($sdType=='avc1' || $sdType=='avc3')
    {
        $nal_unit=$xml->getElementsByTagName("NALUnit");
        if($nal_unit->length==0){
          $framerate=0;
        }
        else{
            for($nal_count=0;$nal_count<$nal_unit->length;$nal_count++)
            {
                if($nal_unit->item($nal_count)->getAttribute("nal_type")=="0x07")
                {    $sps_unit=$nal_count;
                     break;
                }  
            }

            $comment=$nal_unit->item($sps_unit)->getElementsByTagName("comment")->item(0);  


            if($comment->getAttribute("vui_parameters_present_flag")=="0x1")
            {
                if($comment->getAttribute("timing_info_present_flag")=="0x1" )
                {
                    $num_units_in_tick=$comment->getAttribute("num_units_in_tick");
                    $time_scale=$comment->getAttribute("time_scale");
                    $framerate=$time_scale/(2*$num_units_in_tick);
                }
            }
        }
    }
    else if($sdType=='hev1' || $sdType=='hvc1')
    {

        $nal_unit=$xml->getElementsByTagName("NALUnit");
        if($nal_unit->length==0){
            $framerate=0;
        }
        else{
            for($nal_count=0;$nal_count<$nal_unit->length;$nal_count++)
            {
                if($nal_unit->item($nal_count)->getAttribute("nal_unit_type")=="33")
                {    $sps_unit=$nal_count;
                     break;
                }  
            }

            $sps=$nal_unit->item($sps_unit);
            if($sps->getAttribute("vui_parameters_present_flag")=="1")
            {
              if($sps->getAttribute("vui_timing_info_present_flag")=="1" )
              {
                  $num_units_in_tick=$sps->getAttribute("vui_num_units_in_tick");
                  $time_scale=$sps->getAttribute("vui_time_scale");
                  $framerate=$time_scale/($num_units_in_tick);
              }
            }
        }
    }
    return $framerate;
}
function checkAudioChannelSplicePoint($session_dir,$MediaProfDatabase, $adaptation_set_template,$reprsentation_template)
{
    $period_count=sizeof($MediaProfDatabase);
    $adapt_count=sizeof($MediaProfDatabase[0]);
    $errorMsg="";
    for($i=0;$i<($period_count-1);$i++)
    {
       for($adapt=0;$adapt<$adapt_count;$adapt++)
        {
            $adapt_dir = str_replace('$AS$', $adapt, $adaptation_set_template);
            $rep_dir = str_replace(array('$AS$', '$R$'), array($adapt, 0), $reprsentation_template);
            $xml_rep_P1 = get_DOM($session_dir.'/Period'.$i.'/'.$adapt_dir.'/'.$rep_dir.'.xml', 'atomlist');
            if($xml_rep_P1){
                $hdlr=$xml_rep_P1->getElementsByTagName("hdlr")->item(0)->getAttribute("handler_type");
                if($hdlr=="soun"){
                    $decoderSpecInfo=$xml_rep_P1->getElementsByTagName("DecoderSpecificInfo")->item(0);
                    $channels_p1=$decoderSpecInfo->getAttribute("channelConfig");
                }
            }
            $xml_rep_P2 = get_DOM($session_dir.'/Period'.($i+1).'/'.$adapt_dir.'/'.$rep_dir.'.xml', 'atomlist');
            if($xml_rep_P2){
                $hdlr=$xml_rep_P2->getElementsByTagName("hdlr")->item(0)->getAttribute("handler_type");
                if($hdlr=="soun"){
                    $decoderSpecInfo=$xml_rep_P2->getElementsByTagName("DecoderSpecificInfo")->item(0);
                    $channels_p2=$decoderSpecInfo->getAttribute("channelConfig");

                    if($channels_p1 !=$channels_p2)
                        $errorMsg="###Warning: WAVE Content Spec 2018Ed-Section 7.2.2: 'Audio channel configuration Should allow the same stereo or multichannel config between Sequential Sw Sets at the Splice point', violated for Sw set ".$adapt." between CMAF Presentations ".$i." and  ".($i+1)." with channels ".$channels_p1." and ".$channels_p2." respectively.\n";
                    }
            }
              
        }   
    }
    return $errorMsg;
}
?>