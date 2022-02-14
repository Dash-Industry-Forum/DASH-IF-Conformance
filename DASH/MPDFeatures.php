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
 * This PHP script is responsible for MPD feature extracting.
 * @name: MPDFeatures.php
 * @entities: 
 *      @functions{
 *          MPD_features($mpd_xml),
 *          extract_features($xml),
 *          is_element($element)
 *      }
 */
###############################################################################

/*
 * Extract the features of MPD from $mpd_xml
 * @name: MPD_features
 * @input: $mpd_xml - DOM XML of the MPD, to extract the attributes
 * @output: array version of the provided $mpd_xml
 */
function MPD_features($mpd_xml){
    return extract_features($mpd_xml);
}

/*
 * Recursively extract the features of MPD from $mpd_xml and store into an array
 * @name: extract_features
 * @input: $xml - The MPD XML file
 * @output: array of MPD
 */
function extract_features($xml){
    $array = array();
    $attributes = $xml->attributes;
    $children = $xml->childNodes;
    
    foreach($attributes as $attribute)
        $array[$attribute->nodeName] = $attribute->nodeValue;
    
    foreach($children as $child){
        if(is_element($child))
            $array[$child->nodeName][] = extract_features($child);
        if($child->nodeName == 'BaseURL')
            $array['BaseURL'][sizeof($array['BaseURL'])-1]['anyURI'] = $child->firstChild->nodeValue;
    }
    
    return $array;
}

/*
 * Check if the node is a DOM ELEMENT node
 * @name: is_element
 * @input: $element - DOMNode to be checked
 * @output: true or false
 */
function is_element($element){
    if(!empty($element->nodeName) && $element->nodeType == XML_ELEMENT_NODE)
            return true;
    return false;
}