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

function process_MPD(){
    global $session_dir, $mpd_url, $mpd_dom, $mpd_features, $mpd_validation_only, $uploaded,                 // Client block input
            $current_period, $current_adaptation_set, $current_representation, $profiles,                    // MPD process data
            $progress_report, $progress_xml, $reprsentation_template, $adaptation_set_template, $mpd_log,    // Reporting
            $additional_flags,
            $dashif_conformance, $iop_function_name, $iop_when_to_call,                                      // DASH-IF IOP data
            $cmaf_conformance, $cmaf_function_name, $cmaf_when_to_call,                                      // CMAF data
            $hbbtv_conformance, $dvb_conformance, $hbbtv_dvb_function_name, $hbbtv_dvb_when_to_call,         // HbbTV-DVB data
            $ctawave_conformance, $ctawave_function_name, $ctawave_when_to_call,                             // CTA WAVE data
            $low_latency_dashif_conformance, $low_latency_function_name, $low_latency_when_to_call;          // Low Latency DASH-IF data

    ## Open related files
    $progress_xml = simplexml_load_string('<root><Profile></Profile><PeriodCount></PeriodCount><Progress><percent>0</percent><dataProcessed>0</dataProcessed><dataDownloaded>0</dataDownloaded><CurrentPeriod>1</CurrentPeriod><CurrentAdapt>1</CurrentAdapt><CurrentRep>1</CurrentRep></Progress><completed>false</completed></root>');
    $progress_xml->asXml($session_dir . '/' . $progress_report);
    color_code_information();

    ## Load MPD to DOM XML
    if($uploaded){ // If MPD is uploaded, set the mpd_url to local path
        file_put_contents($session_dir . '/uploaded.mpd', $_SESSION['fileContent']);
        $mpd_url = $session_dir . '/uploaded.mpd';
        $GLOBALS["mpd_url"] = $session_dir . '/uploaded.mpd';
    }

    $mpd_dom = mpd_load();
    if(!$mpd_dom){
        $progress_xml->MPDError = "1";
        $progress_xml->asXml($session_dir . '/' . $progress_report);
        writeMPDStatus($mpd_url."MPD with error");
        die("Error: Failed loading XML file\n");
    }
    else{
        $progress_xml->MPDError = "0";
        $progress_xml->asXml($session_dir . '/' . $progress_report);
    }

    writeMPDStatus($mpd_url);

    ## Check if dashif conformance is desired or if its profiles are in the MPD
    if(!$dashif_conformance) {
        if(strpos($mpd_dom->getAttribute('profiles'), 'http://dashif.org/guidelines/dash') !== FALSE){
            $dashif_conformance = 1;
            include '../DASH/IOP/IOP_Initialization.php';
        }
    }
    ## Check if low latency dashif conformance is desired or if its profiles are in the MPD
    if(!$low_latency_dashif_conformance){
        if(strpos($mpd_dom->getAttribute('profiles'), 'http://www.dashif.org/guidelines/low-latency-live-v5') !== FALSE){
            $low_latency_dashif_conformance = 1;
            include '../DASH/LowLatency/LowLatency_Initialization.php';
        }
    }
    ## Check if hbbtv-dvb conformance is desired or if their profiles are in the MPD
    ## If yes, call HbbTV_DVB_Handle for Before-MPD validation
    if(!$hbbtv_conformance){
        if(strpos($mpd_dom->getAttribute('profiles'), 'urn:hbbtv:dash:profile:isoff-live:2012') !== FALSE){
            $hbbtv_conformance = 1;
            if(!$dvb_conformance)
                include '../HbbTV_DVB/HbbTV_DVB_Initialization.php';
        }
    }
    if(!$dvb_conformance){
        if(strpos($mpd_dom->getAttribute('profiles'), 'urn:dvb:dash:profile:dvb-dash:2014') !== FALSE || strpos($mpd_dom->getAttribute('profiles'), 'urn:dvb:dash:profile:dvb-dash:isoff-ext-live:2014')!== FALSE || strpos($mpd_dom->getAttribute('profiles'), 'urn:dvb:dash:profile:dvb-dash:isoff-ext-on-demand:2014') !== FALSE){
            $dvb_conformance = 1;
            if(!$hbbtv_conformance)
                include '../HbbTV_DVB/HbbTV_DVB_Initialization.php';
        }
    }
    json_encode("DVB/HbbTV: $dvb_conformance $hbbtv_conformance");
    if($hbbtv_conformance || $dvb_conformance)
        $return_val = $hbbtv_dvb_function_name($hbbtv_dvb_when_to_call[0]);

    ## Get MPD features into an array
    $mpd_features = MPD_features($mpd_dom);
    $profiles = derive_profiles();
    writeProfiles();
    $progress_xml->Profile = $mpd_features['profiles'];
    $progress_xml->asXml(trim($session_dir . '/' . $progress_report));

    //------------------------------------------------------------------------//
    ## Perform MPD Validation
    ## Write to MPD report
    ## If only MPD validation is requested or inferred, exit
    ## If any error is found in the MPD validation process, exit
    ## If no error is found, then proceed with segment validation below
    $valid_mpd = validate_MPD();
    $result_for_json[] = $valid_mpd[1];

    ## If DASH-IF IOP conformance, call DASH-IF IOP MPD validation
    $return_dashif = '';
    if($dashif_conformance)
        $return_dashif = $iop_function_name($iop_when_to_call[0]);
    ## If Low Latency DASH-IF cnformance, call MPD validation for low latency
    $returnValue = '';
    if($low_latency_dashif_conformance){
        $returnValue = $low_latency_function_name($low_latency_when_to_call[0]);
    }
    ## If HbbTV_DVB_Handle, call HbbTV_DVB_Handle for MPD validation
    $return_val = '';
    if($hbbtv_conformance || $dvb_conformance)
        $return_val = $hbbtv_dvb_function_name($hbbtv_dvb_when_to_call[1]);

    MPD_report($valid_mpd[1] . $return_dashif . $returnValue . $return_val);
    writeMPDEndTime();
    print_console($session_dir.'/'.$mpd_log.'.txt', "MPD Validation Results");
    tabulateResults($session_dir . '/' . $mpd_log . '.txt', 'MPD');

    if($uploaded){ // Check if absolute URL is provided in the uploaded MPD for segment fetching.
                   // Otherwise, only the mpd validation will be performed.
        $Baseurl_abs = $mpd_dom->getElementsByTagName('BaseURL');
        if ($Baseurl_abs->length > 0){
            $Baseurl_abs = $Baseurl_abs->item(0);
            $absolute = $Baseurl_abs->nodeValue;
            if (($absolute === './') || (strpos($absolute, 'http') === false)){
                $mpd_validation_only = 1;
            }
        }
        else
            $mpd_validation_only = 1;
    }

    if(!$valid_mpd[0] || $mpd_validation_only){
        if($mpd_dom->getAttribute('type') == 'dynamic')
            $result_for_json[] = explode('/', $session_dir)[sizeof(explode('/', $session_dir))-1];

        json_encode($valid_mpd[1]);
        $progress_xml->completed = "true";
        $progress_xml->completed->addAttribute('time', time());
        $progress_xml->asXml(trim($session_dir . '/progress.xml'));
        writeEndTime((int)$progress_xml->completed->attributes());
        session_close();
        exit;
    }
    //------------------------------------------------------------------------//

    ## Save information on the current period structure to progress report
    if($mpd_features['type'] == 'dynamic'){
        if($mpd_dom->getElementsByTagName('SegmentTemplate')->length == 0)
            $result_for_json[] = explode('/', $session_dir)[sizeof(explode('/', $session_dir))-1];
        $result_for_json[] = "dynamic";
        json_encode($result_for_json);
    }

    $ResultXML = $progress_xml->addChild('Results');
    for ($i1 = 0; $i1 < sizeof($mpd_features['Period']); $i1++){
        $PeriodXML = $ResultXML->addChild('Period');
        for ($j1 = 0; $j1 < sizeof($mpd_features['Period'][$i1]['AdaptationSet']); $j1++){
            $AdaptationXML = $PeriodXML->addChild('Adaptation');
            for ($k1 = 0; $k1 < sizeof($mpd_features['Period'][$i1]['AdaptationSet'][$j1]['Representation']); $k1++){
                $RepXML = $AdaptationXML->addChild('Representation');
                $RepXML->addAttribute('id', $k1 + 1);
            }
        }

        if($mpd_features['type'] === 'dynamic')
            break;

        $current_period++;
    }
    $progress_xml->asXml(trim($session_dir . '/' . $progress_report));
    $result_for_json[] = sizeof($mpd_features['Period']);
    $result_for_json[] = explode('/', $session_dir)[sizeof(explode('/', $session_dir))-1];
    json_encode($result_for_json);

    //------------------------------------------------------------------------//
    ## Perform Segment Validation for each representation in each adaptation set within the current period
    check_before_segment_validation($result_for_json);
    if($mpd_features['type'] !== 'dynamic') $current_period = 0;
    while($current_period < sizeof($mpd_features['Period']))
    {
        $period_info = current_period();
        $urls = process_base_url();
        $segment_urls = derive_segment_URLs($urls, $period_info);
 
        $period_dir_name = "Period".$current_period;
        $curr_period_dir = $session_dir . '/' . $period_dir_name;
        create_folder_in_session($curr_period_dir);

        $progress_xml->Progress->CurrentPeriod = $current_period + 1;
        $progress_xml->asXml(trim($session_dir . '/' . $progress_report));

        $period = $mpd_features['Period'][$current_period];
        processAdaptationSetOfCurrentPeriod($period,$curr_period_dir,$ResultXML,$segment_urls);

        if($mpd_features['type'] === 'dynamic')
            break;

        $current_period++;
    }
    if($ctawave_conformance && $current_period>=1)
        $return_arr = $ctawave_function_name($ctawave_when_to_call[2]);

    session_close();
    $send_string = json_encode($file_error);
    #error_log("ReturnFinish:" . $send_string);
    $progress_xml->completed = "true";
    $progress_xml->completed->addAttribute('time', time());
    $progress_xml->asXml(trim($session_dir . '/' . $progress_report));
    writeEndTime((int)$progress_xml->completed->attributes());
    exit;
}

