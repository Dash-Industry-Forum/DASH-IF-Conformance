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

function CTAPresentation()
{   
    global $mpd_features,$session_dir,$CTApresentation_infofile,$current_period,$adaptation_set_template, $opfile, $string_info, $progress_xml, $progress_report;
    $opfile="";

    if(!($opfile = open_file($session_dir. '/Period' . $current_period . '/' . $CTApresentation_infofile . '.txt', 'w'))){
            echo "Error opening/creating Presentation profile conformance check file: "."./Presentation_infofile_ctawave.txt";
            return;
    }
    $adapts = $mpd_features['Period'][$current_period]['AdaptationSet'];
    $result= CTACheckPresentation(sizeof($adapts), $session_dir, $adaptation_set_template, $opfile);
    fclose($opfile);
    
    $temp_string = str_replace(array('$Template$'),array($CTApresentation_infofile),$string_info);
    file_put_contents($session_dir.'/Period'.$current_period.'/'.$CTApresentation_infofile.'.html',$temp_string);
    
    $searchfiles = file_get_contents($session_dir.'/Period'.$current_period.'/'.$CTApresentation_infofile.'.txt');
    if(strpos($searchfiles, "CTAWAVE check violated") !== FALSE){
        $progress_xml->Results[0]->Period[$current_period]->addChild('CTAWAVEPresentation', 'error');
        $file_error[] = $session_dir.'/Period' .$current_period.'/'.$CTApresentation_infofile.'.html';
    }
    elseif(strpos($searchfiles, "Warning") !== FALSE || strpos($searchfiles, "WARNING") !== FALSE){
        $progress_xml->Results[0]->Period[$current_period]->addChild('CTAWAVEPresentation', 'warning');
        $file_error[] = $session_dir.'/Period'.$current_period.'/'.$CTApresentation_infofile.'.html';
    }
    else{
        $progress_xml->Results[0]->Period[$current_period]->addChild('CTAWAVEPresentation', 'noerror');
        $file_error[] = "noerror";
    }
    
    $tempr_string = str_replace('$Template$', '/Period'.$current_period.'/'.$CTApresentation_infofile, $string_info);
    file_put_contents($session_dir.'/Period'.$current_period.'/'.$CTApresentation_infofile.'.html', $tempr_string);
    $progress_xml->asXml(trim($session_dir . '/' . $progress_report));
    
    print_console($session_dir.'/Period'.$current_period.'/'.$CTApresentation_infofile.'.txt', "Period " . ($current_period+1) . " CTA WAVE Presentation Results");
}

