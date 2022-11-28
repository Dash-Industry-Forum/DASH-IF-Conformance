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

function validate_segment($adaptationDirectory, $representationDirectory, $period, $adaptation_set, $representation, $segment_url, $is_subtitle_rep)
{
    global $sizearray, $current_adaptation_set, $current_representation;


    $sizearray = array();
    $codecs = ($adaptation_set['codecs'] == null) ? $representation['codecs'] : $adaptation_set['codecs'];
    $is_dolby = (($codecs != null) and
                 ((substr($codecs, 0, 4) == "ac-3") or
                  (substr($codecs, 0, 4) == "ec-3") or
                  (substr($codecs, 0, 4) == "ac-4")));
    $sizearray = download_data($representationDirectory, $segment_url, $is_subtitle_rep, $is_dolby);
    if ($sizearray != 0) {
        ## Put segments in one file
        assemble($representationDirectory, $segment_url, $sizearray);

        ## Create config file with the flags for segment validation
        $config_file_loc = config_file_for_backend($period, $adaptation_set, $representation, $representationDirectory, $is_dolby);

        ## Run the backend
        $returncode = run_backend($config_file_loc, $representationDirectory);

        $varinfo = var_export($adaptation_set, true);

        ## Analyse the results and report them
        $file_location = analyze_results($returncode, $adaptationDirectory, $representationDirectory);
    } else {
        ## Save to progress report that the representation does not exist
        $file_location[] = 'notexist';
    }

    return $file_location;
}

function validate_segment_hls($URL_array, $CodecArray)
{
    global $session__dir, $hls_stream_inf_file, $hls_x_media_file, $hls_iframe_file, $hls_tag;

    $is_dolby = false;
    for ($i = 0; $i < sizeof($CodecArray); $i++) {
        $is_dolby = (($CodecArray[$i] != null) and
                     ((substr($CodecArray[$i], 0, 4) == "ac-3") or
                     (substr($CodecArray[$i], 0, 4) == "ec-3") or
                     (substr($CodecArray[$i], 0, 4) == "ac-4")));
        if ($is_dolby) {
            break;
        }
    }

    $tag_array = array($hls_stream_inf_file, $hls_iframe_file, $hls_x_media_file);
    for ($i = 0; $i < sizeof($URL_array); $i++) {
        list($segmentURL, $sizeArray) = segmentDownload($URL_array[$i], $tag_array[$i], $is_dolby);

        for ($j = 0; $j < sizeof($segmentURL); $j++) {
            if ($sizeArray[$j] != 0) {
                $hls_tag = $tag_array[$i] . '_' . $j;

                ## Put segments in one file
                assemble($session__dir . '/' . $tag_array[$i] . '/' . $j . '/', $segmentURL[$j], $sizeArray[$j]);

                ## Create config file with the flags for segment validation
                $config_file_loc = config_file_for_backend(null, null, null, $hls_tag, $is_dolby);

                ## Run the backend
                $returncode = run_backend($config_file_loc);

                ## Analyse the results and report them
                $file_location[] = analyze_results($returncode, $session__dir . '/' . $tag_array[$i], $j);

                ## Determine media type based on atomxml information
                determineMediaType($session__dir . '/' . $tag_array[$i] . '/' . $j . '.xml', $hls_tag);
            } else {
                ## Save to progress report that the representation does not exist
                $file_location[] = 'notexist';
            }
        }
    }

    return $file_location;
}

function assemble($representationDirectory, $segment_urls, $sizearr)
{
    global $segment_accesses,$current_adaptation_set, $current_representation, $hls_manifest, $hls_tag;



    $index = ($segment_accesses[$current_adaptation_set][$current_representation][0]['initialization']) ? 0 : 1;

    for ($i = 0; $i < sizeof($segment_urls); $i++) {
        $fp1 = fopen("$representationDirectory/assembled.mp4", 'a+');

        $segment_name = basename($segment_urls[$i]);
        if (file_exists($representationDirectory . "/" . $segment_name)) {
            $size = $sizearr[$i]; // Get the real size of the file (passed as inupt for function)
            $file2 = file_get_contents($representationDirectory . "/" . $segment_name); // Get the file contents

            fwrite($fp1, $file2); // dump it in the container file
            fclose($fp1);
            file_put_contents("$representationDirectory/sizes.txt", $index . " " . $size . "\n", FILE_APPEND); // add size to a text file to be passed to conformance software

            $index++; // iterate over all segments within the segments folder
        }
    }
}

