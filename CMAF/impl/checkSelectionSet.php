<?php

global $current_period, $cmaf_mediaTypes, $adaptation_set_template, $selectionset_infofile;

global $logger, $session;

$selectionSets = $this->getSelectionSets();
foreach ($selectionSets as $selectionSet) {
    if (empty($selectionSet)) {
        continue;
    }

    $selectionSetLength = sizeof($selectionSet);
    $logger->test(
        "CMAF",
        "Section 7.3.5",
        "A CMAF Selection Set SHALL contain one or more CMAF Switching Sets",
        $selectionSetLength,
        "FAIL",
        "Found $selectionSetLength CMAF Switching Sets",
        "Found no CMAF Switching Sets"
    );

    $switchingSetDurations = array();
    $longestFragmentDuration = 0;
    for ($i = 0; $i < $selectionSetLength; $i++) {
        $adaptationIndex = $selectionSet[$i];

        # Compare media types of CMAF switching sets within CMAF selection set
        $mediaTypesInSet1 = $cmaf_mediaTypes[$current_period][$adaptationIndex];
        for ($j = $i + 1; $j < $selectionSetLength; $j++) {
            $compareIndex = $selectionSet[$j];
            $mediaTypesInSet2 = $cmaf_mediaTypes[$current_period][$compareIndex];

            $logger->test(
                "CMAF",
                "Section 7.3.5",
                "All CMAF Switching Sets within a CMAF Selection Set SHALL be of the same media type",
                count(array_unique($mediaTypesInSet1)) === 1 &&
                count(array_unique($mediaTypesInSet2)) === 1 &&
                end($mediaTypesInSet1) == end($mediaTypesInSet2),
                "FAIL",
                "Media type matches between $adaptationIndex and $compareIndex in period $current_period",
                "Media differs between $adaptationIndex and $compareIndex in period $current_period"
            );
        }

        $location = $session->getAdaptationDirectory($current_period, $adaptationIndex);
        $filecount = 0;
        $files = glob($location . "/*.xml");
        if ($files) {
            $filecount = count($files);
        }

        $logger->test(
            "CMAF",
            "Presentation Checks",
            "Attempting to open switching sets for adaptationSet $adaptationIndex",
            file_exists($location),
            "FAIL",
            "Files exist",
            "Files don't exist: Possible cause: Representations are not valid and no " .
            "file/directory for box info is created.)"
        );

        $longestTrackDuration = 0;
        for ($f = 0; $f < $filecount; $f++) {
            $xml = get_DOM($files[$f], 'atomlist');
            if ($xml) {
                $timescale = $xml->getElementsByTagName('mdhd')->item(0)->getAttribute('timescale');
                $mehdBoxes = $xml->getElementsByTagName('mehd');
                $moofBoxes = $xml->getElementsByTagName('moof');

                $cumulativeFragmentDuration = 0;
                for ($m = 0; $m < $moofBoxes->length; $m++) {
                    $trafBoxes = $moofBoxes->item($m)->getElementsByTagName('traf');
                    if ($trafBoxes->length != 0) {
                        $trunBoxes = $trafBoxes->item(0)->getElementsByTagName('trun');
                        if ($trunBoxes->length != 0) {
                            $fragmentDuration = ($trunBoxes->item(0)->getAttribute('cummulatedSampleDuration')) /
                                                $timescale;

                            if ($fragmentDuration > $longestFragmentDuration) {
                                $longestFragmentDuration = $fragmentDuration;
                            }

                            if (!$mehdBoxes->length) {
                                $cumulativeFragmentDuration += $fragmentDuration;
                            }
                        }
                    }
                }
                if ($mehdBoxes->length) {
                    //Only overwrite if not set before!
                    $cumulativeFragmentDuration = ($mehdBoxes->item(0)->getAttribute('fragmentDuration')) / 1000;
                }

                if ($cumulativeFragmentDuration > $longestTrackDuration) {
                    $longestTrackDuration = $cumulativeFragmentDuration;
                }
            }
        }

        array_push($switchingSetDurations, $longestTrackDuration);
    }

    if (count($switchingSetDurations) > 0) {
        for ($s1 = 0; $s1 < sizeof($switchingSetDurations); $s1++) {
            $switchingSetDuration1 = $switchingSetDurations[$s1];
            for ($s2 = $s1 + 1; $s2 < sizeof($switchingSetDurations); $s2++) {
                $switchingSetDuration2 = $switchingSetDurations[$s2];

                $logger->test(
                    "CMAF",
                    "Section 7.3.5",
                    "All Switching Sets within a CMAF Selection Set SHALL be of the same duration, within a " .
                    "tolerance of the longest CMAF Fragment duration of any Track in the Selection Set",
                    abs($switchingSetDuration2 - $switchingSetDuration1) <= $longestFragmentDuration,
                    "FAIL",
                    "Files exist",
                    "Matches between $adaptationIndex and $compareIndex in period $current_period",
                    "Differs between $adaptationIndex and $compareIndex in period $current_period"
                );
            }
        }
    }
}
