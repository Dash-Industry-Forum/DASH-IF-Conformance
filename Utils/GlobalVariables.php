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

# General variables
$main_dir = dirname(__DIR__) . '/Conformance-Frontend/';
$session_dir = (isset($_SESSION['locate'])) ? $_SESSION['locate'] : dirname(__DIR__) . '/Conformance-Frontend/temp';
$mpd_url = '';
$mpd_validation_only = 0;
$uploaded = false;
$current_adaptation_set = 0;
$current_representation = 0;
$period_timing_info = array();
$segment_accesses = array();
$sizearray = array();
$additional_flags = '';
$error_message = 1;
$warning_message = 1;
$info_message = 1;
$suppressatomlevel = 0;
$profileCommandLine=array();

# HLS variable
$hls_manifest = 0;
$hls_manifest_type = "";
$hls_stream_inf_file = 'StreamINF';
$hls_x_media_file = 'XMedia';
$hls_iframe_file = 'IFrameByteRange';
$hls_tag = '';
$hls_error_file = '$hls_tag$log';
$hls_info_file = '$hls_tag$_infofile';
$hls_mdat_file = 'mdatoffset';
$hls_current_index = 0;
$hls_media_types = array('video' => array(), 'iframe' => array(), 'audio' => array(), 'subtitle' => array(), 'unknown' => array());

# DASH-IF IOP variables

# DASH-IF IOP LL variables
$low_latency_cross_validation_file = '';
$inband_event_stream_info = array();
$utc_timing_info = array();
$service_description_info = array();
$availability_times = array();

# CMAF variables
$cmaf_mediaTypes;
$cmaf_mediaProfiles;
$compinfo_file = '';
$comparison_folder = '';
$presentation_infofile = '';
$selectionset_infofile = '';
$alignedswitching_infofile= '';

# HbbTV_DVB variables
$bitrate_script = '';
$segment_duration_script = '';
$subtitle_segments_location = '';
$hbbtv_dvb_crossvalidation_logfile = '';

# CTA WAVE variables
$CTApresentation_infofile = '';
$CTAselectionset_infofile = '';
$CTAspliceConstraitsLog = '';

# DASH schema version
$schema_url = '';

if (isset($_POST['urlcode'])){
    $url_array = json_decode($_POST['urlcode']);
    $mpd_url = $url_array[0];
    $_SESSION['url'] = $mpd_url;
    
    $mpd_validation_only = $url_array[1];
    $schema_url = $url_array[9];
}
if (isset($_POST['urlcodehls'])){
    $url_array = json_decode($_POST['urlcodehls']);
    $mpd_url = $url_array[0];
    $_SESSION['url'] = $mpd_url;
    
    
    $hls_manifest = 1;
    $main_dir = dirname(__DIR__) . '/Conformance-Frontend-HLS/';
    $session_dir = (isset($_SESSION['locate'])) ? $_SESSION['locate'] : dirname(__DIR__) . '/Conformance-Frontend-HLS/temp';
}
if (isset($_SESSION['url']))
    $mpd_url = $_SESSION['url'];
if (isset($_SESSION['foldername']))
    $foldername = $_SESSION['foldername'];
if (isset($_FILES['afile']['tmp_name'])){
    $_SESSION['fileContent'] = file_get_contents($_FILES['afile']['tmp_name']);
    $uploaded = true;
}

# Command line arguments
if(isset($_POST['url'])){
    $mpd_url = json_decode($_POST['url']);
    if(strpos($mpd_url, '.m3u8') !== FALSE){
        $hls_manifest = 1;
    }
}
if(isset($_POST['mpdonly'])){
    if($hls_manifest)
        echo "\n\n\033[".'0;34'."m"."The option 'mpdonly' can only be used for DASH manifests, ignoring for this test..."."\033[0m"."\n\n";
    else
        $mpd_validation_only = 1;
}
if(isset($_POST['schema'])){
    $schema_url = json_decode($_POST['schema']);
}
if (isset($_POST['noerror'])){
    $error_message = 0;
}
if (isset($_POST['nowarning'])){
    $warning_message = 0;
}
if (isset($_POST['noinfo'])){
    $info_message = 0;
}
if (isset($_POST['suppressatomlevel'])){
    $suppressatomlevel = 1;
}
if(isset($_POST['profile'])){
    $profileCommandLine = (array)json_decode($_POST['profile'],true);
}
# Important for Backend block
$command_file = 'command.txt';
$config_file = 'config_file.txt';
$stderr_file = 'stderr.txt';
$leafinfo_file = 'leafinfo.txt';
$atominfo_file = 'atominfo.xml';
$sample_data = 'sample_data';

# Important for reporting
$counter_file = '';
$counter_dir = $main_dir . 'visitorLogs';
$counter_write = TRUE;
$mpd_log = 'mpdreport';
$featurelist_log = 'featuresList.xml';
$featurelist_log_html = 'featuretable.html';
$progress_report = 'progress.xml';
$progress_xml = '';
$missinglink_file = 'missinglink';

$string_info = '<!doctype html> 
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Log detail</title>
  <style>
    table, th, td {
      border: 1px solid black;
      border-collapse: collapse;
    }
  </style>
  <p>***Legend: <font color="red">Errors</font>, <font color="orange">Warnings</font>, <font color="blue">Information</font> ***  </p>
</head>
<body>
    <div id="logtable">$Table$</div>
</body>
</html>';

$modules = array();