function analyze_results($returncode, $curr_adapt_dir, $representationDirectory)
{
    global $mpdHandler,
            $string_info, $current_adaptation_set, $current_representation, 
            $hls_manifest, $hls_tag, $hls_error_file, $hls_info_file;

    $adaptation_set = $mpdHandler->getFeatures()['Period'][$mpdHandler->getSelectedPeriod()]['AdaptationSet'][$current_adaptation_set];
    $representation = $adaptation_set['Representation'][$current_representation];
    ///\todo refactor "Make these into proper logger messages
    if ($returncode != 0) {
        #error_log('Processing AdaptationSet ' . $current_adaptation_set . ' Representation ' . $current_representation . ' returns: ' . $returncode);
        if (filesize("$representationDirectory/stderr.txt") == 0) {
            if (!$hls_manifest) {
                if ($adaptation_set['mimeType'] == 'application/ttml+xml' || $adaptation_set['mimeType'] == 'image/jpeg') {
                    file_put_contents($representationDirectory . '/stderr.txt', "### error:  \n###        Failed to process Adaptation Set " . $current_adaptation_set . ', Representation ' . $current_representation . "!, as mimeType= '" . $adaptation_set['mimeType'] . "' is not supported");
                } elseif ($representation['mimeType'] == "application/ttml+xml" || $representation['mimeType'] == "image/jpeg") {
                    file_put_contents($representationDirectory . '/stderr.txt', "### error:  \n###        Failed to process Adaptation Set " . $current_adaptation_set . ', Representation ' . $current_representation . "!, as mimeType= '" . $representation['mimeType'] . "' is not supported");
                } else {
                    file_put_contents($representationDirectory . '/stderr.txt', "### error:  \n###        Failed to process Adaptation Set " . $current_adaptation_set . ', Representation ' . $current_representation . '!');
                }
            } else {
              ///\TodoRefactor -- Also fix for hls
              /*
                $tag_array = explode('_', $hls_tag);
                $files = array_diff(scandir($session__dir . '/' . $tag_array[0] . '/' . $tag_array[1] . "/"), array('.', '..'));
                if (strpos($files[2], 'webvtt') !== false || strpos($files[2], 'xml') !== false || strpos($files[2], 'html') !== false) {
                    file_put_contents($session__dir . '/' . 'stderr.txt', "### error:  \n###        Failed to process " . $tag_array[0] . ' with index ' . $tag_array[1] . ', as the file type is ' . explode('.', $files[2])[1] . '!');
                } else {
                    file_put_contents($session__dir . '/' . 'stderr.txt', "### error:  \n###        Failed to process " . $tag_array[0] . ' with index ' . $tag_array[1] . '!');
                }
               */
            }
        }
    }

    ///\DoubleCheck create leafinfo.txt
    //rename($ession__dir . "/leafinfo.txt", "$representationDirectory/leafInfo.txt");

    if (!$hls_manifest) {
        ## Check segment duration and start times against MPD times.
        loadAndCheckSegmentDuration();
    } else {
        $error_log = str_replace('$hls_tag$', $hls_tag, $hls_error_file);
    }

    ///\TodoRefactor Check when sampledata is generated, and create it in the correct directory
    //if (file_exists($session__dir . '/sample_data.txt') && !$hls_manifest) {
    //    rename($session__dir . '/sample_data.txt', "$representationDirectory/sampleData.xml");
    //}
}

