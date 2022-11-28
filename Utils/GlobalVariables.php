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
$mpd_url = '';
$uploaded = false;
$current_adaptation_set = 0;
$current_representation = 0;
$period_timing_info = array();
$segment_accesses = array();
$sizearray = array();
$additional_flags = '';
$suppressatomlevel = 0;
$profileCommandLine=array();

# HLS variable
$hls_manifest = 0;
$hls_manifest_type = "";
$hls_tag = '';
$hls_current_index = 0;
$hls_media_types = array('video' => array(), 'iframe' => array(), 'audio' => array(), 'subtitle' => array(), 'unknown' => array());


# DASH-IF IOP LL variables
$availability_times = array();

if (isset($_POST['urlcode'])){
    $url_array = json_decode($_POST['urlcode']);
    $mpd_url = $url_array[0];
    $_SESSION['url'] = $mpd_url;
    
}
if (isset($_POST['urlcodehls'])){
    $url_array = json_decode($_POST['urlcodehls']);
    $mpd_url = $url_array[0];
    $_SESSION['url'] = $mpd_url;
    
    
    $hls_manifest = 1;
    $main_dir = dirname(__DIR__) . '/Conformance-Frontend-HLS/';
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
if (isset($_POST['suppressatomlevel'])){
    $suppressatomlevel = 1;
}
if(isset($_POST['profile'])){
    $profileCommandLine = (array)json_decode($_POST['profile'],true);
}

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
