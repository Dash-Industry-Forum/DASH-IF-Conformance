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
    global $main_dir, $counter_name, $start_time, $mem, $cpu_avg_load;

    $counter_dir = $main_dir . '/' . $counter_name;
    if (!file_exists($counter_name)){
        $f = open_file($counter_name, 'w');
        $timezone = date_default_timezone_get();
        $date = date('m/d/Y h:i:s a', time());
        fwrite($f, "The current server timezone is: " . $timezone . ", file created at: " . $date . "\n" ."No. of visitors"."\n". "0"."\n");
        fwrite($f, "----IP hash, ID, Start-time, %CPU, Memory(total, used, free, shared,buffers,cached), MPD-Status, MPD-End-time, End-time----\n");
        fclose($f);
    }
    // Read the current value of visitor counter from the file.
    $f = open_file($counter_name, 'r');
    $content = fread($f, filesize($counter_name));
    $contents = explode("\n", $content);
    $contents_new=$contents;
    $counterVal=$contents[2];
    fclose($f);

    $counterVal++;
    $contents_new[2]=$counterVal;
    $user_IP = getUserIPAddr(); // get the IP address of the visitor.
    $user_IP_hash=md5($user_IP); // convert IP to MD5 hash.

    $f = open_file($counter_name, 'w');
    foreach($contents_new as $value){ // write file contents as it is with incremented counter value.
     fwrite($f, $value.PHP_EOL);
    }
    fwrite($f, $user_IP_hash .", ".$_SESSION['foldername'].", ".$start_time.", ");
    fwrite($f, $cpu_avg_load[0].", ");
    fwrite($f, $mem[1].",".$mem[2].",".$mem[3].",".$mem[4].",".$mem[5].",".$mem[6].", ");

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
 * Updating the counter file with the end time of the session
 * @name: writeEndTime
 * @input: $end_time_sec - the end time of the session in seconds
 * @output: NA
 */
function writeEndTime($end_time_sec){
    global $counter_name, $main_dir;
    $end_time=date('m/d/Y h:i:s a', $end_time_sec);
    
    $file = file_get_contents($counter_name);
    $lines = explode("\n", $file);
    $ID=$_SESSION['foldername'];
    
    //Read each line and search for correct ID, then append end time to that line.
    foreach ($lines as $key => &$value){
        $pos_ID=strpos($value,$ID);
        if($pos_ID!=FALSE){
            $value = $value.$end_time;
            break;
        }
    }
    file_put_contents($counter_name, implode("\n", $lines));
}

/*
 * Updating the counter file with the MPD status (found, not found, uploaded)
 * @name: writeMPDStatus
 * @input: $mpd - MPD URL
 * @output: NA
 */
function writeMPDStatus($mpd){
    global $counter_name, $main_dir;
    
    $file = file_get_contents($counter_name);
    $lines = explode("\n", $file);
    $ID=$_SESSION['foldername'];
    
    $uploaded=(strpos($mpd, "uploaded.mpd")!=FALSE && strpos($mpd, "var/www")!=FALSE); //Check if the mpd is an uploaded file.
    
    //Read each line and search for correct ID, then append end time to that line.
    foreach ($lines as $key => &$value){
        $pos_ID=strpos($value,$ID);
        if($pos_ID!=FALSE){
            if ($uploaded==FALSE){
                $output= get_headers($mpd);
                $pos=strpos($output[0], "200 OK");

                if($pos!=FALSE)
                    $value = $value."200 OK, ";
                else if(strpos($output[0], "404 Not Found"))
                    $value = $value."404 Not Found- ".$mpd;
                else
                    $value = $value.$output[0].", ";
            }
            else
            {
                if(strpos($mpd, "MPD with error")!=FALSE)
                    $value = $value."uploaded- MPD with error. ";
                else
                    $value = $value."uploaded, ";
            }
            break;
        }
    }
    file_put_contents($counter_name, implode("\n", $lines));
}

/*
 * Updating the counter file with the MPD validation end time
 * @name: writeMPDEndTime
 * @input: NA
 * @output: NA
 */
function writeMPDEndTime(){
    global $counter_name, $main_dir;
    
    $mpd_end_time = date('m/d/Y h:i:s a', time());
    $file = file_get_contents($counter_name);
    $lines = explode("\n", $file);
    $ID=$_SESSION['foldername'];
    
    //Read each line and search for correct ID, then append end time to that line.
    foreach ($lines as $key => &$value) {
        $pos_ID=strpos($value,$ID);
        if($pos_ID!=FALSE){
            $value = $value.$mpd_end_time.", ";
            break;
        }
    }
    file_put_contents($counter_name, implode("\n", $lines));
}
