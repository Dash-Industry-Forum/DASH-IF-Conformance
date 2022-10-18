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

class PathBuildActionTypes {
    const INSERT_NEW_CHILD = 1;
    const SWITCH_TO_SIBLING = 2;
    const CHANGE_PARENT = 3;
}

/* ! \fn createMpdFeatureList($mpdfile,$schematronIssuesReport)  
  \brief Creates an XML document with the same schema that the MPD field ($mpdfile)
  but for each attribute, marks its value with "false" if no Schematron issue was found, or with
  the text of the issue, if some found.
  \param $mpdfile is the MPD file.
  \param $schematronIssuesReport  contains an array of objects that describes all the
  Schematron issues found in the MPD. This array has number of elements as Schematron issues found:

  $schematronIssuesReport[$i]->text : is the content of the Schematron issue.
  $schematronIssuesReport[$i]->location : contains the path of the issue The path
  is build concatenanting the element name (e.g. /MPD[1]/Period[1]/AdaptationSet[2]/Representation[1] ).
  giving the sibling number in square brackets.
  $schematronIssuesReport[$i]->attributes : contains an array with the sames of the attributes affected by the issue;
 * 
  \return nothing.
 */
function createMpdFeatureList($mpdfile, $schematronIssuesReport){
    global $session_dir, $featurelist_log;
    global $session;

    //Create MPDlist element in new XML File for feature list:
    $xml = new DOMDocument();

    $xml->appendChild($xml->importNode($mpdfile->cloneNode(true), true));

    $noMoreChilds = false;
    $terminate = false;
    $SubNode = $xml;

    $pathOfElement = "";

    while ($terminate === false){
        if ($SubNode->childNodes and $noMoreChilds === false){
            $j = 0;
            while (true){
                $ChildNode = $SubNode->childNodes->item($j);
                $myName = $ChildNode->nodeName;

                if ((strpos($myName, "#") === false) and ! empty($myName)){
                    $SubNode = $ChildNode;
                    formCurrentXmlPath($pathOfElement, $myName, PathBuildActionTypes::INSERT_NEW_CHILD);
                    setFeatureListEntry($SubNode, $pathOfElement, $schematronIssuesReport);
                    break;
                }
                if (empty($myName)){
                    $noMoreChilds = true;
                    break;
                }
                $j++;
            }
        }
        else{
            $noMoreChilds = false;
            if ($SubNode->nextSibling){
                $SubNode = $SubNode->nextSibling;
                $myName = $SubNode->nodeName;
                if ((strpos($myName, "#") === false) and ! empty($myName)){
                    formCurrentXmlPath($pathOfElement, $myName, PathBuildActionTypes::SWITCH_TO_SIBLING);
                    setFeatureListEntry($SubNode, $pathOfElement, $schematronIssuesReport);
                }
            }
            else{    //There are not following Siblings, so go Up One level (Parent)
                $foundNextSibling = false;
                while ($foundNextSibling === false){
                    if ($SubNode->parentNode){
                        $SubNode = $SubNode->parentNode;
                        $myName = $SubNode->nodeName;

                        formCurrentXmlPath($pathOfElement, $myName, PathBuildActionTypes::CHANGE_PARENT);

                        if ($SubNode->nextSibling){
                            $j = 0;
                            while (true){
                                $SublingNode = $SubNode->nextSibling;
                                $myName = $SublingNode->nodeName;

                                if ((strpos($myName, "#") === false) and ! empty($myName)){
                                    $SubNode = $SublingNode;
                                    formCurrentXmlPath($pathOfElement, $myName, PathBuildActionTypes::SWITCH_TO_SIBLING);
                                    setFeatureListEntry($SubNode, $pathOfElement, $schematronIssuesReport);
                                    $foundNextSibling = true;
                                    break;
                                }
                                if (empty($myName)){
                                    $foundNextSibling = false;
                                    break;
                                }
                                else{
                                    $SubNode = $SublingNode;
                                }
                                $j++;
                            }
                        }
                    }
                    else{    //No more Parent Nodes, so finish:
                        $terminate = true;
                        break;
                    }
                }
            }
        }
    }
    $xml->save($session->getDir() . '/' . $featurelist_log);
}

/* ! \fn formCurrentXmlPath(&$path,$nodeName, $actionType)   
  \brief iteratively builds the current element path ($path) that is a string containing the element path currently evaluated. The path
  is build concatenanting the element name (e.g. /MPD[1]/Period[1]/AdaptationSet[2]/Representation[1] ).
  giving the current sibling number in square brackets.
  \param $path is the input string to which a new element will be appended/modified/deleted to form the current path
  \param $actionType determines the action to be performed to the current path ($path):

  \return nothing.

 */
