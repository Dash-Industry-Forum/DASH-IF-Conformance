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

function validate_segment($curr_adapt_dir, $dir_in_use, $period, $adaptation_set, $representation, $segment_url, $rep_dir_name, $is_subtitle_rep){
    global $sizearray, $current_adaptation_set, $current_representation, $progress_xml;
    
    $sizearray = array();
    $sizearray = download_data($dir_in_use, $segment_url, $is_subtitle_rep);
    if($sizearray != 0){
        ## Put segments in one file
        assemble($dir_in_use, $segment_url, $sizearray);
        
        ## Create config file with the flags for segment validation
        $config_file_loc = config_file_for_backend($period, $adaptation_set, $representation, $rep_dir_name);
        
        ## Run the backend
        $returncode = run_backend($config_file_loc);
        
        ## Analyse the results and report them
        $file_location = analyze_results($returncode, $curr_adapt_dir, $rep_dir_name);
    }
    else{
        ## Save to progress report that the representation does not exist
        $file_location[] = 'notexist';
        $progress_xml->Results[0]->Period[0]->Adaptation[$current_adaptation_set]->Representation[$current_representation] = "notexist";
    }
    
    return $file_location;
}

function validate_segment_hls($URL_array){
    global $session_dir, $hls_stream_inf_file, $hls_x_media_file, $hls_iframe_file, $hls_tag, $progress_xml, $progress_report;
    
    $tag_array = array($hls_stream_inf_file, $hls_iframe_file, $hls_x_media_file);
    for($i=0; $i<sizeof($URL_array); $i++){
        $progress_xml->Progress->CurrentAdapt = $tag_array[$i];
        $progress_xml->asXml(trim($session_dir . '/' . $progress_report));
        
        list($segmentURL, $sizeArray) = segmentDownload($URL_array[$i], $tag_array[$i]);
        
        for($j=0; $j<sizeof($segmentURL); $j++){
            if($sizeArray[$j] != 0){
                $hls_tag = $tag_array[$i] . '_' . $j;
                
                ## Put segments in one file
                assemble($session_dir.'/'.$tag_array[$i].'/'.$j.'/', $segmentURL[$j], $sizeArray[$j]);
                
                ## Create config file with the flags for segment validation
                $config_file_loc = config_file_for_backend(NULL, NULL, NULL, $hls_tag);
                
                ## Run the backend
                $returncode = run_backend($config_file_loc);
                
                ## Analyse the results and report them
                $file_location[] = analyze_results($returncode, $session_dir . '/' . $tag_array[$i], $j);
                
                ## Determine media type based on atomxml information
                determineMediaType($session_dir . '/' . $tag_array[$i] . '/' . $j . '.xml', $hls_tag);
            }
            else{
                ## Save to progress report that the representation does not exist
                $file_location[] = 'notexist';
                //$progress_xml->Results[0]->Period[0]->Adaptation[$current_adaptation_set]->Representation[$current_representation] = "notexist";
            }
        }
    }
    
    return $file_location;
}

function assemble($path, $segment_urls, $sizearr){
    global $session_dir, $segment_accesses, $reprsentation_template, $current_period, $current_adaptation_set, $current_representation, $hls_manifest, $hls_tag;
    
    $index = ($segment_accesses[$current_adaptation_set][$current_representation][0]['initialization']) ? 0 : 1;
    
    for ($i = 0; $i < sizeof($segment_urls); $i++){
        if(!$hls_manifest)
            $rep_dir = 'Period' . $current_period . '/' . str_replace(array('$AS$', '$R$'), array($current_adaptation_set, $current_representation), $reprsentation_template);
        else
            $rep_dir = $hls_tag;
        $fp1 = fopen($session_dir . '/' . $rep_dir . ".mp4", 'a+');
        
        $segment_name = basename($segment_urls[$i]);
        if (file_exists($path . $segment_name)){
            $size = $sizearr[$i]; // Get the real size of the file (passed as inupt for function)
            $file2 = file_get_contents($path . $segment_name); // Get the file contents

            fwrite($fp1, $file2); // dump it in the container file
            fclose($fp1);
            file_put_contents($session_dir . '/' . $rep_dir . ".txt", $index . " " . $size . "\n", FILE_APPEND); // add size to a text file to be passed to conformance software
            
            $index++; // iterate over all segments within the segments folder
        }
    }
}

