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

function assemble($path, $segment_urls, $sizearr){
    global $session_dir, $segment_accesses, $reprsentation_template, $current_adaptation_set, $current_representation;
    
    $index = ($segment_accesses[$current_adaptation_set][$current_representation][0]['initialization']) ? 0 : 1;
    
    for ($i = 0; $i < sizeof($segment_urls); $i++){
        $rep_dir = str_replace(array('$AS$', '$R$'), array($current_adaptation_set, $current_representation), $reprsentation_template);
        $fp1 = fopen($session_dir . '/' . $rep_dir . ".mp4", 'a+');
        
        $segment_name = basename($segment_urls[$i]);
        if (file_exists($path . $segment_name)){
            $size = $sizearr[$i]; // Get the real size of the file (passed as inupt for function)
            $file2 = file_get_contents($path . $segment_name); // Get the file contents

            fwrite($fp1, $file2); // dump it in the container file
            fclose($fp1);
            file_put_contents($session_dir . '/' . $rep_dir . ".txt", $index . " " . $size . "\n", FILE_APPEND); // add size to a text file to be passed to conformance software
            
            $index++; // iterate over all segments within the segments folder
        }
    }
}