<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

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