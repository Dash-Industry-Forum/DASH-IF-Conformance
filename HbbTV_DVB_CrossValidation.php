<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function CrossValidation_HbbTV_DVB(){
    global $hbbtv_conformance, $dvb_conformance, $session_dir, $mpd_features, 
            $current_period, $adaptation_set_template, $hbbtv_dvb_crossvalidation_logfile, 
            $string_info, $progress_xml, $progress_report;
    
    content_protection_report();
    $adapts = $mpd_features['Period'][$current_period]['AdaptationSet'];
    for($adapt_count=0; $adapt_count<sizeof($adapts); $adapt_count++){
        $adapt_dir = str_replace('$AS$', $adapt_count, $adaptation_set_template);
        $loc = $session_dir . '/' . $adapt_dir.'/';
        $filecount = 0;
        $files = glob($loc . "*.xml");
        if($files)
            $filecount = count($files);
        
        $log_file = str_replace('$AS$', $adapt_count, $hbbtv_dvb_crossvalidation_logfile);
        if(!($opfile = open_file($session_dir.'/'.$log_file.'.txt', 'a'))){
            echo 'Error opening/creating HbbTV/DVB Cross representation validation file: ./'.$adapt_dir.$hbbtv_dvb_crossvalidation_logfile.'.txt';
            return;
        }
        
        ## Cross Validation Checks
        for($r=0; $r<$filecount; $r++){
            $xml_r = get_DOM($files[$r], 'atomlist');
            
            for($d=$r+1; $d<$filecount; $d++){
                $xml_d = get_DOM($files[$d], 'atomlist');
                
                if($xml_r && $xml_d){
                    if($hbbtv_conformance){
                        crossValidation_HbbTV_Representations($opfile, $xml_r, $xml_d, $adapt_count, $r, $d);
                    }
                    if($dvb_conformance){
                        crossValidation_DVB_Representations($opfile, $xml_r, $xml_d, $adapt_count, $r, $d);
                    }
                }
            }
        }
        init_seg_commonCheck($files,$opfile);
        if($dvb_conformance){
            DVB_period_continuous_adaptation_sets_check($opfile);
        }
        ##
        
        ## Reporting
        fclose($opfile);
        $temp_string = str_replace(array('$Template$'),array($log_file),$string_info);
        file_put_contents($session_dir.'/'.$log_file.'.html',$temp_string);
        
        $searchfiles = file_get_contents($session_dir.'/'.$log_file.'.txt');
        if(strpos($searchfiles, "DVB check violated") !== FALSE || strpos($searchfiles, "HbbTV check violated") !== FALSE || strpos($searchfiles, 'ERROR') !== FALSE){
            $progress_xml->Results[0]->Period[0]->Adaptation[$adapt_count]->addChild('HbbTVDVBComparedRepresentations', 'error');
            $file_error[] = $session_dir.'/'.$log_file.'.html'; // add error file location to array
        }
        elseif(strpos($searchfiles, "Warning") !== FALSE || strpos($searchfiles, "WARNING") !== FALSE){
            $progress_xml->Results[0]->Period[0]->Adaptation[$adapt_count]->addChild('HbbTVDVBComparedRepresentations', 'warning');
            $file_error[] = $session_dir.'/'.$log_file.'.html'; // add error file location to array
        }
        else{
            $progress_xml->Results[0]->Period[0]->Adaptation[$adapt_count]->addChild('HbbTVDVBComparedRepresentations', 'noerror');
            $file_error[] = "noerror"; // no error found in text file
        }
        $progress_xml->Results[0]->Period[0]->Adaptation[$adapt_count]->HbbTVDVBComparedRepresentations->addAttribute('url', str_replace($_SERVER['DOCUMENT_ROOT'], 'http://' . $_SERVER['SERVER_NAME'], $session_dir.'/'.$log_file.'.txt'));
        $progress_xml->asXml(trim($session_dir . '/' . $progress_report));
        
        err_file_op(2);
        print_console($session_dir.'/'.$log_file.'.txt', "HbbTV-DVB Cross Validation Results for AdaptationSet $adapt_count");
        ##
    }
}