function analyze_results($returncode, $curr_adapt_dir, $rep_dir_name){
    global $session_dir, $mpd_features, $stderr_file, $leafinfo_file, $reprsentation_info_log_template, $reprsentation_error_log_template,
            $string_info, $progress_report, $progress_xml, $current_period, $current_adaptation_set, $current_representation, $atominfo_file, $sample_data,
            $hls_manifest, $hls_tag, $hls_error_file, $hls_info_file;
    
    $adaptation_set = $mpd_features['Period'][$current_period]['AdaptationSet'][$current_adaptation_set];
    $representation = $adaptation_set['Representation'][$current_representation];
    if($returncode != 0){
        error_log('Processing AdaptationSet ' . $current_adaptation_set . ' Representation ' . $current_representation . ' returns: ' . $returncode);
        if (filesize($session_dir . '/' . $stderr_file) == 0){
            if(!$hls_manifest){
                if($adaptation_set['mimeType'] == 'application/ttml+xml' || $adaptation_set['mimeType'] == 'image/jpeg')
                    file_put_contents($session_dir . '/' . 'stderr.txt', "### error:  \n###        Failed to process Adaptation Set " . $current_adaptation_set . ', Representation ' . $current_representation . "!, as mimeType= '".$adaptation_set['mimeType']."' is not supported");
                elseif($representation['mimeType'] == "application/ttml+xml" || $representation['mimeType'] == "image/jpeg")
                    file_put_contents($session_dir . '/' . 'stderr.txt', "### error:  \n###        Failed to process Adaptation Set " . $current_adaptation_set . ', Representation ' . $current_representation . "!, as mimeType= '".$representation['mimeType']."' is not supported");
                else
                    file_put_contents($session_dir . '/' . 'stderr.txt', "### error:  \n###        Failed to process Adaptation Set " . $current_adaptation_set . ', Representation ' . $current_representation . '!');
            }
            else{
                $tag_array = explode('_', $hls_tag);
                $files = array_diff(scandir($session_dir . '/' . $tag_array[0] . '/' . $tag_array[1] . "/"), array('.', '..'));
                if(strpos($files[2], 'webvtt') !== FALSE || strpos($files[2], 'xml') !== FALSE || strpos($files[2], 'html') !== FALSE)
                    file_put_contents($session_dir . '/' . 'stderr.txt', "### error:  \n###        Failed to process " . $tag_array[0] . ' with index ' . $tag_array[1] . ', as the file type is ' . explode('.', $files[2])[1] . '!');
                else
                    file_put_contents($session_dir . '/' . 'stderr.txt', "### error:  \n###        Failed to process " . $tag_array[0] . ' with index ' . $tag_array[1] . '!');
            }
        }
    }

    // Rename the files from backend block and make them visible for UI
    if(!$hls_manifest)
        $info_log = 'Period' . $current_period . '/' . str_replace(array('$AS$', '$R$'), array($current_adaptation_set, $current_representation), $reprsentation_info_log_template);
    else
        $info_log = str_replace('$hls_tag$', $hls_tag, $hls_info_file);
    rename_file($session_dir . '/' . $leafinfo_file, $session_dir . '/' . $info_log . '.txt');
    $file_location[] = relative_path($session_dir . '/' . $info_log . '.html');

    if(!$hls_manifest){
        $error_log = 'Period' . $current_period . '/' . str_replace(array('$AS$', '$R$'), array($current_adaptation_set, $current_representation), $reprsentation_error_log_template);
        rename_file($session_dir . '/' . $stderr_file, $session_dir . '/' . $error_log . '.txt');
        $temp_string = str_replace(array('$Template$'), array($error_log), $string_info);
        file_put_contents($session_dir . '/' . $error_log . '.html', $temp_string);
    }
    else{
        $error_log = str_replace('$hls_tag$', $hls_tag, $hls_error_file);
        rename_file($session_dir . '/' . $stderr_file, $session_dir . '/' . $error_log . '.txt');
    }
    $file_location[] = relative_path($session_dir . '/' . $error_log . '.html');
    
    rename_file($session_dir . '/' . $atominfo_file, $curr_adapt_dir . '/' . $rep_dir_name . ".xml");

    if(file_exists($session_dir . '/' . $sample_data . '.txt') && !$hls_manifest)
        rename_file($session_dir . '/' . $sample_data . '.txt', $session_dir . '/' . 'Period' . $current_period . '/' . $rep_dir_name . $sample_data . '.xml');
    
    // Search for segment validation errors and save it to progress report
    $search = file_get_contents($session_dir . '/' . $error_log . '.txt');
    if (strpos($search, 'error') === false){
        if(!$hls_manifest)
            $progress_xml->Results[0]->Period[$current_period]->Adaptation[$current_adaptation_set]->Representation[$current_representation] = "noerror";
        $file_location[] = 'noerror';
    }
    else{
        if(!$hls_manifest)
            $progress_xml->Results[0]->Period[$current_period]->Adaptation[$current_adaptation_set]->Representation[$current_representation] = "error";
        $file_location[] = 'error'; //else notify client with error
    }
    
    if(!$hls_manifest){
        $progress_xml->Results[0]->Period[$current_period]->Adaptation[$current_adaptation_set]->Representation[$current_representation]->addAttribute('url', str_replace($_SERVER['DOCUMENT_ROOT'], 'http://' . $_SERVER['SERVER_NAME'], $session_dir . '/' . $error_log . '.txt'));
        $progress_xml->asXml(trim($session_dir . '/' . $progress_report));
    }
    return $file_location;
}

