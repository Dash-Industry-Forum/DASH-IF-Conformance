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
 * This PHP script is responsible for downloading segment(s)
 * pointed to by the MPD.
 * @name: SegmentDownload.php
 * @entities:
 *      @functions{
 *          download_data($directory, $array_file),
 *          remote_file_size($url),
 *          partial_download($url, $begin, $end, &$ch)
 *      }
 */
###############################################################################

/*
 * Download segments
 * @name: download_data
 * @input: $directory - download directory for the segment(s)
 *         $array_file - URL of the segment(s) to be downloaded
 *         $is_dolby - Boolean: is the media a Dolby codec?
 * @output: $file_sizearr - array of original size(s) of the segment(s)
 */
function download_data($directory, $array_file, $is_subtitle_rep, $is_dolby)
{
    global $session, $progress_report, $current_period, $reprsentation_mdat_template, $missinglink_file, $current_adaptation_set, $current_representation,
           $hls_byte_range_begin, $hls_byte_range_size, $hls_manifest, $hls_mdat_file, $low_latency_dashif_conformance, $availability_times;

    $mdat_file = (!$hls_manifest) ?
            'Period' . $current_period . '/' . str_replace(array('$AS$', '$R$'), array($current_adaptation_set, $current_representation), $reprsentation_mdat_template) :
            $hls_mdat_file;
    $sizefile = fopen($session->getDir() . '/' . $mdat_file . '.txt', 'a+b'); //create text file containing the original size of Mdat box that is ignored

    $segment_count = sizeof($array_file);
    $initoffset = 0; // Set pointer to 0
    $mdat_index = 0;
    $totaldownloaded = 0; // bytes downloaded
    $totalDataDownloaded = 0;
    $totalDataProcessed = 0; // bytes processed within segments
    $downloadMdat = 0;
    $downloadAll = $is_dolby;
    $byte_range_array = array();
    $ch = curl_init();

    if ($low_latency_dashif_conformance) {
        $count = sizeof($availability_times[$current_adaptation_set][$current_representation]['ASAST']);
        $media_segment_index = ($count == sizeof($array_file)) ? 0 : 1;
        $start_index = 0;
    }

    # Iterate over $array_file
    for ($index = 0; $index < $segment_count; $index++) {
        $filePath = $array_file[$index];
        fwrite(STDERR, "Downloading $filePath\n");
        $filename = basename($filePath);
        $file_information = remote_file_size($filePath);
        print_r($file_information);
        $file_exists = $file_information[0];
        $file_size = ($hls_manifest && $hls_byte_range_size) ? $hls_byte_range_size[$index] + $hls_byte_range_begin[$index] : $file_information[1];

        if (!$file_exists) {
            $missing = (!$hls_manifest) ?
                    fopen($session->getDir() . '/Period' . $current_period . '/' . $missinglink_file . '.txt', 'a+b') :
                    fopen($session->getDir() . '/' . $missinglink_file . '.txt', 'a+b');
            fwrite($missing, $filePath . "\n");
            continue;
        }

        if ($file_size == 0) {
            //download all
            $content = partial_download($filePath, $ch);
            if (!$content) {
                // add to missing link
                continue;
            }

            $sizepos = $sizepos = ($hls_byte_range_begin) ? $hls_byte_range_begin[$index] : 0;
            $location = 1;
            $box_name = null;
            $box_size = 0;
            $newfile = fopen($directory . "/" . $filename, 'a+b');

            # Assure that the pointer doesn't exceed size of downloaded bytes
            $byte_array = unpack('C*', $content);
            while ($location < sizeof($byte_array)) {
                $diff = sizeof($byte_array) - $location;
                if ($diff < 3) {
                    break;
                }

                $box_size = $byte_array[$location] * 16777216 + $byte_array[$location + 1] * 65536 + $byte_array[$location + 2] * 256 + $byte_array[$location + 3];
                $box_name = substr($content, $location + 3, 4);
                if ($downloadAll || $box_name != 'mdat') {
                    fwrite($newfile, substr($content, $location - 1, $box_size));
                } else {
                    # If it is mdat box
                    # If mdat downloading is chosen
                    #   Stuff complete mdat data with zeros
                    # Else
                    #   Add the original size of the mdat to text file without the name and size bytes(8 bytes)
                    #   Copy only the mdat name and size to the segment
                    if ($downloadMdat) {
                        fwrite($sizefile, ($initoffset + $sizepos + 8) . " " . 0 . "\n");
                        fwrite($newfile, substr($content, $location - 1, 8));
                        fwrite($newfile, str_pad("0", $box_size - 8, "0"));
                    } else {
                        fwrite($sizefile, ($initoffset + $sizepos + 8) . " " . ($box_size - 8) . "\n");
                        fwrite($newfile, substr($content, $location - 1, 8));

                        ## For DVB subtitle checks related to mdat content
                        ## Save the mdat boxes' content into xml files
                        if ($is_subtitle_rep) {
                            $subtitle_xml_string = '<subtitle>';
                            $mdat_file = $directory . '/Subtitles/' . $mdat_index . '.xml';
                            fopen($mdat_file, 'w');
                            chmod($mdat_file, 0777);
                            $mdat_index++;

                            $text = substr($content, ($initoffset + $location + 7), ($box_size - 7));
                            $text = substr($text, strpos($text, '<tt'));
                            $subtitle_xml_string .= $text;

                            $subtitle_xml_string = substr($subtitle_xml_string, 0, strrpos($subtitle_xml_string, '>') + 1);
                            $subtitle_xml_string .= '</subtitle>';
                            $mdat_data = simplexml_load_string($subtitle_xml_string);
                            $mdat_data->asXML($mdat_file);
                        }
                    }
                }

                $sizepos = $sizepos + $box_size;
                $location = $location + $box_size;
                $file_size = $file_size + $box_size;
                $totalDataDownloaded = $totalDataDownloaded + $box_size;
                $percent = (int) (100 * $index / (sizeof($array_file) - 1));
            }

            # Modify node and sav it to a progress report
        } else {
            $sizepos = $sizepos = ($hls_byte_range_begin) ? $hls_byte_range_begin[$index] : 0;
            $remaining = $file_size - $sizepos;
            while ($sizepos < $file_size) {
                $location = 1; // temporary pointer
                $name = null; // box name
                $box_size = 0; // box size
                $newfile = fopen($directory . "/" . $filename, 'a+b'); // create an empty mp4 file to contain data needed from remote segment

                # Download the partial content and unpack
                $content = partial_download($filePath, $ch, $sizepos, $sizepos + 1500);
                $byte_array = unpack('C*', $content);
                $byte_range_array = array_merge($byte_range_array, $byte_array);
                # Update the total size of downloaded data
                $totalDataDownloaded = $totalDataDownloaded + 1500;

                # Assure that the pointer doesn't exceed size of downloaded bytes
                while ($location < sizeof($byte_array)) {
                    $diff = sizeof($byte_array) - $location;
                    if ($diff < 3) {
                        //$prev_data = array_slice($byte_array, $location, $diff);
                        break;
                    } else {
                        $box_size = $byte_array[$location] * 16777216 + $byte_array[$location + 1] * 65536 + $byte_array[$location + 2] * 256 + $byte_array[$location + 3];
                    }

                    $size_copy = $box_size; // keep a copy of size to add to $location when it is replaced by remaining
                    if ($box_size > $remaining) {
                        $size_copy = $remaining;
                    }

                    if ($segment_count === 1) { // if presentation contain only single segment
                        $totaldownloaded += (!$hls_byte_range_begin) ? $box_size : $size_copy;   // total data being processed
                        $percent = (int) (100 * $totaldownloaded / $file_size); //get percent over the whole file size
                    } else {
                        $percent = (int) (100 * $index / (sizeof($array_file) - 1)); // percent of remaining segments
                    }

                    $name = substr($content, $location + 3, 4); //get box name exist in the next 4 bytes from the bytes containing the size
                    if ($downloadAll || $name != 'mdat') {
                        # If it is not mdat box download it
                        # The total size being downloaded is location + size
                        # If the amount of byte processed < the data downloaded at begining
                        #   Copy the whole data to the new mp4 file
                        # Else
                        #   Download the rest of the box from the remote segment
                        #   Copy the rest to the file
                        $total = $location + ((!$hls_byte_range_begin) ? $box_size : $size_copy);
                        if ($total < sizeof($byte_array)) {
                            fwrite($newfile, substr($content, $location - 1, ((!$hls_byte_range_begin) ? $box_size : $size_copy)));
                        } else {
                            $rest = partial_download($filePath, $ch, $sizepos, $sizepos + ((!$hls_byte_range_begin) ? $box_size : $size_copy) - 1);
                            $totalDataDownloaded = $totalDataDownloaded + ((!$hls_byte_range_begin) ? $box_size : $size_copy) - 1;
                            fwrite($newfile, $rest);
                        }
                    } else {
                        # If it is mdat box
                        # If mdat downloading is chosen
                        #   Stuff complete mdat data with zeros
                        # Else
                        #   Add the original size of the mdat to text file without the name and size bytes(8 bytes)
                        #   Copy only the mdat name and size to the segment
                        if ($downloadMdat) {
                            fwrite($sizefile, ($initoffset + $sizepos + 8) . " " . 0 . "\n");
                            fwrite($newfile, substr($content, $location - 1, 8));
                            fwrite($newfile, str_pad("0", ((!$hls_byte_range_begin) ? $box_size : $size_copy) - 8, "0"));
                        } else {
                            fwrite($sizefile, ($initoffset + $sizepos + 8) . " " . (((!$hls_byte_range_begin) ? $box_size : $size_copy) - 8) . "\n");
                            fwrite($newfile, substr($content, $location - 1, 8));

                            ## For DVB subtitle checks related to mdat content
                            ## Save the mdat boxes' content into xml files
                            if ($is_subtitle_rep) {
                                $subtitle_xml_string = '<subtitle>';
                                $mdat_file = $directory . '/Subtitles/' . $mdat_index . '.xml';
                                fopen($mdat_file, 'w');
                                chmod($mdat_file, 0777);
                                $mdat_index++;
                                $total = $location + $box_size;
                                if ($total < sizeof($byte_array)) {
                                    $text = substr($content, ($initoffset + $location + 7), ($box_size - 7));
                                    $text = substr($text, strpos($text, '<tt'));
                                    $subtitle_xml_string .= $text;
                                    //fwrite($mdat_file, substr($content, ($initoffset + $sizepos + 8), ($box_size - 1)));
                                } else {
                                    $rest = partial_download($filePath, $ch, $sizepos + 8, $sizepos + $box_size - 1);
                                    $text = $rest;
                                    $text = substr($text, strpos($text, '<tt'));
                                    $subtitle_xml_string .= $text;
                                    //fwrite($mdat_file, $rest);
                                }
                                $subtitle_xml_string = substr($subtitle_xml_string, 0, strrpos($subtitle_xml_string, '>') + 1);
                                $subtitle_xml_string .= '</subtitle>';
                                $mdat_data = simplexml_load_string($subtitle_xml_string);
                                $mdat_data->asXML($mdat_file);
                            }
                        }
                    }

                    $sizepos = $sizepos + ((!$hls_byte_range_begin) ? $box_size : $size_copy); // move size pointer
                    $remaining = $file_size - $sizepos;
                    $location = $location + $box_size; // move location pointer

                    if ($remaining == 0) {
                        break;
                    }
                }

                # Modify node and sav it to a progress report
            }
        }

        $initoffset = (!$hls_byte_range_begin) ? $initoffset + $file_size : 0;
        $totalDataProcessed = $totalDataProcessed + $totalDataDownloaded;
        $sizearray[] = $file_size;

        fflush($newfile);
        fclose($newfile);
    }

    # All done
    curl_close($ch);
    fflush($sizefile);
    fclose($sizefile);
//    fflush($missing);
//    fclose($missing);

    if (!isset($sizearray)) {
        $sizearray = 0;
    }

    return $sizearray;
}

