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

$period_count = 0;
$adapt_video_count = 0;
$adapt_audio_count = 0;
$main_audios = array();
$main_audio_found = false;
$main_video_found = false;
$hoh_subtitle_lang = array();
$video_bw = array();
$audio_bw = array();
$subtitle_bw = array();
$associativity = array();

function HbbTV_DVB_mpdvalidator() {
    global $session_dir, $mpd_log, $hbbtv_conformance, $dvb_conformance, $mpd_xml, $mpd_xml_report;
    
    $mpdreport = open_file($session_dir . '/' . $mpd_log . '.txt', 'a+b');
    if(!$mpdreport)
        return;
    
    fwrite($mpdreport, "Start HbbTV-DVB Validation \n");
    fwrite($mpdreport, "===========================\n\n");

    ## Report on profile-specific media types' completeness
    DVB_HbbTV_profile_specific_media_types_report($mpdreport);
    
    ## Informational cross-profile check
    DVB_HbbTV_cross_profile_check($mpdreport);
    
    if($dvb_conformance){
        DVB_mpdvalidator($mpdreport);
        DVB_mpd_anchor_check($mpdreport);
    }
    
    if($hbbtv_conformance){
        HbbTV_mpdvalidator($mpdreport);
    }
    
    fclose($mpdreport);
    
    ## Return 'warning' or 'error' to the mpdprocessing part.
    $returnValue="true";
    $mpdreportText=file_get_contents($session_dir . '/' . $mpd_log . '.txt');
    if(strpos($mpdreportText, '###')!=FALSE)
            $returnValue="error";
    elseif(strpos($mpdreportText, 'Warning')!=FALSE)
             $returnValue="warning";
    
    $mpd_xml = simplexml_load_file($session_dir . '/' . $mpd_xml_report);
    $mpd_xml->hbbtv_dvb = $returnValue;
    $mpd_xml->asXml($session_dir . '/' . $mpd_xml_report);
    
    return $returnValue;
}

function DVB_HbbTV_profile_specific_media_types_report($mpdreport){
    global $mpd_dom, $dvb_conformance, $hbbtv_conformance;
    
    $mpd_profiles = $mpd_dom->getAttribute('profiles');
    
    if($dvb_conformance && (strpos($mpd_dom->getAttribute('profiles'), 'urn:dvb:dash:profile:dvb-dash:2014') === FALSE && strpos($mpd_dom->getAttribute('profiles'), 'urn:dvb:dash:profile:dvb-dash:isoff-ext-live:2014') === FALSE && strpos($mpd_dom->getAttribute('profiles'), 'urn:dvb:dash:profile:dvb-dash:isoff-ext-on-demand:2014') === FALSE))
        $mpd_profiles .= ',urn:dvb:dash:profile:dvb-dash:2014';
    if($hbbtv_conformance && strpos($mpd_dom->getAttribute('profiles'), 'urn:hbbtv:dash:profile:isoff-live:2012') === FALSE)
        $mpd_profiles .= ',urn:hbbtv:dash:profile:isoff-live:2012';
    
    $profiles_arr = explode(',', $mpd_profiles);
    if(sizeof($profiles_arr) > 1){
        ## Generate the profile-specific MPDs
        foreach($profiles_arr as $profile){
            $domDocument = new DOMDocument('1.0');
            $domElement = $domDocument->createElement('MPD');
            $domElement = $mpd_dom->cloneNode();
    
            $domElement->setAttribute('profiles', $profile);
            $domElement = recursive_generate($mpd_dom, $domDocument, $domElement, $profile);
            $domDocument->appendChild($domDocument->importNode($domElement, true));
            
            $profile_specific_MPDs[] = $domDocument;
        }
        
        ## Compare each profile-specific MPD with the original MPD 
        $mpd_media_types = media_types($mpd_dom);
        $ind = 0;
        foreach($profile_specific_MPDs as $profile_specific_MPD){
            $mpd_media_types_new = media_types($profile_specific_MPD->getElementsByTagName('MPD')->item(0));
            
            $str = '';
            foreach($mpd_media_types as $mpd_media_type){
                if(!in_array($mpd_media_type, $mpd_media_types_new))
                    $str = $str . " $mpd_media_type"; 
            }
            if($str != '')
                fwrite($mpdreport, "###HbbTV-DVB DASH Validation Requirements Conformance violated: Section 'MPD' - media type:$str is missing after the provided MPD is processed for profile: " . $profiles_arr[$ind] . ".\n");
            
            $ind++;
        }
    }
}

function recursive_generate($node, &$domDocument, &$domElement, $profile){
    foreach($node->childNodes as $child){
        if($child->nodeType == XML_ELEMENT_NODE){
            if($child->getAttribute('profiles') == '' || strpos($child->getAttribute('profiles'), $profile) !== FALSE){
                $domchild = $domDocument->createElement($child->nodeName);
                $domchild = $child->cloneNode();
                
                $domchild = recursive_generate($child, $domDocument, $domchild, $profile);
                $domElement->appendChild($domchild);
            }
        }
    }
    
    return $domElement;
}

function media_types($MPD){
    $media_types = array();
    
    $adapts = $MPD->getElementsByTagName('AdaptationSet');
    $reps = $MPD->getElementsByTagName('Representation');
    $subreps = $MPD->getElementsByTagName('SubRepresentation');
    
    if($adapts->length != 0){
        for($i=0; $i<$adapts->length; $i++){
            $adapt = $adapts->item($i);
            $adapt_contentType = $adapt->getAttribute('contentType');
            $adapt_mimeType = $adapt->getAttribute('mimeType');
            
            if($adapt_contentType == 'video' || strpos($adapt_mimeType, 'video') !== FALSE){
                $media_types[] = 'video';
            }
            if($adapt_contentType == 'audio' || strpos($adapt_mimeType, 'audio') !== FALSE){
                $media_types[] = 'audio';
            }
            if($adapt_contentType == 'text' || strpos($adapt_mimeType, 'application') !== FALSE){
                $media_types[] = 'subtitle';
            }
            
            $contentcomps = $adapt->getElementsByTagName('ContentComponent');
            foreach($contentcomps as $contentcomp){
                $contentcomp_contentType = $contentcomp->getAttribute('contentType');
                
                if($contentcomp_contentType == 'video'){
                    $media_types[] = 'video';
                }
                if($contentcomp_contentType == 'audio'){
                    $media_types[] = 'audio';
                }
                if($contentcomp_contentType == 'text'){
                    $media_types[] = 'subtitle';
                }
            }
        }
    }
    
    if($reps->length != 0){
        for($i=0; $i<$reps->length; $i++){
            $rep = $reps->item($i);
            $rep_mimeType = $rep->getAttribute('mimeType');
            
            if(strpos($rep_mimeType, 'video') !== FALSE){
                $media_types[] = 'video';
            }
            if(strpos($rep_mimeType, 'audio') !== FALSE){
                $media_types[] = 'audio';
            }
            if(strpos($rep_mimeType, 'application') !== FALSE){
                $media_types[] = 'subtitle';
            }
        }
    }
    
    if($subreps->length != 0){
        for($i=0; $i<$subreps->length; $i++){
            $subrep = $subreps->item($i);
            $subrep_mimeType = $subrep->getAttribute('mimeType');
            
            if(strpos($subrep_mimeType, 'video') !== FALSE){
                $media_types[] = 'video';
            }
            if(strpos($subrep_mimeType, 'audio') !== FALSE){
                $media_types[] = 'audio';
            }
            if(strpos($subrep_mimeType, 'application') !== FALSE){
                $media_types[] = 'subtitle';
            }
        }
    }
    
    return array_unique($media_types);
}

function DVB_HbbTV_cross_profile_check($mpdreport){
    global $mpd_dom;
    $profiles = $mpd_dom->getAttribute('profiles');
    
    $supported_profiles = array('urn:mpeg:dash:profile:isoff-on-demand:2011', 'urn:mpeg:dash:profile:isoff-live:2011', 
                                'urn:mpeg:dash:profile:isoff-main:2011', 'http://dashif.org/guidelines/dash264', 
                                'urn:dvb:dash:profile:dvb-dash:2014', 'urn:hbbtv:dash:profile:isoff-live:2012',
                                'urn:dvb:dash:profile:dvb-dash:isoff-ext-live:2014', 'urn:dvb:dash:profile:dvb-dash:isoff-ext-on-demand:2014');
    
    $profiles_arr = explode(',', $profiles);
    foreach($profiles_arr as $profile){
        $profile_found = false;
        foreach($supported_profiles as $supported_profile){
            if(strpos($profile, $supported_profile) !== FALSE)
                $profile_found = true;
        }
        
        if(!$profile_found)
            fwrite($mpdreport, "Information on HbbTV-DVB DASH Validation Requirements conformance: Section 'MPD' - MPD element is scoped by the profile \"$profile\" that the tool is not validating against.\n");
    }
}

# // Previous MPD check (6) where the elements that are not used in MPD-level HbbTV profile validation  
#function DVB_HbbTV_cross_profile_check($dom, $mpdreport){
#    // All the elements here for cross-profile checks exist in DVB but not in HbbTV
#    $MPD = $dom->getElementsByTagName('MPD')->item(0);
#    
#    $BaseURLs = $MPD->getElementsByTagName('BaseURL');
#    if($BaseURLs->length != 0)
#        fwrite($mpdreport, "Information on DVB-HbbTV conformance: BaseURL element is found in the MPD. This element is scoped by DVB profile that the tool is not validating against.\n");
#    
#    if($MPD->getAttribute('type') == 'dynamic' || $MPD->getAttribute('availabilityStartTime') != ''){
#        $UTCTimings = $MPD->getElementsByTagName('UTCTiming');
#        if($UTCTimings->length != 0)
#            fwrite($mpdreport, "Information on DVB-HbbTV conformance: UTCTiming element is found in the MPD. This element is scoped by DVB profile that the tool is not validating against.\n");
#    }
#    
#    $periods = $MPD->getElementsByTagName('Period');
#    foreach($periods as $period){
#        foreach($period->childNodes as $child){
#            if($child->nodeName == 'EventStream'){
#                fwrite($mpdreport, "Information on DVB-HbbTV conformance: EventStream element is found in the MPD. This element is scoped by DVB profile that the tool is not validating against.\n");
#                
#                foreach($child->childNodes as $ch){
#                    if($ch->nodeName == 'Event')
#                        fwrite($mpdreport, "Information on DVB-HbbTV conformance: Event element is found in the MPD. This element is scoped by DVB profile that the tool is not validating against.\n");
#                }
#            }
#        }
#    }
#}

