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

function tabulateResults($logFilename, $step) {
    global $string_info;
    
    $str = '<table style="width:100%">';
    
    $th_str_start = '<th bgcolor="#077bff">';
    $th_str_end = '</th>';
    $th_color_start = '<font color="#fffff">';
    $th_color_end = '</font>';
    switch ($step) {
        case 'MPD':
            $str .= '<tr>';
            $str .= $th_str_start.$th_color_start.'Log No'.$th_color_end.$th_str_end;
            $str .= $th_str_start.$th_color_start.'MPD Validation Report'.$th_color_end.$th_str_end;
            $str .= '</tr>';
            break;
        case 'Segment':
            $str .= '<tr>';
            $str .= $th_str_start.$th_color_start.'Log No'.$th_color_end.$th_str_end;
            $str .= $th_str_start.$th_color_start.'Segment Validation Report'.$th_color_end.$th_str_end;
            $str .= '</tr>';
            break;
        case 'Cross':
            $str .= '<tr>';
            $str .= $th_str_start.$th_color_start.'Log No'.$th_color_end.$th_str_end;
            $str .= $th_str_start.$th_color_start.'Cross Validation Report'.$th_color_end.$th_str_end;
            $str .= '</tr>';
            break;
        case 'Missing':
            $str .= '<tr>';
            $str .= $th_str_start.$th_color_start.'No'.$th_color_end.$th_str_end;
            $str .= $th_str_start.$th_color_start.'Missing Links Report'.$th_color_end.$th_str_end;
            $str .= '</tr>';
            break;
        default:
            break;
    }
    
    $logs = explode("\n", file_get_contents($logFilename));
    $log_no = 1;
    foreach ($logs as $log) {
        if($log === "") {
            continue;
        }
        
        if(strpos($log, 'error') !== FALSE || 
           strpos($log, 'Error') !== FALSE || 
           strpos($log, 'ERROR') !== FALSE ||
           strpos($log, 'violated') !== FALSE) {
            $td_str_color_start = '<font color="#ff0000">';
            $td_str_color_end = '</font>';
        }
        elseif(strpos($log, 'warning') !== FALSE ||
               strpos($log, 'Warning') !== FALSE ||
               strpos($log, 'WARNING') !== FALSE) {
            $td_str_color_start = '<font color="#ffa500">';
            $td_str_color_end = '</font>';
        }
        else{
            $td_str_color_start = '<font color="#0000ff">';
            $td_str_color_end = '</font>';
        }
        
        $str .= '<tr>';
        $str .= '<td bgcolor="#f6f6f6">' . $log_no . '</td>';
        $str .= '<td bgcolor="#f6f6f6">' . $td_str_color_start . $log . $td_str_color_end . '</td>';
        $str .= '</tr>';
        
        $log_no++;
    }
    $str .= '</table>';
    
    $temp_string = str_replace('$Table$', $str, $string_info);
    $fileseparator = strrpos($logFilename, '/');
    $location = substr($logFilename, 0, $fileseparator+1);
    $name = explode('.', substr($logFilename, $fileseparator+1))[0];
    $htmlFilename = $location . $name . '.html';
    file_put_contents($htmlFilename, $temp_string);
}