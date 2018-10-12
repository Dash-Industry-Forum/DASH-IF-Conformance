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

function color_code_information(){
    echo "\n\n***Legend: \033[0;31mErrors\033[0m,  \033[1;33mWarnings\033[0m, \033[0;34mInformation\033[0m ***\n\n";
}

function print_console($filename, $expression){
    global $error_message, $warning_message, $info_message;
    
    echo "------------------- $expression -------------------\n";
    $contents = file_get_contents($filename);
    $contents_array = explode("\n", $contents);
    
    if(strpos($expression, 'MPD Validation') !== FALSE)
        $contents_array = print_console_mpd($contents);
    
    foreach ($contents_array as $content){
        if(strpos($content, 'error') !== FALSE || strpos($content, '###') !== FALSE || strpos($content, '**') !== FALSE){
            if($error_message)
                echo "\033[".'0;31'."m".$content."\033[0m"."\n";
        }
        elseif(strpos($content, 'Warning') !== FALSE || strpos($content, 'WARNING') !== FALSE){
            if($warning_message)
                echo "\033[".'1;33'."m".$content."\033[0m"."\n";
        }
        else{
            if($info_message)
                echo "\033[".'0;34'."m".$content."\033[0m"."\n";
        }
    }
    
    $str = str_repeat('-', strlen($expression));
    echo "--------------------" . $str . "--------------------\n\n";
}

function print_console_mpd($contents){
    $contents_array = NULL;
    
    $until = 0;
    while(1){
        $from = strpos($contents, 'Start', $until);
        $until = strpos($contents, 'Start', $from+1);
        $temp_content = ($until !== FALSE) ? substr($contents, $from, $until-$from) : substr($contents, $from);
        
        if(strpos($temp_content, 'not successful') !== FALSE){
            $array = explode("\n", $temp_content);
            foreach($array as $line){
                if(strpos($line, 'Start') !== FALSE || strpos($line, '====') !== FALSE)
                    echo "\033[".'0;34'."m".$line."\033[0m"."\n";
                else
                    echo "\033[".'0;31'."m".$line."\033[0m"."\n";
            }
        }
        else{
            if(strpos($temp_content, 'HbbTV-DVB') !== FALSE){
                $array = explode("\n", $temp_content);
                foreach($array as $line_number => $line){
                    if(strpos($line, 'Start') !== FALSE || strpos($line, '====') !== FALSE){
                        echo "\033[".'0;34'."m".$line."\033[0m"."\n";
                        unset($array[$line_number]);
                    }
                }
                $contents_array = $array;
            }
            else
                echo "\033[".'0;34'."m".$temp_content."\033[0m"."\n";
        }
        
        if($until === FALSE)
            break;
    }
    
    return $contents_array;
}