function DVB_mpdvalidator($mpdreport){
    global $adapt_video_count, $adapt_audio_count, $main_audio_found, $main_audios, $hoh_subtitle_lang, $period_count, 
            $audio_bw, $video_bw, $subtitle_bw, $supported_profiles, $mpd_dom, $mpd_url;
    global $onRequest_array, $xlink_not_valid_array;
    
    if(!empty($onRequest_array)){
        $onRequest_k_v  = implode(', ', array_map(
        function ($v, $k) { return sprintf(" %s with index (starting from 0) '%s'", $v, $k); },
        $onRequest_array,array_keys($onRequest_array)));
        fwrite($mpdreport, "###'HbbTV-DVB DASH Validation Requirements check violated for DVB: Section 'xlink' - MPD SHALL NOT have xlink:actuate set to onRequest', found in".$onRequest_k_v."\n"); 
    } 
    
    if(!empty($xlink_not_valid_array)){
        $xlink_not_valid_k_v  = implode(', ', array_map(
        function ($v, $k) { return sprintf(" %s with index (starting from 0) '%s'", $v, $k); },
        $xlink_not_valid_array,array_keys($xlink_not_valid_array)));
        fwrite($mpdreport, "###'HbbTV-DVB DASH Validation Requirements check violated for DVB: Section 'xlink' - MPD invalid xlink:href', found in:".$xlink_not_valid_k_v."\n"); 
    }
    TLS_bitrate_check($mpdreport);
    
    $mpd_doc = get_doc($mpd_url);
    $mpd_string = $mpd_doc->saveXML();
    $mpd_bytes = strlen($mpd_string);
    if($mpd_bytes > 256*1024){
        fwrite($mpdreport, "###'DVB check violated: Section 4.5- The MPD size after xlink resolution SHALL NOT exceed 256 Kbytes', found " . ($mpd_bytes/1024) . " Kbytes.\n");
    }
    
    ## Warn on low values of MPD@minimumUpdatePeriod (for now the lowest possible value is assumed to be 1 second)
    if($mpd_dom->getAttribute('minimumUpdatePeriod') != ''){
        $mup = time_parsing($mpd_dom->getAttribute('minimumUpdatePeriod'));
        if($mup < 1)
            fwrite($mpdreport, "Warning for HbbTV-DVB DASH Validation Requirements check for DVB: Section 'MPD' - 'MPD@minimumUpdatePeriod has a lower value than 1 second.\n");
    }
    ##
    
    ## Information from this part is used for Section 4.1 and 11.1 checks
    $profiles = $mpd_dom->getAttribute('profiles');
    if(strpos($profiles, 'urn:dvb:dash:profile:dvb-dash:2014') === FALSE && strpos($profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') === FALSE)
        fwrite($mpdreport, "###'DVB check violated: Section E.2.1- The MPD SHALL indicate either or both of the following profiles: \"urn:dvb:dash:profile:dvb-dash:2014\" and \"urn:hbbtv:dash:profile:isoff-live:2012\"', specified profile could not be found.\n");
    
    $profile_exists = false;
    if(strpos($profiles, 'urn:dvb:dash:profile:dvb-dash:2014') === FALSE && (strpos($profiles, 'urn:dvb:dash:profile:dvb-dash:isoff-ext-live:2014') === FALSE || strpos($profiles, 'urn:dvb:dash:profile:dvb-dash:isoff-ext-on-demand:2014') === FALSE))
        fwrite($mpdreport, "Warning for DVB check: Section 11.1- 'All Representations that are intended to be decoded and presented by a DVB conformant Player SHOULD be such that they will be inferred to have an @profiles attribute that includes the profile name defined in clause 4.1 as well as either the one defined in 4.2.5 or the one defined in 4.2.8', found profiles: $profiles.\n");
    elseif(strpos($profiles, 'urn:dvb:dash:profile:dvb-dash:2014') === TRUE && (strpos($profiles, 'urn:dvb:dash:profile:dvb-dash:isoff-ext-live:2014') === TRUE || strpos($profiles, 'urn:dvb:dash:profile:dvb-dash:isoff-ext-on-demand:2014') === TRUE))
        $profile_exists = true;
    ##
    
    ## Information from this part is used for Section 11.9.5: relative url warning
    $BaseURLs = $mpd_dom->getElementsByTagName('BaseURL');
    foreach ($BaseURLs as $BaseURL){
        if(!isAbsoluteURL($BaseURL->nodeValue)){
            if($BaseURL->getAttribute('serviceLocation') != '' && $BaseURL->getAttribute('priority') != '' && $BaseURL->getAttribute('weight') != '')
                fwrite($mpdreport, "Warning for DVB check: Section 11.9.5- 'Where BaseURLs contain relative URLs, these SHOULD NOT include @serviceLocation, @priority or @weight attributes', however found in this MPD.\n");
        }
    }
    ##
    
    ## Verifying the DVB Metric reporting mechanism according to Section 10.12.3
    $metrics = $mpd_dom->getElementsByTagName('Metrics');
    if($metrics->length != 0){
        foreach($metrics as $metric){
            $reportings = $metric->getElementsByTagName('Reporting');
            if($reportings->length != 0){
                foreach($reportings as $reporting){
                    if($reporting->getAttribute('schemeIdUri') == 'urn:dvb:dash:reporting:2014' && $reporting->getAttribute('value') == 1){
                        if($reporting->getAttribute('reportingUrl') == '' && $reporting->getAttribute('dvb:reportingUrl') == '')
                            fwrite($mpdreport, "Information on DVB conformance: Section 10.12.3 - Where DVB Metric reporting mechanism is indicated in a Reporting descriptor, it SHALL have the @reportingUrl attribute.\n");
                        else{
                            if(!isAbsoluteURL($reporting->getAttribute('reportingUrl')) && !isAbsoluteURL($reporting->getAttribute('dvb:reportingUrl')))
                                fwrite($mpdreport, "Information on DVB conformance: Section 10.12.3 - value of the @reportingUrl attribute in the Reporting descriptor needs to be and absolute HTTP or HTTPS URL.\n");
                        }
                        
                        if($reporting->getAttribute('probability') != ''){
                            $probability = $reporting->getAttribute('probability');
                            if(!(((string) (int) $probability === $probability) && ($probability <= 1000) && ($probability >= 1)))
                                fwrite($mpdreport, "Information on DVB conformance: Section 10.12.3 - value of the @probability attribute in the Reporting descriptor needs to be a positive integer between 0 and 1000.\n");
                        }
                        if($reporting->getAttribute('dvb:probability') != ''){
                            $probability = $reporting->getAttribute('dvb:probability');
                            if(!(((string) (int) $probability === $probability) && ($probability <= 1000) && ($probability >= 1)))
                                fwrite($mpdreport, "Information on DVB conformance: Section 10.12.3 - value of the @probability attribute in the Reporting descriptor needs to be a positive integer between 0 and 1000.\n");
                        }
                    }
                }
            }
        }
    }
    ##
    
    $cenc = $mpd_dom->getAttribute('xmlns:cenc');
    
    // Periods within MPD
    $period_count = 0;
    $video_service = false;
    $type = $mpd_dom->getAttribute('type');
    $AST = $mpd_dom->getAttribute('availabilityStartTime');
    
    if($type == 'dynamic' || $AST != ''){
        $UTCTimings = $mpd_dom->getElementsByTagName('UTCTiming');
        $acceptedTimingURIs = array('urn:mpeg:dash:utc:ntp:2014', 
                                    'urn:mpeg:dash:utc:http-head:2014', 
                                    'urn:mpeg:dash:utc:http-xsdate:2014',
                                    'urn:mpeg:dash:utc:http-iso:2014',
                                    'urn:mpeg:dash:utc:http-ntp:2014');
        $utc_info = '';
        
        if($UTCTimings->length == 0)
            fwrite($mpdreport, "Warning for DVB check: Section 4.7.2- 'If the MPD is dynamic or if the MPD@availabilityStartTime is present then the MPD SHOULD countain at least one UTCTiming element with the @schemeIdUri attribute set to one of the following: ".join(', ', $acceptedTimingURIs)." ', UTCTiming element could not be found in the provided MPD.\n");
        else{
            foreach($UTCTimings as $UTCTiming){
                if(!(in_array($UTCTiming->getAttribute('schemeIdUri'), $acceptedTimingURIs)))
                    $utc_info .= 'wrong ';
            }
            
            if($utc_info != '')
                fwrite($mpdreport, "Warning for DVB check: Section 4.7.2- 'If the MPD is dynamic or if the MPD@availabilityStartTime is present then the MPD SHOULD countain at least one UTCTiming element with the @schemeIdUri attribute set to one of the following: ".join(', ', $acceptedTimingURIs)." ', could not be found in the provided MPD.\n");
        }
    }
    
    foreach($mpd_dom->childNodes as $node){
        if($node->nodeName == 'Period'){
            $period_count++;
            $adapt_video_count = 0; 
            $main_video_found = false;
            $main_audio_found = false;
            
            foreach ($node->childNodes as $child){
                if($child->nodeName == 'SegmentList')
                    fwrite($mpdreport, "###'DVB check violated: Section 4.2.2- The Period.SegmentList SHALL not be present', but found in Period $period_count.\n");
                
                if($child->nodeName == 'EventStream'){
                    DVB_event_checks($child, $mpdreport);
                }
                if($child->nodename == 'SegmentTemplate'){
                    if(strpos($profiles, 'urn:dvb:dash:profile:dvb-dash:isoff-ext-on-demand:2014') === TRUE)
                        fwrite($mpdreport, "###'DVB check violated: Section 4.2.6- The Period.SegmentTemplate SHALL not be present for Period elements conforming to On Demand profile', but found in Period $period_count.\n");
                }
            }
            
            // Adaptation Sets within each Period
            $adapts = $node->getElementsByTagName('AdaptationSet');
            $adapts_len = $adapts->length;
            
            if($adapts_len > 16)
                fwrite($mpdreport, "###'DVB check violated: Section 4.5- The MPD has a maximum of 16 adaptation sets per period', found $adapts_len in Period $period_count.\n");
            
            $audio_adapts = array();
            for($i=0; $i<$adapts_len; $i++){
                $adapt = $adapts->item($i);
                $video_found = false;
                $audio_found = false;
                
                $adapt_profile_exists = false;
                $adapt_profiles = $adapt->getAttribute('profiles');
                if($profile_exists && $adapt_profiles != ''){
                    if(strpos($adapt_profiles, 'urn:dvb:dash:profile:dvb-dash:2014') === FALSE && (strpos($adapt_profiles, 'urn:dvb:dash:profile:dvb-dash:isoff-ext-live:2014') === FALSE || strpos($adapt_profiles, 'urn:dvb:dash:profile:dvb-dash:isoff-ext-on-demand:2014') === FALSE))
                        fwrite($mpdreport, "Warning for DVB check: Section 11.1- 'All Representations that are intended to be decoded and presented by a DVB conformant Player SHOULD be such that they will be inferred to have an @profiles attribute that includes the profile name defined in clause 4.1 as well as either the one defined in 4.2.5 or the one defined in 4.2.8', found profiles: $adapt_profiles.\n");
                    else
                        $adapt_profile_exists = true;
                }
                
                $reps = $adapt->getElementsByTagName('Representation');
                $reps_len = $reps->length;
                if($reps_len > 16)
                    fwrite($mpdreport, "###'DVB check violated: Section 4.5- The MPD has a maximum of 16 representations per adaptation set', found $reps_len in Period $period_count Adaptation Set " . ($i+1) . ".\n");
                
                $contentTemp_vid_found = false;
                $contentTemp_aud_found = false;
                foreach ($adapt->childNodes as $ch){
                    if($ch->nodeName == 'ContentComponent'){
                        if($ch->getAttribute('contentType') == 'video')
                            $contentTemp_vid_found = true;
                        if($ch->getAttribute('contentType') == 'audio')
                            $contentTemp_aud_found = true;
                    }
                    if($ch->nodeName == 'Representation'){
                        if($profile_exists && ($adapt_profiles == '' || $adapt_profile_exists)){
                            $rep_profile_exists = false;
                            $rep_profiles = $ch->getAttribute('profiles');
                            if($rep_profiles != ''){
                                if(strpos($rep_profiles, 'urn:dvb:dash:profile:dvb-dash:2014') === FALSE && (strpos($rep_profiles, 'urn:dvb:dash:profile:dvb-dash:isoff-ext-live:2014') === FALSE || strpos($rep_profiles, 'urn:dvb:dash:profile:dvb-dash:isoff-ext-on-demand:2014') === FALSE))
                                    fwrite($mpdreport, "Warning for DVB check: Section 11.1- 'All Representations that are intended to be decoded and presented by a DVB conformant Player SHOULD be such that they will be inferred to have an @profiles attribute that includes the profile name defined in clause 4.1 as well as either the one defined in 4.2.5 or the one defined in 4.2.8', found profiles: $rep_profiles.\n");
                                else
                                    $rep_profile_exists = true;
                            }
                        }
                        if(strpos($ch->getAttribute('mimeType'), 'video') !== FALSE)
                            $video_found = true;
                        if(strpos($ch->getAttribute('mimeType'), 'audio') !== FALSE)
                            $audio_found = true;
                        
                        if($profile_exists && ($adapt_profiles == '' || $adapt_profile_exists) && ($rep_profiles == '' || $rep_profile_exists)){
                            foreach($ch->childNodes as $c){
                                if($c->nodeName == 'SubRepresentation'){
                                    $subrep_profiles = $c->getAttribute('profiles');
                                    if($subrep_profiles != ''){
                                        if(strpos($subrep_profiles, 'urn:dvb:dash:profile:dvb-dash:2014') === FALSE && (strpos($subrep_profiles, 'urn:dvb:dash:profile:dvb-dash:isoff-ext-live:2014') === FALSE || strpos($subrep_profiles, 'urn:dvb:dash:profile:dvb-dash:isoff-ext-on-demand:2014') === FALSE))
                                            fwrite($mpdreport, "Warning for DVB check: Section 11.1- 'All Representations that are intended to be decoded and presented by a DVB conformant Player SHOULD be such that they will be inferred to have an @profiles attribute that includes the profile name defined in clause 4.1 as well as either the one defined in 4.2.5 or the one defined in 4.2.8', found profiles: $subrep_profiles.\n");
                                    }
                                }
                            }
                        }
                    }
                }
                
                if($adapt->getAttribute('contentType') == 'video' || $contentTemp_vid_found || $video_found || strpos($adapt->getAttribute('mimeType'), 'video') !== FALSE){
                    $video_service = true;
                    DVB_video_checks($adapt, $reps, $mpdreport, $i, $contentTemp_vid_found);
                    
                    if($contentTemp_aud_found){
                        DVB_audio_checks($adapt, $reps, $mpdreport, $i, $contentTemp_aud_found);
                    }
                }
                elseif($adapt->getAttribute('contentType') == 'audio' || $contentTemp_aud_found || $audio_found || strpos($adapt->getAttribute('mimeType'), 'audio') !== FALSE){
                    DVB_audio_checks($adapt, $reps, $mpdreport, $i, $contentTemp_aud_found);
                    
                    if($contentTemp_vid_found){
                        DVB_video_checks($adapt, $reps, $mpdreport, $i, $contentTemp_vid_found);
                    }
                    
                    $audio_adapts[] = $adapt;
                }
                else{
                    DVB_subtitle_checks($adapt, $reps, $mpdreport, $i);
                }
                
                if($adapt_video_count > 1 && $main_video_found == false)
                    fwrite($mpdreport, "###'DVB check violated: Section 4.2.2- If a Period element contains multiple Adaptation Sets with @contentType=\"video\" then at least one Adaptation Set SHALL contain a Role element with @schemeIdUri=\"urn:mpeg:dash:role:2011\" and @value=\"main\"', could not be found in Period $period_count.\n");
                
                DVB_content_protection($adapt, $reps, $mpdreport, $i, $cenc);
            }
            
            if($video_service){
                StreamBandwidthCheck($mpdreport);
            }
            
            ## Section 6.6.3 - Check for Audio Fallback Operation
            if(!empty($audio_adapts) && sizeof($audio_adapts) > 1){
                FallbackOperationCheck($audio_adapts, $mpdreport);
            }
            ##
            
            ## Section 7.1.2 Table 11 - First Row "Hard of Hearing"
            if($main_audio_found){
                if(!empty($hoh_subtitle_lang)){
                    $main_lang = array();
                    foreach($main_audios as $main_audio){
                        if($main_audio->getAttribute('lang') != '')
                            $main_lang[] = $main_audio->getAttribute('lang');
                    }
                    
                    foreach($hoh_subtitle_lang as $hoh_lang){
                        if(!empty($main_lang)){
                            if(!in_array($hoh_lang, $main_lang))
                                fwrite($mpdreport, "###'DVB check violated: Section 7.1.2- According to Table 11, when hard of hearing subtitle type is signalled the @lang attribute of the subtitle representation SHALL be the same as the main audio for the programme', @lang attributes do not match in Period $period_count.\n");
                        }
                    }
                }
            }
            ##
        }
        
        if($period_count > 64)
            fwrite($mpdreport, "###'DVB check violated: Section 4.5- The MPD has a maximum of 64 periods after xlink resolution', found $period_count.\n");
    }
    
    DVB_associated_adaptation_sets_check($mpdreport);
    
    if($adapt_audio_count > 1 && $main_audio_found == false)
        fwrite($mpdreport, "###'DVB check violated: Section 6.1.2- If there is more than one audio Adaptation Set in a DASH Presentation then at least one of them SHALL be tagged with an @value set to \"main\"', could not be found in Period $period_count.\n");
    
}

function FallbackOperationCheck($audio_adapts, $mpdreport){
    global $period_count;
    $len = sizeof($audio_adapts);
    for($i=0; $i<$len; $i++){
        $audio_adapt_i = $audio_adapts[$i];
        $supps_i = $audio_adapt_i->getElementsByTagName('SupplementalProperty');
        
        $value = '';
        foreach($supps_i as $supp_i){
            if($supp_i->getAttribute('schemeIdUri') == 'urn:dvb:dash:fallback_adaptation_set:2014'){
                $value = $supp_i->getAttribute('value');
            }
        }
        
        if($value != ''){
            $string_info = '';
            for($j=0; $j<$len; $j++){
                if($j != $i){
                    $audio_adapt_j = $audio_adapts[$j];
                    $id = $audio_adapt_j->getAttribute('id');
                    
                    if($value == $id)
                        $string_info .= 'yes ';
                }
            }
            
            if($string_info == '')
                fwrite($mpdreport, "###'DVB check violated: Section 6.6.3- The (SupplementalProperty) descriptor SHALL have the @schemeIdUri attibute set to \"urn:dvb:dash:fallback_adaptation_set:2014\" and the @value attribute equal to the @id attribute of the Adaptation Set for which it supports the falling back operation', fallback operation is signalled via SupplementalProperty but the value does not match with any audio Adaptation Set @id in Period $period_count.\n");
            else{
                $role_i = $audio_adapt_i->getElementsByTagName('Role')->item(0);
                $role_j = $audio_adapt_j->getElementsByTagName('Role')->item(0);
                
                if($role_i->getAttribute('schemeIdUri') != $role_j->getAttribute('schemeIdUri') || $role_i->getAttribute('value') != $role_j->getAttribute('value'))
                    fwrite($mpdreport, "###'DVB check violated: Section 6.6.3- An additional low bit rate fallback Adaptation Set SHALL also be tagged with the same role as the Adaptation Set which it provides the fallback option for', roles are not the same in Period $period_count.\n");
            }
        }
    }
}

function DVB_associated_adaptation_sets_check($mpdreport){
    global $mpd_dom;
    $periods = $mpd_dom->getElementsByTagName('Period');
    $period_cnt = $periods->length;
    
    for($i=0; $i<$period_cnt; $i++){
        $period1 = $periods->item($i);
        $assets1 = $period1->getElementsByTagName('AssetIdentifier');
        
        if($assets1->length != 0){
            for($j=$i+1; $j<$period_cnt; $j++){
                $period2 = $periods->item($j);
                $assets2 = $period2->getElementsByTagName('AssetIdentifier');
                
                if($assets2->length != 0){
                    $assetCheck = checkAssetIdentifiers($assets1, $assets2);
                    if($assetCheck === TRUE){
                        checkAdaptationSetsIds($period1->getElementsByTagName('AdaptationSet'), $period2->getElementsByTagName('AdaptationSet'), $i, $j, $mpdreport);
                    }
                }
            }
        }
    }
}

function checkAssetIdentifiers($assets1, $assets2){
    $return = FALSE;
    $len1 = $assets1->length;
    $len2 = $assets2->length;
    
    for($i=0; $i<$len1; $i++){
        $asset1 = $assets1->item($i);
        
        for($j=0; $j<$len2; $j++){
            $asset2 = $assets2->item($j);
            
            if(nodes_equal($asset1, $asset2)){
                return TRUE;
            }
        }
    }
    
    return $return;
}

function checkAdaptationSetsIds($adapts1, $adapts2, $periodId1, $periodId2, $mpdreport){
    global $associativity;
    $len1 = $adapts1->length;
    $len2 = $adapts2->length;
    $associativity_local = array();
    
    for($i=0; $i<$len1; $i++){
        $adapt1 = $adapts1->item($i);
        $id1 = $adapt1->getAttribute('id');
        
        for($j=0; $j<$len2; $j++){
            $adapt2 = $adapts2->item($j);
            $id2 = $adapt2->getAttribute('id');
            
            $is_associative = true;
            if($id1 != '' && $id2 != '' && $id1 == $id2){
                # Section 10.5.2.2 - Check the DVB requirements for Associated Adaptation Sets
                // @lang
                $lang1 = $adapt1->getAttribute('lang');
                $lang2 = $adapt2->getAttribute('lang');
                if($lang1 != '' && $lang2 != '' && $lang1 != $lang2){
                    fwrite($mpdreport, "###'DVB check violated: Section 10.5.2.2- If Adaptation Sets in two different Periods are associated, then the language as decribed by the @lang attribute SHALL be identical for the two Adaptation Sets', not identical for Adaptation Set " . ($i+1) . " in Period " . ($periodId1+1) . " and Adaptation Set " . ($j+1) . " in Period " . ($periodId2+1) . ".\n");
                    $is_associative = false;
                }
                
                // @contentType
                $contentType1 = $adapt1->getAttribute('contentType');
                $contentType2 = $adapt2->getAttribute('contentType');
                if($contentType1 != '' && $contentType2 != '' && $contentType1 != $contentType2){
                    fwrite($mpdreport, "###'DVB check violated: Section 10.5.2.2- If Adaptation Sets in two different Periods are associated, then the media content component type decribed by the @contentType attribute SHALL be identical for the two Adaptation Sets', not identical for Adaptation Set " . ($i+1) . " in Period " . ($periodId1+1) . " and Adaptation Set " . ($j+1) . " in Period " . ($periodId2+1) . ".\n");
                    $is_associative = false;
                }
                
                // @par
                $par1 = $adapt1->getAttribute('par');
                $par2 = $adapt2->getAttribute('par');
                if($par1 != '' && $par2 != '' && $par1 != $par2){
                    fwrite($mpdreport, "###'DVB check violated: Section 10.5.2.2- If Adaptation Sets in two different Periods are associated, then the picture aspect ratio decribed by the @par attribute SHALL be identical for the two Adaptation Sets', not identical for Adaptation Set " . ($i+1) . " in Period " . ($periodId1+1) . " and Adaptation Set " . ($j+1) . " in Period " . ($periodId2+1) . ".\n");
                    $is_associative = false;
                }
                
                // Role
                $roles1 = $adapt1->getElementsByTagName('Role');
                $roles2 = $adapt2->getElementsByTagName('Role');
                $roles1_cnt = $roles1->length;
                $roles2_cnt = $roles2->length;
                if($roles1_cnt != $roles2_cnt){
                    fwrite($mpdreport, "###'DVB check violated: Section 10.5.2.2- If Adaptation Sets in two different Periods are associated, then any role properties as decribed by the Role elements SHALL be identical for the two Adaptation Sets', not identical number of Role elements found for Adaptation Set " . ($i+1) . " in Period " . ($periodId1+1) . " and Adaptation Set " . ($j+1) . " in Period " . ($periodId2+1) . ".\n");
                    $is_associative = false;
                }
                else{
                    for($r=0; $r<$roles1_cnt; $r++){
                        if(!nodes_equal($roles1->item($r), $roles2->item($r))){
                            fwrite($mpdreport, "###'DVB check violated: Section 10.5.2.2- If Adaptation Sets in two different Periods are associated, then any role properties as decribed by the Role element SHALL be identical for the two Adaptation Sets', not identical for Adaptation Set " . ($i+1) . " in Period " . ($periodId1+1) . " and Adaptation Set " . ($j+1) . " in Period " . ($periodId2+1) . ".\n");
                            $is_associative = false;
                        }
                    }
                }
                
                // Accessibility
                $accessibility1 = $adapt1->getElementsByTagName('Accessibility');
                $accessibility2 = $adapt2->getElementsByTagName('Accessibility');
                $accessibility1_cnt = $accessibility1->length;
                $accessibility2_cnt = $accessibility2->length;
                if($accessibility1_cnt != $accessibility2_cnt){
                    fwrite($mpdreport, "###'DVB check violated: Section 10.5.2.2- If Adaptation Sets in two different Periods are associated, then any accessibility properties as decribed by the Role elements SHALL be identical for the two Adaptation Sets', not identical number of Role elements found for Adaptation Set " . ($i+1) . " in Period " . ($periodId1+1) . " and Adaptation Set " . ($j+1) . " in Period " . ($periodId2+1) . ".\n");
                    $is_associative = false;
                }
                else{
                    for($a=0; $a<$accessibility1_cnt; $a++){
                        if(!nodes_equal($accessibility1->item($a), $accessibility2->item($a))){
                            fwrite($mpdreport, "###'DVB check violated: Section 10.5.2.2- If Adaptation Sets in two different Periods are associated, then any accessibility properties as decribed by the Role element SHALL be identical for the two Adaptation Sets', not identical for Adaptation Set " . ($i+1) . " in Period " . ($periodId1+1) . " and Adaptation Set " . ($j+1) . " in Period " . ($periodId2+1) . ".\n");
                            $is_associative = false;
                        }
                    }
                }
                
                // Viewpoint
                $viewpoint1 = $adapt1->getElementsByTagName('Viewpoint');
                $viewpoint2 = $adapt2->getElementsByTagName('Viewpoint');
                $viewpoint1_cnt = $viewpoint1->length;
                $viewpoint2_cnt = $viewpoint2->length;
                if($viewpoint1_cnt != $viewpoint2_cnt){
                    fwrite($mpdreport, "###'DVB check violated: Section 10.5.2.2- If Adaptation Sets in two different Periods are associated, then any viewpoint properties as decribed by the Role elements SHALL be identical for the two Adaptation Sets', not identical number of Role elements found for Adaptation Set " . ($i+1) . " in Period " . ($periodId1+1) . " and Adaptation Set " . ($j+1) . " in Period " . ($periodId2+1) . ".\n");
                    $is_associative = false;
                }
                else{
                    for($v=0; $v<$viewpoint1_cnt; $v++){
                        if(!nodes_equal($viewpoint1->item($v), $viewpoint2->item($v))){
                            fwrite($mpdreport, "###'DVB check violated: Section 10.5.2.2- If Adaptation Sets in two different Periods are associated, then any viewpoint properties as decribed by the Role element SHALL be identical for the two Adaptation Sets', not identical for Adaptation Set " . ($i+1) . " in Period " . ($periodId1+1) . " and Adaptation Set " . ($j+1) . " in Period " . ($periodId2+1) . ".\n");
                            $is_associative = false;
                        }
                    }
                }
                
                // Table 3 for Audio Adaptation Sets
                $mimeType1 = $adapt1->getAttribute('mimeType');
                $mimeType2 = $adapt2->getAttribute('mimeType');
                $isaudio = ((strpos($mimeType1, 'audio') !== FALSE) | $contentType1 == 'audio') & ((strpos($mimeType2, 'audio') !== FALSE) | $contentType2 == 'audio');
                if($mimeType1 == '' || $mimeType2 == ''){
                    $reps1 = $adapt1->getElementsByTagName('Representation');
                    $reps2 = $adapt1->getElementsByTagName('Representation');
                    if($reps1->length == $reps2->length){
                        for($r=0; $r<$reps1->length; $r++){
                            $rep1 = $reps1->item($r);
                            $rep2 = $reps2->item($r);
                            $isaudio |= ((strpos($rep1->getAttribute('mimeType'), 'audio') !== FALSE) & (strpos($rep2->getAttribute('mimeType'), 'audio') !== FALSE));
                        }
                    }
                }
                
                if($isaudio){
                    // Adaptation Set level
                    $mimeTypeExists = 0;
                    $codecsExits = 0;
                    $audioSamplingRateExists = 0;
                    $audioChannelConfigurationExits = 0;
                    
                    // @mimeType
                    if($mimeType1 != '' && $mimeType2 != '' && $mimeType1 != $mimeType2){
                        $mimeTypeExists = 1;
                        fwrite($mpdreport, "###'DVB check violated: Section 10.5.2.2- If Adaptation Sets in two different Periods are associated, then for audio Adaptation Sets all values and presence of all attributes and elements listed in Table 3 SHALL be identical for the two Adaptation Sets', @mimeType is not identical for Adaptation Set " . ($i+1) . " in Period " . ($periodId1+1) . " and Adaptation Set " . ($j+1) . " in Period " . ($periodId2+1) . ".\n");
                        $is_associative = false;
                    }
                    
                    // @codecs
                    $codecs1 = $adapt1->getAttribute('codecs');
                    $codecs2 = $adapt2->getAttribute('codecs');
                    if($codecs1 != '' && $codecs2 != '' && $codecs1 != $codecs2){
                        $codecsExits = 1;
                        fwrite($mpdreport, "###'DVB check violated: Section 10.5.2.2- If Adaptation Sets in two different Periods are associated, then for audio Adaptation Sets all values and presence of all attributes and elements listed in Table 3 SHALL be identical for the two Adaptation Sets', @codecs is not identical for Adaptation Set " . ($i+1) . " in Period " . ($periodId1+1) . " and Adaptation Set " . ($j+1) . " in Period " . ($periodId2+1) . ".\n");
                        $is_associative = false;
                    }
                    
                    // @audioSamplingRate
                    $audioSamplingRate1 = $adapt1->getAttribute('audioSamplingRate');
                    $audioSamplingRate2 = $adapt2->getAttribute('audioSamplingRate');
                    if($audioSamplingRate1 != '' && $audioSamplingRate2 != '' && $audioSamplingRate1 != $audioSamplingRate2){
                        $audioSamplingRateExists = 1;
                        fwrite($mpdreport, "###'DVB check violated: Section 10.5.2.2- If Adaptation Sets in two different Periods are associated, then for audio Adaptation Sets all values and presence of all attributes and elements listed in Table 3 SHALL be identical for the two Adaptation Sets', @audioSamplingRate is not identical for Adaptation Set " . ($i+1) . " in Period " . ($periodId1+1) . " and Adaptation Set " . ($j+1) . " in Period " . ($periodId2+1) . ".\n");
                        $is_associative = false;
                    }
                    
                    // AudioChannelConfiguration
                    $audioChannelConfiguration1 = $adapt1->getElementsByTagName('AudioChannelConfiguration');
                    $audioChannelConfiguration2 = $adapt2->getElementsByTagName('AudioChannelConfiguration');
                    $audioChannelConfiguration1_cnt = $audioChannelConfiguration1->length;
                    $audioChannelConfiguration2_cnt = $audioChannelConfiguration2->length;
                    if($audioChannelConfiguration1_cnt != $audioChannelConfiguration2_cnt){
                        fwrite($mpdreport, "###'DVB check violated: Section 10.5.2.2- If Adaptation Sets in two different Periods are associated, then for audio Adaptation Sets all values and presence of all attributes and elements listed in Table 3 SHALL be identical for the two Adaptation Sets', not identical number of AudioChannelConfiguration elements found for Adaptation Set " . ($i+1) . " in Period " . ($periodId1+1) . " and Adaptation Set " . ($j+1) . " in Period " . ($periodId2+1) . ".\n");
                        $is_associative = false;
                    }
                    else{
                        for($a=0; $a<$audioChannelConfiguration1_cnt; $a++){
                            $audioChannelConfigurationExits = 1;
                            if(!nodes_equal($audioChannelConfiguration1->item($a), $audioChannelConfiguration2->item($a))){
                                fwrite($mpdreport, "###'DVB check violated: Section 10.5.2.2- If Adaptation Sets in two different Periods are associated, then for audio Adaptation Sets all values and presence of all attributes and elements listed in Table 3 SHALL be identical for the two Adaptation Sets', AudioChannelConfiguration is not identical for Adaptation Set " . ($i+1) . " in Period " . ($periodId1+1) . " and Adaptation Set " . ($j+1) . " in Period " . ($periodId2+1) . ".\n");
                                $is_associative = false;
                            }
                        }
                    }
                    
                    // Representation Set level
                    $allExitst = $mimeTypeExists & $codecsExits & $audioSamplingRateExists & $audioChannelConfigurationExits;
                    if(!$allExitst){
                        if($reps1->length != $reps2->length){
                            fwrite($mpdreport, "###'DVB check violated: Section 10.5.2.2- If Adaptation Sets in two different Periods are associated, then for audio Adaptation Sets all values and presence of all attributes and elements listed in Table 3 SHALL be identical for the two Adaptation Sets', not identical number of AudioChannelConfiguration elements found for Adaptation Set " . ($i+1) . " in Period " . ($periodId1+1) . " and Adaptation Set " . ($j+1) . " in Period " . ($periodId2+1) . ".\n");
                            $is_associative = false;
                        }
                        else{
                            for($r=0; $r<$reps1->length; $r++){
                                $rep1 = $reps1->item($r);
                                $rep2 = $reps2->item($r);
                                
                                // @mimeType
                                $mimeType1 = $rep1->getAttribute('mimeType');
                                $mimeType2 = $rep2->getAttribute('mimeType');
                                if(!$mimeTypeExists && $mimeType1 != '' && $mimeType2 != '' && $mimeType1 != $mimeType2){
                                    fwrite($mpdreport, "###'DVB check violated: Section 10.5.2.2- If Adaptation Sets in two different Periods are associated, then for audio Adaptation Sets all values and presence of all attributes and elements listed in Table 3 SHALL be identical for the two Adaptation Sets', @mimeType is not identical for Representation " . ($r+1) . " Adaptation Set " . ($i+1) . " in Period " . ($periodId1+1) . " and Representation " . ($r+1) . " Adaptation Set " . ($j+1) . " in Period " . ($periodId2+1) . ".\n");
                                    $is_associative = false;
                                }
                                
                                // @codecs
                                $codecs1 = $rep1->getAttribute('codecs');
                                $codecs2 = $rep2->getAttribute('codecs');
                                if(!$codecsExits && $codecs1 != '' && $codecs2 != '' && $codecs1 != $codecs2){
                                    fwrite($mpdreport, "###'DVB check violated: Section 10.5.2.2- If Adaptation Sets in two different Periods are associated, then for audio Adaptation Sets all values and presence of all attributes and elements listed in Table 3 SHALL be identical for the two Adaptation Sets', @codecs is not identical for Representation " . ($r+1) . " Adaptation Set " . ($i+1) . " in Period " . ($periodId1+1) . " and Representation " . ($r+1) . " Adaptation Set " . ($j+1) . " in Period " . ($periodId2+1) . ".\n");
                                    $is_associative = false;
                                }
                                
                                // @audioSamplingRate
                                $audioSamplingRate1 = $rep1->getAttribute('audioSamplingRate');
                                $audioSamplingRate2 = $rep2->getAttribute('audioSamplingRate');
                                if(!$audioSamplingRateExists && $audioSamplingRate1 != '' && $audioSamplingRate2 != '' && $audioSamplingRate1 != $audioSamplingRate2){
                                    fwrite($mpdreport, "###'DVB check violated: Section 10.5.2.2- If Adaptation Sets in two different Periods are associated, then for audio Adaptation Sets all values and presence of all attributes and elements listed in Table 3 SHALL be identical for the two Adaptation Sets', @audioSamplingRate is not identical for Representation " . ($r+1) . " Adaptation Set " . ($i+1) . " in Period " . ($periodId1+1) . " and Representation " . ($r+1) . " Adaptation Set " . ($j+1) . " in Period " . ($periodId2+1) . ".\n");
                                    $is_associative = false;
                                }
                                
                                // AudioChannelConfiguration
                                $audioChannelConfiguration1 = $rep1->getElementsByTagName('AudioChannelConfiguration');
                                $audioChannelConfiguration2 = $rep2->getElementsByTagName('AudioChannelConfiguration');
                                $audioChannelConfiguration1_cnt = $audioChannelConfiguration1->length;
                                $audioChannelConfiguration2_cnt = $audioChannelConfiguration2->length;
                                if(!$audioChannelConfigurationExits){
                                    if($audioChannelConfiguration1_cnt != $audioChannelConfiguration2_cnt){
                                        fwrite($mpdreport, "###'DVB check violated: Section 10.5.2.2- If Adaptation Sets in two different Periods are associated, then for audio Adaptation Sets all values and presence of all attributes and elements listed in Table 3 SHALL be identical for the two Adaptation Sets', not identical number of AudioChannelConfiguration elements found for Representation " . ($r+1) . " Adaptation Set " . ($i+1) . " in Period " . ($periodId1+1) . " and Representation " . ($r+1) . " Adaptation Set " . ($j+1) . " in Period " . ($periodId2+1) . ".\n");
                                        $is_associative = false;
                                    }
                                    else{
                                        for($a=0; $a<$audioChannelConfiguration1_cnt; $a++){
                                            if(!nodes_equal($audioChannelConfiguration1->item($a), $audioChannelConfiguration2->item($a))){
                                                fwrite($mpdreport, "###'DVB check violated: Section 10.5.2.2- If Adaptation Sets in two different Periods are associated, then for audio Adaptation Sets all values and presence of all attributes and elements listed in Table 3 SHALL be identical for the two Adaptation Sets', AudioChannelConfiguration is not identical for Representation " . ($r+1) . " Adaptation Set " . ($i+1) . " in Period " . ($periodId1+1) . " and Representation " . ($r+1) . " Adaptation Set " . ($j+1) . " in Period " . ($periodId2+1) . ".\n");
                                                $is_associative = false;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            else{
                $is_associative = false;
            }
            
            if($is_associative)
                $associativity[] = "$periodId1 $i $periodId2 $j";
        }
    }
}

function StreamBandwidthCheck($mpdreport){
    global $video_bw, $audio_bw, $subtitle_bw;
    
    for($v=0; $v<sizeof($video_bw); $v++){
        for($a=0; $a<sizeof($audio_bw); $a++){
            if(!empty($subtitle_bw)){
                for($s=0; $s<sizeof($subtitle_bw); $s++){
                    $total_bw = $video_bw[$v] + $subtitle_bw[$s] + $audio_bw[$a];
                    if($audio_bw[$a] > 0.2*$total_bw)
                        fwrite($mpdreport, "Warning for DVB check: Section 11.3.0- 'If the service being delivered is a video service, then audio SHOULD be 20% or less of the total stream bandwidth', exceeding stream found with bandwidth properties: video " . $video_bw[$v] . ", audio " . $audio_bw[$a] . ", subtitle " . $subtitle_bw[$s] . "\n");
                }
            }
            else{
                $total_bw = $video_bw[$v] + $audio_bw[$a];
                if($audio_bw[$a] > 0.2*$total_bw)
                    fwrite($mpdreport, "Warning for DVB check: Section 11.3.0- 'If the service being delivered is a video service, then audio SHOULD be 20% or less of the total stream bandwidth', exceeding stream found with bandwidth properties: video " . $video_bw[$v] . ", audio " . $audio_bw[$a] . "\n");
            }
        }
    }
    
    $video_bw = array();
    $audio_bw = array();
    $subtitle_bw = array();
}

function DVB_event_checks($possible_event, $mpdreport){
    global $period_count;
    if($possible_event->getAttribute('schemeIdUri') == 'urn:dvb:iptv:cpm:2014'){
        if($possible_event->getAttribute('value') == '1'){
            $events = $possible_event->getElementsByTagName('Event');
            foreach ($events as $event){
                if($event->getAttribute('presentationTime') == '')
                    fwrite($mpdreport, "###'DVB check violated: Section 9.1.2.1- The events associated with the @schemeIdUri attribute \"urn:dvb:iptv:cpm:2014\" and with @value attribute of \"1\", the presentationTime attribute of an MPD event SHALL be set', not set accordingly in Period $period_count.\n");
                                
                $event_value = $event->nodeValue;
                if($event_value != ''){
                    $event_str = '<doc>' . $event_value . '</doc>';
                    $event_xml = simplexml_load_string($event_str); 
                    if($event_xml === FALSE)
                        fwrite($mpdreport, "###'DVB check violated: Section 9.1.2.2- In order to carry XML structured data within the string value of an MPD Event element, the data SHALL be escaped or placed in a CDATA section in accordance with the XML specification 1.0', not done accordingly in Period $period_count.\n");
                    else{
                        foreach ($event_xml as $broadcastevent){
                            $name = $broadcastevent->getName();
                            if($name != 'BroadcastEvent')
                                fwrite($mpdreport, "###'DVB check violated: Section 9.1.2.2- The format of the event payload carrying content programme metadata SHALL be one or more TV-Anytime BroadcastEvent elements that form a valid TVAnytime XML document', not set accordingly in Period $period_count.\n");
                        }
                    }
                }
            }
        }
    }
}

function DVB_video_checks($adapt, $reps, $mpdreport, $i, $contentTemp_vid_found){
    global $adapt_video_count, $main_video_found, $period_count, $video_bw;
    
    ## Information from this part is used for Section 4.2.2 check about multiple Adaptation Sets with video as contentType
    if($adapt->getAttribute('contentType') == 'video'){
        $adapt_video_count++;
    }
    
    $ids = array();
    foreach ($adapt->childNodes as $ch){
        if($ch->nodeName == 'Role'){
            if($adapt->getAttribute('contentType') == 'video'){
                if($ch->getAttribute('schemeIdUri') == 'urn:mpeg:dash:role:2011' && $ch->getAttribute('value') == 'main')
                    $main_video_found = true;
            }
        }
        if($ch->nodeName == 'ContentComponent'){
            if($ch->getAttribute('contentType') == 'video')
                $ids[] = $ch->getAttribute('id');
        }
        if($ch->nodeName == 'SupplementalProperty'){
            if($ch->getAttribute('schemeIdUri') == 'urn:dvb:dash:fontdownload:2014' && $ch->getAttribute('value') == '1'){
                if(($ch->getAttribute('url') != '' || $ch->getAttribute('dvburl') != '') && ($ch->getAttribute('fontFamily') != '' || $ch->getAttribute('dvb:fontFamily') != '') && ($ch->getAttribute('mimeType') != '' || $ch->getAttribute('dvb:mimeType') != ''))
                    fwrite($mpdreport, "###'DVB check violated: Section 7.2.1.1- For DVB font download for subtitles, a descriptor with these properties SHALL only be placed within an Adaptation Set containing subtitle Representations', found SupplementalProperty element signaling downloadable fonts in video Adaptation Set in Period $period_count Adaptation Set " . ($i+1) . ".\n");
            }
        }
        if($ch->nodeName == 'EssentialProperty'){
            if($ch->getAttribute('schemeIdUri') == 'urn:dvb:dash:fontdownload:2014' && $ch->getAttribute('value') == '1'){
                if(($ch->getAttribute('url') != '' || $ch->getAttribute('dvburl') != '') && ($ch->getAttribute('fontFamily') != '' || $ch->getAttribute('dvb:fontFamily') != '') && ($ch->getAttribute('mimeType') != '' || $ch->getAttribute('dvb:mimeType') != ''))
                    fwrite($mpdreport, "###'DVB check violated: Section 7.2.1.1- For DVB font download for subtitles, a descriptor with these properties SHALL only be placed within an Adaptation Set containing subtitle Representations', found EssentialProperty element signaling downloadable fonts in video Adaptation Set in Period $period_count Adaptation Set " . ($i+1) . ".\n");
            }
        }
    }
    ##
    
    $adapt_width_present = true; 
    $adapt_height_present = true; 
    $adapt_frameRate_present = true;
    if($adapt->getAttribute('width') == '')
        $adapt_width_present = false;
    if($adapt->getAttribute('height') == '')
        $adapt_height_present = false;
    if($adapt->getAttribute('frameRate') == '')
        $adapt_frameRate_present = false;
    
    $adapt_codecs = $adapt->getAttribute('codecs');
    $reps_len = $reps->length;
    $reps_codecs = array();
    $subreps_codecs = array();
    for($j=0; $j<$reps_len; $j++){
        $rep = $reps->item($j);
        
        ## Information from this part is used for Section 4.4 check
        $reps_width[] = $rep->getAttribute('width');
        $reps_height[] = $rep->getAttribute('height');
        $reps_frameRate[] = $rep->getAttribute('frameRate');
        $reps_scanType[] = $rep->getAttribute('scanType');
        
        if($adapt->getAttribute('contentType') == 'video'){
            if($adapt_width_present == false && $rep->getAttribute('width') == '')
                fwrite($mpdreport, "###'DVB check violated: Section 4.4- For any Representation within an Adaptation Set with @contentType=\"video\" @width attribute SHALL be present if not in the AdaptationSet element', could not be found in neither Period $period_count Adaptation Set " . ($i+1) . " nor Period $period_count Adaptation Set " . ($i+1) . " Representation " . ($j+1) . ".\n");
            if($adapt_height_present == false && $rep->getAttribute('height') == '')
                fwrite($mpdreport, "###'DVB check violated: Section 4.4- For any Representation within an Adaptation Set with @contentType=\"video\" @height attribute SHALL be present if not in the AdaptationSet element', could not be found in neither Period $period_count Adaptation Set " . ($i+1) . " nor Period $period_count Adaptation Set " . ($i+1) . " Representation " . ($j+1) . ".\n");
            if($adapt_frameRate_present == false && $rep->getAttribute('frameRate') == '')
                fwrite($mpdreport, "###'DVB check violated: Section 4.4- For any Representation within an Adaptation Set with @contentType=\"video\" @frameRate attribute SHALL be present if not in the AdaptationSet element', could not be found in neither Period $period_count Adaptation Set " . ($i+1) . " nor Period $period_count Adaptation Set " . ($i+1) . " Representation " . ($j+1) . ".\n");
            if($adapt->getAttribute('sar') == '' && $rep->getAttribute('sar') == '')
                fwrite($mpdreport, "Warning for DVB check: Section 4.4- 'For any Representation within an Adaptation Set with @contentType=\"video\" @sar attribute SHOULD be present or inherited from the Adaptation Set', could not be found in neither Period $period_count Adaptation Set " . ($i+1) . " nor Period $period_count Adaptation Set " . ($i+1) . " Representation " . ($j+1) . ".\n");
        }
        ##
        
        $reps_codecs[] = $rep->getAttribute('codecs');
        $subreps = $rep->getElementsByTagName('SubRepresentation');
        for($k=0; $k<$subreps->length; $k++){
            $subrep = $subreps->item($k);
            $subreps_codecs[] = $subrep->getAttribute('codecs');
            
            ##Information from this part is for Section 11.3.0: audio stream bandwidth percentage
            if($contentTemp_vid_found){
                if(in_array($subrep->getAttribute('contentComponent'), $ids)){
                    $video_bw[] = ($rep->getAttribute('bandwidth') != '') ? (float)($rep->getAttribute('bandwidth')) : (float)($ch->getAttribute('bandwidth'));
                }
            }
            ##
        }
        
        #Information from this part is for Section 11.3.0: audio stream bandwidth percentage
        if(!$contentTemp_vid_found){
            $video_bw[] = (float)($rep->getAttribute('bandwidth'));
        }
        ##
    }
    
    ## Information from this part is used for Section 5.1 AVC codecs
    if((strpos($adapt_codecs, 'avc') !== FALSE)){
        $codec_parts = array();
        $codecs = explode(',', $adapt_codecs);
        foreach($codecs as $codec){
            if(strpos($codec, 'avc') !== FALSE){
                $codec_parts = explode('.', $codec);
                $pcl = strlen($codec_parts[1]);
                if($pcl != 6)
                    fwrite($mpdreport, "###'DVB check violated: Section 5.1.3- If (AVC video codec is) present the value of @codecs attribute SHALL be set in accordance with RFC 6381, clause 3.3', not found or not complete within Period $period_count Adaptation Set " . ($i+1) . ".\n");
            }
        }
    }
    foreach($reps_codecs as $rep_codecs){
        $codecs = explode(',', $rep_codecs);
        foreach($codecs as $codec){
            if(strpos($codec, 'avc') !== FALSE){
                $codec_parts = explode('.', $codec);
                $pcl = strlen($codec_parts[1]);
                if($pcl != 6)
                    fwrite($mpdreport, "###'DVB check violated: Section 5.1.3- If (AVC video codec is) present the value of @codecs attribute SHALL be set in accordance with RFC 6381, clause 3.3', not found or not complete within Period $period_count Adaptation Set " . ($i+1) . ".\n");
            }
        }
    }
    foreach($subreps_codecs as $subrep_codecs){
        $codecs = explode(',', $subrep_codecs);
        foreach($codecs as $codec){
            if(strpos($codec, 'avc') !== FALSE){
                $codec_parts = explode('.', $codec);
                $pcl = strlen($codec_parts[1]);
                if($pcl != 6)
                    fwrite($mpdreport, "###'DVB check violated: Section 5.1.3- If (AVC video codec is) present the value of @codecs attribute SHALL be set in accordance with RFC 6381, clause 3.3', not found or not complete within Period $period_count Adaptation Set " . ($i+1) . ".\n");
            }
        }
    }
    ##
    
    ## Information from this part is used for Section 4.4 check
    if($adapt->getAttribute('contentType') == 'video'){
        if($adapt->getAttribute('maxWidth') == '' && $adapt_width_present == false)
            fwrite($mpdreport, "Warning for DVB check: Section 4.4- 'For any Adaptation Sets with @contentType=\"video\" @maxWidth attribute (or @width if all Representations have the same width) SHOULD be present', could not be found in Period $period_count Adaptation Set " . ($i+1) . ".\n");
        if($adapt->getAttribute('maxHeight') == '' && $adapt_height_present == false)
            fwrite($mpdreport, "Warning for DVB check: Section 4.4- 'For any Adaptation Sets with @contentType=\"video\" @maxHeight attribute (or @height if all Representations have the same height) SHOULD be present', could not be found in Period $period_count Adaptation Set " . ($i+1) . ".\n");
        if($adapt->getAttribute('maxFrameRate') == '' && $adapt_frameRate_present == false)
            fwrite($mpdreport, "Warning for DVB check: Section 4.4- 'For any Adaptation Sets with @contentType=\"video\" @maxFrameRate attribute (or @frameRate if all Representations have the same frameRate) SHOULD be present', could not be found in Period $period_count Adaptation Set " . ($i+1) . ".\n");
        if($adapt->getAttribute('par') == '')
            fwrite($mpdreport, "Warning for DVB check: Section 4.4- 'For any Adaptation Sets with @contentType=\"video\" @par attribute SHOULD be present', could not be found in Period $period_count Adaptation Set " . ($i+1) . ".\n");
        if(in_array('interlaced', $reps_scanType) && in_array('', $reps_scanType)){
                fwrite($mpdreport, "###'DVB check violated: Section 4.4- For any Representation within an Adaptation Set with @contentType=\"video\" @scanType attribute SHALL be present if interlaced pictures are used within any Representation in the Adaptation Set', could not be found in neither Period $period_count Adaptation Set " . ($i+1) . " nor Period $period_count Adaptation Set " . ($i+1) . " Representation " . ($j+1) . ".\n");
        }
        
        ## Information from this part is used for Section 11.2.2 frame rate check
        $frame_rate_len = sizeof($reps_frameRate);
        for($f1=0; $f1<$frame_rate_len; $f1++){
            if($reps_frameRate[$f1] != ''){
                for($f2=$f1+1; $f2<$frame_rate_len; $f2++){
                    if($reps_frameRate[$f2] != ''){
                        $modulo = ($reps_frameRate[$f1] > $reps_frameRate[$f2]) ? ($reps_frameRate[$f1] % $reps_frameRate[$f2]) : ($reps_frameRate[$f2] % $reps_frameRate[$f1]);
                        
                        if($modulo != 0)
                            fwrite($mpdreport, "Warning for DVB check: Section 11.2.2- 'The frame rates used SHOULD be multiple integers of each other to enable seamless switching', not satisfied for Period $period_count Adaptation Set " . ($i+1) . "- Representation " . ($f1+1) . " and Representation " . ($f2+1) . ".\n");
                
                    }
                }
            }
        }
    }
    ##
}

function DVB_audio_checks($adapt, $reps, $mpdreport, $i, $contentTemp_aud_found){
    global $adapt_audio_count, $main_audios, $main_audio_found, $period_count, $audio_bw;
    
    if($adapt->getAttribute('contentType') == 'audio'){
        $adapt_audio_count++;
    }
    
    $adapt_role_element_found = false;
    $rep_role_element_found = false;
    $contentComp_role_element_found = false;
    $adapt_audioChConf_element_found = false;
    $adapt_audioChConf_scheme = array();
    $adapt_audioChConf_value = array();
    $adapt_mimeType = $adapt->getAttribute('mimeType');
    $adapt_audioSamplingRate = $adapt->getAttribute('audioSamplingRate');
    $adapt_specific_role_count = 0;
    $adapt_codecs = $adapt->getAttribute('codecs');
    
    $ids = array();
    foreach($adapt->childNodes as $ch){
        if($ch->nodeName == 'Role'){
            $adapt_role_element_found = true;
            
            if($ch->getAttribute('schemeIdUri') == 'urn:mpeg:dash:role:2011'){
                $adapt_specific_role_count++;
                $role_values[] = $ch->getAttribute('value');
                
                if($ch->getAttribute('value') == 'main'){
                    $main_audio_found = true;
                    $main_audios[] = $adapt;
                }
            }
        }
        if($ch->nodeName == 'Accessibility'){
            if($ch->getAttribute('schemeIdUri') == 'urn:tva:metadata:cs:AudioPurposeCS:2007'){
                $accessibility_roles[] = $ch->getAttribute('value');
            }
        }
        if($ch->nodeName == 'AudioChannelConfiguration'){
            $adapt_audioChConf_element_found = true;
            $adapt_audioChConf_scheme[] = $ch->getAttribute('schemeIdUri');
            $adapt_audioChConf_value[] = $ch->getAttribute('value');
        }
        if($contentTemp_aud_found && $ch->nodeName == 'ContentComponent'){
            if($ch->getAttribute('contentType') == 'audio'){
                foreach($ch->childNodes as $c){
                    if($c->nodeName == 'Role'){
                        $contentComp_role_element_found = true;
                    }
                }
                $ids[] = $ch->getAttribute('id');
            }
        }
        if($ch->nodeName == 'SupplementalProperty'){
            if($ch->getAttribute('schemeIdUri') == 'urn:dvb:dash:fontdownload:2014' && $ch->getAttribute('value') == '1'){
                if(($ch->getAttribute('url') != '' || $ch->getAttribute('dvburl') != '') && ($ch->getAttribute('fontFamily') != '' || $ch->getAttribute('dvb:fontFamily') != '') && ($ch->getAttribute('mimeType') != '' || $ch->getAttribute('dvb:mimeType') != ''))
                    fwrite($mpdreport, "###'DVB check violated: Section 7.2.1.1- For DVB font download for subtitles, a descriptor with these properties SHALL only be placed within an Adaptation Set containing subtitle Representations', found SupplementalProperty element signaling downloadable fonts in audio Adaptation Set in Period $period_count Adaptation Set " . ($i+1) . ".\n");
            }
        }
        if($ch->nodeName == 'EssentialProperty'){
            if($ch->getAttribute('schemeIdUri') == 'urn:dvb:dash:fontdownload:2014' && $ch->getAttribute('value') == '1'){
                if(($ch->getAttribute('url') != '' || $ch->getAttribute('dvburl') != '') && ($ch->getAttribute('fontFamily') != '' || $ch->getAttribute('dvb:fontFamily') != '') && ($ch->getAttribute('mimeType') != '' || $ch->getAttribute('dvb:mimeType') != ''))
                    fwrite($mpdreport, "###'DVB check violated: Section 7.2.1.1- For DVB font download for subtitles, a descriptor with these properties SHALL only be placed within an Adaptation Set containing subtitle Representations', found EssentialProperty element signaling downloadable fonts in audio Adaptation Set in Period $period_count Adaptation Set " . ($i+1) . ".\n");
            }
        }
    }
    
    ## Information from this part is for Section 6.1: distinguishing Adaptation Sets
    if($adapt->getAttribute('contentType') == 'audio' && $adapt_specific_role_count == 0)
        fwrite($mpdreport, "###'DVB check violated: Section 6.1.2- Every audio Adaptation Set SHALL include at least one Role Element using the scheme \"urn:mpeg:dash:role:2011\" as defined in ISO/IEC 23009-1', could not be found in Period $period_count Adaptation Set " . ($i+1) . ".\n");
    ##
    
    ## Information from this part is for Section 6.3:Dolby and 6.4:DTS
    if(strpos($adapt_codecs, 'ec-3') !== FALSE || strpos($adapt_codecs, 'ac-4') !== FALSE){
        if(!empty($adapt_audioChConf_scheme)){
            if(strpos($adapt_codecs, 'ec-3') !== FALSE){
                if(!in_array('tag:dolby.com,2014:dash:audio_channel_configuration:2011', $adapt_audioChConf_scheme) && !in_array('urn:dolby:dash:audio_channel_configuration:2011', $adapt_audioChConf_scheme))
                    fwrite($mpdreport, "###'DVB check violated: Section E.2.5- For E-AC-3 the AudioChannelConfiguration element SHALL use either the \"tag:dolby.com,2014:dash:audio_channel_configuration:2011\" or the legacy \"urn:dolby:dash:audio_channel_configuration:2011\" schemeURI', conformance is not satisfied in Period $period_count Adaptation Set " . ($i+1) . " AudioChannelConfiguration.\n");
                
                if(in_array('tag:dolby.com,2014:dash:audio_channel_configuration:2011', $adapt_audioChConf_scheme)){
                    $value = $adapt_audioChConf_value[array_search('tag:dolby.com,2014:dash:audio_channel_configuration:2011', $adapt_audioChConf_scheme)];
                    if(strlen($value) != 4 || (strlen($value) == 4 && !ctype_xdigit($value)))
                        fwrite($mpdreport, "###'DVB check violated: Section 6.3- (For E-AC-3 and AC-4 the AudioChannelConfiguration element) the @value attribute SHALL contain four digit hexadecimal representation of the 16 bit field', found \"$value\" in Period $period_count Adaptation Set " . ($i+1) . " AudioChannelConfiguration.\n");
                }
            }
            if(strpos($adapt_codecs, 'ac-4') !== FALSE){
                if(!in_array('tag:dolby.com,2014:dash:audio_channel_configuration:2011', $adapt_audioChConf_scheme))
                    fwrite($mpdreport, "###'DVB check violated: Section 6.3- For E-AC-3 and AC-4 the AudioChannelConfiguration element SHALL use the \"tag:dolby.com,2014:dash:audio_channel_configuration:2011\" scheme URI', conformance is not satisfied in Period $period_count Adaptation Set " . ($i+1) . " AudioChannelConfiguration.\n");
                else{
                    $value = $adapt_audioChConf_value[array_search('tag:dolby.com,2014:dash:audio_channel_configuration:2011', $adapt_audioChConf_scheme)];
                    if(strlen($value) != 4 || (strlen($value) == 4 && !ctype_xdigit($value)))
                        fwrite($mpdreport, "###'DVB check violated: Section 6.3- (For E-AC-3 and AC-4 the AudioChannelConfiguration element) the @value attribute SHALL contain four digit hexadecimal representation of the 16 bit field', found \"$value\" in Period $period_count Adaptation Set " . ($i+1) . " AudioChannelConfiguration.\n");
                }
            }
        }
    }
    if(strpos($adapt_codecs, 'dtsc') !== FALSE || strpos($adapt_codecs, 'dtsh') !== FALSE || strpos($adapt_codecs, 'dtse') !== FALSE || strpos($adapt_codecs, 'dtsl') !== FALSE){
        if(!empty($adapt_audioChConf_scheme) && !in_array('tag:dts.com,2014:dash:audio_channel_configuration:2012', $adapt_audioChConf_scheme))
            fwrite($mpdreport, "###'DVB check violated: Section 6.4- For all DTS audio formats AudioChannelConfiguration element SHALL use the \"tag:dts.com,2014:dash:audio_channel_configuration:2012\" for the @schemeIdUri attribute', conformance is not satisfied in Period $period_count Adaptation Set " . ($i+1) . " AudioChannelConfiguration.\n");
    }
    ##
    
    $reps_len = $reps->length;
    $rep_audioChConf_scheme = array();
    $rep_audioChConf_value = array();
    $subrep_audioChConf_scheme = array();
    $subrep_audioChConf_value = array();
    $dependencyIds = array();
    for($j=0; $j<$reps_len; $j++){
        $rep = $reps->item($j);
        $rep_role_element_found = false;
        $rep_audioChConf_element_found = false;
        $rep_codecs = $rep->getAttribute('codecs');
        $dependencyIds[] = $rep->getAttribute('dependencyId');
        
        $ind = 0;
        foreach ($rep->childNodes as $ch){
            if($ch->nodeName == 'Role')
                $rep_role_element_found = true;
            if($ch->nodeName == 'AudioChannelConfiguration'){
                $rep_audioChConf_element_found = true;
                $rep_audioChConf_scheme[] = $ch->getAttribute('schemeIdUri');
                $rep_audioChConf_value[] = $ch->getAttribute('value');
            }
            if($ch->nodeName == 'SubRepresentation'){
                $ind++;
                $subrep_codecs = $ch->getAttribute('codecs');
                foreach($ch->childNodes as $c){
                    if($c->nodeName == 'AudioChannelConfiguration'){
                        $subrep_audioChConf_scheme[] = $c->getAttribute('schemeIdUri');
                        $subrep_audioChConf_value[] = $ch->getAttribute('value');
                    }
                }
                
                ##Information from this part is for Section 6.3:Dolby and 6.4:DTS
                if(($adapt_codecs != '' && strpos($adapt_codecs, 'ec-3') !== FALSE) || ($rep_codecs != '' && strpos($rep_codecs, 'ec-3') !== FALSE) || (strpos($subrep_codecs, 'ec-3') !== FALSE)){
                    if(!empty($subrep_audioChConf_scheme)){
                        if(!in_array('tag:dolby.com,2014:dash:audio_channel_configuration:2011', $subrep_audioChConf_scheme) && !in_array('urn:dolby:dash:audio_channel_configuration:2011', $subrep_audioChConf_scheme))
                            fwrite($mpdreport, "###'DVB check violated: Section E.2.5- For E-AC-3 the AudioChannelConfiguration element SHALL use either the \"tag:dolby.com,2014:dash:audio_channel_configuration:2011\" or the legacy \"urn:dolby:dash:audio_channel_configuration:2011\" schemeURI', conformance is not satisfied in Period $period_count Adaptation Set " . ($i+1) . " Representation " . ($j+1) . " SubRepresentation " . ($ind+1) . " AudioChannelConfiguration.\n");
                        if(in_array('tag:dolby.com,2014:dash:audio_channel_configuration:2011', $subrep_audioChConf_scheme)){
                            $value = $subrep_audioChConf_value[array_search('tag:dolby.com,2014:dash:audio_channel_configuration:2011', $subrep_audioChConf_scheme)];
                            if(strlen($value) != 4 || (strlen($value) == 4 && !ctype_xdigit($value)))
                                fwrite($mpdreport, "###'DVB check violated: Section 6.3- (For E-AC-3 and AC-4 the AudioChannelConfiguration element) the @value attribute SHALL contain four digit hexadecimal representation of the 16 bit field', found \"$value\" in Period $period_count Adaptation Set " . ($i+1) . " Representation " . ($j+1) . " SubRepresentation " . ($ind+1) . " AudioChannelConfiguration.\n");
                        }
                    }
                }
                if(($adapt_codecs != '' && strpos($adapt_codecs, 'ac-4') !== FALSE) || ($rep_codecs != '' && strpos($rep_codecs, 'ac-4') !== FALSE) || (strpos($subrep_codecs, 'ec-3') !== FALSE)){
                    if(!empty($subrep_audioChConf_scheme)){
                        if(!in_array('tag:dolby.com,2014:dash:audio_channel_configuration:2011', $subrep_audioChConf_scheme))
                            fwrite($mpdreport, "###'DVB check violated: Section 6.3- For E-AC-3 and AC-4 the AudioChannelConfiguration element SHALL use the \"tag:dolby.com,2014:dash:audio_channel_configuration:2011\" scheme URI', conformance is not satisfied in Period $period_count Adaptation Set " . ($i+1) . " Representation " . ($j+1) . " SubRepresentation " . ($ind+1) . " AudioChannelConfiguration.\n");
                        else{
                            $value = $subrep_audioChConf_value[array_search('tag:dolby.com,2014:dash:audio_channel_configuration:2011', $subrep_audioChConf_scheme)];
                            if(strlen($value) != 4 || (strlen($value) == 4 && !ctype_xdigit($value)))
                                fwrite($mpdreport, "###'DVB check violated: Section 6.3- (For E-AC-3 and AC-4 the AudioChannelConfiguration element) the @value attribute SHALL contain four digit hexadecimal representation of the 16 bit field', found \"$value\" in Period $period_count Adaptation Set " . ($i+1) . " Representation " . ($j+1) . " SubRepresentation " . ($ind+1) . " AudioChannelConfiguration.\n");
                        }
                    }
                }
                if((strpos($adapt_codecs, 'dtsc') !== FALSE || strpos($adapt_codecs, 'dtsh') !== FALSE || strpos($adapt_codecs, 'dtse') !== FALSE || strpos($adapt_codecs, 'dtsl') !== FALSE) ||
                   (strpos($rep_codecs, 'dtsc') !== FALSE || strpos($rep_codecs, 'dtsh') !== FALSE || strpos($rep_codecs, 'dtse') !== FALSE || strpos($rep_codecs, 'dtsl') !== FALSE) ||
                   (strpos($subrep_codecs, 'dtsc') !== FALSE || strpos($subrep_codecs, 'dtsh') !== FALSE || strpos($subrep_codecs, 'dtse') !== FALSE || strpos($subrep_codecs, 'dtsl') !== FALSE)){
                    if(!empty($subrep_audioChConf_scheme) && !in_array('tag:dts.com,2014:dash:audio_channel_configuration:2012', $subrep_audioChConf_scheme))
                        fwrite($mpdreport, "###'DVB check violated: Section 6.4- For all DTS audio formats AudioChannelConfiguration element SHALL use the \"tag:dts.com,2014:dash:audio_channel_configuration:2012\" for the @schemeIdUri attribute', conformance is not satisfied in Period $period_count Adaptation Set " . ($i+1) . " Representation " . ($j+1) . " SubRepresentation " . ($ind+1) . " AudioChannelConfiguration.\n");
                }
                ##
                
                ##Information from this part is for Section 11.3.0: audio stream bandwidth percentage
                if($contentTemp_aud_found){
                    if(in_array($ch->getAttribute('contentComponent'), $ids)){
                        $audio_bw[] = ($rep->getAttribute('bandwidth') != '') ? (float)($rep->getAttribute('bandwidth')) : (float)($ch->getAttribute('bandwidth'));
                    }
                }
                ##
            }
        }
        
        ##Information from this part is for Section 11.3.0: audio stream bandwidth percentage 
        if(!$contentTemp_aud_found){
            $audio_bw[] = (float)($rep->getAttribute('bandwidth'));
        }
        ##
        
        ##Information from this part is for Section 6.3:Dolby and 6.4:DTS
        if(($adapt_codecs != '' && strpos($adapt_codecs, 'ec-3') !== FALSE) || strpos($rep_codecs, 'ec-3') !== FALSE){
            if(!empty($rep_audioChConf_scheme)){
                if(!in_array('tag:dolby.com,2014:dash:audio_channel_configuration:2011', $rep_audioChConf_scheme) && !in_array('urn:dolby:dash:audio_channel_configuration:2011', $rep_audioChConf_scheme))
                    fwrite($mpdreport, "###'DVB check violated: Section E.2.5- For E-AC-3 the AudioChannelConfiguration element SHALL use either the \"tag:dolby.com,2014:dash:audio_channel_configuration:2011\" or the legacy \"urn:dolby:dash:audio_channel_configuration:2011\" schemeURI', conformance is not satisfied in Period $period_count Adaptation Set " . ($i+1) . " Representation " . ($j+1) . " AudioChannelConfiguration.\n");
                
                if(in_array('tag:dolby.com,2014:dash:audio_channel_configuration:2011', $rep_audioChConf_scheme)){
                    $value = $rep_audioChConf_value[array_search('tag:dolby.com,2014:dash:audio_channel_configuration:2011', $rep_audioChConf_scheme)];
                    if(strlen($value) != 4 || (strlen($value) == 4 && !ctype_xdigit($value)))
                        fwrite($mpdreport, "###'DVB check violated: Section 6.3- (For E-AC-3 and AC-4 the AudioChannelConfiguration element) the @value attribute SHALL contain four digit hexadecimal representation of the 16 bit field', found \"$value\" in Period $period_count Adaptation Set " . ($i+1) . " Representation " . ($j+1) . " AudioChannelConfiguration.\n");
                }
            }
        }
        if(($adapt_codecs != '' && strpos($adapt_codecs, 'ac-4') !== FALSE) || strpos($rep_codecs, 'ac-4') !== FALSE){
            if(!in_array('tag:dolby.com,2014:dash:audio_channel_configuration:2011', $rep_audioChConf_scheme))
                fwrite($mpdreport, "###'DVB check violated: Section 6.3- For E-AC-3 and AC-4 the AudioChannelConfiguration element SHALL use the \"tag:dolby.com,2014:dash:audio_channel_configuration:2011\" scheme URI', conformance is not satisfied in Period $period_count Adaptation Set " . ($i+1) . " Representation " . ($j+1) . " AudioChannelConfiguration.\n");
            else{
                $value = $rep_audioChConf_value[array_search('tag:dolby.com,2014:dash:audio_channel_configuration:2011', $rep_audioChConf_scheme)];
                if(strlen($value) != 4 || (strlen($value) == 4 && !ctype_xdigit($value)))
                fwrite($mpdreport, "###'DVB check violated: Section 6.3- (For E-AC-3 and AC-4 the AudioChannelConfiguration element) the @value attribute SHALL contain four digit hexadecimal representation of the 16 bit field', found \"$value\" in Period $period_count Adaptation Set " . ($i+1) . " Representation " . ($j+1) . " AudioChannelConfiguration.\n");
            }
        }
        if((strpos($adapt_codecs, 'dtsc') !== FALSE || strpos($adapt_codecs, 'dtsh') !== FALSE || strpos($adapt_codecs, 'dtse') !== FALSE || strpos($adapt_codecs, 'dtsl') !== FALSE) ||
           (strpos($rep_codecs, 'dtsc') !== FALSE || strpos($rep_codecs, 'dtsh') !== FALSE || strpos($rep_codecs, 'dtse') !== FALSE || strpos($rep_codecs, 'dtsl') !== FALSE)){
            if(!empty($rep_audioChConf_scheme) && !in_array('tag:dts.com,2014:dash:audio_channel_configuration:2012', $rep_audioChConf_scheme))
                fwrite($mpdreport, "###'DVB check violated: Section 6.4- For all DTS audio formats AudioChannelConfiguration element SHALL use the \"tag:dts.com,2014:dash:audio_channel_configuration:2012\" for the @schemeIdUri attribute', conformance is not satisfied in Period $period_count Adaptation Set " . ($i+1) . " Representation " . ($j+1) . " AudioChannelConfiguration.\n");
        }
        ##
        
        ## Information from this part is for Section 6.1 Table 3
        if($adapt_role_element_found == false && $contentComp_role_element_found == false && $rep_role_element_found == false)
            fwrite($mpdreport, "###'DVB check violated: Section 6.1.1- All audio Representations SHALL either define or inherit the elements and attributes shown in Table 3', Role element could not be found in Period $period_count Adaptation Set " . ($i+1) . ".\n");
        if($adapt_audioChConf_element_found == false && $rep_audioChConf_element_found == false)
            fwrite($mpdreport, "###'DVB check violated: Section 6.1.1- All audio Representations SHALL either define or inherit the elements and attributes shown in Table 3', AudioChannelConfiguration element could not be found in neither Period $period_count Adaptation Set " . ($i+1) . " nor Period $period_count Adaptation Set " . ($i+1) . " Representation " . ($j+1) . ".\n");
        if($adapt_mimeType == '' && $rep->getAttribute('mimeType') == '')
            fwrite($mpdreport, "###'DVB check violated: Section 6.1.1- All audio Representations SHALL either define or inherit the elements and attributes shown in Table 3', mimeType attribute could not be found in neither Period $period_count Adaptation Set " . ($i+1) . " nor Period $period_count Adaptation Set " . ($i+1) . " Representation " . ($j+1) . ".\n");
        if($adapt_codecs == '' && $rep_codecs == '')
            fwrite($mpdreport, "###'DVB check violated: Section 6.1.1- All audio Representations SHALL either define or inherit the elements and attributes shown in Table 3', codecs attribute could not be found in neither Period $period_count Adaptation Set " . ($i+1) . " nor Period $period_count Adaptation Set " . ($i+1) . " Representation " . ($j+1) . ".\n");
        if($adapt_audioSamplingRate == '' && $rep->getAttribute('audioSamplingRate') == '')
            fwrite($mpdreport, "###'DVB check violated: Section 6.1.1- All audio Representations SHALL either define or inherit the elements and attributes shown in Table 3', audioSamplingRate attribute could not be found in neither Period $period_count Adaptation Set " . ($i+1) . " nor Period $period_count Adaptation Set " . ($i+1) . " Representation " . ($j+1) . ".\n");
        ##
    }
    
    ## Information from this part is for Section 6.1: Receiver Mix AD 
    if(in_array('commentary', $role_values) && in_array('1', $accessibility_roles)){
        if(empty($dependencyIds))
            fwrite($mpdreport, "###'DVB check violated: Section 6.1.2- For receiver mixed Audio Description the associated audio stream SHALL use the @dependencyId attribute to indicate the dependency to the related Adaptation Set's Representations', not found in Period $period_count Adaptation Set " . ($i+1) . ".\n");
    }
    ##
}

function DVB_subtitle_checks($adapt, $reps, $mpdreport, $i){
    global $period_count, $subtitle_bw, $hoh_subtitle_lang;
    
    $str_codec_info = '';
    $adapt_mimeType = $adapt->getAttribute('mimeType');
    $adapt_codecs = $adapt->getAttribute('codecs');
    $adapt_type = $adapt->getAttribute('contentType');
    $contentComp = false;
    $contentComp_type = array();
    $subtitle = false;
    $supp_present = false; $supp_scheme = array(); $supp_val = array(); $supp_url = array(); $supp_fontFam = array(); $supp_mime = array();
    $ess_present = false; $ess_scheme = array(); $ess_val = array(); $ess_url = array(); $ess_fontFam = array(); $ess_mime = array();
    
    if(strpos($adapt_codecs, 'stpp') !== FALSE)
        $str_codec_info .= 'y ';
    
    $ids = array();
    $hoh_acc = false;
    $hoh_role = false;
    foreach($adapt->childNodes as $ch){
        if($ch->nodeName == 'ContentComponent'){
            $contentComp = true;
            $contentComp_type[] = $ch->getAttribute('contentType');
            if($ch->getAttribute('contentType') == 'text')
                $ids[] = $ch->getAttribute('contentType');
        }
        if($ch->nodeName == 'SupplementalProperty'){
            $supp_present = true;
            $supp_scheme[] = $ch->getAttribute('schemeIdUri');
            $supp_val[] = $ch->getAttribute('value');
            $supp_url[] = ($ch->getAttribute('dvb:url') != '') ? $ch->getAttribute('dvb:url') : $ch->getAttribute('url');
            $supp_fontFam[] = ($ch->getAttribute('dvb:fontFamily') != '') ? $ch->getAttribute('dvb:fontFamily') : $ch->getAttribute('fontFamily');
            $supp_mime[] = ($ch->getAttribute('dvb:mimeType') != '') ? $ch->getAttribute('dvb:mimeType') : $ch->getAttribute('mimeType');
        }
        if($ch->nodeName == 'EssentialProperty'){
            $ess_present = true;
            $ess_scheme[] = $ch->getAttribute('schemeIdUri');
            $ess_val[] = $ch->getAttribute('value');
            $ess_url[] = ($ch->getAttribute('dvb:url') != '') ? $ch->getAttribute('dvb:url') : $ch->getAttribute('url');
            $ess_fontFam[] = ($ch->getAttribute('dvb:fontFamily') != '') ? $ch->getAttribute('dvb:fontFamily') : $ch->getAttribute('fontFamily');
            $ess_mime[] = ($ch->getAttribute('dvb:mimeType') != '') ? $ch->getAttribute('dvb:mimeType') : $ch->getAttribute('mimeType');
        }
        if($ch->nodeName == 'Accessibility'){
            if($ch->getAttribute('schemeIdUri') == 'urn:tva:metadata:cs:AudioPurposeCS:2007' && $ch->getAttribute('value') == '2')
                $hoh_acc = true;
        }
        if($ch->nodeName == 'Role'){
            if($ch->getAttribute('schemeIdUri') == 'urn:mpeg:dash:role:2011' && $ch->getAttribute('value') == 'main')
                $hoh_role = true;
        }
    }
    
    if($hoh_acc && $hoh_role){
        if($adapt->getAttribute('lang') != '')
            $hoh_subtitle_lang[] = $adapt->getAttribute('lang');
    }
    
    $reps_len = $reps->length;
    for($j=0; $j<$reps_len; $j++){
        $rep = $reps->item($j);
        
        $rep_codecs = $rep->getAttribute('codecs');
        if(strpos($rep_codecs, 'stpp') !== FALSE)
            $str_codec_info .= 'y ';
        
        $subrep_codecs = array();
        foreach ($rep->childNodes as $ch){
            if($ch->nodeName == 'SubRepresentation'){
                $subrep_codecs[] = $ch->getAttribute('codecs');
                if(strpos($ch->getAttribute('codecs'), 'stpp') !== FALSE)
                    $str_codec_info .= 'y ';
                
                ##Information from this part is for Section 11.3.0: audio stream bandwidth percentage
                if(in_array($ch->getAttribute('contentComponent'), $ids)){
                    $subtitle_bw[] = ($rep->getAttribute('bandwidth') != '') ? (float)($rep->getAttribute('bandwidth')) : (float)($ch->getAttribute('bandwidth'));
                }
                ##
            }
        }
        
        ## Information from this part is for Section 7.1: subtitle carriage
        if($adapt_mimeType == 'application/mp4' || $rep->getAttribute('mimeType') == 'application/mp4'){
            if(strpos($adapt_codecs, 'stpp') !== FALSE || strpos($rep_codecs, 'stpp') !== FALSE || in_array('stpp', $subrep_codecs) !== FALSE){
                $subtitle = true;
                
                if(($adapt_type != '' && $adapt_type != 'text') && !in_array('text', $contentComp_type))
                    fwrite($mpdreport, "###'DVB check violated: Section 7.1.1- The @contetnType attribute indicated for subtitles SHALL be \"text\"', found as ". $adapt->getAttribute('contentType') . " in Period $period_count Adaptation Set " . ($i+1) . " Representation " . ($j+1) . ".\n");
                
                if($adapt->getAttribute('lang') == '')
                    fwrite($mpdreport, "###'DVB check violated: Section 7.1.2- In oder to allow a Player to identify the primary purpose of a subtitle track, the language attribute SHALL be set on the Adaptation Set', not found on Adaptaion Set ". ($i+1) . ".\n");
            }
            
            // Check if subtitle codec attribute is set correctly
            if($str_codec_info == '')
                fwrite($mpdreport, "###'DVB check violated: Section 7.1.1- The @codecs attribute indicated for subtitles SHALL be \"stpp\"', not used for in Period $period_count Adaptation Set " . ($i+1) . " Representation " . ($j+1) . ".\n");
                
            ##Information from this part is for Section 11.3.0: audio stream bandwidth percentage 
            if(! $contentComp){
                $subtitle_bw[] = (float)($rep->getAttribute('bandwidth'));
            }
            ##
        }
        ##
    }
    
    ## Information from this part is for Section 7.2: downloadable fonts and descriptors needed for them
    if($subtitle){
        if($supp_present){
            $x = 0;
            foreach($supp_scheme as $supp_scheme_i){
                if($supp_scheme_i == 'urn:dvb:dash:fontdownload:2014'){
                    if($supp_val[$x] != '1'){
                        fwrite($mpdreport, "###'DVB check violated: Section 7.2.1.1- This descriptor (SupplementalProperty for downloadable fonts) SHALL use the values for @schemeIdUri and @value specified in clause 7.2.1.2', found as \"$supp_scheme_i\" and \"". $supp_val[$x] . "\" in Period $period_count Adaptation Set " . ($i+1) . ".\n");
                    }
                    if($supp_url[$x] == '' || $supp_fontFam[$x] == '' || ($supp_mime[$x] != 'application/font-sfnt' && $supp_mime[$x] != 'application/font-woff')){
                        fwrite($mpdreport, "###'DVB check violated: Section 7.2.1.1- The descriptor (SupplementalProperty for downloadable fonts) SHALL carry all the mandatory additional attributes defined in clause 7.2.1.3', not complete in Period $period_count Adaptation Set " . ($i+1) . ".\n");
                    }
                }
                $x++;
            }
        }
        elseif($ess_present){
            $x = 0;
            foreach($ess_scheme as $ess_scheme_i){
                if($ess_scheme_i == 'urn:dvb:dash:fontdownload:2014'){
                    if($ess_val[$x] != '1'){
                        fwrite($mpdreport, "###'DVB check violated: Section 7.2.1.1- This descriptor (EssentialProperty for downloadable fonts) SHALL use the values for @schemeIdUri and @value specified in clause 7.2.1.2', found as \"$ess_scheme_i\" and \"". $ess_val[$x] . "\" in Period $period_count Adaptation Set " . ($i+1) . ".\n");
                    }
                    if($ess_url[$x] == '' || $ess_fontFam[$x] == '' || ($ess_mime[$x] != 'application/font-sfnt' && $ess_mime[$x] != 'application/font-woff')){
                        fwrite($mpdreport, "###'DVB check violated: Section 7.2.1.1- The descriptor (EssentialProperty for downloadable fonts) SHALL carry all the mandatory additional attributes defined in clause 7.2.1.3', not complete in Period $period_count Adaptation Set " . ($i+1) . ".\n");
                    }
                }
                $x++;
            }
        }
    }
    ##
}

function DVB_content_protection($adapt, $reps, $mpdreport, $i, $cenc){
    global $period_count;
    
    $mp4protection_count = 0;
    $default_KIDs = array();
    $contentProtection = $adapt->getElementsByTagName('ContentProtection');
    foreach ($contentProtection as $contentProtection_i){
        if($contentProtection_i->parentNode->nodeName != 'AdaptationSet')
            fwrite($mpdreport, "###'DVB check violated: Section 8.3- ContentProtection descriptor SHALL be placed at he AdaptationSet level', found at \"" . $contentProtection_i->parentNode->nodeName . "\" level in Period $period_count Adaptation Set " . ($i+1) . ".\n");
        else{
            if($contentProtection_i->getAttribute('schemeIdUri') == 'urn:mpeg:dash:mp4protection:2011' && $contentProtection_i->getAttribute('value') == 'cenc'){
                $mp4protection_count++;
                $default_KIDs[] = $contentProtection_i->getAttribute('cenc:default_KID');
            }
        }
    }
    
    if($contentProtection->length != 0 && $mp4protection_count == 0){
        fwrite($mpdreport, "###'DVB check violated: Section 8.4- Any Adaptation Set containing protected content SHALL contain one \"mp4protection\" ContentProtection descriptor with @schemeIdUri=\"urn:mped:dash:mp4protection:2011\" and @value=\"cenć\", not found in Period $period_count Adaptation Set " . ($i+1) . ".\n");
        if($cenc == '' || ($cenc != '' && empty($default_KIDs)))
            fwrite($mpdreport, "Warning for DVB check: Section 8.4- '\"mp4protection\" ContentProtection descriptor SHOULD include the extension defined in ISO/IEC 23001-7 clause 11.2', not found in Period $period_count Adaptation Set " . ($i+1) . ".\n");
    }
}

function HbbTV_mpdvalidator($mpdreport){
    global $onRequest_array, $xlink_not_valid_array, $mpd_dom, $mpd_url;
    
    if(!empty($onRequest_array)){
        $onRequest_k_v  = implode(', ', array_map(
        function ($v, $k) { return sprintf(" %s with index (starting from 0) '%s'", $v, $k); },
        $onRequest_array,array_keys($onRequest_array)));
        fwrite($mpdreport, "###'HbbTV-DVB DASH Validation Requirements check violated for HbbTV: Section 'xlink' - MPD SHALL NOT have xlink:actuate set to onRequest', found in ".$onRequest_k_v."\n"); 
    }
    
    if(!empty($xlink_not_valid_array)){
        $xlink_not_valid_k_v  = implode(', ', array_map(
        function ($v, $k) { return sprintf(" %s with index (starting from 0) '%s'", $v, $k); },
        $xlink_not_valid_array,array_keys($xlink_not_valid_array)));
        fwrite($mpdreport, "###'HbbTV-DVB DASH Validation Requirements check violated for HbbTV: Section 'xlink' - MPD invalid xlink:href', found in :".$xlink_not_valid_k_v."\n"); 
    }
    TLS_bitrate_check($mpdreport);
    
    $mpd_doc = get_doc($mpd_url);
    $mpd_string = $mpd_doc->saveXML();
    $mpd_bytes = strlen($mpd_string);
    if($mpd_bytes > 100*1024){
        fwrite($mpdreport, "###'HbbTV check violated: Section E.2.1 - The MPD size shall not exceed 100 Kbytes', found " . ($mpd_bytes/1024) . " Kbytes.\n");
    }
    
    //$docType=$dom->getElementsByTagName('!DOCTYPE');
    $docType=$mpd_dom->doctype;
    if($docType!==NULL)
       fwrite($mpdreport, "###'HbbTV check violated: Section E.2.1 - The MPD must not contain an XML Document Type Definition(<!DOCTYPE>)', but found in the MPD \n");

    ## Warn on low values of MPD@minimumUpdatePeriod (for now the lowest possible value is assumed to be 1 second)
    if($mpd_dom->getAttribute('minimumUpdatePeriod') != ''){
        $mup = time_parsing($mpd_dom->getAttribute('minimumUpdatePeriod'));
        if($mup < 1)
            fwrite($mpdreport, "Warning for HbbTV-DVB DASH Validation Requirements check for HbbTV: Section 'MPD' - 'MPD@minimumUpdatePeriod has a lower value than 1 second.\n");
    }
    ##
    
    // Periods within MPD
    $period_count = 0;
    foreach($mpd_dom->childNodes as $node){
        if($node->nodeName == 'Period'){
            $period_count++;
           
            // Adaptation Sets within each Period
            $adapts = $node->getElementsByTagName('AdaptationSet');
            $adapt_count=0;
            $adapt_video_cnt=0;
            $adapt_audio_cnt=0;
            $main_video_found=0;
            $main_audio_found=0;
            //Following has error reporting code if MPD element is not part of validating profile.
            for($i=0; $i< ($adapts->length); $i++){
                $adapt_count++;
#                $subSegAlign=$adapts->item($i)->getAttribute('subsegmentAlignment');
#                if($subSegAlign == TRUE)
#                    fwrite($mpdreport, "###'HbbTV profile violated: The MPD contains an attribute that is not part of the HbbTV profile', i.e., found 'subsegmentAlignment' as true in AdaptationSet ".($i+1)." \n");
                
                $role=$adapts->item($i)->getElementsByTagName('Role');
                if($role->length>0){
                     $schemeIdUri=$role->item(0)->getAttribute('schemeIdUri');
                     $role_value=$role->item(0)->getAttribute('value');
                }
                //Representation in AS and its checks
                $rep_count=0;
                $reps = $adapts->item($i)->getElementsByTagName('Representation');
                
                if($adapts->item($i)->getAttribute('contentType')=='video' || $adapts->item($i)->getAttribute('mimeType')=='video/mp4' || $reps->item(0)->getAttribute('mimeType')=='video/mp4'){
                    $adapt_video_cnt++;
                    if($role->length>0 && (strpos($schemeIdUri,"urn:mpeg:dash:role:2011")!==false && $role_value=="main"))
                        $main_video_found++;
                    HbbTV_VideoRepChecks($adapts->item($i), $adapt_count,$period_count,$mpdreport );
                }
                
                if($adapts->item($i)->getAttribute('contentType')=='audio' || $adapts->item($i)->getAttribute('mimeType')=='audio/mp4' ||$reps->item(0)->getAttribute('mimeType')=='audio/mp4' ){   
                    $adapt_audio_cnt++;
                    if($role->length>0 && (strpos($schemeIdUri,"urn:mpeg:dash:role:2011")!==false && $role_value=="main"))
                        $main_audio_found++;
                    HbbTV_AudioRepChecks($adapts->item($i), $adapt_count,$period_count,$mpdreport);
                }

                 //Following has error reporting code if MPD element is not part of validating profile.
#                $startWithSAP=$adapts->item($i)->getAttribute('subsegmentStartsWithSAP');
#                    if($startWithSAP == 1 || $startWithSAP ==2)
#                        fwrite($mpdreport, "###'HbbTV profile violated: The MPD contains an attribute that is not part of the HbbTV profile', i.e., found 'subsegmentStartsWithSAP' ".$startWithSAP." in AdaptationSet ".($i+1). " \n");
#                    else if ($startWithSAP==3){
#                        if(!($reps->length>1))
#                            fwrite($mpdreport, "###'HbbTV profile violated: The MPD contains an attribute that is not part of the HbbTV profile', i.e., found 'subsegmentStartsWithSAP' ".$startWithSAP." in AdaptationSet ".($i+1). " not containing more than one Representation \n");
#
#                      
#                    }
                for($j=0;$j<($reps->length);$j++){
                    $rep_count++;
#                    $baseURL=$reps->item($j)->getElementsByTagName('BaseURL');
#                    if($baseURL->length>0)
#                        fwrite($mpdreport, "###'HbbTV profile violated: The MPD contains an element that is not part of the HbbTV profile', i.e., found 'BaseURL' element in Representation ".($j+1)." of AdaptationSet ".($i+1). ". \n");
#                    if ($startWithSAP==3){
#                      $currentChild=$reps->item($j);
#                        $currentId= $currentChild->getAttribute('mediaStreamStructureId');
#                        while($currentChild && $currentId!=NULL){
#                            $currentChild=nextElementSibling($currentChild);
#                            if($currentChild!==NULL){
#                                $nextId=$currentChild->getAttribute('mediaStreamStructureId');
#                                if($currentId==$nextId){
#                                    fwrite($mpdreport, "###'HbbTV profile violated: The MPD contains an attribute that is not part of the HbbTV profile', i.e., found 'subsegmentStartsWithSAP' ".$startWithSAP." in AdaptationSet ".($i+1). " with same value of mediaStreamStructureId in more than one Representation \n");
#
#                                }
#                            }
#                        }
#                     }

                }
                if($rep_count>16)
                   fwrite($mpdreport, "###'HbbTV check violated: Section E.2.2 - There shall be no more than 16 Representations per Adaptatation Set  in an MPD', but found ".$rep_count." Represenations in Adaptation Set ".$adapt_count." in Period ".$period_count." \n");
            }
            
            if($adapt_count>16)
                fwrite($mpdreport, "###'HbbTV check violated: Section E.2.2 - There shall be no more than 16 Adaptation Sets per Period in an MPD', but found ".$adapt_count." Adaptation Sets in Period ".$period_count." \n");
            if($adapt_video_cnt==0)
                fwrite($mpdreport, "###'HbbTV check violated: Section E.2.2 - There shall be at least one video Adaptation Set per Period in an MPD', but found ".$adapt_video_cnt." video Adaptation Sets in Period ".$period_count." \n");
            if($adapt_video_cnt>1 && $main_video_found!=1)
                fwrite($mpdreport, "###'HbbTV check violated: Section E.2.2 - If there is more than one video AdaptationSet, exactly one shall be labelled with Role@value 'main' ', but found ".$main_video_found." Role@value 'main' in Period ".$period_count." \n");
            if($adapt_audio_cnt>1 && $main_audio_found!=1)
                fwrite($mpdreport, "###'HbbTV check violated: Section E.2.2 - If there is more than one audio AdaptationSet, exactly one shall be labelled with Role@value 'main' ', but found ".$main_audio_found." Role@value 'main' in Period ".$period_count." \n");
        }
    }
    
    if($period_count>32)
            fwrite($mpdreport, "###'HbbTV check violated: Section E.2.2 - There shall be no more than 32 Periods in an MPD', but found ".$period_count." Periods \n");
}

//Function to find the next Sibling. php funciton next_sibling() is not working.So using this helper function.
function nextElementSibling($node){
    while($node && ($node = $node->nextSibling)){
        if($node instanceof DOMElement){
            break;
        }
    }
    return $node;
}

function HbbTV_VideoRepChecks($adapt, $adapt_num,$period_num,$mpdreport){
    $width=$adapt->getAttribute('width');
    $height=$adapt->getAttribute('height');
    $frameRate=$adapt->getAttribute('frameRate');
    $scanType=$adapt->getAttribute('scanType');
    $codecs=$adapt->getAttribute('codecs');
    if($codecs!=NULL && strpos($codecs, 'avc')===false)
        fwrite($mpdreport, "###'HbbTV check violated: Section E.2.1 - The video content referenced by MPD shall only be encoded using video codecs defined in 7.3.1 (AVC)', but ".$codecs." found in Adaptation Set ".$adapt_num." in Period ".$period_num." \n");

    $reps=$adapt->getElementsByTagName('Representation');
    for($i=0;$i<$reps->length;$i++){
        if($width==NULL && $reps->item($i)->getAttribute('width')==NULL)
            fwrite($mpdreport, "###'HbbTV check violated: Section E.2.3 - The profile-specific MPD shall provide @width information for all Representations', but not found for Representation ".($i+1)." of Adaptation Set ".$adapt_num." in Period ".$period_num." \n");
        if($height==NULL && $reps->item($i)->getAttribute('height')==NULL)
            fwrite($mpdreport, "###'HbbTV check violated: Section E.2.3 - The profile-specific MPD shall provide @height information for all Representations', but not found for Representation ".($i+1)." of Adaptation Set ".$adapt_num." in Period ".$period_num." \n");
        if($frameRate==NULL && $reps->item($i)->getAttribute('frameRate')==NULL)
            fwrite($mpdreport, "###'HbbTV check violated: Section E.2.3 - The profile-specific MPD shall provide @frameRate information for all Representations', but not found for Representation ".($i+1)." of Adaptation Set ".$adapt_num." in Period ".$period_num." \n");
        if($scanType==NULL && $reps->item($i)->getAttribute('scanType')==NULL)
            fwrite($mpdreport, "###'HbbTV check violated: Section E.2.3 - The profile-specific MPD shall provide @scanType information for all Representations', but not found for Representation ".($i+1)." of Adaptation Set ".$adapt_num." in Period ".$period_num." \n");
        if($codecs==NULL && strpos($reps->item($i)->getAttribute('codecs'),'avc')===false)
            fwrite($mpdreport, "###'HbbTV check violated: Section E.2.1 - The video content referenced by MPD shall only be encoded using video codecs defined in 7.3.1 (AVC)', but '".($reps->item($i)->getAttribute('codecs'))."' found for Representation ".($i+1)." of Adaptation Set ".$adapt_num." in Period ".$period_num." \n");
    }
}

function HbbTV_AudioRepChecks($adapt, $adapt_num,$period_num,$mpdreport){
    $SamplingRate=$adapt->getAttribute('audioSamplingRate');
    $lang=$adapt->getAttribute('lang');
    $channelConfig_adapt=$adapt->getElementsByTagName('AudioChannelConfiguration');
    $reps=$adapt->getElementsByTagName('Representation');
    
    $role=$adapt->getElementsByTagName('Role');
    if($role->length>0)
        $roleValue=$role->item(0)->getAttribute('value');
    
    $accessibility=$adapt->getElementsByTagName('Accessibility');
    if($accessibility->length>0)
        $accessibilityValue=$accessibility->item(0)->getAttribute('value');
    
    $codecs_adapt=$adapt->getAttribute('codecs');
    if($codecs_adapt!=NULL && strpos($codecs_adapt, 'mp4a')===false && strpos($codecs_adapt, 'ec-3')===false)
        fwrite($mpdreport, "###'HbbTV check violated: Section E.2.1 - The audio content referenced by MPD shall only be encoded using video codecs defined in 7.3.1 (HE-AAC, E-AC-3)', but '".$codecs_adapt."' found in Adaptation Set ".$adapt_num." in Period ".$period_num." \n");

    for($i=0;$i<$reps->length;$i++){
        if($SamplingRate==NULL && $reps->item($i)->getAttribute('audioSamplingRate')==NULL)
            fwrite($mpdreport, "###'HbbTV check violated: Section E.2.3 - The profile-specific MPD shall provide @audioSamplingRate information for all Representations', but not found for Representation ".($i+1)." of Adaptation Set ".$adapt_num." in Period ".$period_num." \n");
        if($lang==NULL)
            fwrite($mpdreport, "###'HbbTV check violated: Section E.2.3 - The profile-specific MPD shall provide @lang information inherited by all Representations', but not found for Representation ".($i+1)." of Adaptation Set ".$adapt_num." in Period ".$period_num." \n");
        if($roleValue=="commentary" &&  $accessibilityValue==1 && $reps->item($i)->getAttribute('dependencyId')==NULL)
            fwrite($mpdreport, "###'HbbTV check violated: Section E.2.4 - For receiver mix audio description the associated audio stream shall use dependencyId ', but not found for Representation ".($i+1)." of Adaptation Set ".$adapt_num." in Period ".$period_num." \n");
        
        if($codecs_adapt==NULL){
            $codecs=$reps->item($i)->getAttribute('codecs');
            $temp=strpos($codecs, 'mp4a');
            if(strpos($codecs, 'mp4a')===false && strpos($codecs, 'ec-3')===false)
                fwrite($mpdreport, "###'HbbTV check violated: Section E.2.1 - The audio content referenced by MPD shall only be encoded using video codecs defined in 7.3.1 (HE-AAC, E-AC-3)', but '".$codecs."' found in Representation ".($i+1)." Adaptation Set ".$adapt_num." in Period ".$period_num." \n");
        }
        if($channelConfig_adapt->length==0){
            $channelConfig=$reps->item($i)->getElementsByTagName('AudioChannelConfiguration');
            if($channelConfig->length==0)
                fwrite($mpdreport, "###'HbbTV check violated: Section E.2.3 - The profile-specific MPD shall provide AudioChannelConfiguration for all Representations', but not found for Representation ".($i+1)." of Adaptation Set ".$adapt_num." in Period ".$period_num." \n");
            else
                HbbTV_AudioChannelCheck($channelConfig,($codecs_adapt.$codecs),$i, $adapt_num,$period_num,$mpdreport);
        }
        else
            HbbTV_AudioChannelCheck($channelConfig_adapt,($codecs_adapt.$codecs),$i, $adapt_num,$period_num,$mpdreport);
    }
}

function HbbTV_AudioChannelCheck($channelConfig,$codecs,$rep_num, $adapt_num,$period_num,$mpdreport){
    $scheme=$channelConfig->item(0)->getAttribute("schemeIdUri");
    $value=$channelConfig->item(0)->getAttribute("value");
    if(strpos($codecs,'mp4a')!==false){
        if(strpos($scheme,"urn:mpeg:dash:23003:3:audio_channel_configuration:2011")===false)
            fwrite($mpdreport, "###'HbbTV check violated: Section E.2.5 - For HE-AAC the Audio Channel Configuration shall use urn:mpeg:dash:23003:3:audio_channel_configuration:2011 schemeIdURI', but this schemeIdUri not found for Representation ".($rep_num+1)." of Adaptation Set ".$adapt_num." in Period ".$period_num." \n");

        if(!(is_numeric($value) && $value == round($value)))
            fwrite($mpdreport, "###'HbbTV check violated: Section E.2.5 - For HE-AAC the Audio Channel Configuration shall use urn:mpeg:dash:23003:3:audio_channel_configuration:2011 schemeIdURI with value set to an integer number', but non-integer value found for Representation ".($rep_num+1)." of Adaptation Set ".$adapt_num." in Period ".$period_num." \n");
    }
    else if (strpos($codecs,'ec-3')!==false){
        if((strpos($scheme,"tag:dolby.com,2014:dash:audio_channel_configuration:2011")===false && strpos($scheme,"urn:dolby:dash:audio_channel_configuration:2011")===false))
            fwrite($mpdreport, "###'HbbTV check violated: Section E.2.5 - For E-AC-3 the Audio Channel Configuration shall use either the tag:dolby.com,2014:dash:audio_channel_configuration:2011 or urn:dolby:dash:audio_channel_configuration:2011 schemeIdURI', but neither of these found for Representation ".($rep_num+1)." of Adaptation Set ".$adapt_num." in Period ".$period_num." \n");
        if(strlen($value)!=4 || !ctype_xdigit($value))
            fwrite($mpdreport, "###'HbbTV check violated: Section E.2.5 - For E-AC-3 the Audio Channel Configuration value shall contain a four digit hexadecimal number', but found value '".$value."' for Representation ".($rep_num+1)." of Adaptation Set ".$adapt_num." in Period ".$period_num." \n");
    }
}

function xlink_reconstruct_MPD($dom_MPD){
    global $reconstructed_MPD, $stop, $locate;
    global $onRequest_array, $xlink_not_valid_array;
    $onRequest_array = array(); //array to specify the period where the invalidation was found
    $xlink_not_valid_array = array();
    $stop = 0;
    $new_dom = new DOMDocument('1.0');
    $new_dom_node = $new_dom->importNode($dom_MPD, true);
    $new_dom->appendChild($new_dom_node);
    if($new_dom->getElementsByTagName('MPD')->length != 0) // only if the MPD element is found continue
        xlink_reconstruct_MPD_recursive($new_dom);
    //check the final MPD
    /*$reconstructed_MPD_st = $reconstructed_MPD->saveXML();
    $temp_file= fopen($locate ."/content_checker.txt", "w");
    fwrite($temp_file, $reconstructed_MPD_st);
    fclose($temp_file);*/  
}  

function xlink_reconstruct_MPD_recursive($dom_MPD){ //give $dom_sxe as argument when calling function
    global $onRequest_array, $xlink_not_valid_array;
    global $reconstructed_MPD, $stop; //we need the stop value to prohibit the recursion from modifing the MPD with the stack instructions after it has be reconstructed 
    
    $reconstructed_MPD = new DOMDocument('1.0');
    $reconstructed_MPD->preserveWhiteSpace = false;
    $reconstructed_MPD->formatOutput = true;
    $MPD = $dom_MPD->getElementsByTagName('MPD')->item(0);
    $reconstructed_node = $reconstructed_MPD->importNode($MPD, true);
    $reconstructed_MPD->appendChild($reconstructed_node);

    $element_name = array(); 
    foreach ($dom_MPD->getElementsByTagName('*') as $node){ // search for all nodes within mpd   
        $node_name = $node->nodeName;
        //$node_id = $node->getAttribute('id');
        $element_name[] = $node_name;
        $xlink=$node->getAttribute('xlink:href');
        if (($xlink != "") && ($stop === 0)){ //stop needed to stop the recursion from making further modifications after MPD is reconstructed fully
            $name_repetition = array_count_values($element_name);
            $index_for_modifications = $name_repetition[$node_name] - 1; //this will be the index for replacing and inserting the xlink nodes

            $actuate_mode = $node->getAttribute('xlink:actuate');

            if($actuate_mode === 'onRequest')// check if actuate mode is onRequest
                $onRequest_array[$index_for_modifications] = $node_name;

            //if you have a valid url then get the content even if it is onRequest
            $xlink_url = get_headers($xlink);
            if(!strpos($xlink_url[0], "200")){
                $xlink_not_valid_array[$index_for_modifications] = $node_name;
                $reconstructed_MPD->getElementsByTagName($node_name)->item($index_for_modifications)->parentNode->removeChild($reconstructed_MPD->getElementsByTagName($node_name)->item($index_for_modifications)); 
            }
            else {
                //get contents and turn them in xml format
                $xlink_content = file_get_contents($xlink);
                //proceed only if it didn't fail to get contents
                if ($xlink_content !== false){
                    $xlink_content = '<elements xmlns="urn:mpeg:dash:schema:mpd:2011" xmlns:xlink="http://www.w3.org/1999/xlink">'."\n".$xlink_content;
                    $xlink_content = $xlink_content."\n"."</elements>";
                    $xlink_content = simplexml_load_string($xlink_content);
                    //proceed only if conversion to xml is successful
                    if($xlink_content !== false){
                        $dom_xlink = dom_import_simplexml($xlink_content);
                        //proceed only if the conversion to dom element is successful
                        if($dom_xlink !== false){
                            $dom = new DOMDocument('1.0');
                            $dom_xlink = $dom->importNode($dom_xlink, true);
                            $dom->appendChild($dom_xlink);
                            $first_element_checker = 0; //the first element will be replaced with an existing one while the other will be just inserted after that
                            foreach ($dom->documentElement->childNodes as $dom_node){
                                if ($dom_node->nodeName === $node_name){
                                    $xlink = $dom_node->getAttribute('xlink:href');
                                    //first period is replaced with the one with the same index and others are just inserted after the first one
                                    $first_element_checker ++;
                                    $dom_node1 = $reconstructed_MPD->importNode($dom_node, true); //necessary to use replacechild or removechild
                                    if($first_element_checker === 1)
                                        $reconstructed_MPD->getElementsByTagName($node_name)->item($index_for_modifications)->parentNode->replaceChild($dom_node1, $reconstructed_MPD->getElementsByTagName($node_name)->item($index_for_modifications));
                                    else
                                        $reconstructed_MPD->getElementsByTagName($node_name)->item($index_for_modifications)->parentNode->insertBefore($dom_node1, $reconstructed_MPD->getElementsByTagName($node_name)->item($index_for_modifications)->nextSibling);  
                                }      
                            }
                            xlink_reconstruct_MPD_recursive($reconstructed_MPD);
                        }
                    }
                }
            }
        }       
    }         
    $stop = 1; // now don't do any more modifications to the MPD          
}

function DVB_mpd_anchor_check($mpdreport){
    global $mpd_dom;
    $allowed_keys = array('t', 'period', 'track', 'group');
    
    $anchors = explode('#', json_decode($_POST['urlcode'])[0]);
    if(sizeof($anchors) > 1){
        $periods = $mpd_dom->getElementsByTagName('Period');
        $period_ids = array();
        foreach($periods as $period){
            $adapts = $period->getElementsByTagName('AdaptationSet');
            $adapt_ids_groups = array();
            foreach($adapts as $adapt)
                $adapt_ids_groups[] = $adapt->getAttribute('id') . ',' . $adapt->getAttribute('group');
            
            $period_ids[] = array($period->getAttribute('id') => $adapt_ids_groups);
        }
        
        $period_exists = false;
        $t_exists = false;
        $posix_exits = false;
        $anchors = $anchors[1];
        $anchor_parts = explode('&', $anchors);
        foreach($anchor_parts as $anchor){
            $key = substr($anchor, 0, strpos($anchor, '='));
            $value = substr($anchor, strpos($anchor, '=')+1);
            if(!in_array($key, $allowed_keys))
                fwrite($mpdreport, "Information on HbbTV-DVB DASH Validation Requirements conformance for DVB: Section 'DVB DASH Specifics' - Provided MPD anchor key \"$key\" is not one of the keys listed in Table C.1 in clause C.4 in ISO/IEC 23009-1.\n");
            
            if($key == 'period'){
                $period_exists = true;
                $str_info = '';
                foreach($period_ids as $period_id){
                    if(array_key_exists($value, $period_id))
                        $str_info .= 'yes ';
                    else
                        $str_info .= 'no ';
                }
                
                if(strpos($str_info, 'yes') === FALSE)
                    fwrite($mpdreport, "Information on HbbTV-DVB DASH Validation Requirements conformance for DVB: Section 'DVB DASH Specifics' - Provided MPD anchor uses the key \"period\" but its value does not correspond to any of the period @id attributes.\n");
            }
            elseif($key == 'track' || $key == 'group'){
                $str_info_1 = '';
                $str_info_2 = '';
                foreach($period_ids as $period_id){
                    foreach($period_id as $adapt_id_group){
                        foreach($adapt_id_group as $adapt_id_group_temp){
                            $id_group = explode(',', $adapt_id_group_temp);
                            
                            if($key == 'track'){
                                if(strpos($id_group[0], $value) !== FALSE)
                                    $str_info_1 .= 'yes ';
                                else
                                    $str_info_1 .= 'no ';
                            }
                            elseif($key == 'group'){
                                if(strpos($id_group[1], $value) !== FALSE)
                                    $str_info_2 .= 'yes ';
                                else
                                    $str_info_2 .= 'no ';
                            }
                        }
                    }
                }
                if($key == 'track' && strpos($str_info_1, 'yes') === FALSE)
                    fwrite($mpdreport, "Information on HbbTV-DVB DASH Validation Requirements conformance for DVB: Section 'DVB DASH Specifics' - Provided MPD anchor uses the key \"track\" but its value does not correspond to any of the attribute @id attributes.\n");
                
                if($key == 'group' && strpos($str_info_2, 'yes') === FALSE)
                    fwrite($mpdreport, "Information on HbbTV-DVB DASH Validation Requirements conformance for DVB: Section 'DVB DASH Specifics' - Provided MPD anchor uses the key \"group\" but its value does not correspond to any of the attribute @group attributes.\n");
            }
            elseif($key == 't'){
                $t_exists = true;
                if(strpos($value, 'posix') !== FALSE){
                    $posix_exits = true;
                    if($mpd_dom->getAttribute('availabilityStartTime') == '')
                        fwrite($mpdreport, "Information on HbbTV-DVB DASH Validation Requirements conformance for DVB: Section 'DVB DASH Specifics' - Provided MPD anchor uses the key \"t\" with prefix \"posix\" while MPD@availabilityStartTime does not exist.\n");
                    
                    $time_range = explode(',', substr($value, strpos($value, 'posix')+6));
                    $t = compute_timerange($time_range, $mpdreport);
                }
                else{
                    if(strpos($value, 'npt') !== FALSE){
                        $time_range = explode(',', substr($value, strpos($value, 'npt')+4));
                        $t = compute_timerange($time_range, $mpdreport);
                    }
                    else{
                        $time_range = explode(',', $value);
                        $t = compute_timerange($time_range, $mpdreport);
                    }
                }
            }
        }
        
        if($period_exists && $t_exists)
            fwrite($mpdreport, "Information on HbbTV-DVB DASH Validation Requirements conformance for DVB: Section 'DVB DASH Specifics' - Provided MPD anchor uses the key \"t\" while the key \"period\" is also used.\n");
        
        if($t_exists){
            $periodDurations = period_duration_info();
            $p_starts = $periodDurations[0];
            $p_durations = $periodDurations[1];
            $coverage = false;
            
            if(!$posix_exits){      
                if($t[0] >= $p_starts[0]){
                    if(!($t[1] == PHP_INT_MAX)){
                        if($t[1] <= $p_starts[sizeof($p_durations)-1] + $p_durations[sizeof($p_durations)-1])
                            $coverage = true;
                    }
                    else
                        $coverage = true;
                }
            }
            else{
                $AST = strtotime ($mpd_dom->getAttribute('availabilityStartTime'));
                if($t[0] - $AST >= $p_starts[0]){
                    if(!($t[1] == PHP_INT_MAX)){
                        if($t[1] - $AST <= $p_starts[sizeof($p_durations)-1] + $p_durations[sizeof($p_durations)-1])
                            $coverage = true;
                    }
                    else
                        $coverage = true;
                }
            }
            
            if(!$coverage)
                fwrite($mpdreport, "Information on HbbTV-DVB DASH Validation Requirements conformance for DVB: Section 'DVB DASH Specifics' - Provided MPD anchor uses the key \"t\" which refers to a time that is not available according to the times in the MPD.\n");
        }
    }
}

function compute_timerange($time_range, $mpdreport){
    $first = 1;
    foreach($time_range as $timestamp){
        $t = 0;
        
        if($timestamp == ''){
            if(!$first)
                $t = PHP_INT_MAX;
        }
        elseif($timestamp == 'now'){
            $t = time();
        }
        elseif(strpos($timestamp, ':') !== FALSE){
            $vals = explode(':', $timestamp);
            
            if(sizeof($vals) > 3){
                fwrite($mpdreport, "Information on HbbTV-DVB DASH Validation Requirements conformance for DVB: Section 'DVB DASH Specifics' - Provided MPD anchor uses the key \"t\" with W3C Media Fragment format with \"npt\" but allowed formats are one of {npt-ss, npt-mmss, npt-hhmmss}.\n");
            }
            
            for($v=sizeof($vals)-1,$p=0; $v>0; $v--,$p++){
                $val = $vals[$v];
                
                if(strpos($val, '.')){
                    $val_vals = explode('.', $val);
                    $t += ($val_vals[0]*pow(60, $p) + ($val_vals[1]/10)*pow(60,$p));
                    
                    if(!((((string) (int) $val_vals[0] === $val_vals[0]) || (( '0' . (string) (int) $val_vals[0]) === $val_vals[0])) && ($val_vals[0] <= PHP_INT_MAX) && ($val_vals[0] >= ~PHP_INT_MAX)) || !((((string) (int) $val_vals[1] === $val_vals[1]) || (( '0' . (string) (int) $val_vals[1]) === $val_vals[1])) && ($val_vals[1] <= PHP_INT_MAX) && ($val_vals[1] >= ~PHP_INT_MAX)))
                        fwrite($mpdreport, "Information on HbbTV-DVB DASH Validation Requirements conformance for DVB: Section 'DVB DASH Specifics' - Provided MPD anchor uses the key \"t\" where the provided time range is not integer.\n");
                    if($p > 0)
                        fwrite($mpdreport, "Information on HbbTV-DVB DASH Validation Requirements conformance for DVB: Section 'DVB DASH Specifics' - Provided MPD anchor uses the key \"t\" with W3C Media Fragment format with \"npt\" but fraction notation is used for minutes and/or hours.\n");
                    if((sizeof($vals) < 3 || $v != sizeof($vals)-1) && ($val_vals[0] < 0 || $val_vals[0] > 59))
                        fwrite($mpdreport, "Information on HbbTV-DVB DASH Validation Requirements conformance for DVB: Section 'DVB DASH Specifics' - Provided MPD anchor uses the key \"t\" with W3C Media Fragment format with \"npt\" but the range for minutes and/or seconds is not in the range of [0,59].\n");
                }
                else{
                    $t += ($val*pow(60, $p));
                    
                    if((sizeof($vals) < 3 || $v != sizeof($vals)-1) && ($val < 0 || $val > 59))
                        fwrite($mpdreport, "Information on HbbTV-DVB DASH Validation Requirements conformance for DVB: Section 'DVB DASH Specifics' - Provided MPD anchor uses the key \"t\" with W3C Media Fragment format with \"npt\" but the range for minutes and/or seconds is not in the range of [0,59].\n");
                }
            }
        }
        elseif(strpos($timestamp, '.') !== FALSE){ // Fractional time
            $vals = explode('.', $timestamp);
            $t += $vals[0] + $vals[1]/10;
            
            if(!((((string) (int) $vals[0] === $vals[0]) || (( '0' . (string) (int) $vals[0]) === $vals[0])) && ($vals[0] <= PHP_INT_MAX) && ($vals[0] >= ~PHP_INT_MAX)) || !((((string) (int) $vals[1] === $vals[1]) || (( '0' . (string) (int) $vals[1]) === $vals[1])) && ($vals[1] <= PHP_INT_MAX) && ($vals[1] >= ~PHP_INT_MAX)))
                fwrite($mpdreport, "Information on HbbTV-DVB DASH Validation Requirements conformance for DVB: Section 'DVB DASH Specifics' - Provided MPD anchor uses the key \"t\" where the provided time range is not integer.\n");
        }
        else{
            $t += $timestamp;
            if(!(((string) (int) $timestamp === $timestamp) && ($timestamp <= PHP_INT_MAX) && ($timestamp >= ~PHP_INT_MAX)))
                fwrite($mpdreport, "Information on HbbTV-DVB DASH Validation Requirements conformance for DVB: Section 'DVB DASH Specifics' - Provided MPD anchor uses the key \"t\" where the provided time range is not integer.\n");
        }
        
        if($first){
            $first = 0;
            $t_start = $t;
        }
        else
            $t_end = $t;
    }
    
    if(sizeof($time_range) == 1)
        $t_end = PHP_INT_MAX;
    
    if($t_start > $t_end)
        fwrite($mpdreport, "Information on HbbTV-DVB DASH Validation Requirements conformance for DVB: Section 'DVB DASH Specifics' - Provided MPD anchor uses the key \"t\" but the start time is larger that the end time.\n");
    
    return [$t_start, $t_end];
}

function TLS_bitrate_check($mpdreport){
    global $session_dir, $mpd_dom, $locate;
    //test link https://media.axprod.net/TestVectors/v7-MultiDRM-SingleKey/Manifest.mpd
    if($mpd_dom->getElementsByTagName('BaseURL')->length !=0){
        $base_url = $mpd_dom->getElementsByTagName('BaseURL')->item(0)->textContent;
    }
    else{
        $base_url = '';
    }

    $MPD_url = $GLOBALS["url"];
    //check if TLS is used
    if (strpos($base_url, 'https') !== false){
        $TLS_falg = true; 
    }
    elseif (strpos($base_url, 'http') !== false){
        $TLS_falg = false;
    }
    else{
        if (strpos($MPD_url, 'https')!== false){
            $TLS_falg = true;
        }
        else{
            $TLS_falg = false;
        }
    }
    //if TLS is used then check if any combination excedes the constraint
    if($TLS_falg){
        $perio_index = 0;
        foreach ($mpd_dom->getElementsByTagName('Period') as $period){
            $period_id = $perio_index + 1;
            $video_rep_array = array();
            $audio_rep_array = array();
            $sub_rep_array = array();
            
            foreach ($period->getElementsByTagName('AdaptationSet') as $adaptation_set){
                foreach ($adaptation_set->getElementsByTagName('Representation') as $rep){
                    $rep_id = $rep->getAttribute('id');
                    $rep_BW = $rep->getAttribute('bandwidth');
                    $mimeType = $adaptation_set->getAttribute('mimeType');
                    if($mimeType == 'video/mp4'){
                        $video_rep_array[$rep_id] = $rep_BW;
                    }
                    elseif($mimeType == 'audio/mp4'){
                        $audio_rep_array[$rep_id] = $rep_BW;
                    }
                    elseif ($mimeType == 'application/mp4'){
                        $sub_rep_array[$rep_id] = $rep_BW;
                    }
                    elseif($mimeType == ''){
                        if($rep->getAttribute('mimeType') == 'video/mp4'){
                            $video_rep_array[$rep_id] = $rep_BW;
                        }
                        elseif ($rep->getAttribute('mimeType') == 'audio/mp4'){
                            $audio_rep_array[$rep_id] = $rep_BW;
                        }
                        elseif ($rep->getAttribute('mimeType') == 'application/mp4'){
                            $sub_rep_array[$rep_id] = $rep_BW;
                        }
                    }     
                }
            }
            //if the mpdreport.txt fails to be created or opened then skip the rest of the code
            foreach ($video_rep_array as $k_v => $v_BW){
                foreach ($audio_rep_array as $k_a => $a_BW){
                    foreach ($sub_rep_array as $k_s => $s_BW){
                        $total_BW = $s_BW + $a_BW + $v_BW;
                        if(($total_BW > 12000000) && ($total_BW <= 39000000)){
                            // 12 Mbit/s if the terminal does not support UHD video.
                            fwrite($mpdreport, "***Information on HbbTV-DVB DASH Validation Requirements for HbbTV: Section 'TLS' - Period ".$period_id." -> HbbTV TLS bitrate constraint violation Section 7.3.1.2 - If the terminal does not support UHD video the bitrate "
                                    . "should not exceed 12 Mbit/s.\n---The bandwidth sum of representations: ".$k_v.", ".$k_a.", ".$k_s." with the respective bandwidths: "
                                    . $v_BW." bps, ".$a_BW." bps, ".$s_BW." bps which amounts to a total of ".$total_BW." bps was found to violate this constraint.***\n");

                        }
                        elseif (($total_BW > 39000000) && ($total_BW <= 51000000)){
                            // 12 Mbit/s if the terminal does not support UHD video.
                            // 39 Mbit/s if the terminal does support UHD video but does not support HFR video. 
                            fwrite($mpdreport, "***Information on HbbTV-DVB DASH Validation Requirements for HbbTV: Section 'TLS' - Period ".$period_id." -> HbbTV TLS bitrate constraint violation Section 7.3.1.2 - If the terminal does support UHD video but does not support HFR video"
                                    . " the bitrate should not exceed 39 Mbit/s.\n---The bandwidth sum of representations: ".$k_v.", ".$k_a.", ".$k_s." with the respective bandwidths: "
                                    . $v_BW." bps, ".$a_BW." bps, ".$s_BW." bps which amounts to a total of ".$total_BW." bps was found to violate this constraint.***\n");
                        }
                        elseif($total_BW > 51000000){
                            // 12 Mbit/s if the terminal does not support UHD video.
                            // 39 Mbit/s if the terminal does support UHD video but does not support HFR video.
                            // 51 Mbit/s if the terminal supports UHD HFR video. 
                            fwrite($mpdreport, "***Information on HbbTV-DVB DASH Validation Requirements for HbbTV: Section 'TLS' - Period ".$period_id." -> HbbTV TLS bitrate constraint violation Section 7.3.1.2 - If the terminal supports UHD HFR video"
                                    . " the bitrate should not exceed 51 Mbit/s.\n---The bandwidth sum of representations: ".$k_v.", ".$k_a.", ".$k_s." with the respective bandwidths: "
                                    . $v_BW." bps, ".$a_BW." bps, ".$s_BW." bps which amounts to a total of ".$total_BW." bps was found to violate this constraint.***\n");
                        } 
                    }
                }
            }
            $perio_index ++;
        }
    }
}