function run_backend($configFile, $representationDirectory = "")
{
    global $session, $logger;

    $sessionDirectory = $session->getDir();


    ## Select the executable version
    ## Copy segment validation tool to session folder
    $validatemp4 = 'ValidateMP4.exe';
    $validatemp4_path = dirname(__FILE__) . "/../ISOSegmentValidator/public/linux/bin/";
    copy($validatemp4_path . $validatemp4, "$sessionDirectory/$validatemp4");
    chmod("$sessionDirectory/$validatemp4", 0777);

    ## Execute backend conformance software
    $command = "timeout -k 30s 30s $sessionDirectory/$validatemp4 -logconsole -atomxml -configfile " . $configFile;
    $output = [];
    $returncode = 0;
    chdir($sessionDirectory);


    $t = time();
    exec($command, $output, $returncode);
    $et = time();

    $moveAtom = true;

    $moveAtom &= $logger->test(
        "Health Checks",
        "Segment Validation",
        "ISOSegmentValidator runs successful",
        $returncode == 0,
        "FAIL",
        "Ran succesful on $configFile; took ". ($et - $t) . "seconds",
        "Issues with $configFile; Returncode $returncode; took " . ($et - $t) . " seconds"
    );

    $moveAtom &= $logger->test(
        "Health Checks",
        "Segment Validation",
        "AtomInfo written",
        file_exists("$sessionDirectory/atominfo.xml"),
        "FAIL",
        "Atominfo for $representationDirectory exists",
        "Atominfo for $representationDirectory missing"
    );

$atomXmlString = file_get_contents("$sessionDirectory/atominfo.xml");
$STYPBeginPos = strpos($atomXmlString, "<styp");
if ($STYPBugPos !== false){
  //try with newline for prettyprinted
  $emptyCompatBrands = strpos($atomXmlString, "compatible_brands='[\n  </styp>", $STYPBeginPos);
  if ($emptyCompatBrands === false){
    //Also try without newline just to be sure
    $emptyCompatBrands = strpos($atomXmlString, "compatible_brands='[</styp>", $STYPBeginPos);
  }
  if ($emptyCompatBrands !== false){
    $fixedAtom= substr_replace($atomXmlString, "]'>", $emptyCompatBrands+20, 0);
    file_put_contents("$sessionDirectory/atominfo.xml", $fixedAtom);
  }

}

$xml = DASHIF\Utility\parseDOM("$sessionDirectory/atominfo.xml", 'atomlist');
$moveAtom &= $logger->test(
    "Health Checks",
    "Segment Validation",
    "AtomInfo contains valid xml",
    $xml !== false,
    "FAIL",
    "Atominfo for $representationDirectory has valid xml",
    "Atominfo for $representationDirectory has invalid xml"
);

    $moveAtom &= $logger->test(
        "Health Checks",
        "Segment Validation",
        "AtomInfo < 100Mb",
        filesize("$sessionDirectory/atominfo.xml") < (100 * 1024 * 1024),
        "FAIL",
        "Atominfo for $representationDirectory < 100Mb",
        "Atominfo for $representationDirectory is ". filesize("$sessionDirectory/atominfo.xml")
    );


    if (!$moveAtom){
      fwrite(STDERR, "Ignoring atomfile for $representationDirectory\n");
      if ($representationDirectory != "") {
          rename("$sessionDirectory/atominfo.xml", "$representationDirectory/errorAtomInfo.xml");
      }
    }else{
      fwrite(STDERR, "Using atomfile for $representationDirectory\n");
      if ($representationDirectory != "") {
          rename("$sessionDirectory/atominfo.xml", "$representationDirectory/atomInfo.xml");
      }
    }

    return $returncode;
}

function config_file_for_backend($period, $adaptation_set, $representation, $representationDirectory, $is_dolby)
{
    global $additional_flags, $suppressatomlevel, $current_adaptation_set, $current_representation, $hls_manifest, $hls_mdat_file;

    if (!$hls_manifest) {
        $file = fopen("$representationDirectory/segmentValidatorConfig.txt", 'w');
        fwrite($file, "$representationDirectory/assembled.mp4 \n");
              ///\TodoRefactor -- Generate correct infofile
        /*
        fwrite($file, "-infofile" . "\n");
        fwrite($file, "$representationDirectory/infoFile.txt \n");
         */
    } else {
              ///\TodoRefactor -- Also fix for hls
              /*
        $file = fopen($session__dir . '/config_file.txt', 'w');
        fwrite($file, $session__dir . '/' . $rep_dir_name . '.mp4 ' . "\n");
        fwrite($file, "-infofile" . "\n");
        fwrite($file, $session__dir . '/' . $rep_dir_name . '.txt' . "\n");
               */
    }

    if (!$is_dolby) {
              ///\TodoRefactor -- Generate correct offsetfile
      /*
        fwrite($file, "-offsetinfo" . "\n");
        fwrite($file, "$representationDirectory/offsetInfo.txt \n");
        */
    }

    $flags = (!$hls_manifest) ? construct_flags($period, $adaptation_set, $representation) . $additional_flags : $additional_flags;
    $piece = explode(" ", $flags);
    foreach ($piece as $pie) {
        if ($pie !== "") {
            fwrite($file, $pie . "\n");
        }
    }
    if ($suppressatomlevel) {
        fwrite($file, '-suppressatomlevel' . "\n");
    }

    fclose($file);
    if (!$hls_manifest) {
        return "$representationDirectory/segmentValidatorConfig.txt";
    }
              ///\TodoRefactor -- Also fix for hls
    //return $session__dir . '/config_file.txt';
}

