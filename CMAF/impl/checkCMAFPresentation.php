<?php

global  $mpd_features, $current_period, $profiles, $period_timing_info,
        $cfhd_SwSetFound,$caac_SwSetFound, $encryptedSwSetFound,
        $presentation_infofile, $adaptation_set_template;

global $logger;

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

$presentationDuration = $period_timing_info[1];
$adaptationSets = $mpd_features['Period'][$current_period]['adaptationSetationSet'];
for ($adaptationSetIndex = 0; $adaptationSetIndex < sizeof($adaptationSets); $adaptationSetIndex++) {
    $adaptationSet = $adaptationSets[$adaptationSetIndex];

    $location = $session->getAdaptationDir($current_period, $adaptationSetIndex);
    $filecount = 0;
    $files = glob($location . "/*.xml");
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
            $xml = get_DOM($filename, 'atomlist');
            $id = $adaptationSet['Representation'][$i]['id'];

            $cmfhdProfile = strpos(
                $profiles[$current_period][$adaptationSetIndex][$i],
                'urn:mpeg:cmaf:presentation_profile:cmfhd:2017'
            );
            $cmfhdcProfile = strpos(
                $profiles[$current_period][$adaptationSetIndex][$i],
                'urn:mpeg:cmaf:presentation_profile:cmfhdc:2017'
            );
            $cmfhdsProfile = strpos(
                $profiles[$current_period][$adaptationSetIndex][$i],
                'urn:mpeg:cmaf:presentation_profile:cmfhds:2017'
            );

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

                //Check profile conformance
                if ($cmfhdProfile || $cmfhdcProfile || $cmfhdsProfile) {
                    if ($hdlrType == 'vide') {
                        $videoFound = 1;
                        if (!$this->cfhdMediaProfileConformance($xml)) {
                            break;
                        }
                        $videoCounter = $videoCounter + 1;

                        if ($this->cfhdSwitchingSetFound = 0 && $videoCounter == $filecount) {
                            $this->cfhdSwitchingSetFound = 1;
                        }
                    } elseif ($hdlrType == 'soun') {
                        $audioFound = 1;
                        if (!$this->caacMediaProfileConformance($xml)) {
                            break;
                        }
                        $audioCounter = $audioCounter + 1;

                        if ($this->caacSwitchingSetFound = 0 && $audioCounter == $filecount) {
                            $this->caacSwitchingSetFound = 1;
                        }
                    }
                }
                if ($cmfhdProfile) {
                    $logger->test(
                        "CMAF",
                        "Section A.1.2",
                        "All CMAF Tracks SHALL NOT contain encrypted Samples or a TrackEncryptionBox",
                        $xml->getElementsByTagName('tenc')->length == 0,
                        "FAIL",
                        "Switching Set $adaptationSetIndex  Track $id has no encryption",
                        "Switching Set $adaptationSetIndex  Track $id has encryption"
                    );
                }
                if ($cmfhdcProfile) {
                    if ($xml->getElementsByTagName('tenc')->length > 0) {
                        $encryptedCounter = $encryptedCounter + 1;
                        $schm = $xml->getElementsByTagName('schm');
                        if ($schm->length) {
                            $logger->test(
                                "CMAF",
                                "Section A.1.3",
                                "Any CMAF Switching Set that is encrypted SHALL be available in 'cenc' " .
                                "Common Encryption scheme",
                                $schm->item(0)->getAttribute('scheme') == 'cenc',
                                "FAIL",
                                "Switching Set $adaptationSetIndex  Track $id has 'cenc' encryption",
                                "Switching Set $adaptationSetIndex  Track $id has '" .
                                $schm->item(0)->getAttribute('scheme') . "' encryption instead"
                            );
                        }
                        if ($this->encryptedSwitchingSetFound = 0 && $encryptedCounter == $filecount) {
                            $this->encryptedSwitchingSetFound = 1;
                        }
                    }
                }
                if ($cmfhdsProfile) {
                    if ($xml->getElementsByTagName('tenc')->length > 0) {
                        $encryptedCounter = $encryptedCounter + 1;
                        $schm = $xml->getElementsByTagName('schm');
                        if ($schm->length) {
                            $logger->test(
                                "CMAF",
                                "Section A.1.4",
                                "Any CMAF Switching Set that is encrypted SHALL be available in 'cbcs' ",
                                "Common Encryption scheme",
                                $schm->item(0)->getAttribute('scheme') == 'cenc',
                                "FAIL",
                                "Switching Set $adaptationSetIndex  Track $id has 'cbcs' encryption",
                                "Switching Set $adaptationSetIndex  Track $id has '" .
                                $schm->item(0)->getAttribute('scheme') . "' encryption instead"
                            );
                        }
                        if ($this->encryptedSwitchingSetFound = 0 && $encryptedCounter == $filecount) {
                            $this->encryptedSwitchingSetFound = 1;
                        }
                    }
                }
            }
        }
    }

    //Check for subtitle conformance of Section A.1
    if ($cmfhdProfile || $cmfhdcProfile || $cmfhdsProfile) {
        if (strpos($adaptationSet['mimeType'], "application/ttml+xml")) {
            $subtitleFound = 1;
            $adaptationSetCodecs = $adaptationSet['codecs'];
            $lang = $adaptationSet['language'];
            if ($lang != 0) {
                if (empty($subtitles)) {
                    $subtitles = array($lang => 0);
                } else {
                    if (!array_key_exists($lang, $subtitles)) {
                        $subtitles[$lang] = 0;
                    }
                }
                if (strpos($adaptationSetCodecs, "im1t")) {
                    $subtitles[$lang] = 1;
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
if ($presentationDuration) {
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

if ($cmfhdProfile || $cmfhdcProfile || $cmfhdsProfile) {
    if ($videoFound) {
        $logger->test(
            "CMAF",
            "Section A.1.2/A.1.3/A.1.4",
            "If containing video, SHALL include at least one Switching Set constrained to the 'cfhd' Media Profile",
            $this->cfhdSwitchingSetFound,
            "FAIL",
            "'cfhd' compatible set found",
            "'cfhd' compatible set not found"
        );
    }
    if ($audioFound) {
        $logger->test(
            "CMAF",
            "Section A.1.2/A.1.3/A.1.4",
            "If containing audio, SHALL include at least one Switching Set constrained to the 'caac' Media Profile",
            $this->caacSwitchingSetFound,
            "FAIL",
            "'caac' compatible set found",
            "'caac' compatible set not found"
        );
    }
    if ($subtitleFound) {
        $subtitleLanguagesCount = count(subtitles);
        for ($z = 0; $z < $subtitleLanguagesCount; $z++) {
            $logger->test(
                "CMAF",
                "Section A.1.2/A.1.3/A.1.4",
                "If containing subtitles, SHALL include at least one Switching Set for each language and role in " .
                "the 'im1t' Media Profile",
                $subtitles[$z] == 1,
                "FAIL",
                "Valid for subtitle $z",
                "Not valid for subtitle $z"
            );
        }
    }
}

if ($cmfhdcProfile || $cmfhdsProfile) {
    $logger->test(
        "CMAF",
        "Section A.1.3/A.1.4",
        "At least one CMAF Switching Set SHALL be encrypted",
        $this->encryptedSwitchingSetFound,
        "FAIL",
        "Encrypted switching set found",
        "No encrypted switching set found"
    );
}