function CTACheckPresentation($adapts_count,$session_dir,$adaptation_set_template,$opfile)
{
    global $current_period;
    
    $cfhdVideoSwSetFound=0;$videoSelectionSetFound=0;
    $caacAudioSwSetFound=0;$audioSelectionSetFound=0;
    $im1tSubtitleSwSetFound=0;$subtitleSelectionSetFound=0;
    $handler_type=""; $errorMsg="";$encryptedTrackFound=0;$cencSwSetFound=0;$cbcsSwSetFound=0;
    $presentationProfile="";

    for($adapt_count=0; $adapt_count<$adapts_count; $adapt_count++){

        $SwSet_MP=array();    
        $EncTracks=array();
        $adapt_dir = str_replace('$AS$', $adapt_count, $adaptation_set_template);
        $loc = $session_dir . '/Period' . $current_period . '/' . $adapt_dir.'/';
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
                    if($handler_type=="vide")
                    {
                        $videoSelectionSetFound=1;

                    }
                    if($handler_type=="soun")
                    {
                        $audioSelectionSetFound=1;

                    }
                    if($handler_type=="subt")
                    {
                        $subtitleSelectionSetFound=1;

                    }
                       array_push($SwSet_MP, $MPTrack); 

                    //Check for encrypted tracks
                    if($xml->getElementsByTagName('tenc')->length >0)
                    {
                        $encryptedTrackFound=1;
                        $schm=$xml->getElementsByTagName('schm');
                        if($schm->length>0)
                             array_push($EncTracks,$schm->item(0)->getAttribute('scheme'));
                    }
                }     
            }

            if(count(array_unique($SwSet_MP)) === 1)
            {
                if($handler_type==="vide" && array_unique($SwSet_MP)[0]==="HD"){
                        $cfhdVideoSwSetFound=1;
                }elseif($handler_type=="soun" && array_unique($SwSet_MP)[0]==="AAC_Core"){
                        $caacAudioSwSetFound=1;
                }
                elseif($handler_type=="subt" && array_unique($SwSet_MP)[0]==="TTML_IMSC1_Text"){
                        $im1tSubtitleSwSetFound=1;
                }
                
            }
            if($encryptedTrackFound===1)
            {
                if(count($EncTracks)==$filecount && count(array_unique($EncTracks)) === 1 && array_unique($EncTracks)[0]==="cenc")
                    $cencSwSetFound=1;
                elseif(count($EncTracks)==$filecount && count(array_unique($EncTracks)) === 1 && array_unique($EncTracks)[0]==="cbcs")
                    $cbcsSwSetFound=1;

            }
        }
    }
    

    $PresProfArray=array();
    if($videoSelectionSetFound )
    {
        if(!$cfhdVideoSwSetFound)
        {
            array_push($PresProfArray,"");
            fprintf ($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 5: 'If a video track is included, then conforming Presentation will at least include that video in a CMAF SwSet conforming to required AVC (HD) Media Profile', but AVC-HD SwSet not found in the presentation. \n");

        }
        else
            array_push($PresProfArray,getPresentationProfile($encryptedTrackFound,$cencSwSetFound,$cbcsSwSetFound,$opfile));
             
    }
    if($audioSelectionSetFound )//&& $caacAudioSwSetFound) ||( && $im1tSubtitleSwSetFound
    {
        if(!$caacAudioSwSetFound)
        {
            array_push($PresProfArray,"");
            fprintf ($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 5: 'If an audio track is included, then conforming Presentation will at least include that audio in a CMAF SwSet conforming to required AAC (Core) Media Profile', but AAC-Core SwSet not found in the presentation. \n");

        }
        else
            array_push($PresProfArray,getPresentationProfile($encryptedTrackFound,$cencSwSetFound,$cbcsSwSetFound,$opfile));
    }
    if($subtitleSelectionSetFound)
    {
        if(!$im1tSubtitleSwSetFound)
        {
            array_push($PresProfArray,"");
            fprintf ($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 5: 'If a subtitle track is included, then conforming Presentation will at least include that subtitle in a CMAF SwSet conforming to TTML Text Media Profile', but TTML Text SwSet not found in the presentation. \n");

        }
        else
            array_push($PresProfArray,getPresentationProfile($encryptedTrackFound,$cencSwSetFound,$cbcsSwSetFound,$opfile));
    }

    if(in_array("", $PresProfArray))
        $presentationProfile="";
    elseif(count(array_unique($PresProfArray))===1)
        $presentationProfile=$PresProfArray[0];
    else
        $presentationProfile="";
    /*
        fprintf($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 5: For CMAF Presentation profile ".$presnt_profile." ',if video track is included, then Presentation will at least include that video in a CMAF SwSet conforming to AVC-HD Media Profile', video track found but SWSet conforming to AVC-HD not found \n");
    if(inarray($profile,$profilesArray) && $audioSelectionSetFound && $caacAudioSwSetFound!=1)
        fprintf($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 5: For CMAF Presentation profile ".$presnt_profile." ',if audio track is included, then Presentation will at least include that audio in a CMAF SwSet conforming to AAC-Core Media Profile', audio track found but SWSet conforming to AAC-Core not found \n");

    if(inarray($profile,$profilesArray) && $subtitleSelectionSetFound && $im1tSubtitleSwSetFound!=1)
        fprintf($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 5: For CMAF Presentation profile ".$presnt_profile." ',if subtitile track is included, then Presentation will at least include that subtitle in a CMAF SwSet conforming to IMSC1-Text Media Profile', subtitle track found but SWSet conforming to IMSC1-Text not found \n");
    if($profile_cmfhdc && $encryptedSwSetFound!=1)
        fprintf($opfile, "**'CMAF check violated: Section A.1.3 - 'At least one CMAF Switching Set SHALL be encrypted', but found none. \n");
    if($profile_cmfhds && $encryptedSwSetFound!=1)
        fprintf($opfile, "**'CMAF check violated: Section A.1.4 - 'At least one CMAF Switching Set SHALL be encrypted', but found none. \n");
    */
    
    if($presentationProfile!=="")
        fprintf ($opfile, "Information: The WAVE content conforms to CMAF Presentation Profile- ".$presentationProfile." \n");
    else
        fprintf ($opfile, "Information: The WAVE content doesn't conform to any of the CMAF Presentation Profiles \n");
    
return $presentationProfile;    
    
}
function getPresentationProfile($encryptedTrackFound,$cencSwSetFound,$cbcsSwSetFound,$opfile)
{
    $presentationProfile="";
    if($encryptedTrackFound===0)
        $presentationProfile="CMFHD";
    elseif($encryptedTrackFound && $cencSwSetFound && $cbcsSwSetFound)
        fprintf($opfile, "###CTAWAVE check violated: WAVE Content Spec 2018Ed-Section 5: 'Each CMAF Presentation Profile contains either all unencrypted samples or some samples encrypted with CENC using 'cenc' or 'cbcs' scheme, but not both', here SwSet with 'cenc' and 'cbcs' are found.");
    elseif($encryptedTrackFound && $cencSwSetFound)
        $presentationProfile="CMFHDc";
    elseif($encryptedTrackFound && $cencSwSetFound)
        $presentationProfile="CMFHDs";
    
    return $presentationProfile;
}
?>