function processAdaptationSetOfCurrentPeriod($period,$curr_period_dir,$ResultXML,$segment_urls) {
   global  $mpd_features, $current_period, $current_adaptation_set, $adaptation_set_template,$current_representation,$reprsentation_template,$session_dir,
           $progress_xml, $progress_report, $reprsentation_error_log_template, $additional_flags,
           $dashif_conformance, $iop_function_name, $iop_when_to_call,                                      // DASH-IF IOP data
           $cmaf_conformance, $cmaf_function_name, $cmaf_when_to_call,                                      // CMAF data
           $hbbtv_conformance, $dvb_conformance, $hbbtv_dvb_function_name, $hbbtv_dvb_when_to_call,         // HbbTV-DVB data
           $ctawave_conformance, $ctawave_function_name, $ctawave_when_to_call,                             // CTA WAVE data
           $low_latency_dashif_conformance, $low_latency_function_name, $low_latency_when_to_call;          // Low Latency DASH-IF data

    $adaptation_sets = $period['AdaptationSet'];
    while($current_adaptation_set < sizeof($adaptation_sets)){
        $adaptation_set = $adaptation_sets[$current_adaptation_set];
        $representations = $adaptation_set['Representation'];

        $adapt_dir_name = str_replace('$AS$', $current_adaptation_set, $adaptation_set_template);
        $curr_adapt_dir = $curr_period_dir . '/' . $adapt_dir_name . '/';
        create_folder_in_session($curr_adapt_dir);

        $progress_xml->Progress->CurrentAdapt = $current_adaptation_set + 1;
        $progress_xml->asXml(trim($session_dir . '/' . $progress_report));

        while($current_representation < sizeof($representations)){
            $representation = $representations[$current_representation];
            $segment_url = $segment_urls[$current_adaptation_set][$current_representation];

            $curr_rep_xml = $progress_xml->Results[0]->Period[($mpd_features['type'] !== 'dynamic') ? $current_period : 0]->Adaptation[$current_adaptation_set]->Representation[$current_representation];
            $curr_rep_xml->addAttribute('url', '{' . implode(',', $segment_url) . '}');

            $rep_dir_name = str_replace(array('$AS$', '$R$'), array($current_adaptation_set, $current_representation), $reprsentation_template);
            $curr_rep_dir = $curr_period_dir . '/' . $rep_dir_name . '/';
            create_folder_in_session($curr_rep_dir);

            $progress_xml->Progress->CurrentRep = $current_representation + 1;
            $progress_xml->asXml(trim($session_dir . '/' . $progress_report));

            $additional_flags = '';
            if($cmaf_conformance)
                $return_val = $cmaf_function_name($cmaf_when_to_call[0]);
            if($hbbtv_conformance || $dvb_conformance)
                $is_subtitle_rep = $hbbtv_dvb_function_name($hbbtv_dvb_when_to_call[2]);
            if($ctawave_conformance)
                $return_cta = $ctawave_function_name($ctawave_when_to_call[0]);

            $return_seg_val = validate_segment($curr_adapt_dir, $curr_rep_dir, $period, $adaptation_set, $representation, $segment_url, $rep_dir_name, $is_subtitle_rep);
            $codecs = ($adaptation_set['codecs'] == NULL) ? $representation['codecs'] : $adaptation_set['codecs'];
            $is_dolby = (($codecs != NULL) and ((substr($codecs, 0, 4) == "ac-3") or (substr($codecs, 0, 4) == "ec-3") or (substr($codecs, 0, 4) == "ac-4")));
            if($is_dolby)
                ValidateDolby($adaptation_set, $representation);
            if($dashif_conformance)
                $return_seg_val[] = $iop_function_name($iop_when_to_call[1]);
            if($cmaf_conformance)
                $return_seg_val[] = $cmaf_function_name($cmaf_when_to_call[1]);
            if($hbbtv_conformance || $dvb_conformance)
                $return_seg_val[] = $hbbtv_dvb_function_name($hbbtv_dvb_when_to_call[3]);

            ## Report to client
            $send_string = json_encode($return_seg_val);
            #error_log('RepresentationDownloaded_Return:' . $send_string);

            err_file_op(1);
            print_console(dirname(__DIR__) . '/' . explode('.', $return_seg_val[1])[0] . '.txt', "Period " . ($current_period+1) . " Adaptation Set " . ($current_adaptation_set+1) . " Representation " . ($current_representation+1) . " Results");
            $segment_log = 'Period' . $current_period . '/' . str_replace(array('$AS$', '$R$'), array($current_adaptation_set, $current_representation), $reprsentation_error_log_template);
            tabulateResults($session_dir . '/' . $segment_log . '.txt', 'Segment');
            
            $current_representation++;
        }

        ## Representations in current Adaptation Set finished
        crossRepresentationProcess();
        if($hbbtv_conformance || $dvb_conformance)
            $return_seg_val[] = $hbbtv_dvb_function_name($hbbtv_dvb_when_to_call[4]);
        if($cmaf_conformance)
            $return_arr = $cmaf_function_name($cmaf_when_to_call[2]);

        $current_representation = 0;
        $current_adaptation_set++;
    }

    ## Adaptation Sets in current Period finished
    $file_error = adapt_result($ResultXML);
    if($dashif_conformance)
        $iop_function_name($iop_when_to_call[2]);
    if($cmaf_conformance){
        $return_arr = $cmaf_function_name($cmaf_when_to_call[3]);
        foreach($return_arr as $return_item)
            $file_error[] = $return_item;
    }
    if($hbbtv_conformance || $dvb_conformance)
        $return_arr = $hbbtv_dvb_function_name($hbbtv_dvb_when_to_call[5]);
    if($ctawave_conformance)
        $return_arr = $ctawave_function_name($ctawave_when_to_call[1]);
    if($low_latency_dashif_conformance)
        $return_arr = $low_latency_function_name($low_latency_when_to_call[1]);

    err_file_op(2);
    $current_adaptation_set = 0;
}