function crossValidation_DVB_Representations($opfile, $xml_r, $xml_d, $i, $r, $d){
    global $mpd_features, $current_period;
    
    ## Section 4.3 checks for sample entry type and track_ID
    $hdlr_r = $xml_r->getElementsByTagName('hdlr')->item(0);
    $hdlr_type_r = $hdlr_r->getAttribute('handler_type');
    $sdType_r = $xml_r->getElementsByTagName($hdlr_type_r.'_sampledescription')->item(0)->getAttribute('sdType');
    
    $hdlr_d = $xml_r->getElementsByTagName('hdlr')->item(0);
    $hdlr_type_d = $hdlr_d->getAttribute('handler_type');
    $sdType_d = $xml_d->getElementsByTagName($hdlr_type_d.'_sampledescription')->item(0)->getAttribute('sdType');
    
    ## Non-switchable audia representation reporting
    if($sdType_r != $sdType_d){
        fwrite($opfile, "###'DVB check violated: Section 4.3- All the initialization segments for Representations within an Adaptation Set SHALL have the same sample entry type', found $sdType_r in Adaptation Set " . ($i+1) . " Representation " . ($r+1) . " $sdType_d in Adaptation Set " . ($i+1) . " Representation " . ($d+1) . ".\n");
    
        if($hdlr_type_r == $hdlr_type_d && $hdlr_type_r == 'soun')
            fwrite($opfile, "Warning for HbbTV-DVB DASH Validation Requirements check for DVB: Section 'Adaptation Sets' - 'Non-switchable audio codecs SHOULD NOT be present within the same Adaptation Set for the presence of consistent Representations within an Adaptation Set ', found in Adaptation Set " . ($i+1) . ": Representation " . ($r+1) . " and Representation " . ($d+1) . ".\n");
    }
    ##
    
    $tkhd_r = $xml_r->getElementsByTagName('tkhd')->item(0);
    $track_ID_r = $tkhd_r->getAttribute('trackID');
    $tfhds_r = $xml_r->getElementsByTagName('tfhd');
    
    $tkhd_d = $xml_d->getElementsByTagName('tkhd')->item(0);
    $track_ID_d = $tkhd_d->getAttribute('trackID');
    $tfhds_d = $xml_d->getElementsByTagName('tfhd');
    
    $tfhd_info = '';
    foreach($tfhds_r as $index => $tfhd_r){
        if($tfhd_r->getAttribute('trackID') != $tfhds_d->item($index)->getAttribute('trackID'))
            $tfhd_info .= ' error'; 
    }
    
    if($tfhd_info != '' || $track_ID_r != $track_ID_d)
        fwrite($opfile, "###'DVB check violated: Section 4.3- All Representations within an Adaptation Set SHALL have the same track_ID', not equal in Adaptation Set " . ($i+1) . ": Representation " . ($r+1) . " and Representation " . ($d+1) . ".\n");
    ##
    
    ## Section 5.1.2 check for initialization segment identicalness
    if($sdType_r == $sdType_d && ($sdType_r == 'avc1' || $sdType_r == 'avc2')){
        $stsd_r = $xml_r->getElementsByTagName('stsd')->item(0);
        $stsd_d = $xml_d->getElementsByTagName('stsd')->item(0);
        
        if(!nodes_equal($stsd_r, $stsd_d))
            fwrite($opfile, "###'DVB check violated: Section 5.1.2- In this case (content offered using either of the 'avc1' or 'avc2' sample entries), the Initialization Segment SHALL be common for all Representations within an Adaptation Set', not equal in Adaptation Set " . ($i+1) . ": Representation " . ($r+1) . " and Representation " . ($d+1) . ".\n");
    }
    ##
    
    ## Section 8.3 check for default_KID value
    $tenc_r = $xml_r->getElementsByTagName('tenc')->item(0);
    $tenc_d = $xml_d->getElementsByTagName('tenc')->item(0);
    
    if($tenc_r->length != 0 && $tenc_d->length != 0){
        if($tenc_r->getAttribute('default_KID') != $tenc_d->getAttribute('default_KID')){
            fwrite($opfile, "###'DVB check violated: Section 8.3- All Representations (in the same Adaptation Set) SHALL have the same value of 'default_KID' in their 'tenc' boxes in their Initialization Segments', not equal in Adaptation Set " . ($i+1) . ": Representation " . ($r+1) . " and Representation " . ($d+1) . ".\n");
            
            $vide_r = $xml_r->getElementsByTagName($hdlr_type_r.'_sampledescription')->item(0);
            $vide_d = $xml_d->getElementsByTagName($hdlr_type_r.'_sampledescription')->item(0);
            $width_r = $vide_r->getAttribute('width');
            $height_r = $vide_r->getAttribute('height');
            $width_d = $vide_r->getAttribute('width');
            $height_d = $vide_r->getAttribute('height');
            
            if(($width_r < 1280 && $height_r < 720 && $width_d >= 1280 && $height_d >= 720) || ($width_d < 1280 && $height_d < 720 && $width_r >= 1280 && $height_r >= 720))
                fwrite ($opfile, "###'DVB check violated: Section 8.3- In cases where HD and SD content are contained in one presentation and MPD, but different licence rights are given for each resolution, then they SHALL be contained in different HD and SD Adaptation Sets', but SD and HD contents are contained the same adaptation set: Adaptation Set " . ($i+1) . ": Representation " . ($r+1) . " and Representation " . ($d+1) . ".\n");
        }
    }
    ##
    
    ## Section 10.4 check for audio switching
    if($hdlr_type_r == 'soun' && $hdlr_type_d == 'soun'){
        $adapt = $mpd_features['Period'][$current_period]['AdaptationSet'][$i];
        $rep_r = $adapt['Representation'][$r];
        $rep_d = $adapt['Representation'][$d];
        
        if(sizeof($rep_r) != sizeof($rep_d))
            fwrite($opfile, "Information on DVB conformance: Section 10.4- 'Players SHALL support seamless swicthing between audio Representations which only differ in bit rate', different attributes found in Adaptation Set " . ($i+1) . ": Representation " . ($r+1) . " and Representation " . ($d+1) . ".\n");
        else{
            foreach($rep_r as $key_r => $val_r){
                if(!is_array($val_r)){
                    if(!array_key_exists($key_r, $rep_d))
                        fwrite($opfile, "Information on DVB conformance: Section 10.4- 'Players SHALL support seamless swicthing between audio Representations which only differ in bit rate', $key_r attribute found and not found in Adaptation Set " . ($i+1) . ": Representation " . ($r+1) . " and Representation " . ($d+1) . ", respectively.\n");
                    else{
                        $val_d = $rep_d[$key_r];
                        if($key_r != 'bandwidth' && $key_r != 'id'){
                            if($val_r != $val_d)
                                fwrite($opfile, "Information on DVB conformance: Section 10.4- 'Players SHALL support seamless swicthing between audio Representations which only differ in bit rate', different attribute values found in Adaptation Set " . ($i+1) . ": Representation " . ($r+1) . " and Representation " . ($d+1) . ".\n");
                        }
                    }
                }
            }
        }
        
        ## Section 6.1.1 Table 3 cross-checks for audio representations
        // @mimeType
        $adapt_mime = $adapt['mimeType'];
        $rep_mime_r = $rep_r['mimeType'];
        $rep_mime_d = $rep_d['mimeType'];
        if($adapt_mime == ''){
            if($rep_mime_r != $rep_mime_d)
                fwrite($opfile, "###'DVB check violated: Section 6.1.1- @mimeType attribute SHALL be common between all audio Representations in an Adaptation Set', not common in Adaptation Set " . ($i+1) . ": Representation " . ($r+1) . " and Representation " . ($d+1) . ".\n");
        }
        
        // @codecs
        $adapt_codecs = $adapt['codecs'];
        $rep_codecs_r = $rep_r['codecs'];
        $rep_codecs_d = $rep_d['codecs'];
        if($adapt_codecs == ''){
            if(($rep_codecs_r != '' && $rep_codecs_d != '') && $rep_codecs_r != $rep_codecs_d)
                fwrite($opfile, "Warning for DVB check: Section 6.1.1- @codecs attribute SHOULD be common between all audio Representations in an Adaptation Set', not common in Adaptation Set " . ($i+1) . ": Representation " . ($r+1) . " and Representation " . ($d+1) . ".\n");
        }
        
        // @audioSamplingRate
        $adapt_audioSamplingRate = $adapt['audioSamplingRate'];
        $rep_audioSamplingRate_r = $rep_r['audioSamplingRate'];
        $rep_audioSamplingRate_d = $rep_d['audioSamplingRate'];
        if($adapt_audioSamplingRate == ''){
            if(($rep_audioSamplingRate_r != '' && $rep_audioSamplingRate_d != '') && $rep_audioSamplingRate_r != $rep_audioSamplingRate_d)
                fwrite($opfile, "Warning for DVB check: Section 6.1.1- @audioSamplingRate attribute SHOULD be common between all audio Representations in an Adaptation Set', not common in Adaptation Set " . ($i+1) . ": Representation " . ($r+1) . " and Representation " . ($d+1) . ".\n");
        }
        
        // AudioChannelConfiguration and Role
        $adapt_audioChConf = array();
        $adapt_role = array();
        foreach($adapt['AudioChannelConfiguration'] as $adapt_ch){
            $adapt_audioChConf[] = $adapt_ch;
        }
        foreach($adapt['Role'] as $adapt_ch){
            $adapt_role[] = $adapt_ch;
        }
        
        if(empty($adapt_audioChConf)){
            $rep_audioChConf_r = array();
            $rep_audioChConf_d = array();
            foreach($rep_r['AudioChannelConfiguration'] as $rep_r_ch){
                $rep_audioChConf_r[] = $rep_r_ch;
            }
            foreach($rep_d['AudioChannelConfiguration'] as $rep_d_ch){
                $rep_audioChConf_d[] = $rep_d_ch;
            }
            
            if(!empty($rep_audioChConf_r) && !empty($rep_audioChConf_d)){
                $equal_info = '';
                if(sizeof($rep_audioChConf_r) != sizeof($rep_audioChConf_d))
                    fwrite($opfile, "Warning for DVB check: Section 6.1.1- AudioChannelConfiguration SHOULD be common between all audio Representations in an Adaptation Set', not common in Adaptation Set " . ($i+1) . ": Representation " . ($r+1) . " and Representation " . ($d+1) . ".\n");
                else{
                    for($racc=0; $racc<sizeof($rep_audioChConf_r); $racc++){
                        $rep_audioChConf_r_i = $rep_audioChConf_r[$racc];
                        $rep_audioChConf_d_i = $rep_audioChConf_d[$racc];
                        
                        if(!nodes_equal($rep_audioChConf_r_i, $rep_audioChConf_d_i))
                            $equal_info .= 'no';
                    }
                }
                
                if($equal_info != '')
                    fwrite($opfile, "Warning for DVB check: Section 6.1.1- AudioChannelConfiguration SHOULD be common between all audio Representations in an Adaptation Set', not common in Adaptation Set " . ($i+1) . ": Representation " . ($r+1) . " and Representation " . ($d+1) . ".\n");
            }
            else
                fwrite($opfile, "Warning for DVB check: Section 6.1.1- AudioChannelConfiguration SHOULD be common between all audio Representations in an Adaptation Set', not common in Adaptation Set " . ($i+1) . ": Representation " . ($r+1) . " and Representation " . ($d+1) . ".\n");
        }
        
        if(empty($adapt_role)){
            $rep_role_r = array();
            $rep_role_d = array();
            foreach($rep_r['Role'] as $rep_r_ch){
                $rep_role_r[] = $rep_r_ch;
            }
            foreach($rep_d['Role'] as $rep_d_ch){
                $rep_role_d[] = $rep_d_ch;
            }
            
            if(!empty($rep_role_r) && !empty($rep_role_d)){
                $equal_info = '';
                if(sizeof($rep_role_r) != sizeof($rep_role_d))
                    fwrite($opfile, "###'DVB check violated: Section 6.1.1- Role element SHALL be common between all audio Representations in an Adaptation Set', not common in Adaptation Set " . ($i+1) . ": Representation " . ($r+1) . " and Representation " . ($d+1) . ".\n");
                else{
                    for($rr=0; $rr<sizeof($rep_role_r); $rr++){
                        $rep_role_r_i = $rep_role_r[$rr];
                        $rep_role_d_i = $rep_role_d[$rr];
                        
                        if(!nodes_equal($rep_role_r_i, $rep_role_d_i))
                            $equal_info .= 'no';
                    }
                }
                
                if($equal_info != '')
                    fwrite($opfile, "###'DVB check violated: Section 6.1.1- Role element SHALL be common between all audio Representations in an Adaptation Set', not common in Adaptation Set " . ($i+1) . ": Representation " . ($r+1) . " and Representation " . ($d+1) . ".\n");
            }
            else
                fwrite($opfile, "###'DVB check violated: Section 6.1.1- Role element SHALL be common between all audio Representations in an Adaptation Set', not common in Adaptation Set " . ($i+1) . ": Representation " . ($r+1) . " and Representation " . ($d+1) . ".\n");
        }
        ##
        
        ## Section 6.4 on DTS audio frame durations
        if(strpos($adapt_codecs, 'dtsc') !== FALSE || strpos($adapt_codecs, 'dtsh') !== FALSE || strpos($adapt_codecs, 'dtse') !== FALSE || strpos($adapt_codecs, 'dtsl') !== FALSE
           || ($adapt_codecs == '' && (strpos($rep_codecs_r, 'dtsc') !== FALSE || strpos($rep_codecs_r, 'dtsh') !== FALSE || strpos($rep_codecs_r, 'dtse') !== FALSE || strpos($rep_codecs_r, 'dtsl') !== FALSE) 
           && (strpos($rep_codecs_d, 'dtsc') !== FALSE || strpos($rep_codecs_d, 'dtsh') !== FALSE || strpos($rep_codecs_d, 'dtse') !== FALSE || strpos($rep_codecs_d, 'dtsl') !== FALSE))){
            $timescale_r = (int)($xml_r->getElementsByTagName('mdhd')->item(0)->getAttribute('timescale'));
            $timescale_d = (int)($xml_d->getElementsByTagName('mdhd')->item(0)->getAttribute('timescale'));
            $trun_boxes_r = $xml_r->getElementsByTagName('trun');
            $trun_boxes_d = $xml_d->getElementsByTagName('trun');
            
            if($trun_boxes_r->length == $trun_boxes_d->length){
                $trun_len = $trun_boxes_r->length;
                for($t=0; $t<$trun_len; $t++){
                    $cummulatedSampleDuration_r = (int)($trun_boxes_r->item($t)->getAttribute('cummulatedSampleDuration'));
                    $cummulatedSampleDuration_d = (int)($trun_boxes_d->item($t)->getAttribute('cummulatedSampleDuration'));
                    
                    if($cummulatedSampleDuration_r/$timescale_r != $cummulatedSampleDuration_d/$timescale_d)
                        fwrite($opfile, "###'DVB check violated: Section 6.4- the audio frame duration SHALL reamin constant for all streams within a given Adaptation Set', not common in Segment \"$t\" in Adaptation Set " . ($i+1) . ": Representation " . ($r+1) . " and Representation " . ($d+1) . ".\n");
                }
            }
        }
        ##
        
        ## Adaptation Set check for consistent representations: Highlight 5.1 audio and 2.0 Audio in the same adaptation set
        $soun_r = $xml_r->getElementsByTagName('soun_sampledescription')->item(0);
        $conf_r = $soun_r->getElementsByTagName('DecoderSpecificInfo')->item(0);
        $conf_atts_r = $conf_r->attributes;
        $conf_aud_r = '';
        foreach($conf_atts_r as $conf_att_r){
            if(strpos($conf_att_r->value, 'config is') !== FALSE)
                $conf_aud_r = $conf_att_r->value;
        }
        
        $soun_d = $xml_d->getElementsByTagName('soun_sampledescription')->item(0);
        $conf_d = $soun_d->getElementsByTagName('DecoderSpecificInfo')->item(0);
        $conf_atts_d = $conf_d->attributes;
        $conf_aud_d = '';
        foreach($conf_atts_d as $conf_att_d){
            if(strpos($conf_att_d->value, 'config is') !== FALSE)
                $conf_aud_d = $conf_att_d->value;
        }
        
        if($conf_aud_r != '' && $conf_aud_d != ''){
            if(($conf_aud_r == 'config is 5+1' && $conf_aud_d == 'config is stereo') || ($conf_aud_d == 'config is 5+1' && $conf_aud_r == 'config is stereo'))
                fwrite($opfile, "Warning for DVB check: '5.1 Audio and 2.0 Audio SHOULD NOT be present within the same Adaptation Set for the presence of consistent Representations within an Adaptation Set ', found in Adaptation Set " . ($i+1) . ": Representation " . ($r+1) . " and Representation " . ($d+1) . ".\n");
        }
        ##
    }
    ##
    
    ## Section 10.4 check for video switching
    if($hdlr_type_r == 'vide' && $hdlr_type_d == 'vide'){
        $adapt = $mpd_features['Period'][$current_period]['AdaptationSet'][$i];
        $rep_r = $adapt['Representation'][$r];
        $rep_d = $adapt['Representation'][$d];
        
        if(sizeof($rep_r) != sizeof($rep_d))
            fwrite($opfile, "Information on DVB conformance: Section 10.4- 'Players SHALL support seamless swicthing between video Representations', different number of attributes found in Adaptation Set " . ($i+1) . ": Representation " . ($r+1) . " and Representation " . ($d+1) . ".\n");
        else{
            foreach($rep_r as $key_r => $val_r){
                if(!is_array($val_r)){
                    if(!array_key_exists($key_r, $rep_d))
                        fwrite($opfile, "Information on DVB conformance: Section 10.4- 'Players SHALL support seamless swicthing between video Representations', $key_r attribute found and not found in Adaptation Set " . ($i+1) . ": Representation " . ($r+1) . " and Representation " . ($d+1) . ", respectively.\n");
                    else{
                        $val_d = $rep_d[$key_r];
                        if($key_r != 'bandwidth' && $key_r != 'id' && $key_r != 'frameRate' && $key_r != 'width' && $key_r != 'height' && $key_r != 'codecs'){
                            if($val_r != $val_d)
                                fwrite($opfile, "Information on DVB conformance: Section 10.4- 'Players SHALL support seamless swicthing between video Representations which differ only in frame rate, bit rate, profile and/or level, and resolution', different attribute values found in Adaptation Set " . ($i+1) . ": Representation " . ($r+1) . " and Representation " . ($d+1) . ".\n");
                        }
                    }
                }
            }
            
            // Frame rate
            $possible_fr1 = array('25', '25/1', '50', '50/1');
            $possible_fr2 = array('30/1001', '60/1001');
            $possible_fr3 = array('30', '30/1', '60', '60/1');
            $possible_fr4 = array('24', '24/1', '48', '48/1');
            $possible_fr5 = array('24/1001');
            $fr_r = $rep_r['frameRate'];
            $fr_d = $rep_d['frameRate'];
            if($fr_r != '' && $fr_d != ''){
                if((in_array($fr_r, $possible_fr1) && !in_array($fr_d, $possible_fr1)) || (!in_array($fr_r, $possible_fr1) && in_array($fr_d, $possible_fr1)))
                    fwrite($opfile, "Information on DVB conformance: Section 10.4- 'Players SHALL support seamless swicthing between video Representations which can differ in framerate, providing the frame rate is within on of the specified families', different attribute values found in Adaptation Set " . ($i+1) . ": Representation " . ($r+1) . " and Representation " . ($d+1) . ".\n");
                if((in_array($fr_r, $possible_fr2) && !in_array($fr_d, $possible_fr2)) || (!in_array($fr_r, $possible_fr2) && in_array($fr_d, $possible_fr2)))
                    fwrite($opfile, "Information on DVB conformance: Section 10.4- 'Players SHALL support seamless swicthing between video Representations which can differ in framerate, providing the frame rate is within on of the specified families', different attribute values found in Adaptation Set " . ($i+1) . ": Representation " . ($r+1) . " and Representation " . ($d+1) . ".\n");
                if((in_array($fr_r, $possible_fr3) && !in_array($fr_d, $possible_fr3)) || (!in_array($fr_r, $possible_fr3) && in_array($fr_d, $possible_fr3)))
                    fwrite($opfile, "Information on DVB conformance: Section 10.4- 'Players SHALL support seamless swicthing between video Representations which can differ in framerate, providing the frame rate is within on of the specified families', different attribute values found in Adaptation Set " . ($i+1) . ": Representation " . ($r+1) . " and Representation " . ($d+1) . ".\n");
                if((in_array($fr_r, $possible_fr4) && !in_array($fr_d, $possible_fr4)) || (!in_array($fr_r, $possible_fr4) && in_array($fr_d, $possible_fr4)))
                    fwrite($opfile, "Information on DVB conformance: Section 10.4- 'Players SHALL support seamless swicthing between video Representations which can differ in framerate, providing the frame rate is within on of the specified families', different attribute values found in Adaptation Set " . ($i+1) . ": Representation " . ($r+1) . " and Representation " . ($d+1) . ".\n");
                if((in_array($fr_r, $possible_fr5) && !in_array($fr_d, $possible_fr5)) || (!in_array($fr_r, $possible_fr5) && in_array($fr_d, $possible_fr5)))
                    fwrite($opfile, "Information on DVB conformance: Section 10.4- 'Players SHALL support seamless swicthing between video Representations which can differ in framerate, providing the frame rate is within on of the specified families', different attribute values found in Adaptation Set " . ($i+1) . ": Representation " . ($r+1) . " and Representation " . ($d+1) . ".\n");
            }
            
            // Resolution
            $width_r = $rep_r['width'];
            $height_r = $rep_r['height'];
            $width_d = $rep_d['width'];
            $height_d = $rep_d['height'];
            if($width_r != '' && $height_r != '' && $width_d != '' && $height_d != ''){
                if($adapt['par'] != ''){
                    $par = $adapt['par'];
                    if($width_r != $width_d || $height_r != $height_d){
                        $par_arr = explode(':', $par);
                        $par_ratio = (float)$par_arr[0] / (float)$par_arr[1];
                        
                        $par_r = $width_r/$height_r;
                        $par_d = $width_d/$height_d;
                        if($par_r != $par_d || $par_r != $par_ratio || $par_d != $par_ratio)
                            fwrite($opfile, "Information on DVB conformance: Section 10.4- 'Players SHALL support seamless swicthing between video Representations which can differ in resolution, maintaining the same picture aspect ratio', different attribute values found in Adaptation Set " . ($i+1) . ": Representation " . ($r+1) . " and Representation " . ($d+1) . ".\n");
                    }
                }
                else{
                    $content_comps = $adapt['ContentComponent'];
                    foreach($content_comps as $content_comp){
                        $pars[] = $content_comp['par'];
                    }
                    
                    if(count(array_unique($pars)) != 1 || (count(array_unique($pars)) == 1 && in_array('', $pars)))
                        fwrite($opfile, "Information on DVB conformance: Section 10.4- 'Players SHALL support seamless swicthing between video Representations which can differ in resolution, maintaining the same picture aspect ratio', different attribute values found in Adaptation Set " . ($i+1) . ": Representation " . ($r+1) . " and Representation " . ($d+1) . ".\n");
                    
                    elseif(count(array_unique($pars)) == 1 && !in_array('', $pars)){
                        if($width_r != $width_d || $height_r != $height_d){
                            $par = $pars[0];
                            $par_arr = explode(':', $par);
                            $par_ratio = (float)$par_arr[0] / (float)$par_arr[1];
                            
                            $par_r = $width_r/$height_r;
                            $par_d = $width_d/$height_d;
                            if($par_r != $par_d || $par_r != $par_ratio || $par_d != $par_ratio)
                                fwrite($opfile, "Information on DVB conformance: Section 10.4- 'Players SHALL support seamless swicthing between video Representations which can differ in resolution, maintaining the same picture aspect ratio', different attribute values found in Adaptation Set " . ($i+1) . ": Representation " . ($r+1) . " and Representation " . ($d+1) . ".\n");
                        }
                    }
                }
            }
        }
    }
    ##
}

