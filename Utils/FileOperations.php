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
 * This PHP script is responsible for common file operations.
 * @name: FileOperations.php
 * @entities: 
 *      @functions{
 *          create_folder_in_session($pathdir),
 *          syscall($command),
 *      }
 */
###############################################################################


/*
 * Create a new folder
 * @name: create_folder_in_session
 * @input: $pathdir - the directory path to be created
 * @output: NA
 */
function create_folder_in_session($pathdir){
    if (!file_exists($pathdir)){
        $oldmask = umask(0);
        mkdir($pathdir, 0777, true);
        umask($oldmask);
    }
}

/*
 * Modification of standard PHP System() function to have system output 
 * from both the STDERR and STDOUT
 * @name: syscall
 * @input: $command - command to be executed
 * @output: result of the executed command or 0
 */
function syscall($command){
    $result = 0;
    if ($proc = popen("($command)2>&1", "r")){
        while (!feof($proc))
            $result .= fgets($proc, 1000);
        pclose($proc);
    }
    return $result;
}

// Check if the nodes and their descendandts are the same
function nodes_equal($node_1, $node_2){
    $equal = true;
    
    $atts_1 = $node_1->attributes;
    $atts_2 = $node_2->attributes;
    if($atts_1->length != $atts_2->length){
        return false;
    }
    
    for($i=0; $i<$atts_1->length; $i++){
        if($atts_1->item($i)->name != $atts_2->item($i)->name || $atts_1->item($i)->value != $atts_2->item($i)->value){
            $equal = false;
            break;
        }
    }
    if(!$equal) {
        return false;
    }
    
    foreach($node_1->childNodes as $index => $ch_1){
        $ch_2 = $node_2->childNodes->item($index);

        if($ch_1->nodeType == XML_ELEMENT_NODE && $ch_2->nodeType == XML_ELEMENT_NODE){
            if($ch_1->nodeName != $ch_2->nodeName){
                $equal = false;
                break;
            }

            $equal = nodes_equal($ch_1, $ch_2);
            if($equal == false)
                break;
        }
    }

    return $equal;
}
