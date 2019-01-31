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
$error_message = 1;
$warning_message = 1;
$info_message = 1;
$suppressatomlevel = 0;
$profileCommandLine='';

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

# CTA WAVE variables
$ctawave_conformance = false;
$CTApresentation_infofile = '';
$CTAselectionset_infofile = '';

if (isset($_POST['urlcode'])){
    $url_array = json_decode($_POST['urlcode']);
    $mpd_url = $url_array[0];
    $_SESSION['url'] = $mpd_url;
    
    $mpd_validation_only = $url_array[1];
    $cmaf_conformance = $url_array[2];
    $dvb_conformance = $url_array[3];
    $hbbtv_conformance = $url_array[4];
    $dashif_conformance=$url_array[5];
    $ctawave_conformance=$url_array[6];
}
if (isset($_POST['urlcodehls'])){
    $url_array = json_decode($_POST['urlcodehls']);
    $mpd_url = $url_array[0];
    $_SESSION['url'] = $mpd_url;
    
    $cmaf_conformance = $url_array[1];
    $ctawave_conformance=$url_array[2];
    
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
if(isset($_POST['cmaf'])){
    $cmaf_conformance = 1;
}
if(isset($_POST['dvb'])){
    if($hls_manifest)
        echo "\n\n\033[".'0;34'."m"."The option 'dvb' can only be used for DASH manifests, ignoring for this test..."."\033[0m"."\n\n";
    else
        $dvb_conformance = 1;
}
if(isset($_POST['hbbtv'])){
    if($hls_manifest)
        echo "\n\n\033[".'0;34'."m"."The option 'hbbtv' can only be used for DASH manifests, ignoring for this test..."."\033[0m"."\n\n";
    else
        $hbbtv_conformance = 1;
}
if(isset($_POST['dashif'])){
    if($hls_manifest)
        echo "\n\n\033[".'0;34'."m"."The option 'dashif' can only be used for DASH manifests, ignoring for this test..."."\033[0m"."\n\n";
    else
        $dashif_conformance = 1;
}
if(isset($_POST['ctawave'])){
    $ctawave_conformance = 1;
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
$counter_name = (!$hls_manifest) ? dirname(__DIR__) . '/DASH/counter.txt' : dirname(__DIR__) . '/HLS/counter.txt';
$mpd_log = 'mpdreport';
$featurelist_log = 'featuresList.xml';
$featurelist_log_html = 'featuretable.html';
$progress_report = 'progress.xml';
$progress_xml = '';
$missinglink_file = 'missinglink';
$mpd_xml_string = '<mpdresult><xlink>No Result</xlink><schema>No Result</schema><schematron>No Result</schematron></mpdresult>';
$mpd_xml = '';
$mpd_xml_report = 'mpdresult.xml';

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

<div id="mpddiv"></div>
 
<script>
window.onload = tester;

function tester(){
var url = document.URL.split("/");
var newPathname = url[0];
var loc = window.location.pathname.split("/");
var txtloc = "";
var txtloc_start_ind = (loc.indexOf("Conformance-Frontend") != -1) ? loc.indexOf("Conformance-Frontend") : loc.indexOf("Conformance-Frontend-HLS");
var txtlocuntil = (document.URL.search("mpdreport") !== -1) ? 1 : 2
var pathnameuntil = (document.URL.search("mpdreport") !== -1) ? 4 : 5

for ( j = txtloc_start_ind; j < loc.length-txtlocuntil; j++){
    txtloc += "/";
    txtloc += loc[j];
}

for ( i = 1; i < url.length-pathnameuntil; i++ ) {
  newPathname += "/";
  newPathname += url[i];
}
var location = newPathname+"/Utils/Give.php";
$.post (location,
{val:txtloc+"/$Template$"},
function(result){
$( "#init" ).remove();
resultant=JSON.parse(result);

var end0 = "";
var end1 = "";
var end2 = "";

if(document.URL.search("mpdreport") !== -1){
    var until = 0;
    var from = 0;
    var tempContent;
    var content = resultant.join("\n");
    
    while(1){
        from = content.indexOf("Start ", until);
        until = content.indexOf("Start ", from+1);
        
        tempContent = (until !== -1) ? content.substring(from, until) : content.substring(from);
        var array = tempContent.split("\n");
        if(tempContent.search("not successful") !== -1){
            for(var i=0; i<array.length; i++){
                var endn = "";
                if(array[i].search("Start ") !== -1 || array[i].search("===") !== -1){
                    endn = endn+" "+array[i];
                    addParagraph(endn, "blue");
                }
                else{
                    endn = endn+" "+array[i];
                    addParagraph(endn, "red");
                }
            }
            addParagraph("<br/>", "blue");
        }
        else{
            if(tempContent.search("HbbTV-DVB") !== -1){
                for(var i=0; i<array.length; i++){
                    var endn = "";
                    if(array[i].search("Start ") !== -1 || array[i].search("===") !== -1){
                        array[i] = array[i] + "<br />";
                        endn = endn+" "+array[i];
                        addParagraph(endn, "blue");
                    }
                    else{
                        endn = endn+" "+array[i];
                        
                        var Warning=array[i].search("Warning") ;
                        var WARNING=array[i].search("WARNING");
                        var errorFound=array[i].search("###");
                        var cmafError=array[i].search("CMAF check violated");

                        if(Warning===-1 && WARNING===-1 && errorFound===-1 && cmafError===-1)
                            addParagraph(endn, "blue");
                        else if(errorFound===-1 && cmafError===-1)
                            addParagraph(endn, "orange");
                        else
                            addParagraph(endn, "red");
                    }
                }
            }
            else{
                for(var i=0; i<array.length; i++){
                    var endn = "";
                    endn = endn+" "+array[i];
                    addParagraph(endn, "blue");
                }
                addParagraph("<br/>", "blue");
            }
        }

        if(until === -1)
            break;
    }
}
else{
    for(var i =0;i<resultant.length;i++){
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
}

});

}

var index = 0;
function addParagraph(string, color){
    index++;
    var ind = index.toString();
    
    var para = document.createElement("p");
    para.setAttribute("id", ind);
    para.style.fontSize = "16px";
    para.style.color = color;
    var element = document.getElementById("mpddiv");
    element.appendChild(para);
    
    document.getElementById(ind).innerHTML = string;
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
if($ctawave_conformance){
    include '../CMAF/CTAWAVE/CTAWAVE_Initialization.php';
}