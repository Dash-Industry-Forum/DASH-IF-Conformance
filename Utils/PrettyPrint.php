<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
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
                    echo "$line\n";
                else
                    echo "\033[".'0;31'."m".$line."\033[0m"."\n";
            }
        }
        else{
            if(strpos($temp_content, 'HbbTV-DVB') !== FALSE){
                $array = explode("\n", $temp_content);
                foreach($array as $line_number => $line){
                    if(strpos($line, 'Start') !== FALSE || strpos($line, '====') !== FALSE){
                        echo "$line\n";
                        unset($array[$line_number]);
                    }
                }
                $contents_array = $array;
            }
            else
                echo $temp_content."\n";
        }
        
        if($until === FALSE)
            break;
    }
    
    return $contents_array;
}