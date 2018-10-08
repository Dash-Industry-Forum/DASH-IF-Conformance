<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$cfhd_SwSetFound=0;
$caac_SwSetFound=0;
$encryptedSwSetFound=0;

function checkPresentation(){
    global $session_dir, $progress_xml, $progress_report, $string_info,
            $presentation_infofile, $selectionset_infofile, $alignedswitching_infofile;
    
    checkCMAFPresentation();
    checkSelectionSet();
    checkAlignedSwitchingSets();
    
    if(file_exists($session_dir.'/'.$selectionset_infofile.'.txt')){
        $selSetFile=file_get_contents($session_dir.'/'.$selectionset_infofile.'.txt');
        if(strpos($selSetFile, "CMAF check violated") == false){
             $progress_xml->Results[0]->addChild('SelectionSet', 'noerror');
             $file_error[] = "noerror"; // no error found in text file
        }
        else{
            $progress_xml->Results[0]->addChild('SelectionSet', 'error');
            $tempr_string = str_replace(array('$Template$'), array($selectionset_infofile), $string_info); // this string shows a text file on HTML
            file_put_contents($session_dir.'/'.$selectionset_infofile.'.html', $tempr_string); // Create html file containing log file result
            $file_error[] = $session_dir.'/'.$selectionset_infofile.'.html'; // add error file location to array
        }
        $progress_xml->asXml(trim($session_dir . '/' . $progress_report));
        print_console($session_dir.'/'.$selectionset_infofile.'.txt', "CMAF Selection Set Results");
    }
    if(file_exists($session_dir.'/'.$presentation_infofile.'.txt')){
        $presentnFile=file_get_contents($session_dir.'/'.$presentation_infofile.'.txt');
        if(strpos($presentnFile, "CMAF check violated") == false){
             $progress_xml->Results[0]->addChild('CMAFProfile', 'noerror');
             $file_error[] = "noerror"; // no error found in text file
        }
        else{
            $progress_xml->Results[0]->addChild('CMAFProfile', 'error');
            $tempr_string = str_replace(array('$Template$'), array($presentation_infofile), $string_info); // this string shows a text file on HTML
            file_put_contents($session_dir.'/'.$presentation_infofile.'.html', $tempr_string); // Create html file containing log file result
            $file_error[] = $session_dir.'/'.$presentation_infofile.'.html'; // add error file location to array
        }
        $progress_xml->asXml(trim($session_dir . '/' . $progress_report));
        print_console($session_dir.'/'.$presentation_infofile.'.txt', "CMAF Presentation Results");
    }
    
    return $file_error;
}