function run_backend($config_file){
    global $session_dir;
    
    ## Select the executable version
    ## Copy segment validation tool to session folder
    $validatemp4 = 'ValidateMP4.exe';
    $validatemp4_path = dirname(__FILE__) . "/../ISOSegmentValidator/public/linux/bin/";
    copy($validatemp4_path . $validatemp4, $session_dir . '/' . $validatemp4);
    chmod($session_dir . '/' . $validatemp4, 0777);

    ## Execute backend conformance software
    $command = $session_dir . '/' . $validatemp4 . " -logconsole -atomxml -configfile " . $config_file;
    $output = [];
    $returncode = 0;
    chdir($session_dir);
    exec($command, $output, $returncode);
    
    return $returncode;
}

function config_file_for_backend($period, $adaptation_set, $representation, $rep_dir_name){
    global $session_dir, $config_file, $additional_flags, $suppressatomlevel, $reprsentation_mdat_template, $current_period, $current_adaptation_set, $current_representation, $hls_manifest, $hls_mdat_file;
    
    if(!$hls_manifest){
        $file = open_file($session_dir . '/Period' . $current_period . '/' . $config_file, 'w');
        fwrite($file, $session_dir . '/Period' . $current_period . '/' . $rep_dir_name . '.mp4 ' . "\n");
        fwrite($file, "-infofile" . "\n");
        fwrite($file, $session_dir . '/Period' .$current_period. '/' . $rep_dir_name . '.txt' . "\n");
    }
    else{
        $file = open_file($session_dir . '/' . $config_file, 'w');
        fwrite($file, $session_dir . '/' . $rep_dir_name . '.mp4 ' . "\n");
        fwrite($file, "-infofile" . "\n");
        fwrite($file, $session_dir . '/' . $rep_dir_name . '.txt' . "\n");
    }
    
    if(!$hls_manifest)
        $offsetinfo = 'Period' . $current_period . '/' . str_replace(array('$AS$', '$R$'), array($current_adaptation_set, $current_representation), $reprsentation_mdat_template);
    else
        $offsetinfo = $rep_dir_name . '_' . $hls_mdat_file;
    
    fwrite($file, "-offsetinfo" . "\n");
    fwrite($file, $session_dir . '/' . $offsetinfo . '.txt' . "\n");
    
    $flags = (!$hls_manifest) ? construct_flags($period, $adaptation_set, $representation) . $additional_flags : $additional_flags;
    $piece = explode(" ", $flags);
    foreach ($piece as $pie)
        if ($pie !== "")
            fwrite($file, $pie . "\n");
    if($suppressatomlevel)
        fwrite($file, '-suppressatomlevel' . "\n");
    
    fclose($file);
    return (!$hls_manifest) ? ($session_dir . '/Period' . $current_period. '/' . $config_file) : ($session_dir . '/' . $config_file);
}