function crossValidation_HbbTV_Representations($opfile, $xml_r, $xml_d, $i, $r, $d){
    ## Section E.3.2 checks on Adaptation Sets
    // Second bullet on same trackID
    $tkhd_r = $xml_r->getElementsByTagName('tkhd')->item(0);
    $track_ID_r = $tkhd_r->getAttribute('trackID');
    $tfhds_r = $xml_r->getElementsByTagName('tfhd');
    
    $tkhd_d = $xml_d->getElementsByTagName('tkhd')->item(0);
    $track_ID_d = $tkhd_d->getAttribute('trackID');
    $tfhds_d = $xml_d->getElementsByTagName('tfhd');
    
    $tfhd_info = '';
    foreach($tfhds_r as $index => $tfhd_r){
        if($tfhd_r->getAttribute('trackID') != $tfhds_d->item($index)->getAttribute('trackID'))
            $tfhd_info .= ' error'; 
    }
    
    if($tfhd_info != '' || $track_ID_r != $track_ID_d)
        fwrite($opfile, "###'HbbTV check violated: Section E.3.2- All ISO BMFF Representations SHALL have the same track_ID in the track header box and track fragment header box', not equal in Adaptation Set " . ($i+1) . ": Representation " . ($r+1) . " and Representation " . ($d+1) . ".\n");
    
    // Third bullet on initialization segment identicalness
    $stsd_r = $xml_r->getElementsByTagName('stsd')->item(0);
    $stsd_d = $xml_d->getElementsByTagName('stsd')->item(0);
    
    if(!nodes_equal($stsd_r, $stsd_d))
        fwrite($opfile, "###'HbbTV check violated: Section E.3.2- Initialization Segment SHALL be common for all Representations', not equal in Adaptation Set " . ($i+1) . ": Representation " . ($r+1) . " and Representation " . ($d+1) . ".\n");
    ##
    
    $hdlr_r = $xml_r->getElementsByTagName('hdlr')->item(0);
    $hdlr_type_r = $hdlr_r->getAttribute('handler_type');
    $sdType_r = $xml_r->getElementsByTagName($hdlr_type_r.'_sampledescription')->item(0)->getAttribute('sdType');
    
    $hdlr_d = $xml_r->getElementsByTagName('hdlr')->item(0);
    $hdlr_type_d = $hdlr_d->getAttribute('handler_type');
    $sdType_d = $xml_d->getElementsByTagName($hdlr_type_d.'_sampledescription')->item(0)->getAttribute('sdType');
    
    ## Highlight HEVC and AVC for different representations in the same Adaptation Set
    if($hdlr_type_r == 'vide' && $hdlr_type_d == 'vide'){
        if((($sdType_r == 'hev1' || $sdType_r == 'hvc1') && strpos($sdType_d, 'avc')) || (($sdType_d == 'hev1' || $sdType_d == 'hvc1') && strpos($sdType_r, 'avc')))
            fwrite($opfile, "Warning for HbbTV-DVB DASH Validation Requirements check for HbbTV: Section 'Adaptation Sets' - 'Terminals cannot switch between HEVC and AVC video Represntations present in the same Adaptation Set', found in Adaptation Set " . ($i+1) . ": Representation " . ($r+1) . " and Representation " . ($d+1) . ".\n");
    }
    ##
    
    ## Highlight 5.1 Audio and 2.0 Audio
    if($hdlr_type_r == 'soun' && $hdlr_type_d == 'soun'){
        $soun_r = $xml_r->getElementsByTagName('soun_sampledescription')->item(0);
        $conf_r = $soun_r->getElementsByTagName('DecoderSpecificInfo')->item(0);
        $conf_atts_r = $conf_r->attributes;
        $conf_aud_r = '';
        foreach($conf_atts_r as $conf_att_r){
            if(strpos($conf_att_r->value, 'config is') !== FALSE)
                $conf_aud_r = $conf_att_r->value;
        }
        
        $soun_d = $xml_d->getElementsByTagName('soun_sampledescription')->item(0);
        $conf_d = $soun_d->getElementsByTagName('DecoderSpecificInfo')->item(0);
        $conf_atts_d = $conf_d->attributes;
        $conf_aud_d = '';
        foreach($conf_atts_d as $conf_att_d){
            if(strpos($conf_att_d->value, 'config is') !== FALSE)
                $conf_aud_d = $conf_att_d->value;
        }
        
        if($conf_aud_r != '' && $conf_aud_d != ''){
            if(($conf_aud_r == 'config is 5+1' && $conf_aud_d == 'config is stereo') || ($conf_aud_d == 'config is 5+1' && $conf_aud_r == 'config is stereo'))
                fwrite($opfile, "Warning for HbbTV-DVB DASH Validation Requirements check for HbbTV: Section 'Adaptation Sets' - '5.1 Audio and 2.0 Audio SHOULD NOT be present within the same Adaptation Set for the presence of consistent Representations within an Adaptation Set ', found in Adaptation Set " . ($i+1) . ": Representation " . ($r+1) . " and Representation " . ($d+1) . ".\n");
        }
    }
    ##
}

