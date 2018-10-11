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
 * This PHP script is responsible for session operations, such as
 * session creating, destroying and old sessions deleting.
 * @name: Session.php
 * @entities: 
 *      @functions{
 *          set_session_name(),
 *          session_check(),
 *          get_all_sessions(),
 *          old_sessions_delete(),
 *          should_be_deleted($folder_dir),
 *          session_delete($dir),
 *          session_close(),
 *          session_create()
 *      }
 */
###############################################################################

/*
 * Session name set
 * @name: set_session_name
 * @input: NA
 * @output: NA
 */
function set_session_name(){
    session_name('sess' . rand());
}

/*
 * Check the session parameters that come from the UI
 * Initialize the session directory name and create it
 * Update the visitor counter that is used for old session(s) deleting
 * @name: session_check
 * @input: NA
 * @output: NA
 */
function session_check(){
    global $session_dir;
    if (isset($_FILES['afile']['tmp_name']))
        $_SESSION['fileContent'] = file_get_contents($_FILES['afile']['tmp_name']);
    
    if (isset($_POST['foldername'])){
        $foldername = $_POST['foldername'];
        $paths = explode("/", $foldername);
        if (count($paths) > 1)
            $foldername = end($paths);
    }
    else
        $foldername = 'id' . rand();
    
    if(!file_exists($session_dir))
        create_folder_in_session($session_dir);
    
    $_SESSION['foldername'] = $foldername;
    $session_dir .= '/' . $foldername;
    $_SESSION['locate'] = $session_dir;
    
    $_SESSION['foldername'] = $foldername;
    update_visitor_counter();
    
    create_folder_in_session($session_dir);
}

/*
 * Return all the sessions in the directory where all of them are stored
 * @name: get_all_sessions
 * @input: NA
 * @output: array of all session folders' names
 */
function get_all_sessions(){
    global $session_dir;
    
    return array_diff(scandir($session_dir), array('..', '.'));
}

/*
 * Clean temp folder from old sessions in order to save diskspace
 * @name: old_sessions_delete
 * @input: NA
 * @output: NA
 */
function should_be_deleted($folder_dir){
    $change1 = 0; //duration after conformance test is done
    $change2 = time() - filemtime($folder_dir); // duration of file implementation
    $time_constraint = 7200; // threshold time for deleting decision

    $progress_dir = $folder_dir . '/progress.xml';
    $progress_XML = simplexml_load_file($progress_dir);
    if($progress_XML !== FALSE){
        if ((string) $progress_XML->completed === "true")
            $change1 = time() - (int) $progress_XML->completed->attributes();

        # Clean folder after 2 hours after test completed or 2 hours after test started
        if ($change1 > $time_constraint || $change2 > $time_constraint)
            return TRUE;
    }

    return FALSE;
}

/*
 * Delete the old session that expired the specified time
 * @name: session_delete
 * @input: $dir - session folder path
 * @output: NA
 */
function session_delete($dir){
    if (is_dir($dir)){
        $objects = scandir($dir);
        foreach ($objects as $object){
            if ($object != "." && $object != ".."){
                if (filetype($dir . "/" . $object) == "dir")
                    rrmdir($dir . "/" . $object);
                else
                {
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
 * Delete the old sessions from the storage based on the expiry time 
 * @name: old_sessions_delete
 * @input: NA
 * @output: NA
 */
function old_sessions_delete(){
    global $session_dir;

    $directories = get_all_sessions();
    foreach ($directories as $folder){
        $folder_dir = $session_dir . '/' . $folder;
        if (file_exists($folder_dir) && should_be_deleted($folder_dir))
                session_delete($folder_dir);
    }
}

/*
 * Close the current session 
 * @name: session_close
 * @input: NA
 * @output: NA
 */
function session_close(){
    session_destroy();
}

/*
 * Create a new session while controlling the disk space with old_sessions_delete()
 * @name: session_create
 * @input: NA
 * @output: NA
 */
function session_create(){
    start_visitor_counter();
    set_session_name();
    old_sessions_delete();
    session_check();
}
