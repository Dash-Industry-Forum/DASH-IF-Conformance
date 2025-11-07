<?php

global  $period_timing_info;

global $logger, $session, $mpdHandler;

//Assuming one of the CMAF profiles will be present.
$videoFound = 0;
$audioFound = 0;
$firstEntryflag = 1;
$firstVideoflag = 1;
$firstNonVideoflag = 1;
$im1tSwitchingSetFound = 0;
$subtitles = array();
$subtitleFound = 0;
$trackDurArray = array();
$longestFragmentDuration = 0;
$videoFragDur = 0;

$presentationDuration = $period_timing_info["duration"];
$adaptationSets = $mpdHandler->getFeatures()['Period'][$mpdHandler->getSelectedPeriod()]['AdaptationSet'];
$adaptationCount = ($adaptationSets == null ? 0 : count($adaptationSets));
for ($adaptationSetIndex = 0; $adaptationSetIndex < $adaptationCount; $adaptationSetIndex++) {
    $adaptationSet = $adaptationSets[$adaptationSetIndex];

    $location = $session->getAdaptationDir($mpdHandler->getSelectedPeriod(), $adaptationSetIndex);
    $filecount = 0;
    $files = DASHIF\rglob("$location/*.xml");
    if ($files) {
        $filecount = count($files);
    }

    $videoCounter = 0;
    $audioCounter = 0;
    $encryptedCounter = 0;


    $logger->test(
        "CMAF",
        "Presentation Checks",
        "Attempting to open switching sets for adaptationSet $adaptationSetIndex",
        file_exists($location),
        "FAIL",
        "Files exist",
        "Files don't exist: Possible cause: Representations are not valid and no " .
        "file/directory for box info is created.)"
    );

    if (file_exists($location)) {
        for ($i = 0; $i < $filecount; $i++) {
            $filename = $files[$i];
            $xml = DASHIF\Utility\parseDOM($filename, 'atomlist');
            $id = $adaptationSet['Representation'][$i]['id'];


            if ($xml) {
                //Check Section 7.3.4 conformance
                $tfdtBox = $xml->getElementsByTagName('tfdt')->item(0);
                $baseMediaDecodeTime = $tfdtBox->getAttribute('baseMediaDecodeTime');
                $trunBoxes = $xml->getElementsByTagName('trun');
                $earliestCompositionTime = $trunBoxes->item(0)->getAttribute('earliestCompositionTime');
                $hdlrBox = $xml->getElementsByTagName('hdlr')->item(0);
                $hdlrType = $hdlrBox->getAttribute('handler_type');
                $elstEntries = $xml->getElementsByTagName('elstEntry');
                $mdhdBoxes = $xml->getElementsByTagName('mdhd');
                $timescale = $mdhdBoxes->item(0)->getAttribute('timescale');

                $mediaTime = 0;
                if ($elstEntries->length > 0) {
                    $mediaTime = $elstEntries->item(0)->getAttribute('mediaTime');
                }

                if ($firstEntryflag) {
                    $firstEntryflag = 0;
                    $firstTrackTime = $baseMediaDecodeTime / $timescale;
                } else {
                    $logger->test(
                        "CMAF",
                        "Section 7.3.6",
                        "All CMAF Tracks in a CMAF Presentation SHALL have the same timeline origin",
                        $firstTrackTime == $baseMediaDecodeTime / $timescale,
                        "FAIL",
                        "Switching Set 1 Track 1 and Switching Set $adaptationSetIndex Track $id share " .
                        "the same origin",
                        "Switching Set 1 Track 1 and Switching Set $adaptationSetIndex Track $id have a " .
                        "different origin"
                    );
                }

                //Check alignment of presentation time for video and non video tracks separately. FDIS
                if ($hdlrType == 'vide') {
                    if ($firstVideoflag) {
                        $firstVideoflag = 0;
                        $firstVideoTrackPT = $mediaTime - $earliestCompositionTime;
                        $firstVideoAdaptationSetIndex = $adaptationSetIndex;
                        $firstVideoTrackId = $id;
                    } else {
                        $logger->test(
                            "CMAF",
                            "Section 7.3.6",
                            "All CMAF Tracks in a CMAF Presentation containing video SHALL be start aligned with " .
                            "CMAF presentation time zero equal to the earliest video sample presentation start time " .
                            "in the earliest CMAF Fragment",
                            $firstVideoTrackPT == $mediaTime - $earliestCompositionTime,
                            "FAIL",
                            "Switching Set $firstVideoAdaptationSetIndex Track $firstVideoTrackId and " .
                            "Switching Set $adaptationSetIndex Track $id have a valid configuration",
                            "Switching Set $firstVideoAdaptationSetIndex Track $firstVideoTrackId and " .
                            "Switching Set $adaptationSetIndex Track $id have an invalid configuration"
                        );
                    }
                } else {
                    if ($firstNonVideoflag) {
                        $firstNonVideoflag = 0;
                        $firstNonVideoTrackPT = $mediaTime + $earliestCompositionTime;
                        $firstNonVideoAdaptationSetIndex = $adaptationSetIndex;
                        $firstNonVideoTrackId = $id;
                    } else {
                        $logger->test(
                            "CMAF",
                            "Section 7.3.6",
                            "All CMAF Tracks in a CMAF Presentation that does not contain video SHALL be start " .
                            "aligned with CMAF presentation time zero equal to the earliest audio sample " .
                            "presentation start time in the earliest CMAF Fragment",
                            $firstNonVideoTrackPT == $mediaTime - $earliestCompositionTime,
                            "FAIL",
                            "Switching Set $firstNonVideoAdaptationSetIndex Track $firstNonVideoTrackId and " .
                            "Switching Set $adaptationSetIndex Track $id have a valid configuration",
                            "Switching Set $firstNonVideoAdaptationSetIndex Track $firstNonVideoTrackId and " .
                            "Switching Set $adaptationSetIndex Track $id have an invalid configuration"
                        );
                    }
                }

                //To find the longest CMAF track in the presentation
                $mvhdBoxes = $xml->getElementsByTagName('mvhd');
                $mehdBoxes = $xml->getElementsByTagName('mehd');
                $moofBoxesCount = $xml->getElementsByTagName('moof')->length;
                if ($mehdBoxes->length > 0) {
                    $mvhdTimescale = $mvhdBoxes->item(0)->getAttribute('timeScale');
                    $fragmentDuration = $mehdBoxes->item(0)->getAttribute('fragmentDuration');
                    array_push($trackDurArray, $fragmentDuration / $mvhdTimescale);
                } else {
                    $tfdtBoxLast = $xml->getElementsByTagName('tfdt')->item($moofBoxesCount - 1);
                    $lastDecodeTime = $tfdtBoxLast->getAttribute('baseMediaDecodeTime');
                    $trunBoxLast = $xml->getElementsByTagName('trun')->item($moofBoxesCount - 1);
                    $cumulativeSampleDuration = $trunBoxLast->getAttribute('cummulatedSampleDuration');
                    array_push($trackDurArray, ($lastDecodeTime + $cumulativeSampleDuration) / $timescale);
                }

                //Find max video fragment duration from all the tracks.
                if ($hdlrType == 'vide') {
                    for ($z = 0; $z < $moofBoxesCount; $z++) {
                        $fragmentDuration = ($trunBoxes->item($z)->getAttribute('cummulatedSampleDuration')) /
                                            $timescale;
                        if ($fragmentDuration > $longestFragmentDuration) {
                            $longestFragmentDuration = $fragmentDuration;
                        }
                    }
                }

            }
        }
    }
}

