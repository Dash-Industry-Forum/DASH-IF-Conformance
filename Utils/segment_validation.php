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

function validate_segment(
    $adaptationDirectory,
    $representationDirectory,
    $period,
    $adaptation_set,
    $representation,
    $segment_url,
    $is_subtitle_rep,
    $detailedSegmentOutput = true
)
{
    global $sizearray;


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
        $config_file_loc = config_file_for_backend(
            $period,
            $adaptation_set,
            $representation,
            $representationDirectory,
            $is_dolby
        );

        ## Run the backend
        $returncode = run_backend($config_file_loc, $representationDirectory);

        $varinfo = var_export($adaptation_set, true);

        ## Analyse the results and report them
        $file_location = analyze_results($returncode, $adaptationDirectory, $representationDirectory);
    } else {
        ## Save to progress report that the representation does not exist
        $file_location[] = 'notexist';
    }

    // Save content of stderr
    saveStdErrOutput($representationDirectory, $detailedSegmentOutput);

    return $file_location;
}

function validate_segment_hls($URL_array, $CodecArray)
{
    global $session, $hls_tag;


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

    $tag_array = array('StreamINF', 'IFrameByteRange', 'XMedia');
    for ($i = 0; $i < sizeof($URL_array); $i++) {
        list($segmentURL, $sizeArray) = segmentDownload($URL_array[$i], $tag_array[$i], $is_dolby);


        for ($j = 0; $j < sizeof($segmentURL); $j++) {
            if ($sizeArray[$j] != 0) {
                $hls_tag = $tag_array[$i] . '_' . $j;

                ## Put segments in one file
                $representationDir = $session->getDir() . '/' . $tag_array[$i] . '/' . $j;
                assemble($representationDir, $segmentURL[$j], $sizeArray[$j]);

                ## Create config file with the flags for segment validation
                $config_file_loc = config_file_for_backend(null, null, null, $representationDir, $is_dolby);


                ## Run the backend
                $returncode = run_backend($config_file_loc);

                ## Analyse the results and report them
                $file_location[] = analyze_results(
                    $returncode,
                    $session->getDir() . '/' . $tag_array[$i],
                    $representationDir
                );

                ## Determine media type based on atomxml information
                determineMediaType($session->getDir() . '/' . $tag_array[$i] . '/' . $j . '.xml', $hls_tag);
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
    global $segment_accesses, $hls_manifest, $mpdHandler;


    $index = ($segment_accesses[$mpdHandler->getSelectedAdaptationSet()]
    [$mpdHandler->getSelectedRepresentation()][0]
    ['initialization']) ? 0 : 1;

    for ($i = 0; $i < sizeof($segment_urls); $i++) {
        $fp1 = fopen("$representationDirectory/assembled.mp4", 'a+');

        $segment_name = basename($segment_urls[$i]);
        if (file_exists($representationDirectory . "/" . $segment_name)) {
            $size = $sizearr[$i]; // Get the real size of the file (passed as inupt for function)
            $file2 = file_get_contents($representationDirectory . "/" . $segment_name); // Get the file contents

            fwrite($fp1, $file2); // dump it in the container file
            fclose($fp1);
            file_put_contents(
                "$representationDirectory/assemblerInfo.txt",
                $index . " " . $size . "\n",
                FILE_APPEND
            ); // add size to a text file to be passed to conformance software

            $index++; // iterate over all segments within the segments folder
        }
    }
}

function analyze_results($returncode, $curr_adapt_dir, $representationDirectory)
{
    global $mpdHandler, $session, $logger,
           $hls_manifest, $hls_tag, $hls_info_file;

    $selectedPeriod = $mpdHandler->getSelectedPeriod();
    $selectedAdaptation = $mpdHandler->getSelectedAdaptationSet();
    $selectedRepresentation = $mpdHandler->getSelectedRepresentation();

    $adaptation_set = $mpdHandler->getFeatures()['Period'][$selectedPeriod]['AdaptationSet'][$selectedAdaptation];
    $representation = $adaptation_set['Representation'][$selectedRepresentation];
    $stdErrPath = $session->getDir() . "/stderr.txt";
    if (!$hls_manifest) {
        $logger->test(
            "Segment Validations",
            "analyze_results()",
            "Output file contains validation results",
            filesize($stdErrPath) !== 0 && filesize($stdErrPath) !== false,
            "FAIL",
            "Contents in stderr.txt found",
            "Failed to process adaptationset $selectedAdaptation, " .
            "representation $selectedRepresentation, mimetype" . $adaptation_set['mimeType']
        );
    } else {
        $logger->test(
            "Segment Validations",
            "analyze_results()",
            "Output file contains validation results",
            filesize($stdErrPath) !== 0 && filesize($stdErrPath) !== false,
            "FAIL",
            "Contents in stderr.txt found",
            "Failed to process HLS $tag_array[0] index $tag_array[1]"
        );
    }
    if (filesize("$representationDirectory/stderr.txt") == 0) {
        if (!$hls_manifest) {
        } else {
            $tag_array = explode('_', $hls_tag);
            $files = array_diff(scandir($session->getDir() . '/' .
                $tag_array[0] . '/' . $tag_array[1] . "/"), array('.', '..'));
            if (
                strpos($files[2], 'webvtt') !== false || strpos($files[2], 'xml') !== false ||
                strpos($files[2], 'html') !== false
            ) {
                file_put_contents(
                    $session->getDir() . '/' . 'stderr.txt',
                    "### error:  \n###        Failed to process " .
                    $tag_array[0] . ' with index ' . $tag_array[1] . ', as the file type is ' .
                    explode('.', $files[2])[1] . '!'
                );
            } else {
                file_put_contents(
                    $session->getDir() . '/' . 'stderr.txt',
                    "### error:  \n###        Failed to process " .
                    $tag_array[0] . ' with index ' . $tag_array[1] . '!'
                );
            }
        }
    }

    rename($session->getDir() . "/leafinfo.txt", "$representationDirectory/leafInfo.txt");

    if (file_exists($stdErrPath)) {
        rename($stdErrPath, "$representationDirectory/stderr.txt");
    }

    if (!$hls_manifest) {
        ## Check segment duration and start times against MPD times.
        loadAndCheckSegmentDuration();
    }

    if (file_exists($session->getDir() . '/sample_data.txt') && !$hls_manifest) {
        rename($session->getDir() . '/sample_data.txt', "$representationDirectory/sampleData.xml");
    }
}

function run_backend($configFile, $representationDirectory = "")
{
    global $session, $logger, $mpdHandler;

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

    $currentModule = $logger->getCurrentModule();
    $currentHook = $logger->getCurrentHook();

    $logger->setModule("HEALTH");
    $moveAtom &= $logger->test(
        "Health Checks",
        "Segment Validation",
        "ISOSegmentValidator runs successful",
        $returncode == 0,
        "FAIL",
        "Ran succesful on $configFile; took " . ($et - $t) . "seconds",
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
    if ($STYPBeginPos !== false) {
        //try with newline for prettyprinted
        $emptyCompatBrands = strpos($atomXmlString, "compatible_brands='[\n  </styp>", $STYPBeginPos);
        if ($emptyCompatBrands === false) {
            //Also try without newline just to be sure
            $emptyCompatBrands = strpos($atomXmlString, "compatible_brands='[</styp>", $STYPBeginPos);
        }
        if ($emptyCompatBrands !== false) {
            $logger->message(
                "Fixed empty styp xml bug for period " . $mpdHandler->getSelectedPeriod() . " adaptation " .
                $mpdHandler->getSelectedAdaptationSet() . " representation " . $mpdHandler->getSelectedRepresentation()
            );
            $fixedAtom = substr_replace($atomXmlString, "]'>", $emptyCompatBrands + 20, 0);
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
        "Atominfo for $representationDirectory is " . filesize("$sessionDirectory/atominfo.xml")
    );


    if (!$moveAtom) {
        fwrite(STDERR, "Ignoring atomfile for $representationDirectory\n");
        if ($representationDirectory != "") {
            rename("$sessionDirectory/atominfo.xml", "$representationDirectory/errorAtomInfo.xml");
        }
    } else {
        if ($representationDirectory != "") {
            rename("$sessionDirectory/atominfo.xml", "$representationDirectory/atomInfo.xml");
        }
    }

    // Restore module information since health checks are over
    $logger->setModule($currentModule);
    $logger->setHook($currentHook);

    return $returncode;
}

function config_file_for_backend($period, $adaptation_set, $representation, $representationDirectory, $is_dolby)
{
    global $additional_flags, $suppressatomlevel, $hls_manifest;


    $file = fopen("$representationDirectory/segmentValidatorConfig.txt", 'w');
    fwrite($file, "$representationDirectory/assembled.mp4 \n");
    fwrite($file, "-infofile" . "\n");
    fwrite($file, "$representationDirectory/assemblerInfo.txt \n");

    if (!$is_dolby) {
        if (file_exists("$representationDirectory/mdatoffset") && filesize("$representationDirectory/mdatoffset") > 0) {
            fwrite($file, "-offsetinfo" . "\n");
            fwrite($file, "$representationDirectory/mdatoffset \n");
        } else {
            fwrite($file, "-c 1" . "\n");
        }
    }

    $flags = (!$hls_manifest) ? construct_flags(
            $period,
            $adaptation_set,
            $representation
        ) . $additional_flags : $additional_flags;
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
    return "$representationDirectory/segmentValidatorConfig.txt";
}

function loadAndCheckSegmentDuration()
{
    global $mpdHandler;
    global $session;

    $adaptation_set = $mpdHandler->getFeatures()['Period'][$mpdHandler->getSelectedPeriod()]
    ['AdaptationSet'][$mpdHandler->getSelectedAdaptationSet()];
    $timeoffset = 0;
    $timescale = 1;
    $segmentAlignment = false;
    if ($adaptation_set['segmentAlignment']) {
        $segmentAlignment = ($adaptation_set['segmentAlignment'] == "true");
    }
    $subsegmentAlignment = false;
    if ($adaptation_set['subsegmentAlignment']) {
        $subsegmentAlignment = ($adaptation_set['subsegmentAlignment'] == "true");
    }
    $bitstreamSwitching = false;
    if ($adaptation_set['bitstreamSwitching']) {
        $bitstreamSwitching = ($adaptation_set['bitstreamSwitching'] == "true");
    }

    if ($segmentAlignment || $subsegmentAlignment || $bitstreamSwitching) {
        $leafInfo = array();

        $representation = $adaptation_set['Representation'][$mpdHandler->getSelectedRepresentation()];
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
        if (
            (
                ($adaptation_set['SegmentTemplate'] && sizeof($adaptation_set['SegmentTemplate']) > 0) ||
                ($representation['SegmentTemplate'] && sizeof($representation['SegmentTemplate']) > 0)
            ) && $duration != 0
        ) {
            $representationDirectory = $session->getSelectedRepresentationDir();
            loadSegmentInfoFile($offsetmod, $duration, $representationDirectory);
        }
    }
}

function loadSegmentInfoFile($PresTimeOffset, $duration, $representationDirectory)
{
    $info = array();

    $segmentInfoFile = fopen("$representationDirectory/leafInfo.txt", 'r');
    if (!$segmentInfoFile) {
        return;
    }

    fscanf($segmentInfoFile, "%lu\n", $accessUnitDurationNonIndexedTrack);
    fscanf($segmentInfoFile, "%u\n", $info['numTracks']);

    $info['leafInfo'] = array();
    $info['numLeafs'] = array();
    $info['trackTypeInfo'] = array();

    for ($i = 0; $i < $info['numTracks']; $i++) {
        fscanf(
            $segmentInfoFile,
            "%lu %lu\n",
            $info['trackTypeInfo'][$i]['track_ID'],
            $info['trackTypeInfo'][$i]['componentSubType']
        );
    }

    for ($i = 0; $i < $info['numTracks']; $i++) {
        fscanf($segmentInfoFile, "%u\n", $info['numLeafs'][$i]);
        $info['leafInfo'][$i] = array();

        for ($j = 0; $j < $info['numLeafs'][$i]; $j++) {
            fscanf(
                $segmentInfoFile,
                "%d %f %f\n",
                $info['leafInfo'][$i][$j]['firstInSegment'],
                $info['leafInfo'][$i][$j]['earliestPresentationTime'],
                $info['leafInfo'][$i][$j]['lastPresentationTime']
            );
        }
    }
    checkSegmentDurationWithMPD($info['leafInfo'], $PresTimeOffset, $duration, $representationDirectory);
    fclose($segmentInfoFile);
}

function checkSegmentDurationWithMPD($segmentsTime, $PTO, $duration, $representationDirectory)
{
    global $mpdHandler, $logger, $period_timing_info;

    if ($mpdHandler->getFeatures()['type'] == 'dynamic') {
        return;
    }

    $segmentDur = array();
    $num_segments = sizeof($segmentsTime[0]);
    if ($mpdHandler->getSelectedPeriod() == 0) {
        $pres_start = $period_timing_info["start"] + $PTO;
    } else {
        $pres_start = $PTO;
    }

    $segmentDurMPD = $duration;
    for ($i = 0; $i < $num_segments; $i++) {
        $segmentDur[$i] =
            $segmentsTime[0][$i]['lastPresentationTime'] - $segmentsTime[0][$i]['earliestPresentationTime'];

        $logger->test(
            "DASH ISO/IEC 23009-1",
            "Section 7.2.1",
            "The maximum tolerance of segment duration shall be +/-50% of the signaled segment duration",
            ($i != ($num_segments - 1) && $segmentDurMPD * 0.5 > $segmentDur[$i]) &&
            $segmentDur[$i] <= $segmentDurMPD * 1.5,
            "FAIL",
            "Segment $i with duration " . $segmentDur[$i] . " is within bounds of signaled " . $segmentDurMPD,
            "Segment $i with duration " . $segmentDur[$i] . " violates bounds of signaled " . $segmentDurMPD
        );

        $MPDSegmentStartTime = $pres_start + $i * $segmentDurMPD;

        $logger->test(
            "DASH ISO/IEC 23009-1:2019",
            "Section 7.2.1",
            "The difference between MPD start time and presentation time shall not exceed +/-50% of value of " .
            "@duration divided by the value of the @timescale attribute",
            $MPDSegmentStartTime - (0.5 * $segmentDurMPD) <= $segmentsTime[0][$i]['earliestPresentationTime'] &&
            $segmentsTime[0][$i]['earliestPresentationTime'] <= $MPDSegmentStartTime + (0.5 * $segmentDurMPD),
            "FAIL",
            "Correct for segment $i with duration " . $segmentsTime[0][$i]['earliestPresentationTime'],
            "Incorrect for segment $i with duration " . $segmentsTime[0][$i]['earliestPresentationTime']
        );
    }
}

function saveStdErrOutput($representationDirectory, $saveDetailedOutput = true)
{
    global $logger;

    $currentModule = $logger->getCurrentModule();
    $currentHook = $logger->getCurrentHook();
    $logger->setModule("SEGMENT_VALIDATION");

    $content = file_get_contents("$representationDirectory/stderr.txt");
    $contentArray = explode("\n", $content);

    if (!count($contentArray)) {
        $logger->test(
            "Segment Validation",
            "Segment Validation",
            "Check for content in error log",
            true,
            "PASS",
            "Segment validation did not produce any output",
            $content
        );
    } else {
        $commonSeverity = "PASS";
        foreach ($contentArray as $i => $msg) {
            $severity = "PASS";
            //Catch both warn and warning
            if (stripos($msg, "warn") !== FALSE) {
                $severity = "WARN";
                if ($commonSeverity == "PASS") {
                    $commonSeverity = $severity;
                }
            }
            //Catch errors
            if (stripos($msg, "error") !== FALSE) {
                $severity = "FAIL";
                if ($commonSeverity != "FAIL") {
                    $commonSeverity = $severity;
                }
            }

            if ($saveDetailedOutput) {
                $logger->test(
                    "Segment Validation",
                    "Segment Validation",
                    "Check for content in error log",
                    $severity == "PASS",
                    $severity,
                    $msg,
                    $msg
                );
            }
        }

        if (!$saveDetailedOutput) {
            $logger->test(
                "Segment Validation",
                "Segment Validation",
                "Check for content in error log",
                $commonSeverity == "PASS",
                $commonSeverity,
                "Segment validation did not produce any errors",
                "Segment validation produced errors but output is not depicted in detail"
            );
        }
    }


    // Restore module information since health checks are over
    $logger->setModule($currentModule);
    $logger->setHook($currentHook);
}