function checkCMAFPresentation(){   
    global $session_dir, $mpd_features, $current_period, $profiles, 
            $cfhd_SwSetFound,$caac_SwSetFound, $encryptedSwSetFound, 
            $presentation_infofile, $adaptation_set_template;
    
    //Assuming one of the CMAF profiles will be present.
    $videoFound=0;
    $audioFound=0;
    $firstEntryflag=1;
    $firstVideoflag=1;
    $firstNonVideoflag=1;
    $im1t_SwSetFound=0;
    $subtitle_array=array();
    $subtitleFound=0;
    $trackDurArray=array();
    $maxFragDur=0;
    $videoFragDur=0;
    //$lang_count=0;
    
    if(!($opfile = open_file($session_dir. '/' . $presentation_infofile . '.txt', 'w'))){
        echo "Error opening/creating Presentation profile conformance check file: "."./Presentation_infofile.txt";
        return;
    }
    
    $PresentationDur = time_parsing($mpd_features['mediaPresentationDuration']);
    $adapts = $mpd_features['Period'][$current_period]['AdaptationSet'];
    for($adapt_count=0; $adapt_count<sizeof($adapts); $adapt_count++){
        $Adapt = $adapts[$adapt_count];
        
        $adapt_dir = str_replace('$AS$', $adapt_count, $adaptation_set_template);
        $loc = $session_dir . '/' . $adapt_dir.'/';
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
                $filename = $files[$i];
                $xml = get_DOM($filename, 'atomlist');
                $id = $Adapt['Representation'][$i]['id'];
                
                $profile_cmfhd=strpos($profiles[$adapt_count][$i], 'urn:mpeg:cmaf:presentation_profile:cmfhd:2017');
                $profile_cmfhdc=strpos($profiles[$adapt_count][$i], 'urn:mpeg:cmaf:presentation_profile:cmfhdc:2017');
                $profile_cmfhds=strpos($profiles[$adapt_count][$i], 'urn:mpeg:cmaf:presentation_profile:cmfhds:2017');
                
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
                if($xml_elst->length>0 )
                    $mediaTime=$xml_elst->item(0)->getAttribute('mediaTime');

                if($firstEntryflag){
                    $firstEntryflag=0;
                    $firstTrackTime=$xml_baseDecodeTime/$timescale;
                }
                else{
                    if($firstTrackTime!=$xml_baseDecodeTime/$timescale)
                        fprintf ($opfile,"**'CMAF check violated: Section 7.3.6-'All CMAF Tracks in a CMAF Presentation SHALL have the same timeline origin', but not matching between Switching Set 1 Track 1 and Switching Set ".($adapt_count+1)." Track ".$id." \n");
                }
                
                //Check alignment of presentation time for video and non video tracks separately. FDIS
                if($xml_handlerType=='vide'){
                    if($firstVideoflag){
                        $firstVideoflag=0;
                        $firstVideoTrackPT=$mediaTime-$xml_earliestCompTime;
                        $firstVideoAdaptcount=$adapt_count;
                        $firstVideoRepId=$id;
                    }
                    else{
                        if($firstVideoTrackPT!=$mediaTime-$xml_earliestCompTime)
                            fprintf ($opfile,"**'CMAF check violated: Section 7.3.6-'All CMAF Tracks in a CMAF Presentation containing video SHALL be start aligned with CMAF presentation time zero equal to the earliest video sample presentation start time in the earliest CMAF Fragment ', but not matching between Switching Set ".($firstVideoAdaptcount+1)." Track ".$firstVideoRepId." and Switching Set ".($adapt_count+1)." Track ".$id." \n");
                    }
                }
                else{
                    if($firstNonVideoflag){
                        $firstNonVideoflag=0;
                        $firstNonVideoTrackPT=$mediaTime+$xml_earliestCompTime;
                        $firstNonVideoAdaptcount=$adapt_count;
                        $firstNonVideoRepId=$id;
                    }
                    else{
                        if($firstNonVideoTrackPT!=$mediaTime+$xml_earliestCompTime)
                            fprintf ($opfile,"**'CMAF check violated: Section 7.3.6-'All CMAF Tracks in a CMAF Presentation that does not contain video SHALL be start aligned with CMAF presentation time zero equal to the earliest audio sample presentation start time in the earliest CMAF Fragment ', but not matching between Switching Set ".($firstNonVideoAdaptcount+1)." Track ".$firstNonVideoRepId." and Switching Set ".($adapt_count+1)." Track ".$id." \n");
                    }
                }

                //To find the longest CMAF track in the presentation
                $xml_mvhd=$xml->getElementsByTagName('mvhd');
                $xml_mehd=$xml->getElementsByTagName('mehd');
                $xml_num_moofs=$xml->getElementsByTagName('moof')->length;
                if($xml_mehd->length>0){
                    $mvhd_timescale=$xml_mvhd->item(0)->getAttribute('timeScale');
                    $fragmentDur=$xml_mehd->item(0)->getAttribute('fragmentDuration');
                    array_push($trackDurArray,$fragmentDur/$mvhd_timescale);
                }
                else{ 
                    $xml_lasttfdt=$xml->getElementsByTagName('tfdt')->item($xml_num_moofs-1);
                    $xml_lastDecodeTime=$xml_lasttfdt->getAttribute('baseMediaDecodeTime');
                    $xml_lasttrun=$xml->getElementsByTagName('trun')->item($xml_num_moofs-1);
                    $xml_cumSampleDur=$xml_lasttrun->getAttribute('cummulatedSampleDuration');
                    array_push($trackDurArray,($xml_lastDecodeTime+$xml_cumSampleDur)/$timescale);
                }

                //Find max video fragment duration from all the tracks.
                if($xml_handlerType=='vide'){
                    for($z=0;$z<$xml_num_moofs;$z++){
                        $fragDur=($xml_trun->item($z)->getAttribute('cummulatedSampleDuration'))/$timescale;
                        if($fragDur>$maxFragDur)
                            $maxFragDur=$fragDur;
                    }
                }

                //Check profile conformance
                //$xml_ftyp=$xml->getElementsByTagName('ftyp')[0];
                //$brands=(string)$xml_ftyp->getAttribute('compatible_brands');
                if($profile_cmfhd){
                    if($xml_handlerType=='vide'){
                        $videoFound=1;
                        if(cfhd_MediaProfileConformance($xml)==false)
                            break;
                        else
                            $video_counter=$video_counter+1;

                        if($cfhd_SwSetFound=0 && $video_counter ==$filecount)
                            $cfhd_SwSetFound=1;
                    }
                    else if($xml_handlerType=='soun'){
                        $audioFound=1;
                        if(caac_mediaProfileConformance($xml)==false)
                            break;
                        else
                        $audio_counter=$audio_counter+1;

                        if($caac_SwSetFound=0 && $audio_counter == $filecount)
                            $caac_SwSetFound=1;
                    }

                    if($xml->getElementsByTagName('tenc')->length >0)
                        fprintf($opfile, "**'CMAF check violated: Section A.1.2 - 'All CMAF Tracks SHALL NOT contain encrypted Samples or a TrackEncryptionBox', but found in Switching Set ".$adapt_count." Rep ".$id." \n");
                }
                if($profile_cmfhdc){
                    if($xml_handlerType=='vide'){
                        $videoFound=1;
                        if(cfhd_MediaProfileConformance($xml)==false)
                            break;
                        else
                            $video_counter=$video_counter+1;

                        if($cfhd_SwSetFound=0 && $video_counter ==$filecount)
                            $cfhd_SwSetFound=1;
                    }
                    else if($xml_handlerType=='soun'){
                        $audioFound=1;
                        if(caac_mediaProfileConformance($xml)==false)
                            break;
                        else
                        $audio_counter=$audio_counter+1;

                        if($caac_SwSetFound=0 && $audio_counter == $filecount)
                            $caac_SwSetFound=1;
                    }

                    if($xml->getElementsByTagName('tenc')->length >0){   
                        $enc_counter=$enc_counter+1;
                        $schm=$xml->getElementsByTagName('schm');
                        if($schm->length>0)
                            if($schm->item(0)->getAttribute('scheme')!='cenc')
                                fprintf($opfile, "**'CMAF check violated: Section A.1.3 - 'Any CMAF Switching Set that is encrypted SHALL be available in 'cenc' Common Encryption scheme', but found scheme ".$schm->item(0)->getAttribute('scheme')." \n");
                        if($encryptedSwSetFound=0 && $enc_counter == $filecount)
                            $encryptedSwSetFound=1;
                    }

                }
                if($profile_cmfhds){
                    if($xml_handlerType=='vide'){
                        $videoFound=1;
                        if(cfhd_MediaProfileConformance($xml)==false)
                            break;
                        else
                            $video_counter=$video_counter+1;

                        if($cfhd_SwSetFound=0 && $video_counter ==$filecount)
                            $cfhd_SwSetFound=1;
                    }
                    else if($xml_handlerType=='soun'){
                        $audioFound=1;
                        if(caac_mediaProfileConformance($xml)==false)
                            break;
                        else
                        $audio_counter=$audio_counter+1;

                        if($caac_SwSetFound=0 && $audio_counter == $filecount)
                            $caac_SwSetFound=1;
                    }

                    if($xml->getElementsByTagName('tenc')->length >0){   
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
            if(strpos($Adapt['mimeType'],"application/ttml+xml")){
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
    }
    
    //Check if presentation duration is same as longest track duration.
    if(round($PresentationDur,1)!=round(max($trackDurArray),1))
        fprintf($opfile, "**'CMAF check violated: Section 7.3.6 - 'The duration of a CMAF presentation shall be the duration of its longest CMAF track', but not found (Presentation time= ".$PresentationDur." and longest Track= ".max($trackDurArray).") \n");
    
    for($y=0;$y<count($trackDurArray);$y++){
        if(!((round($trackDurArray[$y],1)>=round($PresentationDur-$maxFragDur,1)) && (round($trackDurArray[$y],1)<=round($PresentationDur+$maxFragDur,1))))
            fprintf ($opfile,"**'CMAF check violated: Section 7.3.6-'CMAF Tracks in a CMAF Presentation SHALL equal the CMAF Presentation duration within a tolerance of the longest video CMAF Fragment duration ', but not found in the Track ".$y." \n");

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
        for($z=0;$z<$count;$z++){
            if($subtitle_array[$z]!=1)
                fprintf($opfile, "**'CMAF check violated: Section A.1.2/A.1.3/A.1.4 - 'If containing subtitles, SHALL include at least one Switching Set for each language and role in the 'im1t' Media Profile', but found none \n");
        }
    }

    fclose($opfile);
}

function checkSelectionSet(){
    global $session_dir, $mpd_features, $current_period, $adaptation_set_template, $selectionset_infofile;
    $longFragDur=0;
    $firstEntryflag=1;
    $SwSetDurArray=array();
    if(!($opfile = open_file($session_dir. '/' . $selectionset_infofile . '.txt', 'w'))){
        echo "Error opening/creating SelectionSet_infofile conformance check file: "."SelectionSet_infofile.txt";
        return;
    }
    
    $adapts = $mpd_features['Period'][$current_period]['AdaptationSet'];
    if(sizeof($adapts)<1)
        fprintf ($opfile,"**'CMAF check violated: Section 7.3.5-'A CMAF Selection Set SHALL contain one or more CMAF Switching Sets', but found none. \n");

    for($adapt_count=0; $adapt_count<sizeof($adapts); $adapt_count++){
        $Adapt=$adapts[$adapt_count];
        
        $adapt_dir = str_replace('$AS$', $adapt_count, $adaptation_set_template);
        $loc = $session_dir . '/' . $adapt_dir.'/';
        $filecount = 0;
        $files = glob($loc . "*.xml");
        if($files)
            $filecount = count($files);
        
        if(!file_exists($loc))
            fprintf ($opfile, "Tried to retrieve data from a location that does not exist. \n (Possible cause: Representations are not valid and no file/directory for box info is created.) for Switching Set ".$adapt_count."\n");
        else if($filecount>0){
            $filename = $files[0];
            $xml = get_DOM($filename, 'atomlist');
            $xml_hdlr=$xml->getElementsByTagName('hdlr')->item(0);
            $xml_handlerType=$xml_hdlr->getAttribute('handler_type');
            if($firstEntryflag){
                $firstEntryflag=0;
                $firstSwSetType=$xml_handlerType;
                continue;
            }
            else{
                if($firstSwSetType!=$xml_handlerType)
                     fprintf ($opfile,"**'CMAF check violated: Section 7.3.5-'All CMAF Switching Sets within a CMAF Selection Set SHALL be of the same media type', but not matching between Switching Set 1 and ".($adapt_count+1)." \n");
            }
            
            $xml_mehd=$xml->getElementsByTagName('mehd');
            if($xml_mehd->length>0){
                $trackDur=$xml_mehd->item(0)->getAttribute('fragmentDuration');
                $trackDur=$trackDur/1000; // Convert to seconds.
                array_push($SwSetDurArray, $trackDur);
                
                //Check that needs data from all Tracks
                for($i=0; $i<$filecount; $i++){
                    $filename = $files[$i];
                    $xml = get_DOM($filename, 'atomlist');
                    $xml_moof=$xml->getElementsByTagName('moof');
                    $xml_tfhd=$xml->getElementsByTagName('tfhd');
                    $xml_trun=$xml->getElementsByTagName('trun');
                    $xml_mdhd=$xml->getElementsByTagName('mdhd');
                    $timescale=$xml_mdhd->item(0)->getAttribute('timescale');
                    for($j=0;$j<$xml_moof->length;$j++){
                        //$sampleDur=$xml_tfhd[$j]->getAttribute('defaultSampleDuration');
                        //$sampleCount=$xml_trun[$j]->getAttribute('sampleCount');
                        $cummulatedSampleDur=$xml_trun->item($j)->getAttribute('cummulatedSampleDuration');
                        if($longFragDur< $cummulatedSampleDur/$timescale) // Process in seconds.
                            $longFragDur= $cummulatedSampleDur/$timescale;
                    }
                }
            }
        }
    }
    if(count($SwSetDurArray)>0){
        $min_dur=min($SwSetDurArray);
        for($k=0;$k<count($SwSetDurArray);$k++){
            $SwSetDurArray[$k]=$SwSetDurArray[$k]-$min_dur;
        }
        for($k=0;$k<count($SwSetDurArray);$k++){
            if($SwSetDurArray[$k]>$longFragDur)
               fprintf ($opfile,"**'CMAF check violated: Section 7.3.5-'All Switching Sets within a CMAF Selection Set SHALL be of the same duration, withing a tolerance of the longest CMAF Fragment duration of any Track in the Selection Set', but not found \n");
        }
    }
    
    fclose($opfile);
}

function checkAlignedSwitchingSets(){
    global $session_dir, $mpd_features, $current_period, $alignedswitching_infofile;
    $index=array();
    //Todo:More generalized approach with many Aligned Sw Sets.
    //Here assumption is only two Sw Sets are aligned.
    $adapts = $mpd_features['Period'][$current_period]['AdaptationSet'];
    for($z=0;$z<count($adapts);$z++){
        if($adapts[$z]['SupplementalProperty']){
            if($adapts[$z]['SupplementalProperty'][0]['schemeIdUri'] == 'urn:mpeg:dash:adaptation-set-switching:2016')
                array_push($index, (int)($adapts[$z]['SupplementalProperty'][0]['value']));
        }             
    }
    if(count($index)>=1){ // 0 means no Aligned SwSet, 2 or more is fine, 1 means error should be raised.
        if(!($opfile = open_file($session_dir. '/' . $alignedswitching_infofile . '.txt', 'w'))){
            echo "Error opening/creating Aligned SwitchingSet conformance check file: "."./AlignedSwitchingSet_infofile.txt";
            return;
        }
    }
    else
        return;
    
    if(count($index)>=2){
        $loc1 = $session_dir . '/Adapt' . ($index[0]-1).'/'; // For this naming there is no automation yet, since this implementation has an assumption on ids
        $filecount1 = 0;
        $files1 = glob($loc1. "*.xml");
        if($files1)
            $filecount1 = count($files1);
        
        if(!file_exists($loc1))
            fprintf ($opfile, "Tried to retrieve data from a location that does not exist. \n (Possible cause: Representations are not valid and no file/directory for box info is created.)");
        else{
            fprintf($opfile, "**Aligned SwitchingSet conformance check for: SwitchingSets (Adaptationsets) ".$index[1]." and ".$index[0].":\n\n");
            
            for($i=0;$i<$filecount1;$i++){
                $xml = get_DOM($files1[$i], 'atomlist');
                $id = $adapts[$index[0]-1]['Representation'][$i]['id'];
                
                $loc2 = $session_dir . '/Adapt' . ($index[1]-1).'/'; // For this naming there is no automation yet, since this implementation has an assumption on ids
                $filecount2 = 0;
                $files2 = glob($loc2. "*.xml");
                if($files2)
                    $filecount2 = count($files2);
                if(!file_exists($loc2))
                    fprintf ($opfile, "Tried to retrieve data from a location that does not exist. \n (Possible cause: Representations are not valid and no file/directory for box info is created.)");
                else{
                    for($j=0;$j<$filecount2;$j++){
                        $xml_comp = xmlFileLoad($files2[$j]);
                        $id_comp = $adapts[$index[1]-1]['Representation'][$j]['id'];
                        
                        $xml_num_moofs=$xml->getElementsByTagName('moof')->length;
                        $xml_comp_num_moofs=$xml_comp->getElementsByTagName('moof')->length;
                        
                        //Check Tracks have same ISOBMFF defined duration.
                        if($i==0 && $j==0){ // As duration is checked between Sw Sets, checking only once is enough.
                            if($xml->getElementsByTagName('mehd')->length >0 && $xml_comp->getElementsByTagName('mehd')->length >0 ){
                                $xml_mehd=$xml->getElementsByTagName('mehd')->item(0);
                                $xml_mehdDuration=$xml_mehd->getAttribute('fragmentDuration');
                                $xml_comp_mehd=$xml_comp->getElementsByTagName('mehd')->item(0);
                                $xml_comp_mehdDuration=$xml_comp_mehd->getAttribute('fragmentDuration');

                                if($xml_mehdDuration!=$xml_comp_mehdDuration)
                                    fprintf($opfile, "**'CMAF check violated: Section 7.3.4.4- Aligned Switching Sets SHALL contain CMAF switching sets of equal duration', but not matching between Switching Set ".$index[0]." and Switching Set ".$index[1]." \n");
                            }
                            else{
                                $xml_lasttfdt=$xml->getElementsByTagName('tfdt')->item($xml_num_moofs-1);
                                $xml_comp_lasttfdt=$xml_comp->getElementsByTagName('tfdt')->item($xml_comp_num_moofs-1);

                                $xml_lastDecodeTime=$xml_lasttfdt->getAttribute('baseMediaDecodeTime');
                                $xml_comp_lastDecodeTime=$xml_comp_lasttfdt->getAttribute('baseMediaDecodeTime');

                                $xml_lasttrun=$xml->getElementsByTagName('trun')->item($xml_num_moofs-1);
                                $xml_comp_lasttrun=$xml_comp->getElementsByTagName('trun')->item($xml_comp_num_moofs-1);

                                $xml_cumSampleDur=$xml_lasttrun->getAttribute('cummulatedSampleDuration');
                                $xml_comp_cumSampleDur=$xml_comp_lasttrun->getAttribute('cummulatedSampleDuration');

                                if($xml_lastDecodeTime+$xml_cumSampleDur != $xml_comp_lastDecodeTime+$xml_comp_cumSampleDur)
                                    fprintf($opfile, "**'CMAF check violated: Section 7.3.4.4- Aligned Switching Sets SHALL contain CMAF switching sets of equal duration', but not matching between Rep". $id." of Switching Set ".$index[0]." and Rep ".$id_comp." of Switching Set ".$index[1]." \n");
                            }
                        }
                        
                        //Check Tracks have same number of moofs.
                        if($xml_num_moofs!=$xml_comp_num_moofs){
                            fprintf($opfile, "**'CMAF check violated: Section 7.3.4.4- Aligned Switching Sets SHALL contain the same number of CMAF Fragments in every CMAF Track', but not matching between Rep ". $id." of Switching Set ".$index[0]." and Rep ".$id_comp." of Switching Set ".$index[1]." \n");
                            break;
                        }
                        
                        //This check only if previous check is not failed.
                        $xml_tfhd=$xml->getElementsByTagName('tfhd');
                        $xml_trun=$xml->getElementsByTagName('trun');
                        $xml_tfdt=$xml->getElementsByTagName('tfdt');
                        $xml_comp_tfhd=$xml_comp->getElementsByTagName('tfhd');
                        $xml_comp_trun=$xml_comp->getElementsByTagName('trun');
                        $xml_comp_tfdt=$xml_comp->getElementsByTagName('tfdt');
                        
                        for($y=0; $y<$xml_num_moofs;$y++){
                            //$sampleDur1=$xml_tfhd[$y]->getAttribute('defaultSampleDuration');
                            //$sampleCount1=$xml_trun[$y]->getAttribute('sampleCount');
                            $cummulatedSampleDur1=$xml_trun->item($y)->getAttribute('cummulatedSampleDuration');
                            $decodeTime1=$xml_tfdt->item($y)->getAttribute('baseMediaDecodeTime');

                            //$sampleDur2=$xml_comp_tfhd[$y]->getAttribute('defaultSampleDuration');
                            //$sampleCount2=$xml_comp_trun[$y]->getAttribute('sampleCount');
                            $cummulatedSampleDur2=$xml_comp_trun->item($y)->getAttribute('cummulatedSampleDuration');
                            $decodeTime2=$xml_comp_tfdt->item($y)->getAttribute('baseMediaDecodeTime');

                            if($cummulatedSampleDur1!= $cummulatedSampleDur2 || $decodeTime1!=$decodeTime2){
                               fprintf($opfile, "**'CMAF check violated: Section 7.3.4.4- Aligned Switching Sets SHALL contain CMAF Fragments in every CMAF Track with matching baseMediaDecodeTime and duration', but not matching between Rep ". $id." of Switching Set ".$index[0]." and Rep ".$id_comp." of Switching Set ".$index[1]." \n");
                               break;
                            }
                        }
                    }
                }
            }
        }
    }
    else{
        fprintf($opfile, "**Aligned SwitchingSet conformance check :\n\n");
        fprintf($opfile, "**'CMAF check violated: Section 7.3.4.4- Aligned Switching Sets SHALL contain two or more CMAF switching sets', but only one found. \n");
    }
    
    fclose($opfile);
}

function cfhd_MediaProfileConformance($xml){
    $conform=true;
    $xml_videSample=$xml->getElementsByTagName('vide_sampledescription');
    if($xml_videSample->length>0){
        $sdType=$xml_videSample->item(0)->getAttribute('sdType');
        if($sdType != "avc1" && $sdType != "avc3")
            $conform=false;
            
        $width=$xml_videSample->item(0)->getAttribute('width');
        $height=$xml_videSample->item(0)->getAttribute('height');
        if($width > 1920 && $height > 1080)
            $conform=false;
    }
    else
        $conform=false;
            
    $xml_avcC=$xml->getElementsByTagName('avcC');
    $xml_avcProfile=$xml_avcC->item(0)->getAttribute('profile');
    if($xml_avcProfile !=100 && $xml_avcProfile !=110 && $xml_avcProfile !=122 && $xml_avcProfile !=144)
        $conform=false;
    
    $xml_avcComment=$xml_avcC->item(0)->getElementsByTagName('Comment');
    $xml_level=$xml_avcComment->item(0)->getAttribute('level');
    if($xml_level !=31 && $xml_level !=40)
        $conform=false;
        
    $xml_NALUnit=$xml->getElementsByTagName('NALUnit');
    if($xml_NALUnit->length>0){
        $xml_NALComment=$xml_NALUnit->item(0)->getElementsByTagName('comment');
        if($xml_NALComment->length>0){
            if($xml_NALComment->item(0)->getAttribute(video_signal_type_present_flag) !=0x0 && $xml_NALComment->item(0)->getAttribute('colour_description_present_flag') !=0x0)
            {
                $colorPrimaries=$xml_NALComment->item(0)->getAttribute('colour_primaries');
                if($colorPrimaries !=0x1 && $colorPrimaries !=0x5 && $colorPrimaries !=0x6)
                    $conform=false;

                $tranferChar=$xml_NALComment->item(0)->getAttribute('transfer_characteristics');
                if($tranferChar !=0x1 && $tranferChar!= 0x6)
                    $conform=false;

                $matrixCoeff=$xml_NALComment->item(0)->getAttribute('matrix_coefficients');
                if($matrixCoeff !=0x1 && $matrixCoeff !=0x5 && $matrixCoeff !=0x6 )
                    $conform=false;
            }
        
            $num_ticks=$xml_NALComment->item(0)->getAttribute('num_units_in_tick');
            $time_scale=$xml_NALComment->item(0)->getAttribute('time_scale');
            $max_FPS=ceil((int)time_scale /(2*(int)num_ticks));
            if($max_FPS >60)
                $conform=false;
        }
    }
    return $conform;
}

function caac_mediaProfileConformance($xml){
    $conform=true;
    $xml_audioSample=$xml->getElementsByTagName('soun_sampledescription');
    if($xml_audioSample->length>0){
        $samplingRate=$xml_audioSample->item(0)->getAttribute('sampleRate');
        if((float)$samplingRate>48000.0)
            $conform=false;
    }
    $xml_audioDec=$xml->getElementsByTagName('DecoderSpecificInfo');
    $channelConfig=$xml_audioDec->item(0)->getAttribute('channelConfig');
    if($channelConfig !=0x1 && $channelConfig!=0x2)
        $conform=false;
    
    return  $conform;
}