$logger->test(
    "CMAF",
    "Section 7.3.6",
    "Presentation duration needs to be known for validation",
    $presentationDuration,
    "WARN",
    "Presentation duration is known",
    "Presentation duration is not known, skipping further duration checks"
);


//Check if presentation duration is same as longest track duration.
if ($presentationDuration && !empty($trackDurArray)) {
    $logger->test(
        "CMAF",
        "Section 7.3.6",
        "Presentation duration needs to be known for validation",
        round($presentationDuration, 1) == round(max($trackDurArray), 1),
        "FAIL",
        "Presentation duration is equal to the longest CMAF track",
        "Presentation duration is $presentationDuration but should be " . max($trackDurArray)
    );


    for ($y = 0; $y < count($trackDurArray); $y++) {
        $timeRounded = round($trackDurArray[$y], 1);
        $lowerTimeBound = round($presentationDuration - $longestFragmentDuration, 1);
        $withinLowerTimeBound = $timeRounded >= $lowerTimeBound;
        $upperTimeBound = round($presentationDuration + $longestFragmentDuration, 1);
        $withinUpperTimeBound = $timeRounded <= $upperTimeBound;

        $logger->test(
            "CMAF",
            "Section 7.3.6",
            "CMAF Tracks in a CMAF Presentation SHALL equal the CMAF Presentation duration within a " .
            "tolerance of the longest video CMAF Fragment duration",
            $withinLowerTimeBound && $withinUpperTimeBound,
            "FAIL",
            "Track $y within bounds",
            "Track $y exceeds bounds"
        );
    }
}

