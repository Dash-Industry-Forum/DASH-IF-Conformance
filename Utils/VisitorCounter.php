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

###############################################################################
/*
 * This PHP script is responsible for keeping track of the sessions.
 * @name: VisitorCounter.php
 * @entities: 
 *      @functions{
 *          start_visitor_counter(),
 *          update_visitor_counter(),
 *          getUserIPAddr(),
 *          writeEndTime($end_time_sec),
 *          writeMPDStatus($mpd),
 *          writeMPDEndTime()
 *      }
 */
###############################################################################

global $start_time, $mem, $cpu_avg_load;

/*
 * Update the start time, CPU load and memory to be used 
 * by the other functions in this document
 * @name: start_visitor_counter
 * @input: NA
 * @output: NA
 */
function start_visitor_counter(){
  return;
    global $start_time, $mem, $cpu_avg_load;
    
    $start_time = date('m/d/Y h:i:s a', time());
    
    //This returns three samples representing the average system load (the number
    // of processes in the system run queue) over the last 1, 5 and 15 minutes, respectively.
    $cpu_avg_load = sys_getloadavg(); 
    
    $output_mem=null;
    exec('free',$output_mem);
    $mem = explode(" ", $output_mem[1]);
    $mem = array_filter($mem);
    $mem = array_merge($mem);
}

/*
 * If the counter file does not exist, initialize.
 * Otherwise, update the counter file with the information from start_visitor_counter() 
 * by the other functions in this document
 * @name: update_visitor_counter
 * @input: NA
 * @output: NA
 */
function update_visitor_counter(){
  return;
    global $counter_dir, $counter_file, $counter_write, $start_time, $mem, $cpu_avg_load;
    
    // Find a unique individual visitor log file name for the session
    // Default would be the session folder name
    // If the file already exists append a random number to this file name until it is unique
    // If unique name could not be found after the 100th try, continue testing without collecting visitor data
    $counter_filename = $_SESSION['foldername'] . '.txt';
    $counter_file = $counter_dir . '/' . $counter_filename;
    $try = 0;
    while(file_exists($counter_file)){
        if($try < 100){
            $counter_filename = $_SESSION['foldername'] . '_' . rand() . '.txt';
            $counter_file = $counter_dir . '/' . $counter_filename;
            $try++;
        }
    }
    if(file_exists($counter_file)){
        $counter_write = FALSE;
        return;
    }

    $user_IP = getUserIPAddr(); // get the IP address of the visitor.
    $user_IP_hash=md5($user_IP); // convert IP to MD5 hash.
    $f = open_file($counter_file, 'w');
    if($f == NULL){
        return;
    }
    fwrite($f, "#1:".$user_IP_hash .", #2:".$_SESSION['foldername'].", #3:".$start_time.", ");
    fwrite($f, "#4:".$cpu_avg_load[0].", ");
    fwrite($f, "#5:".$mem[1].",".$mem[2].",".$mem[3].",".$mem[4].",".$mem[5].",".$mem[6].", ");
    fclose($f);
}

/*
 * Get the IP address of the connection request for updating the counter file
 * @name: getUserIPAddr
 * @input: NA
 * @output: IP address
 */
function getUserIPAddr(){
    $client  = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $remote  = $_SERVER['REMOTE_ADDR'];

    if(filter_var($client, FILTER_VALIDATE_IP))
        $ip = $client;
    elseif(filter_var($forward, FILTER_VALIDATE_IP))
        $ip = $forward;
    else
        $ip = $remote;

    return $ip;
}

/*
 * Updating the counter file with the MPD status (found, not found, uploaded)
 * @name: writeMPDStatus
 * @input: $mpd - MPD URL
 * @output: NA
 */
function writeMPDStatus($mpd){
  return;
    global $counter_file, $counter_write;
    
    if($counter_write === FALSE)
        return;
    
    // Check if the mpd is an uploaded file.
    $uploaded=(strpos($mpd, "uploaded.mpd")!==FALSE && strpos($mpd, "var/www")!==FALSE); 
    
    // Append MPD loading status to the end of file.
    $f = open_file($counter_file, 'a');
    if($f == NULL){
        return;
    }
    $value = '';
    if ($uploaded==FALSE){
        $output= get_headers($mpd);
        if(strpos($output[0], "200 OK") !== FALSE)
            $value = "#6:200 OK, ";
        else if(strpos($output[0], "404 Not Found") !== FALSE)
            $value = "#6:404 Not Found- ".$mpd;
        else
            $value = "#6:".$output[0].", ";
    }
    else{
        if(strpos($mpd, "MPD with error") !== FALSE)
            $value = "#6:uploaded- MPD with error. ";
        else
            $value = "#6:uploaded, ";
    }
    
    fwrite($f, $value);
    fclose($f);
}

/*
 * Updating the counter file with the profiles
 * (both MPD@profiles and enforced profiles)
 * @name: writeProfiles
 * @input: NA
 * @output: NA
 */
function writeProfiles(){
  return;
    global $counter_file, $counter_write, $mpd_features, $dashif_conformance, $cmaf_conformance, $dvb_conformance, $hbbtv_conformance, $ctawave_conformance;
    
    if($counter_write === FALSE)
        return;
    
    $mpd_profiles = str_replace(',', ';', $mpd_features['profiles']);
    $conformance_profiles = $mpd_profiles . 
                            (($dashif_conformance) ? ";DASH-IF" : '') .
                            (($cmaf_conformance) ? ";CMAF" : '') .
                            (($dvb_conformance) ? ";DVB" : '') .
                            (($hbbtv_conformance) ? ";HbbTV" : '') .
                            (($ctawave_conformance) ? ";CTAWAVE" : '');
    
    // Append MPD and enforced profiles to the end of file.
    $f = open_file($counter_file, 'a');
    if($f == NULL){
        return;
    }
    fwrite($f, $conformance_profiles.", ");
    fclose($f);
}

/*
 * Updating the counter file with the MPD validation end time
 * @name: writeMPDEndTime
 * @input: NA
 * @output: NA
 */
function writeMPDEndTime(){
  return;
    global $counter_file, $counter_write;
    
    if($counter_write === FALSE)
        return;
    
    // Append end time to the end of file.
    $mpd_end_time = date('m/d/Y h:i:s a', time());
    $f = open_file($counter_file, 'a');
    if($f == NULL){
        return;
    }
    fwrite($f, "#7:".$mpd_end_time.", ");
    fclose($f);
}

/*
 * Updating the counter file with the end time of the session
 * @name: writeEndTime
 * @input: $end_time_sec - the end time of the session in seconds
 * @output: NA
 */
function writeEndTime($end_time_sec){
  return;
    global $counter_file, $counter_write;
    
    if($counter_write === FALSE)
        return;
    
    // Append end time to the end of file.
    $end_time=date('m/d/Y h:i:s a', $end_time_sec);
    $f = open_file($counter_file, 'a');
    if($f == NULL){
        return;
    }
    fwrite($f, "#8:".$end_time);
    fclose($f);
}
