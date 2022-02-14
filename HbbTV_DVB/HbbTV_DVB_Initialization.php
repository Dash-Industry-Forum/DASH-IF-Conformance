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

include 'HbbTV_DVB_Handle.php';
include 'HbbTV_DVB_Intermediate.php';
include 'HbbTV_DVB_MPDValidation.php';
include 'HbbTV_DVB_RepresentationValidation.php';
include 'HbbTV_DVB_CrossValidation.php';

global $adaptation_set_template, $reprsentation_template, $bitrate_script, $segment_duration_script, $subtitle_segments_location, $hbbtv_dvb_crossvalidation_logfile, $mpd_xml_string;

$hbbtv_dvb_function_name = 'HbbTV_DVB_Handle';
$hbbtv_dvb_when_to_call = array('BeforeMPD', 'MPD', 'BeforeRepresentation', 'Representation', 'BeforeAdaptationSet', 'AdaptationSet');

$bitrate_script = 'bitratereport.py';
$segment_duration_script = 'seg_duration.py';
$subtitle_segments_location = $reprsentation_template . '/Subtitles/';
$hbbtv_dvb_crossvalidation_logfile =  $adaptation_set_template . '_hbbtv_dvb_compInfo';
$mpd_xml_string = xml_string_update($mpd_xml_string, '<hbbtv_dvb>No Result</hbbtv_dvb>', '<');