function loadAndCheckSegmentDuration()
{
    global $mpdHandler, $current_adaptation_set,$current_representation;
    global $session;

    $adaptation_set = $mpdHandler->getFeatures()['Period'][$mpdHandler->getSelectedPeriod()]['AdaptationSet'][$current_adaptation_set];
    $timeoffset = 0;
    $timescale = 1;
    $segmentAlignment = ($adaptation_set['segmentAlignment']) ? ($adaptation_set['segmentAlignment'] == "true") : false;
    $subsegmentAlignment = ($adaptation_set['subsegmentAlignment']) ? ($adaptation_set['subsegmentAlignment'] == "true") : false;
    $bitstreamSwitching = ($adaptation_set['bitstreamSwitching']) ? ($adaptation_set['bitstreamSwitching'] == "true") : false;

    if ($segmentAlignment || $subsegmentAlignment || $bitstreamSwitching) {
        $leafInfo = array();

        $representation = $adaptation_set['Representation'][$current_representation];
        $timeoffset = 0;
        $timescale = 1;
        $duration = 0;

        if (!empty($adaptation_set['SegmentTemplate'][0]['timescale'])) {
            $timescale = $adaptation_set['SegmentTemplate'][0]['timescale'];
        }

        if (!empty($adaptation_set['SegmentTemplate'][0]['presentationTimeOffset'])) {
            $timeoffset = $adaptation_set['SegmentTemplate'][0]['presentationTimeOffset'];
        }

        if (!empty($adaptation_set['SegmentTemplate'][0]['duration'])) {
            $duration = $adaptation_set['SegmentTemplate'][0]['duration'];
        }

        if (!empty($representation['SegmentTemplate'][0]['timescale'])) {
            $timescale = $representation['SegmentTemplate'][0]['timescale'];
        }

        if (!empty($representation['SegmentTemplate'][0]['presentationTimeOffset'])) {
            $timeoffset = $representation['SegmentTemplate'][0]['presentationTimeOffset'];
        }

        if (!empty($representation['SegmentTemplate'][0]['duration'])) {
            $duration = $representation['SegmentTemplate'][0]['duration'];
        }

        if (!empty($representation['presentationTimeOffset'])) {
            $timeoffset = $representation['presentationTimeOffset'];
        }

        $offsetmod = (float)$timeoffset / $timescale;
        $duration = (float)$duration / $timescale;
        if ((($adaptation_set['SegmentTemplate'] && sizeof($adaptation_set['SegmentTemplate']) > 0) || ($representation['SegmentTemplate'] && sizeof($representation['SegmentTemplate']) > 0)) && $duration != 0) {
            $representationDirectory = $session->getRepresentationDir($mpdHandler->getSelectedPeriod(), $current_adaptation_set, $current_representation);
            loadSegmentInfoFile($offsetmod, $duration, $representationDirectory);
        }
    }
}
function loadSegmentInfoFile($PresTimeOffset, $duration, $representationDirectory)
{
    $info = array();

    ///\DoubleCheck make sure this actually gets created
    $segmentInfoFile = fopen("$representationDirectory/infofile.txt", 'rt');
    if (!$segmentInfoFile) {
        return;
    }

    fscanf($segmentInfoFile, "%lu\n", $accessUnitDurationNonIndexedTrack);
    fscanf($segmentInfoFile, "%u\n", $info['numTracks']);

    $info['leafInfo'] = array();
    $info['numLeafs'] = array();
    $info['trackTypeInfo'] = array();

    for ($i = 0; $i < $info['numTracks']; $i++) {
        fscanf($segmentInfoFile, "%lu %lu\n", $info['trackTypeInfo'][$i]['track_ID'], $info['trackTypeInfo'][$i]['componentSubType']);
    }

    for ($i = 0; $i < $info['numTracks']; $i++) {
        fscanf($segmentInfoFile, "%u\n", $info['numLeafs'][$i]);
        $info['leafInfo'][$i] = array();

        for ($j = 0; $j < $info['numLeafs'][$i]; $j++) {
            fscanf($segmentInfoFile, "%d %f %f\n", $info['leafInfo'][$i][$j]['firstInSegment'], $info['leafInfo'][$i][$j]['earliestPresentationTime'], $info['leafInfo'][$i][$j]['lastPresentationTime']);
        }
    }
    checkSegmentDurationWithMPD($info['leafInfo'], $PresTimeOffset, $duration, $representationDirectory);
    fclose($segmentInfoFile);
}