// Check if the nodes and their descendandts are the same
function nodes_equal($node_1, $node_2){
    $equal = true;
    foreach($node_1->childNodes as $index => $ch_1){
        $ch_2 = $node_2->childNodes->item($index);
        
        if($ch_1->nodeType == XML_ELEMENT_NODE && $ch_2->nodeType == XML_ELEMENT_NODE){
            if($ch_1->nodeName != $ch_2->nodeName){
                $equal = false;
                break;
            }
           
            $atts_1 = $ch_1->attributes;
            $atts_2 = $ch_2->attributes;
            if($atts_1->length != $atts_2->length){
                $equal = false;
                break;
            }
            for($i=0; $i<$atts_1->length; $i++){
                if($atts_1->item($i)->name != $atts_2->item($i)->name || $atts_1->item($i)->value != $atts_2->item($i)->value){
                    $equal = false;
                    break;
                }
            }
            
            $equal = nodes_equal($ch_1, $ch_2);
            if($equal == false)
                break;
        }
    }
    
    return $equal;
}

function init_seg_commonCheck($files,$opfile){
    $rep_count=count($files);
    fwrite($opfile, "Information on HbbTV-DVB DASH Validation Requirements: Section 'Init Segment(s)' - There are ".$rep_count." Representation in the AdaptationSet with \n");
    for($i=0;$i<$rep_count;$i++){
        $xml = get_DOM($files[$i], 'atomlist');
        if($xml){
            $avcC_count=$xml->getElementsByTagName('avcC')->length;
            fwrite($opfile, ", ".$avcC_count." 'avcC' in Representation ".($i+1)." \n");
        }
    }
}

