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

function processHLS(){
    global $session_dir, $mpd_url, $progress_xml, $progress_report, $hls_manifest_type;
    $StreamInfURLArray = array();
    $IframeURLArray = array();
    $XMediaURLArray = array();
            
    // Open related files
    $progress_xml = simplexml_load_string('<root><Profile></Profile><PeriodCount></PeriodCount><Progress><percent>0</percent><dataProcessed>0</dataProcessed><dataDownloaded>0</dataDownloaded><CurrentAdapt>1</CurrentAdapt><CurrentRep>1</CurrentRep></Progress><completed>false</completed></root>');
    $progress_xml->asXml($session_dir . '/' . $progress_report);
    
    // Read each line of manifest file into an array
    $m3u8 = playlistToArray($mpd_url);
    
    if($m3u8){
        if($hls_manifest_type=="MasterPlaylist"){
        // extract the urls to stream_inf, iframe and x_media playlists from master playlist and save them in separate arrays
            list($StreamInfURLArray, $IframeURLArray,$XMediaURLArray) = playlistURLs($m3u8);
            
           
            
        }
        else{
            if(strpos(file_get_contents($mpd_url),"#EXT-X-I-FRAMES-ONLY")) 
                $IframeURLArray[0] =$mpd_url;
            else
                $StreamInfURLArray[0]=$mpd_url;
        } 
        // download segments from stream_inf playlists, x_media playlists, and Iframe playlists
        validate_segment_hls(array($StreamInfURLArray, $IframeURLArray,$XMediaURLArray));  
        
    }
}

#put each line of a playlist into an array
function playlistToArray($url) {
    global $hls_manifest_type;
    $m3u8 = file_get_contents($url);
    if(!$m3u8)
        return false;
    
    $array = preg_split('/$\n?^/m', trim($m3u8), NULL, PREG_SPLIT_NO_EMPTY);
    if($array[0]!= '#EXTM3U')
        die("Data does not look like a m3u8 file, first line is not #EXTM3U! Exiting..");
    else{
        if (strpos("EXT-X-MEDIA" || "EXT-X-STREAM-INF" || "EXT-X-I-FRAME-STREAM-INF")){
            $hls_manifest_type = "MasterPlaylist";
            return $array;
        }
        else {
            $hls_manifest_type = "MediaPlaylist"; 
            return $array;
        }
    }
        
}

//extract the urls to stream_inf, iframe and x_media playlists from master playlist and save them in separate arrays
function playlistURLs($array){
    global $mpd_url;
    
    $base_url = dirname($mpd_url);
    
    //get the unique urls of media playlists
    foreach ($array as $line) {
        $line = trim($line);
        if ($line[0] != '#') { // true just in case of checking the stream inf playlist
            //each line that is not started with '#' is a url 
            $streamURL = getAbsURL($line, $base_url);
            if(!in_array($streamURL, $StreamInfURLArray)){ //check the url not to be a duplicate
                $StreamInfURLArray[] = $streamURL;
            }
        }
        else{
            //geting the iframe playlist urls
            if(strpos($line,"EXT-X-I-FRAME-STREAM-INF") !== FALSE) {
                $IFrameURL = getURLTag($line, $base_url);
                if($IFrameURL && !in_array($IFrameURL, $IframeURLArray)){ //check the url not to be a duplicate
                    $IframeURLArray[] = $IFrameURL;
                }
            }

            //getting the x_media playlist  urls
            if(strpos($line,"EXT-X-MEDIA") !== FALSE) {
                $XMediaURL = getURLTag($line, $base_url);
                if($XMediaURL && !in_array($XMediaURL, $XMediaURLArray)){ //check the url not to be a duplicate
                    $XMediaURLArray[] = $XMediaURL;
                }
            }
        }    
    }

    return [$StreamInfURLArray, $IframeURLArray,$XMediaURLArray];
}

//returns the absolute url 
function getAbsURL($line, $base_url){
    if (isAbsoluteURL($line)) { //if absolute url
        return $line; 
    }
    else{//if relative url
        return $base_url . '/' . $line;
    }
}

//return the url that is mentioned in playlist using a tag
function getURLTag($line, $base_url){
    if (strpos($line, 'URI') !== false) {
        $sub = substr($line, strpos($line,"URI=")+5);
        $x = substr($sub,0,strpos($sub,"\"")); //get value of URI tag 
        return getAbsURL($x, $base_url);   //return the absolute url
    }
    else
        return false;
}

/*get the urls to the segments of a playlist
 *  input is the url to the playlist
 *  output is an array of segments' urls
 */
function segmentURLs($url){
    $segmentURLs = array();
    $base_url = dirname($url);
    $array = playlistToArray($url);
    
    foreach ($array as $line) {
        $line = trim($line);
        if ($line[0] != '#') {
            $segment = getAbsURL($line, $base_url);
            if(!in_array($segment, $segmentURLs)){
                $segmentURLs[] = $segment;
            }
        }
    }
    return $segmentURLs;
}