function formCurrentXmlPath(&$path, $nodeName, $actionType){
    //The path of the current node ir formed.
    //By default, the string [1] is attached to all node names
    //if there are more siblings, this number is incremented, if not, remains the same:
    switch ($actionType){
      case PathBuildActionTypes::INSERT_NEW_CHILD:
            $path = $path . "/" . $nodeName . "[1]";
            break;
      case PathBuildActionTypes::SWITCH_TO_SIBLING:
            $pos = strrpos($path, "/");
            $posEnd = strrpos($path, "[");
            $currentSiblingName = substr($path, $pos + 1, $posEnd - $pos - 1);
            if ($currentSiblingName === $nodeName){
                $currentSiblingName = substr($path, $pos + 1);
                $siblingCount = getDataBetweenTokens($currentSiblingName, "[", "]");
                $path = substr($path, 0, $pos);
                $path = $path . "/" . $nodeName . "[" . ($siblingCount[0] + 1) . "]";
            }
            else{
                //A different sibling, so remplace current:
                $path = substr($path, 0, $pos);
                $path = $path . "/" . $nodeName . "[1]";
            }
            break;
      case PathBuildActionTypes::CHANGE_PARENT:
            $pos = strrpos($path, "/");
            $path = substr($path, 0, $pos);

            break;
    }
}

/* ! \fn isElementWithSchemaIssues($currenElementPath,$schematronReport )
  \brief determine if the $currenElementPath element path have an issue according $schematronReport.
  Compares the $currenElementPath with the $schematronReport[$i]->location for all Schematron issues found.
  \param $currenElementPath is the string from which data will be extracted.
  \param $schematronReport is the string/character that signals the init of the data that will be extracted from $string.
  \return returns an $schemaIssues object that contains two elements: $schemaIssues->issue that is equal to "false"
  if no issue was found, otherwise, it contains the text of the issue, and $schemaIssues->attributes that contains an array
  of all attributes that are affected by the issue, if present.

 */
function isElementWithSchemaIssues($currenElementPath, &$schematronReport){
    //Examine all Schematron Issues to check if the current element
    //have any issue:
    $schemaIssues = array("text" => "false", "attributes" => 0);

    if ($schematronReport){
    for ($i = 0; $i < sizeof($schematronReport); $i++){
        //If the current element have an Schematron Issues
        if ($schematronReport[$i]->location === $currenElementPath){
            $schemaIssues->text = $schematronReport[$i]->text;
            $schemaIssues->attributes = $schematronReport[$i]->attributes;

            //Detelet found Issue, and delete the position:
            unset($schematronReport[$i]);
            return $schemaIssues;
        }
    }
    }

    return $schemaIssues;
}

/* ! \fn isAttributeWithSchemaIssues($name,&$schemaIssues)
  \brief checks if an attribute name $name is within the list of $schemaIssues attibutes.
  If so, deletes that attribute and returns true.
  \param $name name of the attribute to be searched.
  \param $schemaIssues is the object of all Schematron Issues.
  \return returns true is the attribute was found.
 *  
 */
function isAttributeWithSchemaIssues($name, &$schemaIssues){
  if ($schemaIssues && $schemaIssues->attributes){
    for ($i = 0; $i < sizeof($schemaIssues->attributes); $i++){
        //If the current element have an Schematron Issues
        if ($schemaIssues->attributes[$i] === $name){
            unset($schemaIssues->attributes[$i]);
            $schemaIssues->attributes = array_values($schemaIssues->attributes);
            return true;
        }
    }
  }

    return false;
}

/* ! \fn setFeatureListEntry(&$xml,$node,$pathOfElement,$schematronIssuesReport)
  \brief Modify a DOMDocument object ($xml) Node element ($node) attribute if the $node
  Element and attribute have Schematron issues given by the $schematronIssuesReport object.
  If the attribute have an issues, sets the attribute value with the text of the issue, if
  not, sets its value as "false".
  \param $node current Node element of the XML document $xml.
  \param $pathOfElement is a string containing the element path currently evaluated. The path
  is build concatenanting the element name (e.g. /MPD[1]/Period[1]/AdaptationSet[2]/Representation[1] ).
  giving the sibling number in square brackets.
  \param $schematronIssuesReport contains all the Schematron issues found in the MPD (contained in the $xml).
  uses the following structure:
  $schematronIssuesReport[0]->text : is the content of the Schematron issue.
  $schematronIssuesReport[0]->location : contains the path of the issue The path
  is build concatenanting the element name (e.g. /MPD[1]/Period[1]/AdaptationSet[2]/Representation[1] ).
  giving the sibling number in square brackets.
  $schematronIssuesReport[0]->attributes : contains an array with the sames of the attributes affected by the issue;
  \return returns false if the node was not valid.
 *  
 */