function content_protection_report(){
    global $session_dir, $mpd_dom, $adaptation_set_template, $reprsentation_template, $hbbtv_dvb_crossvalidation_logfile;
    $DRM_uuid_array = array ('urn:mpeg:dash:mp4protection:2011'=>'Generic Identifier 1',
                             'urn:mpeg:dash:13818:1:CA_descriptor:2011'=>'Generic Identifier 2',
                             'urn:uuid:5E629AF538DA4063897797FFBD9902D4'=>'Marlin Adaptive Streaming Specification',
                             'urn:uuid:adb41c242dbf4a6d958b4457c0d27b95'=>'Nagra MediaAccess PRM 3.0',
                             'urn:uuid:A68129D3575B4F1A9CBA3223846CF7C3'=>'Cisco/NDS VideoGuard Everywhere DRM',
                             'urn:uuid:9a04f07998404286ab92e65be0885f95'=>'Microsoft PlayReady',
                             'urn:uuid:9a27dd82fde247258cbc4234aa06ec09'=>'Verimatrix ViewRight Web',
                             'urn:uuid:F239E769EFA348509C16A903C6932EFB'=>'Adobe Primetime',
                             'urn:uuid:1f83e1e86ee94f0dba2f5ec4e3ed1a66'=>'SecureMedia',
                             'urn:uuid:644FE7B5260F4FAD949A0762FFB054B4'=>'CMLA',
                             'urn:uuid:6a99532d869f59229a91113ab7b1e2f3'=>'MobiTV',
                             'urn:uuid:35BF197B530E42D78B651B4BF415070F'=>'DivX ',
                             'urn:uuid:B4413586C58CFFB094A5D4896C1AF6C3'=>'Viaccess-Orca',
                             'urn:uuid:edef8ba979d64acea3c827dcd51d21ed'=>'Widevine',
                             'urn:uuid:80a6be7e14484c379e70d5aebe04c8d2'=>'Irdeto',
                             'urn:uuid:dcf4e3e362f158187ba60a6fe33ff3dd'=>'DigiCAP SmartXess',
                             'urn:uuid:45d481cb8fe049c0ada9ab2d2455b2f2'=>'CoreTrust',
                             'urn:uuid:616C7469636173742D50726F74656374'=>'Alticast altiProtect',
                             'urn:uuid:992c46e6c4374899b6a050fa91ad0e39'=>'SecureMedia SteelKnot',
                             'urn:uuid:1077efecc0b24d02ace33c1e52e2fb4b'=>'W3C',
                             'urn:uuid:e2719d58a985b3c9781ab030af78d30e'=>'Clear Key',
                             'urn:uuid:94CE86FB07FF4F43ADB893D2FA968CA2'=>'Apple FairPlay Streaming',
                             'urn:uuid:279fe473512c48feade8d176fee6b40f'=>'Arris Titanium');
    $Adapt_index = 0;
    $key_rotation_used = false;
    foreach ($mpd_dom->getElementsByTagName('AdaptationSet') as $node){
        $adapt_id = $Adapt_index + 1;
        $report_loc = str_replace('$AS$', $Adapt_index, $hbbtv_dvb_crossvalidation_logfile);
        $adaptreport = open_file($session_dir . "/".$report_loc.'.txt', 'a+b');
        if($adaptreport !== false){
            $MPD_systemID_array = array();
            $missing_pssh_array = array(); //holds the uuid-s of the DRM-s which are missing the pssh in the mpd
            $reps_array = array(); // for the summary of reps and their KID
            $rep_index = 0;
            $MPD_kID_flag = false;
            $generic_identifier = "";
            $content_protection_flag = false;
            foreach ($node->getElementsByTagName('ContentProtection') as $cp_node){
                // only if there is a content protection instance the below will be executed
                $content_protection_flag = true;
                if(($cp_node->getAttribute('schemeIdUri') == "urn:mpeg:dash:mp4protection:2011") || ($cp_node->getAttribute('schemeIdUri') == 'urn:mpeg:dash:13818:1:CA_descriptor:2011')){
                    $generic_identifier = $cp_node->getAttribute('schemeIdUri');
                }

                if((!$MPD_kID_flag) && ($generic_identifier != "")){ // if a KID was not found in the init seg check in the mpd
                    $MPD_kID = $cp_node->getAttribute('cenc:default_KID');
                    if($MPD_kID != '')
                    {
                        $MPD_kID = str_replace('-', '', $kID);
                        $MPD_kID_flag = true; //there is a cenc:default_KID, so there must be a pssh in a mpd or init seg
                    }
                }

                $cenc_pssh = $cp_node->getAttribute('cenc:pssh');
                if(($cenc_pssh == '') && ($cp_node->getAttribute('schemeIdUri') != "urn:mpeg:dash:mp4protection:2011") && ($cp_node->getAttribute('schemeIdUri') != 'urn:mpeg:dash:13818:1:CA_descriptor:2011')){
                    $uuid = $cp_node->getAttribute('schemeIdUri');
                    $uuid = str_replace('-', '', $uuid);
                    $missing_pssh_array[] = $uuid; //the drm uuid which is missing a pssh in the mpd
                }

                //excluding generic identifiers which are usually in the first instance of Content Protection
                if (($cp_node->getAttribute('schemeIdUri') != "urn:mpeg:dash:mp4protection:2011") && ($cp_node->getAttribute('schemeIdUri') != 'urn:mpeg:dash:13818:1:CA_descriptor:2011')){
                    $MPD_systemID = $cp_node->getAttribute('schemeIdUri');
                    $MPD_systemID = str_replace('-', '', $MPD_systemID);

                    if(array_key_exists($MPD_systemID, $DRM_uuid_array)){
                        $MPD_systemID_array[$MPD_systemID] = $DRM_uuid_array[$MPD_systemID];
                    }
                    else{
                        $MPD_systemID_array[$MPD_systemID] = 'unknown'; //if no matches are found in the mapping array 
                    }
                }
            }

            /*For an encrypted Adaptation Set, ContentProtection Descriptors shall always be
            present in the AdaptationSet element, and apply to all contained Representations.
            A ContentProtection Descriptor for the mp4 Protection Scheme with the
            @schemeIdUri value of "urn:mpeg:dash:mp4protection:2011" and
            @value=’cenc’ shall be present in the AdaptationSet element if the contained
            Representations are encrypted.*/
            if($content_protection_flag){    
                //reporting DRM systems in use
                if($generic_identifier != ""){
                    $MPD_systemID_k_v  = implode(', ', array_map(
                    function ($v, $k) { return sprintf(" '%s' :: '%s'", $k, $v); },
                    $MPD_systemID_array,array_keys($MPD_systemID_array)));
                    fwrite($adaptreport, "Information on HbbTV-DVB DASH Validation Requirements: Section 'DRM' - DRM systems present in the MPD in Adaptation Set ".$adapt_id.
                    " are identified as follows: ".$generic_identifier." :: ".$DRM_uuid_array[$generic_identifier]." ".$MPD_systemID_k_v."\n");

                    $tenc_kID_array = array();
                    foreach ($node->getElementsByTagName('Representation') as $rep){    
                        $duplication_flag = false;
                        $inconsistency_flag = false;
                        $missing_pssh_flag = false;
                        $PSSH_systemID_array = array(); // to see the DRM uuid in mpd and pssh and compare them
                        $tenc_kID_flag = false;

                        $adapt_dir = str_replace('$AS$', $Adapt_index, $adaptation_set_template);
                        $rep_dir = str_replace(array('$AS$', '$R$'), array($Adapt_index, $rep_index), $reprsentation_template);
                        $xml_file_location = ($session_dir.'/'.$adapt_dir.'/'.$rep_dir.'.xml'); //first rep of the adapt set will have the same pssh as the rest
                        $abs = get_DOM($xml_file_location, 'atomlist'); // load mpd from url
                        if($abs){
                            /*There SHALL be identical values of default_KID in the Track Encryption Box
                            (‘tenc’) of all Representation referenced by one Adaptation Set.*/
                            if($abs->getElementsByTagName('tenc')->length != 0){
                                $tenc_kID = $abs->getElementsByTagName('tenc')->item(0)->getAttribute('default_KID');//default KID can be in the pssh in the init seg
                            }
                            else{
                                $tenc_kID = '';
                            }
                            if($tenc_kID != ''){
                                $tenc_kID_flag = true;
                                $tenc_kID_array[] = $tenc_kID;
                            }
                            foreach ($abs->getElementsByTagName('pssh') as $pssh_node){
                                $PSSH_systemID = 'urn:uuid:'.$pssh_node->getAttribute('systemID');
                                if(array_key_exists($PSSH_systemID, $DRM_uuid_array)){
                                    $PSSH_systemID_array[$PSSH_systemID] = $DRM_uuid_array[$PSSH_systemID];
                                }
                                else{
                                   $PSSH_systemID_array[$PSSH_systemID] = 'unknown'; //if no matches are found in the mapping array 
                                }        
                            }

                            if($tenc_kID_flag || $MPD_kID_flag){   
                                // if a pssh is missing in the mpd then there must be in the init seg
                                // all the nr of instances which are missing the pssh in the mpd must be in the init seg  
                                if((count($missing_pssh_array) != 0) && (count(array_intersect($missing_pssh_array, $PSSH_systemID_array)) != count($missing_pssh_array))){//not all the missing pssh are in the init seg
                                    $missing_pssh_flag = true; 
                                }
                            }

                            if(!empty($PSSH_systemID_array)){ //comparing if there's a DRM in pssh since the MPD has at least a generic one
                                //flag if in both and show inconsistencies 
                                //$diff_1 = array_diff($MPD_systemID_array, $PSSH_systemID_array); // the uuid that are in mpd but not in pssh
                                $diff_2 = array_diff(array_keys($PSSH_systemID_array), array_keys($MPD_systemID_array)); // the uuid that are in pssh but not mpd
                                if(count(array_intersect(array_keys($PSSH_systemID_array), array_keys($MPD_systemID_array))) !=0){
                                    $duplication_flag = true; //there is at least one DRM with the same uuid in both
                                }
                                else{ //the pssh box has at least one DRM uuid which is not in the mpd while all the DRM uuid-s must be in the ContentProtection instance of the MPD, so we have inconsistency 
                                    $inconsistency_flag = true; 
                                }
                            }
                            //add summary for encrypted rep and their kID. use rep id to identify them     
                            $repr_id = $rep->getAttribute('id');
                            $reps_array[$repr_id] = $tenc_kID; 
                            //checking for key rotation:
                            //if there is no pssh in any moof then no key rotation is used
                            foreach ($abs->getElementsByTagName('moof') as $moof){
                                if($moof->getElementsByTagName('pssh')->length != 0){ //if pssh does't exists and is an empty node
                                    if(($moof->getElementsByTagName('sgpd')->length != 0) && ($moof->getElementsByTagName('sbgp')->length != 0)){
                                        $key_rotation_used = true;
                                    }
                                }     
                            }
                            //Check the scheme_type field of the ‘schm’ box has the value ‘cenc’
                            if($abs->getElementsByTagName('schm')->length != 0){
                                if($abs->getElementsByTagName('schm')->item(0)->getAttribute('scheme') !== "cenc"){
                                    fwrite($adaptreport, "Information on HbbTV-DVB DASH Validation Requirements: Section 'DRM' - 'cenc' scheme not found in 'schm' box in Adaptation Set: ".$adapt_id.", Representation: ".$repr_id."\n"); 
                                }  
                            }
                            if(!empty($PSSH_systemID_array)){
                                $PSSH_systemID_k_v  = implode(', ', array_map(
                                function ($v, $k) { return sprintf(" '%s' :: '%s'", $k, $v); },
                                $PSSH_systemID_array,array_keys($PSSH_systemID_array)));
                                fwrite($adaptreport, "Information on HbbTV-DVB DASH Validation Requirements: Section 'DRM' - DRM systems present in the PSSH in Adaptation Set: ".$adapt_id.", Representation: ".$repr_id." are identified as follows ".$PSSH_systemID_k_v."\n"); 
                            }

                            //reporting if there is a missing PSSH
                            if($missing_pssh_flag){
                                fwrite($adaptreport, "Information on HbbTV-DVB DASH Validation Requirements: Section 'DRM' - Warning! There is default_KID: ".$tenc_kID." but there is/are missing PSSH box/es (both in MPD and Init segment)"
                                    . " in Adaptation Set: ".$adapt_id." Representation: ".$repr_id."\n");
                            }
                            //reporting duplicate and inconsistent DRM-s in MPD and PSSH box
                            if($duplication_flag){
                                fwrite($adaptreport, "Information on HbbTV-DVB DASH Validation Requirements: Section 'DRM' - There are consistent DRM-s in MPD and PSSH box in Adaptation Set: ".$adapt_id.", Representation: ".$repr_id."\n");
                            }

                            if($inconsistency_flag){
                                $diff_2_k_v  = implode(', ', array_map(
                                function ($v, $k) { return sprintf(" SystemID: '%s' ", $v); },
                                $diff_2,array_keys($diff_2)));
                                fwrite($adaptreport, "Information on HbbTV-DVB DASH Validation Requirements: Section 'DRM' - There are inconsistent DRM-s in MPD and PSSH box in Adaptation Set: ".$adapt_id.", Representation: ".$repr_id." :\n "
                                        . "the following DRM systems were found present in PSSH but not MPD:\n".$diff_2_k_v."\n");;
                            }
                        }
                        $rep_index ++; 
                    }

                    //summary of reps and the KID used
                    if($tenc_kID_flag){
                        if(count(array_unique($tenc_kID_array)) == 1){ // is all defauld_KID-s in the adaptation set are the same then write the following report
                            $reps_k  = implode(', ', array_map(
                            function ($v, $k) { return sprintf(" '%s' ", $k); },
                            $reps_array,array_keys($reps_array)));
                            fwrite($adaptreport, "Information on HbbTV-DVB DASH Validation Requirements: Section 'DRM' - The KID: ".$tenc_kID." is used for representations:".$reps_k."in Adaptation Set ".$adapt_id."\n"); 
                        }
                        else{
                            fwrite($adaptreport, "###HbbTV-DVB DASH Validation Requirements check violated: Section 'DRM' - The Representations in Adaptation Set ".$adapt_id." shall all have the same 'default_KID' in the 'tenc' box but found otherwise.\n");
                        }
                    }
                    //reporting for key retation   
                    if($key_rotation_used){
                        fwrite($adaptreport, "Information on HbbTV-DVB DASH Validation Requirements: Section 'DRM' - Adaptation Set ".$adapt_id.": Key rotation used.\n");
                    }
                    else{
                        fwrite($adaptreport, "Information on HbbTV-DVB DASH Validation Requirements: Section 'DRM' - Adaptation Set ".$adapt_id.": Key rotation not used.\n");
                    }   
                }
                else{
                    fwrite($adaptreport, "###HbbTV-DVB DASH Validation Requirements check violated: Section 'DRM' - Content Protection instance found in Adaptation Set ".$adapt_id." but the ContentProtection Descriptor for the mp4 Protection Scheme with the".
                    "@schemeIdUri value of 'urn:mpeg:dash:mp4protection:2011' and @value=’cenc’ is missing.\n");
                }
            }
            else{
                fwrite($adaptreport, "Information on HbbTV-DVB DASH Validation Requirements: Section 'DRM' - Content Protection not used in Adaptation Set ".$adapt_id.".\n");
            }

            fclose($adaptreport);
        }    
        $Adapt_index ++; //move to check the next adapt set
    }
}

