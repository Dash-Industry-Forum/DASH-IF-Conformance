<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function WAVEProgramChecks()
{
    global $MediaProfDatabase, $session_dir,$adaptation_set_template,$spliceConstraitsLog,$reprsentation_template;
    $error=checkSequentialSwSetAV($session_dir,$MediaProfDatabase, $adaptation_set_template,$reprsentation_template);
    fwrite($spliceConstraitsLog, $error);
    //Call the CMFHD Baseline constraints. 
    $error=checkCMFHDBaselineConstraints($MediaProfDatabase, $session_dir,$adaptation_set_template,$spliceConstraitsLog);
    fwrite($spliceConstraitsLog, $error);
    //Using the error messages, check other MAY/Need not conditions and print respective informations.
    if(strpos($error,"###CTAWAVE check violated")!== FALSE)
        fwrite($spliceConstraitsLog, "Information:WAVE Content Spec 2018Ed-Section 6.1: 'WAVE Programs that contain more than one CMAF Presentation MAY conform to constraints of a WAVE Splice Constraints Profile (section 6.2)', however non-conformance observed in this WAVE Program. \n ");
    else
        fwrite($spliceConstraitsLog, "Information:WAVE Content Spec 2018Ed-Section 6.1: 'WAVE Programs that contain more than one CMAF Presentation MAY conform to constraints of a WAVE Splice Constraints Profile (section 6.2)', however conformance observed in this WAVE Program. \n ");

    if(strpos($error,"violation observed in WAVE Baseline Splice")!== FALSE)
        fwrite($spliceConstraitsLog, "Information:WAVE Content Spec 2018Ed-Section 6.1: 'CMAF Presentation in a WAVE Program need not conform to any Splice Constraint Profile', however non-conformance to WAVE Baseline Splice constraints found. \n ");
    elseif(strpos($error,"violated as not all CMAF presentations conforms to CMFHD")!== FALSE)
        fwrite($spliceConstraitsLog, "Information:WAVE Content Spec 2018Ed-Section 6.1: 'CMAF Presentation in a WAVE Program need not conform to any Splice Constraint Profile', however non-conformance to CMFHD Baseline constraints found. \n ");

}

function checkCMFHDBaselineConstraints($MediaProfDatabase, $session_dir,$adaptation_set_template,$spliceConstraitsLog)
{
    //Check for CMFHD presentation profile for all periods/presentations
    //and then check WAVE Baseline constraints . If both are satisfied, then CMFHD Baseline Constraints are satisfied.
    $errorMsg="";
    $period_count=sizeof($MediaProfDatabase);
    $presentationProfileArray=array();
    for($i=0;$i<$period_count;$i++)
    {
        $adapts_count=sizeof($MediaProfDatabase[$i]);
       
        $presentationProfile=CTACheckPresentation($adapts_count,$session_dir,$adaptation_set_template,$opfile);
        array_push($presentationProfileArray,$presentationProfile );
    }
    if(!(count(array_unique($presentationProfileArray))===1 && array_unique($presentationProfileArray)[0]=="CMFHD"))
        $errorMsg.="###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 6.2: 'WAVE CMFHD Baseline Program Shall contain a sequence of one or more CMAF Presentations conforming to CMAF CMFHD profile', violated as not all CMAF presentations conforms to CMFHD. \n";

    //WAVE Baseline constraints are already checked, open the log file and check if contains errors and print related error message.
    if(!($opfile = open_file($session_dir. '/' . $spliceConstraitsLog . '.txt', 'w'))){
        echo "Error opening/creating Presentation profile conformance check file: "."./Presentation_infofile_ctawave.txt";
        return;
    }
    else
    {
        $searchfiles = file_get_contents($session_dir.'/'.$spliceConstraitsLog.'.txt');
        if(strpos($searchfiles, "CTAWAVE check violated") !== FALSE){
            $errorMsg.="###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 6.2: 'WAVE CMFHD Baseline Program's Sequential Sw Sets Shall only contain splices conforming to WAVE Baseline Splice profile (section 7.2)', but violation observed in WAVE Baseline Splice constraints. \n";

        }
    }

    
    return $errorMsg;
}

function checkSequentialSwSetAV($session_dir,$MediaProfDatabase, $adaptation_set_template,$reprsentation_template)
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
                $hdlr=$xml_rep_P1->getElementsByTagName('hdlr')->item(0);
                $handler_type_1=$hdlr->getAttribute("handler_type");
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
                $hdlr=$xml_rep_P2->getElementsByTagName('hdlr')->item(0);
                $handler_type_2=$hdlr->getAttribute("handler_type");
                $trun=$xml_rep_P2->getElementsByTagName('trun')->item(0);
                $earlyCompTime_p2=$trun->getAttribute('earliestCompositionTime');
                $xml_elst=$xml_rep_P2->getElementsByTagName('elstEntry');
                $mediaTime_p2=0;
                if($xml_elst->length>0 ){
                    $mediaTime_p2=$xml_elst->item(0)->getAttribute('mediaTime');
                }

            }
            if($handler_type_1==$handler_type_2 && ($handler_type1 == "vide" || $handler_type_1=="soun"))
            {              
                if(($earlyCompTime_p1+$mediaTime_p1+$sumSampleDur) !== ($earlyCompTime_p2+$mediaTime_p2))
                    $errorMsg="###CTA WAVE check violated: WAVE Content Spec 2018Ed-Section 6.1: 'For a WAVE Program with more than one CMAF Presentation, all audio and video Shall be contained in Sequential Sw Sets', overlap/gap in presenation time (non-sequential) is observed for Sw set ".$adapt." between CMAF Presentations ".$i." and  ".($i+1)." for media type ".$handler_type1.".\n";
            }
        }   
    }
    return $errorMsg;
}