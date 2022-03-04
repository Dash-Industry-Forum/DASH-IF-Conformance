<?php
/*This program is free software: you can redistribute it and/or modify
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
 * This PHP script is responsible for analyzing possible schematron issues
 * @name: SchematronIssuesAnalyzer.php
 * @entities: 
 *      @functions{
 *          analyzeSchematronIssues($SchematronIssuesReport),
 *          getSchemaErrorAttributes($errorElement),
 *          getSchemaErrorText($errorElement),
 *          extractStringBetweenTokens($string,$initToken,$endToken),
 *          getSchemaErrorLocation($errorElement),
 *          getLocalName($attribute)
 *      }
 */
###############################################################################

/*! \fn analyzeSchematronIssues($SchematronIssuesReport) 
    \brief receives as input the XML string of the Schematron report ($SchematronIssuesReport) and 
    analyzes the string to extract the issues information and store this information in an structured object.
    \param $SchematronIssuesReport  XML string of the Schematron check output. 
 
    \return $schematronIssue an object containing all the found Schmatron issues according to the followind structure:
  
            $schematronIssue[$i]->text : is the content of the Schematron issue.
 
            $schematronIssue[$i]->location : contains the path of the issue The path
            is build concatenanting the element name (e.g. /MPD[1]/Period[1]/AdaptationSet[2]/Representation[1] ).
            giving the sibling number in square brackets.
 
            $schematronIssue[$i]->attributes : contains an array with the sames of the attributes affected by the issue;
*/
function analyzeSchematronIssues($SchematronIssuesReport){
    $reportStartString = "<svrl:failed-assert";
    $reportEndString = "</svrl:failed-assert>";
    
    $schematronIssue[0]->text = "";
    $schematronIssue[0]->location = "";
    $schematronIssue[0]->attributes = ""; 
    
    $reportXML = extractStringBetweenTokens($SchematronIssuesReport, $reportStartString, $reportEndString);
    $reportXML = str_replace("svrl:","",$reportXML);
    $reportXML = str_replace("failed-assert","assert",$reportXML);
    $reportXML = "<repots>".$reportXML."</repots>";
    
    $sxe = simplexml_load_string($reportXML);
    $dom_sxe = new DOMDocument('1.0');
    $dom_sxe = dom_import_simplexml($sxe);
    
    $numErrors = sizeof($sxe);
    for($i=0;$i<$numErrors;$i++){
        $errorElement = $dom_sxe->getElementsByTagName('assert')->item($i); // access the parent "MPD" in mpd file   
        $schematronIssue[$i]->text = getSchemaErrorText($errorElement);
        $schematronIssue[$i]->location = getSchemaErrorLocation($errorElement); // get mediapersentation duration from mpd level
        $schematronIssue[$i]->attributes = getSchemaErrorAttributes($errorElement); // get mediapersentation duration from mpd level
    }    	
    
    return $schematronIssue;
}

/*! \fn getSchemaErrorAttributes($errorElement)
    \brief takes a node element ("svrl:failed-assert") of the the Schematron report ($errorElement)
    and extract from the attribute "test" all the attributes belonging to the MDP that are mentioned in the issue.
    \param is a single $errorElement node of the Schematron XML report.
 
    \return returns an array $attribute containing all the atributes names found.
*/
function getSchemaErrorAttributes($errorElement){
    $attribute = array();
    $attributestString = $errorElement->getAttribute('test');
    
    while(true){
        $attStartPos = strpos($attributestString,"@"); 
        $attributestString = substr($attributestString,$attStartPos+1); 
        
        $attEndPos1 = strpos($attributestString," "); 
        $attEndPos2 = strpos($attributestString,")"); 
        
        if($attEndPos1!== false and $attEndPos2=== false){
            $attEndPos = $attEndPos1;
        }                   
        else if($attEndPos1=== false and $attEndPos2!== false){
            $attEndPos = $attEndPos2;
        }
        else if($attEndPos1!== false and $attEndPos2!== false and $attEndPos1<$attEndPos2){
            $attEndPos = $attEndPos1;
        }
        else if($attEndPos1!== false and $attEndPos2!== false and $attEndPos1>$attEndPos2){
            $attEndPos = $attEndPos2;
        }  
        
        if($attStartPos === false){
            return array_values(array_unique($attribute));
        }
        
        $attribute[] = substr($attributestString,0,$attEndPos);
        $attributestString = substr($attributestString,$attEndPos); 

    } 
}

