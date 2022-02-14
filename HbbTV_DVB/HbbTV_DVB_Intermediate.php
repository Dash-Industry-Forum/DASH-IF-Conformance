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

function HbbTV_DVB_beforeMPD(){
    global $session_dir, $mpd_url, $mpd_dom, $mpd_doc, $mpd_log, $dvb_conformance;
    if($mpd_dom && $dvb_conformance){
        $mpdreport = fopen($session_dir . '/' . $mpd_log . '.txt', 'a+b');
        $mpd_doc = get_doc($mpd_url);
        $mpd_string = $mpd_doc->saveXML();
        $mpd_bytes = strlen($mpd_string);
        if($mpd_bytes > 1024*256){
            fwrite($mpdreport, "**'DVB check violated: Section 4.5- The MPD size before xlink resolution SHALL NOT exceed 256 Kbytes', found " . ($mpd_bytes/1024) . " Kbytes.\n");
        }
        $period_count = $mpd_dom->getElementsByTagName('Period')->length;
        if($period_count > 64){
            fwrite($mpdreport, "**'DVB check violated: Section 4.5- The MPD has a maximum of 64 periods before xlink resolution', found $period_count.\n");
        }
    }
}

function move_scripts(){
    global $session_dir, $bitrate_script, $segment_duration_script;
    
    copy(dirname(__FILE__)."/$bitrate_script", "$session_dir/$bitrate_script"); 
    chmod("$session_dir/$bitrate_script", 0777);
    copy(dirname(__FILE__)."/$segment_duration_script", "$session_dir/$segment_duration_script");
    chmod("$session_dir/$segment_duration_script", 0777);
}