/*
 * segmentDonload downloads the segments of a playlist and returns the size of downloaded the content
 * inputs are the url to the playlist and the type of the playlist
 */
function segmentDownload($urlarray, $type){
    global $session_dir, $hls_iframe_file, $hls_mdat_file, $hls_current_index, $hls_byte_range_begin, $hls_byte_range_size;
    
    $segment_urls = array();
    $sizearray=array();
    
    if(!$urlarray)
        return [$segment_urls, $sizearray];
    
    ## Define a directory for downloading segments
    $dir = $session_dir.'/'.$type;
    mkdir($dir, 0777, true);
    
    ## Iterate over the playlists, download the segments for each one, and save the segments of each playlist into a different folder
    foreach($urlarray as $url){
        // create a new folder for this playlist
        $tmpdir = $dir.'/'.$hls_current_index.'/';
        mkdir($tmpdir, 0777, true);
        
        // extract the url of the segments of a playlist
        $segmentURLs = segmentURLs($url);
        if($type == $hls_iframe_file){
            $tmparray = playlistToArray($url);
            $array = segURLs($tmparray, $segmentURLs);
            if(!$array)
                continue;
        }
        
        // download data of the playlist into the folder and return size of the downloaded content 
        $segment_urls[] = $segmentURLs;
        $sizearray[] = download_data($tmpdir, ($type == $hls_iframe_file) ? $array : $segmentURLs);
        
        rename_file($session_dir . '/' . $hls_mdat_file . '.txt', $session_dir . '/' . $type . '_' . $hls_current_index . '_' . $hls_mdat_file . '.txt');
        if($type == $hls_iframe_file){
            $hls_byte_range_begin = array();
            $hls_byte_range_size = array();
        }
        
        $hls_current_index++;
    }
    
    $hls_current_index = 0;
    return [$segment_urls, $sizearray];
}

function segURLs($tmparray,$segmentURL){
    global $hls_byte_range_begin, $hls_byte_range_size;
    $array = array();
    $i = 0;
    foreach ($tmparray as $line) { 
        if(strpos($line,"EXT-X-MAP") !== FALSE)
        {
            if (strpos($line,"@")!== FALSE ){
                $hls_byte_range_begin[]= (int)(substr($line,strpos($line,"@")+1,strlen($line)-strpos($line,"@")));
                $st = strpos($line,"BYTERANGE=")+11;
                $en = strpos($line,"@");
                $hls_byte_range_size[] = (int)(substr($line,$st, $en-$st)); 
                $array[]=$segmentURL[0]; 
            }
            else{
                if($hls_byte_range_size[$i-1]){
                    $st = strpos($line,"BYTERANGE=")+11;
                    $hls_byte_range_begin[] = $hls_byte_range_size[$i-1];
                    $hls_byte_range_size[] = (int)(substr($line,$st));
                }
                else{
                    return NULL;
                }
            }
        }
        if(strpos($line,"EXT-X-BYTERANGE") !== FALSE){
            if (strpos($line,"@")!== FALSE ){
                $hls_byte_range_begin[]= (int)(substr($line,strpos($line,"@")+1));
                $st = strpos($line,":")+1;
                $en = strpos($line,"@");
                $hls_byte_range_size[] = (int)(substr($line,$st, $en-$st)); 
                $array[]=$segmentURL[0];
            }
            else{
                if($hls_byte_range_size[$i-1]){
                    $st = strpos($line,":")+1;
                    $hls_byte_range_begin[] = $hls_byte_range_size[$i-1];
                    $hls_byte_range_size[] = (int)(substr($line,$st));
                }
                else{
                    return NULL;
                }
            }
        }
        $i++;
    }
    return $array;
}

function determineMediaType($path, $tag){
    global $hls_media_types;
    
    if(file_exists($path)){
        $xml = get_DOM($path);
        if($path){
            $hdlr = $xml->getElementsByTagName('hdlr')->item(0);
            $hdlr_type = $hdlr->getAttribute('handler_type');
            $sdType = $xml->getElementsByTagName($hdlr_type.'_sampledescription')->item(0)->getAttribute('sdType');

            switch($hdlr_type){
                case 'vide':
                    $hls_media_types['video'][$sdType][] = $tag;
                    break;
                case 'soun':
                    $hls_media_types['audio'][$sdType][] = $tag;
                    break;
                case 'subt':
                    $hls_media_types['subtitle'][$sdType][] = $tag;
                    break;
                /*case '':
                    $hls_media_types['closed-caption'][$sdType][] = $tag;
                    break;*/
                default:
                    $hls_media_types['unknown'][$sdType][] = $tag;
                    break;
            }
            determineMediaType($hdlr_type, $sdType, $hls_tag);
        }
    }
}