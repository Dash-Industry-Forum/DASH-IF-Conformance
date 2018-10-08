<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function print_console($filename, $expression){
    echo "------------------- $expression -------------------\n";
    $contents = file_get_contents($filename);
    $contents_array = explode("\n", $contents);
    
    foreach ($contents_array as $content){
        if(strpos($content, 'error') !== FALSE || strpos($content, '###') !== FALSE){
            echo "\033[".'0;31'."m".$content."\033[0m"."\n";
        }
        elseif(strpos($content, 'Warning') !== FALSE || strpos($content, 'WARNING') !== FALSE){
            echo "\033[".'1;33'."m".$content."\033[0m"."\n";
        }
        else{
            echo "\033[".'0;34'."m".$content."\033[0m"."\n";
        }
    }
    
    $str = str_repeat('-', strlen($expression));
    echo "--------------------" . $str . "--------------------\n\n";
}