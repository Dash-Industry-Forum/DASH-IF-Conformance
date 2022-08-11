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

function validate_MPD(){
    global $main_dir, $mpd_dom, $mpd_url, $session_dir, $mpd_log, $featurelist_log_html, $mpd_xml, $mpd_xml_string, $mpd_xml_report;
    $schematronIssuesReport = NULL;

    $mpd_xml = simplexml_load_string($mpd_xml_string);
    $mpd_xml->asXml($session_dir . '/' . $mpd_xml_report);
    if(!$mpd_xml) {
        exit;
    }

    chdir('DASH/mpdvalidator');
    $dash_schema_location = download_schema();
    $mpdvalidator = syscall('java -cp "saxon9he.jar:xercesImpl.jar:bin" Validator ' . '"' . explode('#', $mpd_url)[0] . '"' . " " . "$session_dir" . "/resolved.xml $dash_schema_location $session_dir/$mpd_xml_report");
    $result = extract_relevant_text($mpdvalidator);
    delete_schema($dash_schema_location);

    ## Generate mpd report
    $mpdreport = fopen($session_dir . '/' . $mpd_log . '.txt', 'a+b');
    fwrite($mpdreport, $result);

    ## Check the PASS/FAIL status
    $exit = false;
    $string = '';
    $mpd_rep_loc = 'temp/' . basename($session_dir) . '/' . $mpd_log . '.html';
    $mpd_xml = simplexml_load_file($session_dir.'/'.$mpd_xml_report);
    if (!is_valid($mpdvalidator, 'XLink resolving successful')){ $string .= $mpd_rep_loc; $exit = true; $mpd_xml->xlink = "error"; $mpd_xml->schema = "error"; $mpd_xml->schematron = "error"; $mpd_xml->asXml($session_dir . '/' . $mpd_xml_report); }
    else { $string .= 'true '; }

    if(!is_valid($mpdvalidator, 'MPD validation successful')){ $string .= $mpd_rep_loc; $exit = true; }
    else { $string .= 'true '; }

    if(!is_valid($mpdvalidator, 'Schematron validation successful')){ $string .= $mpd_rep_loc; $exit = true;  }
    else { $string .= 'true '; }

    ## Featurelist generate
    if(!is_valid($mpdvalidator, 'Schematron validation successful'))
        $schematronIssuesReport = analyzeSchematronIssues($mpdvalidator);
    #copy($main_dir . "/" . $featurelist_log_html, $session_dir . '/' . $featurelist_log_html);
    createMpdFeatureList($mpd_dom, $schematronIssuesReport); 
    convertToHtml();

    chdir('../');
    return array(!$exit, $string);
}

function download_schema(){
    global $session_id, $schema_url, $dvb_conformance, $dvb_conformance_2018, $dvb_conformance_2019, $low_latency_dashif_conformance;
    
    $default_schema_loc = 'schemas/DASH-MPD.xsd';
    
    if($dvb_conformance && $dvb_conformance_2018) {
        $default_schema_loc = 'schemas/DASH-MPD-2nd.xsd';
    }
    elseif($dvb_conformance && $dvb_conformance_2019) {
        $default_schema_loc = 'schemas/DASH-MPD-4th-amd1.xsd';
    }
    elseif($low_latency_dashif_conformance) {
        $default_schema_loc = 'schemas/DASH-MPD-4th-amd1.xsd';
    }
    
    if($schema_url == '') {
        return $default_schema_loc;
    }
    if(pathinfo($schema_url, PATHINFO_EXTENSION) != 'xsd'){
        return $default_schema_loc;
    }
    
    $saveTo = "schemas/DASH-MPD_CustomSchema_$session_id.xsd";
    $fp = fopen($saveTo, 'w+');
    if($fp === FALSE){
        return NULL;
    }

    $ch = curl_init($schema_url);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    
    curl_exec($ch);

    if(curl_errno($ch)){
        return NULL;
    }

    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fp);

    if($statusCode == 200){
        //
    } else{
        delete_schema($saveTo);
        return $default_schema_loc;
    }
    
    chmod($saveTo, 0777);
    return $saveTo;
}

function delete_schema($dash_schema_location){
    global $session_id;
    if($dash_schema_location === "schemas/DASH-MPD_CustomSchema_$session_id.xsd")
        shell_exec("sudo rm $dash_schema_location");
}

function extract_relevant_text($result){
    $needle = 'Start XLink resolving';
    $temp_result = str_replace('[java]', "", $result);

    return substr($temp_result, strpos($temp_result, $needle));
}

function is_valid($haystack, $needle){
    return strpos($haystack, $needle) !== FALSE;
}

function MPD_report($string){
    global $session_dir, $progress_report, $progress_xml, $mpd_url, $mpd_log, $string_info;
    ## Save results to progress report
    $progress_xml->MPDConformance = $string;
    $progress_xml->MPDConformance->addAttribute('url', str_replace($_SERVER['DOCUMENT_ROOT'], 'http://' . $_SERVER['SERVER_NAME'], $session_dir . '/' . $mpd_log . '.txt'));
    $progress_xml->MPDConformance->addAttribute('MPDLocation', $mpd_url);
    $progress_xml->asXml(trim($session_dir . '/' . $progress_report));

    ## Put the report in html
    $temp_string = str_replace(array('$Template$'), array("mpdreport"), $string_info);
    file_put_contents($session_dir . '/' . $mpd_log . '.html', $temp_string);
}
