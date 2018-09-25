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
$main_dir = dirname(__DIR__) . '/webfe/';
$session_dir = (isset($_SESSION['locate'])) ? $_SESSION['locate'] : dirname(__DIR__) . '/webfe/temp';
$mpd_dom;
$mpd_doc;
$mpd_url = '';
$mpd_features;
$mpd_validation_only = 0;
$uploaded = false;
$current_period = 0;
$current_adaptation_set = 0;
$current_representation = 0;
$period_timing_info = array();
$segment_accesses = array();
$profiles = array();
$sizearray = array();
$additional_flags = '';

# DASH-IF IOP variables
$dashif_conformance = false;

# CMAF variables
$cmaf_conformance = false;
$infofile_template = '';
$compinfo_file = '';
$comparison_folder = '';
$presentation_infofile = '';
$selectionset_infofile = '';
$alignedswitching_infofile= '';

# HbbTV_DVB variables
$hbbtv_conformance = false;
$dvb_conformance = false;
$bitrate_script = '';
$segment_duration_script = '';
$subtitle_segments_location = '';
$hbbtv_dvb_crossvalidation_logfile = '';

if (isset($_POST['urlcode'])){
    $url_array = json_decode($_POST['urlcode']);
    $mpd_url = $url_array[0];
    $_SESSION['url'] = $mpd_url;
    
    $mpd_validation_only = $url_array[2];
    $cmaf_conformance = ($url_array[4] == 'yes') ? true : false;
    $dvb_conformance = $url_array[5];
    $hbbtv_conformance = $url_array[6];
    $dashif_conformance=$url_array[7];
}
if (isset($_SESSION['url']))
    $mpd_url = $_SESSION['url'];
if (isset($_SESSION['foldername']))
    $foldername = $_SESSION['foldername'];
if (isset($_FILES['afile']['tmp_name'])){
    $_SESSION['fileContent'] = file_get_contents($_FILES['afile']['tmp_name']);
    $uploaded = true;
}

# Important for Backend block
$command_file = 'command.txt';
$config_file = 'config_file.txt';
$stderr_file = 'stderr.txt';
$leafinfo_file = 'leafinfo.txt';
$atominfo_file = 'atominfo.xml';
$sample_data = 'sample_data';

# Important for reporting
$counter_name = dirname(__DIR__) . '/DASH/counter.txt';
$mpd_log = 'mpdreport';
$featurelist_log = 'featuresList.xml';
$featurelist_log_html = 'featuretable.html';
$progress_report = 'progress.xml';
$progress_xml = '';
$missinglink_file = 'missinglink';

$adaptation_set_template = 'Adapt$AS$';
$adaptation_set_error_log_template = $adaptation_set_template . '_CrossInfofile';

$reprsentation_template = 'Adapt$AS$rep$R$';
$reprsentation_error_log_template = $reprsentation_template . 'log';
$reprsentation_info_log_template = $reprsentation_template . '_infofile';
$reprsentation_index_template = $reprsentation_template . '.txt';
$reprsentation_mdat_template = $reprsentation_template . 'mdatoffset';

$string_info = '<!doctype html> 
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Log detail</title>
  <style>
  #info {
    color: blue;
    margin: 8px;
  }
  #warning {
    color: orange;
    margin: 8px;
  }
  #error {
    color: red;
    margin: 8px;
  }
  </style>
  <script src="https://code.jquery.com/jquery-1.9.1.js"></script>
  <p>***Legend: <font color="red">Errors</font>, <font color="orange">Warnings</font>, <font color="blue">Information</font> ***  </p>
</head>
<body>
 
<p id="init">Processing...</p>
<p id="info"></p>
<p id="warning"></p>
<p id="error"></p>
 
<script>
window.onload = tester;

function tester(){
var url = document.URL.split("/");
var newPathname = url[0];
var loc = window.location.pathname.split("/");
for ( i = 1; i < url.length-4; i++ ) {
  newPathname += "/";
  newPathname += url[i];
}
var location = newPathname+"/Utils/Give.php";
$.post (location,
{val:loc[loc.length-2]+"/$Template$"},
function(result){
$( "#init" ).remove();
resultant=JSON.parse(result);
var end0 = "";
var end1 = "";
var end2 = "";
for(var i =0;i<resultant.length;i++)
{

resultant[i]=resultant[i]+"<br />";
var Warning=resultant[i].search("Warning") ;
var WARNING=resultant[i].search("WARNING");
var errorFound=resultant[i].search("###");
var cmafError=resultant[i].search("CMAF check violated");

if(Warning===-1 && WARNING===-1 && errorFound===-1 && cmafError===-1){
end0 = end0+" "+resultant[i];
$( "#info" ).html( end0);
}
else if(errorFound===-1 && cmafError===-1){
    end1 = end1+" "+resultant[i];
    $( "#warning" ).html( end1);
}
else{
    end2 = end2+" "+resultant[i];
    $( "#error" ).html( end2);
}

}
});

}
</script>
 
</body>
</html>';

# Initialize CMAF and/or HbbTV_DVB only when it is requested
if($cmaf_conformance){
    include '../CMAF/CMAFInitialization.php';
}
if($hbbtv_conformance || $dvb_conformance){
    include '../HbbTV_DVB/HbbTV_DVB_Initialization.php';
}