function DVB_period_continuous_adaptation_sets_check($opfile){
    global $session_dir, $mpd_features, $associativity, $adaptation_set_template, $reprsentation_template;
    $periods = $mpd_features['Period'];
    $period_cnt = sizeof($periods);
    
    for($i=0; $i<$period_cnt; $i++){
        for($j=$i+1; $j<$period_cnt; $j++){
            $period1 = $periods[$i];
            $adapts1 = $period1['AdaptationSet'];
            $period2 = $periods[$j];
            $adapts2 = $period2['AdaptationSet'];
            
            for($a1=0; $a1<sizeof($adapts1); $a1++){
                for($a2=0; $a2<sizeof($adapts2); $a2++){
                    $adapt1 = $adapts1[$a1];
                    $adapt2 = $adapts2[$a2];
                    
                    $adapt1_id = $adapt1['id'];
                    $adapt2_id = $adapt2['id'];
                    if($adapt1_id == $adapt2_id){
                        $supps2 = $adapt2['SupplementalProperty'];
                        foreach($supps2 as $supp2){
                            if($supp2['schemeIdUri'] == 'urn:dvb:dash:period_continuity:2014' && in_array($period1['id'], explode(',', $supp2['value']))){
                                ## Period continuous adapation sets are signalled. 
                                ## Start checking for conformity according to Section 10.5.2.3
                                
                                // Check associativity
                                $string_to_search = "$i $a1 $j $a2";
                                if(!in_array($string_to_search, $associativity))
                                    fwrite($opfile, "###'DVB check violated: Section 10.5.2.2- If Adaptation Sets in two different Periods are period continuous, then Adaptation Sets with the value of their @id attribute set to AID in the first and subsequent Periods SHALL be associated as defined in clause 10.5.2.3', not associated Adaptation Set " . ($a1+1) . " in Period " . ($i+1) . " and Adaptation Set " . ($a2+1) . " in Period " . ($j+1) . ".\n");
                                
                                // EPT1 comparisons within the Adaptation Sets
                                if($i == 0){
                                    $reps1 = $adapt1['Representation'];
                                    $EPT1 = array();
                                    for($r1=0; $r1<sizeof($reps1); $r1++){
                                        $adapt_dir = str_replace('$AS$', $a1, $adaptation_set_template);
                                        $rep_dir = str_replace(array('$AS$', '$R$'), array($a1, $r1), $reprsentation_template);
                                        
                                        $xml_rep = get_DOM($session_dir.'/'.$adapt_dir.'/'.$rep_dir.'.xml', 'atomlist');
                                        if($xml_rep)
                                            $EPT1[] = segment_timing_info($xml_rep);
                                    }
                                    for($r1=0; $r1<sizeof($reps1); $r1++){
                                        for($r1_1=$r1+1; $r1_1<sizeof($reps1); $r1_1++){
                                            if($EPT1[$r1] !== $EPT1[$r1_1])
                                                fwrite($opfile, "###'DVB check violated: Section 10.5.2.2- If Adaptation Sets in two different Periods are period continuous, then all the Representations in the Adaptation Set in the first Period SHALL share the same value EPT1 for the earliest presentation time', not identical for Representation " . ($r1+1) . " and Representation " . ($r1_1+1) . " in Adaptation Set " . ($a1+1) . " in Period " . ($i+1) . ".\n");
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

function segment_timing_info($xml_rep){
    global $mpd_features;
    
    $type = $mpd_features['type'];
    $xml_num_moofs=$xml_rep->getElementsByTagName('moof')->length;
    $xml_trun=$xml_rep->getElementsByTagName('trun');
    $xml_tfdt=$xml_rep->getElementsByTagName('tfdt');
    
    $sidx_boxes = $xml_rep->getElementsByTagName('sidx');
    $subsegment_signaling = array();
    if($sidx_boxes->length != 0){
        foreach($sidx_boxes as $sidx_box){
            $subsegment_signaling[] = (int)($sidx_box->getAttribute('referenceCount'));
        }
    }
    
    $xml_elst = $xml_rep->getElementsByTagName('elst');
    if($xml_elst->length == 0){
        $mediaTime = 0;
    }
    else{
        $mediaTime = (int)($xml_elst->item(0)->getElementsByTagName('elstEntry')->item(0)->getAttribute('mediaTime'));
    }
    
    $timescale=$xml_rep->getElementsByTagName('mdhd')->item(0)->getAttribute('timescale');
    $sidx_index = 0;
    $cum_subsegDur=0;
    $EPT = array();
    if($type != 'dynamic'){
        for($j=0;$j<$xml_num_moofs;$j++){
            $decodeTime = $xml_tfdt->item($j)->getAttribute('baseMediaDecodeTime');
            $compTime = $xml_trun->item($j)->getAttribute('earliestCompositionTime');
            
            $startTime = ($decodeTime + $compTime - $mediaTime)/$timescale;
            if(empty($subsegment_signaling)){
                $EPT[] = $startTime;
            }
            else{
                $ref_count = 1;
                if($sidx_index < sizeof($subsegment_signaling))
                    $ref_count = $subsegment_signaling[$sidx_index];
                
                if($cum_subsegDur == 0)
                    $EPT[] = $startTime;
                
                $cum_subsegDur += (($xml_trun->item($j)->getAttribute('cummulatedSampleDuration'))/$timescale);
                $subsegment_signaling[$sidx_index] = $ref_count - 1;
                if($subsegment_signaling[$sidx_index] == 0){
                    $sidx_index++;
                    $cum_subsegDur = 0;
                }
            }
        }
    }
    
    return $EPT;
}