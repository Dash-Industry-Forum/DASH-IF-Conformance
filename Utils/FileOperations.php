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
 *          open_file($file_path, $mode),
 *          close_file($handle),
 *          create_folder_in_session($pathdir),
 *          rename_file($old, $new),
 *          rrmdir($dir),
 *          syscall($command),
 *          relative_path($path)
 *      }
 */
###############################################################################

/*
 * Open an existing file or create a new one
 * @name: open_file
 * @input: $file_path - path of the file
 *         $mode - in which mode to open the $file_path
 * @output: file handle or NULL
 */
function open_file($file_path, $mode){
    if (!($opfile = fopen($file_path, $mode))){
        echo "Error opening file" . $file_path . "\n";
        return NULL;
    }
    
    return $opfile;
}

/*
 * Close an existing file
 * @name: close_file
 * @input: $handle - file handler
 * @output: NA
 */
function close_file($handle){
    fclose($handle);
}

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
 * Rename a file
 * @name: rename_file
 * @input: $old - file name to be changed
 *         $new - file name to be changed to
 * @output: NA
 */
function rename_file($old, $new){
    rename($old, $new);
}

/*
 * Remove all files and folders within the input folder
 * @name: rrmdir
 * @input: $dir - input folder to be deleted
 * @output: NA
 */
function rrmdir($dir){
    if (is_dir($dir)){
        $objects = scandir($dir);
        foreach ($objects as $object){
            if ($object != "." && $object != ".."){
                if (filetype($dir . "/" . $object) == "dir")
                    rrmdir($dir . "/" . $object);
                else{
                    chmod($dir . "/" . $object, 0777);
                    unlink($dir . "/" . $object);
                }
            }
        }
        reset($objects);
        rmdir($dir);
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

/*
 * Computing the path of the file relative to main directory
 * @name: relative_path
 * @input: $path - path to compute the relative path of
 * @output: relative path
 */
function relative_path($path){
    if(file_exists($path))
        return substr($path, strpos($path, 'Conformance-Frontend'));
    return NULL;
}

//The function to remove repeated error statements from the log files.
function err_file_op($reqFile){
    global $session_dir, $current_period, $already_processed;
    if($reqFile==1)
        $LogFiles=glob($session_dir.'/Period'.$current_period."/*log.txt");
    else
        $LogFiles=glob($session_dir.'/Period'.$current_period."/*compInfo.txt");
    
    //$CrossRepDASH=glob($locate."/*CrossInfofile.txt");
    //$all_report_files = array_merge($RepLogFiles, $CrossValidDVB, $CrossRepDASH); // put all the filepaths in a single array
   
    foreach ($LogFiles as $file_location){       
        if(!in_array($file_location, $already_processed)){
            $duplicate_file = substr_replace($file_location, "full.txt", -4);
            copy($file_location, $duplicate_file);
            $segment_report = file($file_location, FILE_IGNORE_NEW_LINES);
            $segment_report = remove_duplicate($segment_report);
            file_put_contents($file_location, $segment_report);
            $already_processed[] = $file_location;
        }
    }
}

function remove_duplicate($error_array){
    $new_array = array();
    //since we don't have any \n chars in the str we have the whole error string in one line
    for($i = 0; $i < count($error_array); $i++){
        $new_array[$i] = str_word_count($error_array[$i],1);
        $new_array[$i] = implode(" ",$new_array[$i]);
    }
    //add feature to tell how many times an error was repeated
    $count_instances = array_count_values($new_array);
    $new_array = array_unique($new_array);
    foreach ($new_array as $key => $value){//removing some lines that are not necessary
        if((strlen($value) > 5) && ($value != "")){
            $repetitions = $count_instances[$value];
            
            if($repetitions > 1){
                $new_array[$key] = " (".$repetitions.' repetition\s) '.$error_array[$key]."\n";
            }
            else{
                $new_array[$key] = $error_array[$key]."\n";
            }
        }
    } 
    
    return $new_array;
}