function checkSegmentDurationWithMPD($segmentsTime, $PTO, $duration, $representationDirectory)
{
  global $mpdHandler, $period_timing_info;

    if ($mpdHandler->getFeatures()['type'] == 'dynamic') {
        return;
    }

    ///\RefactorTodo make sure this actually gets created.
    $trackErrorFile = fopen("$representationDirectory/errorLog.txt", 'rt');
    if (!$trackErrorFile) {
        return;
    }
    $segmentDur = array();
    $num_segments = sizeof($segmentsTime[0]);
    if ($mpdHandler->getSelectedPeriod() == 0) {
        $pres_start = $period_timing_info[0] + $PTO;
    } else {
        $pres_start = $PTO;
    }

    $segmentDurMPD = $duration;
    for ($i = 0; $i < $num_segments; $i++) {
        $segmentDur[$i] = $segmentsTime[0][$i]['lastPresentationTime'] - $segmentsTime[0][$i]['earliestPresentationTime'];
        if (($i !== ($num_segments - 1)) && !($segmentDurMPD * 0.5 <= $segmentDur[$i]  && $segmentDur[$i] <= $segmentDurMPD * 1.5)) {
            fwrite($trackErrorFile, "###error- DASH ISO/IEC 23009-1, 7.2.1: 'The maximum tolerance of segment duration shall be +/-50% of the signaled segment duration (@duration)',violated for segment " . ($i + 1) . ", with duration " . $segmentDur[$i] . " while signaled @duration is " . $segmentDurMPD . "\n");
        }
        //The lower threshold tolerance does not apply to the last segment, it can be smaller.
        if (($i == ($num_segments - 1)) && $segmentDur[$i] > $segmentDurMPD * 1.5) {
            fwrite($trackErrorFile, "###error- DASH ISO/IEC 23009-1, 7.2.1: 'The maximum tolerance of segment duration shall be +/-50% of the signaled segment duration (@duration)',violated for segment " . ($i + 1) . ", with duration " . $segmentDur[$i] . " while signaled @duration is " . $segmentDurMPD . "\n");
        }

        $MPDSegmentStartTime = $pres_start + $i * $segmentDurMPD;
        if (!($MPDSegmentStartTime - (0.5 * $segmentDurMPD) <= $segmentsTime[0][$i]['earliestPresentationTime']  && $segmentsTime[0][$i]['earliestPresentationTime'] <= $MPDSegmentStartTime + (0.5 * $segmentDurMPD))) {
            fwrite($trackErrorFile, "###error- DASH ISO/IEC 23009-1:2019, 7.2.1: 'The difference between MPD start time and presentation time shall not exceed +/-50% of value of @duration divided by the value of the @timescale attribute.',violated for segment " . ($i + 1) . ", with earliest presentation time " . $segmentsTime[0][$i]['earliestPresentationTime'] . " while signaled MPD start time is " . $MPDSegmentStartTime . " and @duration is " . $segmentDurMPD . "\n");
        }
    }

    fclose($trackErrorFile);
}