/*! \fn getSchemaErrorText($errorElement)
    \brief takes a node element ("svrl:failed-assert") of the the Schematron report ($errorElement)
    and extract from the attribute "text" the text of the issue in that element.
    \param is a single $errorElement node of the Schematron XML report.
 
    \return returns an string $errorText containing the Schematron issue text.
*/
function getSchemaErrorText($errorElement){
    $errorText = "No error Text provided";

    foreach ($errorElement->childNodes as $node){
        $nodeName= $node->nodeName;
        if($nodeName === "text"){
            $errorText = $node->nodeValue;
        }
    }
    
    return $errorText;
}

/*! \fn extractStringBetweenTokens($string,$initToken,$endToken)
    \brief Get the data between an init and an end String/Character in an String, including the token strings.
    \param $string is the string from which data will be extracted.
    \param $initChar is the string/character that signals the init of the data that will be extracted from $string.
    \param $endChar is the string/character that signals the end of the data that will be extracted from $string.
    \return returns $outputArray that is a string that contains the extracted string.
 
    \return returns an string $outputString containing the Schematron issue text of false if not found the tokens.
*/
function extractStringBetweenTokens($string,$initToken,$endToken){
    $xmlRepStartIndex = strpos($string,$initToken); 
    if($xmlRepStartIndex === FALSE){
        return false; 
    }
    else{
        $xmlRepEndIndex = strrpos ($string,$endToken); 
        if($xmlRepEndIndex === FALSE or $xmlRepStartIndex>=$xmlRepEndIndex){
            return false;
        }
        else{
             return $outputString = substr($string,$xmlRepStartIndex,$xmlRepEndIndex-$xmlRepStartIndex+strlen($endToken)); 
        }
    }
}

/*! \fn getSchemaErrorLocation($errorElement)
    \brief takes a node element ("svrl:failed-assert") of the the Schematron report ($errorElement)
    and extract from the attribute "location" the MPD levels elements that are involved in the issue.
    Forms an string containt the path formed by the elements names: The path
    is build concatenanting the element name (e.g. /MPD[1]/Period[1]/AdaptationSet[2]/Representation[1] ).
    giving the sibling number in square brackets.
    \param is a single $errorElement node of the Schematron XML report.
 
    \return returns an string $completeElementPath containing the formed path.
*/
function getSchemaErrorLocation($errorElement){
    $locationString = $errorElement->getAttribute('location');
    $tok = strtok($locationString, "/*");
    $path = array();
    $path[]=$tok;
    while ($tok !== false){
        $tok = strtok("/*");
        $path[]=$tok;
    }
    
    $postion = array();
    $completeElementPath = "";
    for($i=0 ; $i< sizeof($path) ; $i++){
        $postion = getDataBetweenTokens($path[$i],"[","]");
        $pos = substr($path[$i], 1, strpos($path[$i], "[")-1);
        if(sizeof($postion)===1){
            $completeElementPath = $completeElementPath."/".$pos."[1]";
        }
        else if (sizeof($postion)===2){
            $completeElementPath = $completeElementPath."/".$pos."[".$postion[1]."]";
        }
        else{
            //Error
        }
    }
    
    return $completeElementPath;
}

/*! \fn getLocalName($attribute)
    \brief takes an attribute value and extracks the value of the local-name() tag.
    \param $attribute is a string that contains "local-name()='" strings
    that needs its value (after the equal sign "=") to be extracted.
 
    \return returns an string $tempString containing the value of the local-name() tag.
*/
function getLocalName($attribute){   
    $localNameTag = "local-name()='";
    $tempString = "";
    $pos = strpos($attribute,$localNameTag);
    $tempString=substr($attribute,$pos+strlen($localNameTag));
    
    $pos = strpos($tempString,"'");
    
    $tempString=substr($tempString,0,$pos);
    
    return $tempString;
}