function setFeatureListEntry($node, $pathOfElement, $schematronIssuesReport){
    $ret = true;
    $myName = $node->nodeName;
    $firstFalseValueSet = false;
    while (true){
        $foundAttWithIssues = false;
        $schemaIssues = isElementWithSchemaIssues($pathOfElement, $schematronIssuesReport);

        if ($myName !== "#text" and ! empty($myName)){
            $numbOfAttributes = $node->attributes->length;
            if ($numbOfAttributes === 0){
                //$node->nodeValue="";
            }
            else{
                for ($i = 0; $i < $numbOfAttributes;  ++$i){
                    $name = $node->attributes->item($i)->nodeName;
                    $value = $node->attributes->item($i)->nodeValue;

                    if ($schemaIssues->text !== "false" and isAttributeWithSchemaIssues($name, $schemaIssues)){
                        $foundAttWithIssues = true;
                        //$node->setAttribute($name,$schemaIssues->text);

                        $schemaIssue_i = $node->getAttribute($name);

                        if ($firstFalseValueSet === true){
                            //If all the attributes with no issues has been set to false,
                            //In this point the attribute value if either "false" or contains
                            //another issue, so concatenate the current one:                                
                            //Concatenate several issues asociated to a single attribute:
                            if ($schemaIssue_i !== "false"){
                                $newValue = $schemaIssue_i . " " . $schemaIssues->text;
                            }
                            else{
                                $newValue = $schemaIssues->text;
                            }
                        }
                        else{
                            $newValue = $schemaIssues->text;
                        }

                        $node->setAttribute($name, $newValue);
                    }
                    else if ($firstFalseValueSet === false){
                        //There is an Schema Issues at that element, but does not affect the attribute:
                        $node->setAttribute($name, "false");
                    }
                }

                //In this point, all the attributes of the Element has been set to false, except the ones
                //with Schematron issues, so in next runs (other issue of the same element - equal $pathOfElement)
                //do not set again (attribute value) to false of the 
                //attributes that are not in the following Schematron issues of the same element. 
                $firstFalseValueSet = true;

                //If the Element have Schematron Issues, but no attribute involved
                //was found in the MPD, means that there is a missing attributes,
                //so create a new attribute (only for reporting purposes) in order
                //to show this issue in the Feature List. 
                if ($foundAttWithIssues === false){
                    $attVal = $node->getAttribute("elementIssue");
                    $schemaIssue_i = $schemaIssues->text;

                    if ($schemaIssue_i !== "false"){
                        $newValue = $attVal . $schemaIssue_i;
                        $node->setAttribute("elementIssue", $newValue);
                    }
                }
            }
        }

        if (empty($myName) or $schemaIssues->text === "false"){
            return false;
        }
    }
    return $ret;
}

/* ! \fn getDataBetweenTokens($string, $initChar, $endChar)
  \brief Iteratively get the data between an init and an end String/Character in an String.
  \param $string is the string from which data will be extracted.
  \param $initChar is the string/character that signals the init of the data that will be extracted from $string.
  \param $endChar is the string/character that signals the end of the data that will be extracted from $string.
  \return returns $outputArray that is an array that contains the extracted strings, not includin the token strings.
 *  
 */
function getDataBetweenTokens($string, $initChar, $endChar){
    $tempString = $string;
    $outputArray = array();
    $initPos = 0;
    $endPos = 0;
    $itemsFound = 0;

    while (true){
        $initPos = strpos($tempString, $initChar);
        $endPos = strpos($tempString, $endChar);

        if ($endPos === false or $initPos === false){
            return $outputArray;
        }

        $outputArray[] = substr($tempString, $initPos + 1, $endPos - $initPos - 1);
        $itemsFound++;

        $tempString = substr($tempString, $endPos + 1);
    }
}

function convertToHtml() {
    global $session_dir, $featurelist_log, $featurelist_log_html;
    
    $html_str = '<html><body><div>';
    $feature_dom = get_DOM("$session_dir/$featurelist_log", 'MPD');
    $html_str = populateList($feature_dom, $html_str);
    $html_str .= '</div></body></html>';
    file_put_contents("$session_dir/$featurelist_log_html", $html_str);
}

function populateList($xml, $html_str) {
    $name = $xml->nodeName;
    $attributes = $xml->attributes;
    $children = $xml->childNodes;
    
    $html_str .= '<li><b>' . $name . '</b></li><ul>';
    
    foreach($attributes as $attribute) {
        $attr_str = '<font ';
        
        if (strpos($attribute->name, 'xmlns') !== FALSE || 
            strpos($attribute->name, 'xsi') !== FALSE) {
            continue;
        }
        elseif ($attribute->name == "elementIssue") {
            $attr_str .= 'color="red">';
            $attr_str .= "Issues of Missing Attributes: " . $attribute->value . "\r\n";
        }
        elseif ($attribute->value == "false") { //No Schema Error
            $attr_str .= 'color="green">';
            $attr_str .= $attribute->name . "\r\n";
        }
        else { //If other than "false", means that a schema error is found in that attribute
            $attr_str .= 'color="red">';
            $attr_str .= $attribute->name . ':' . $attribute->value . "\r\n";
        }
        
        $attr_str .= '</font>';
        $html_str .= '<li>' . $attr_str . '</li>';
    }
    
    foreach($children as $child){
        if(!empty($child->nodeName) && $child->nodeType == XML_ELEMENT_NODE)
            $html_str = populateList($child, $html_str);
    }
    
    $html_str .= '</ul>';
    return $html_str;
}