function check_before_segment_validation($result_for_json) {
    global $session_dir, $mpd_features, $mpd_dom, $progress_report, $progress_xml;

    $progress_xml->dynamic = ($mpd_features['type'] == 'dynamic') ? 'true' : 'false';
    $progress_xml->PeriodCount = sizeof($mpd_features['Period']);
    $progress_xml->asXml(trim($session_dir . '/' . $progress_report));

    $supplemental=$mpd_dom->getElementsByTagName('SupplementalProperty');
    if($supplemental->length >0){
        $supplementalScheme=$supplemental->item(0)->getAttribute('schemeIdUri');
        if(($supplementalScheme === 'urn:mpeg:dash:chaining:2016') || ($supplementalScheme ==='urn:mpeg:dash:fallback:2016')){
            $MPDChainingURL=$supplemental->item(0)->getAttribute('value');
            $progress_xml->MPDChainingURL=$MPDChainingURL;
            $progress_xml->asXml(trim($session_dir . '/' . $progress_report));
        }
    }

    if ($mpd_dom->getElementsByTagName('SegmentList')->length !== 0){
        $progress_xml->segmentList = "true";
        json_encode($result_for_json);
        session_close();
        $progress_xml->completed = "true";
        $progress_xml->completed->addAttribute('time', time());
        $progress_xml->asXml(trim($session_dir . '/' . $progress_report));
        writeEndTime((int)$progress_xml->completed->attributes());
        exit;
    }
}

function adapt_result($ResultXML){
    global $session_dir, $current_period, $missinglink_file, $progress_xml, $progress_report;

    $file_error[] = "done";

    $missingexist = file_exists($session_dir . '/Period' . $current_period . '/' . $missinglink_file . '.txt');
    if($missingexist){
        tabulateResults($session_dir . '/Period' . $current_period . '/' . $missinglink_file . '.txt', 'Missing');
        $ResultXML->Period[$current_period]->addChild('BrokenURL', "error");
        $ResultXML->Period[$current_period]->BrokenURL->addAttribute('url', str_replace($_SERVER['DOCUMENT_ROOT'], 'http://' . $_SERVER['SERVER_NAME'], $session_dir . '/' . $missinglink_file . '.txt'));
        $file_error[] = relative_path($session_dir . '/Period' . $current_period . '/' . $missinglink_file . '.html');
    }
    else{
        $ResultXML->Period[$current_period]->addChild('BrokenURL', "noerror");
        $file_error[] = "noerror";
    }
    $progress_xml->asXml(trim($session_dir . '/' . $progress_report));
    
    return $file_error;
}