/*
 * Get the size of the segment remotely without downloading it
 * @name: remote_file_size
 * @input: $url - URL of the segment of which the size is requested
 * @output: FALSE or segment size
 */
function remote_file_size($url)
{
    $file_exists = false;
    $file_size = 0;

    # Get all header information
    $data = get_headers($url, true);
    if (
        $data[0] === 'HTTP/1.1 404 Not Found' ||
        $data[0] === 'HTTP/1.0 404 Not Found' ||
        $data[0] === 'HTTP/2 404 Not Found'
    ) {
        return [$file_exists, $file_size];
    }

    $file_exists = true;
    # Look up validity
    if (isset($data['Content-Length'])) {
        $file_size = (int) $data['Content-Length'];
    }

    return [$file_exists, $file_size];
}

/*
 * Download partial bytes of a file by giving file location, start and end byte
 * @name: partial_download
 * @input: $url - URL of the segment of which the size is requested
 *         $begin - byte to start from
 *         $end - byte to end at
 *         $ch - curl object
 * @output: downloaded content
 */
function partial_download($url, &$ch, $begin = 0, $end = 0)
{
    global $session;

    # Temporary container for partial segments downloaded
    $temp_file = $session->getDir() . '//' . "getthefile.mp4";
    if (!($fp = fopen($temp_file, "w+"))) {
        exit;
    }

    # Add curl options and execute
    $options = array(
        CURLOPT_URL => $url,
        CURLOPT_FAILONERROR => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 500,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 0,
        CURLOPT_USERAGENT => 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)',
        CURLOPT_FILE => $fp
    );
    curl_setopt_array($ch, $options);
    if ($end != 0) {
        $range = $begin . '-' . $end;
        curl_setopt($ch, CURLOPT_RANGE, $range);
    }

    curl_exec($ch);

    # Check the downloaded content
    fclose($fp);
    $content = file_get_contents($temp_file);


    return $content;
}
