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
 * This PHP script is responsible for DOM XML loading
 * either for MPD or other possible XML files.
 * @name: Load.php
 * @entities: 
 *      @functions{
 *          mpd_load(),
 *          get_doc($path),
 *          get_DOM($path, $main_element)
 *      }
 */
###############################################################################

/*
 * Initial loading of MPD from the provided URL
 * @name: mpd_load
 * @input: NA
 * @output: FALSE or MPD DOM element
 */
function mpd_load(){
    global $mpd_url;
    $return_val = FALSE;
    
    if(!$mpd_url)
        return $return_val;
    
    $MPD = get_DOM($mpd_url, 'MPD');
    if(!$MPD)
        return $return_val;
    
    return $MPD;
}

/*
 * Get the DOM document provided by the $path
 * @name: get_doc
 * @input: $path - path of the XML file (url or local path)
 * @output: FALSE or DOM document
 */
function get_doc($path){
    $return_val = FALSE;
    
    $loaded = simplexml_load_file($path);
    if(!$loaded)
        return $return_val;
    
    $dom_sxe = dom_import_simplexml($loaded);
    if(!$dom_sxe)
        return $return_val;
    
    $dom_doc = new DOMDocument('1.0');
    $dom_sxe = $dom_doc->importNode($dom_sxe, true);
    if(!$dom_sxe)
        return $return_val;
    
    $dom_doc->appendChild($dom_sxe);
    return $dom_doc;
}

/*
 * Loading the XML file to DOM
 * @name: get_DOM
 * @input: $path - path of the XML file (url or local path)
 *         $main_element - the outer element of the DOM XML to be returned
 * @output: FALSE or the DOM XML element with the outer element
 *          specified by the $main_element
 */
function get_DOM($path, $main_element){
    $return_val = FALSE;
    
    $dom_doc = get_doc($path);
    if(!$dom_doc)
        return $return_val;
    
    $main_element_nodes = $dom_doc->getElementsByTagName($main_element);
    if($main_element_nodes->length == 0)
        return $return_val;
    
    return $main_element_nodes->item(0);
}