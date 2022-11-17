<?php

## Here the implementation follows the DASH-IF IOP guideline for signaling the switchable adaptation sets
global $session, $mpdHandler, $logger;

$indices = array();

//Todo:More generalized approach with many Aligned Sw Sets.
//Here assumption is only two Sw Sets are aligned.
$adaptationSets = $mpdHandler->getFeatures()['Period'][$mpdHandler->getSelectedPeriod()]['AdaptationSet'];
for ($z = 0; $z < count($adaptationSets); $z++) {
    $supplementalProperty = $adaptationSets[$z]['SupplementalProperty'];
    if ($supplementalProperty) {
        if ($supplementalProperty[0]['schemeIdUri'] == 'urn:mpeg:dash:adaptation-set-switching:2016') {
            array_push($indices, (int)($supplementalProperty[0]['value']));
        }
    }
}

if (!count($indices)) { // 0 means no Aligned SwSet, 2 or more is fine, 1 means error should be raised.
    return;
}

$logger->test(
    "CMAF",
    "Section 7.3.4.4",
    "Aligned Switching Sets SHALL contain two or more CMAF switching sets",
    count($indices) > 1,
    "FAIL",
    "Found " . count($indices) . " switching sets, comparing only first two",
    "Found only 1 set"
);
if (count($indices) == 1) {
    return;
}

// For this naming there is no automation yet, since this implementation has an assumption on ids
$location1 = $session->getAdaptationDir($mpdHandler->getSelectedPeriod(), $indices[0] - 1);
$fileCount1 = 0;
$files1 = DASHIF\rglob("$location1/*.xml");
if ($files1) {
    $fileCount1 = count($files1);
}

$logger->test(
    "CMAF",
    "Presentation Checks",
    "Attempting to open switching sets for adaptationSet $indices[0]",
    file_exists($location1),
    "FAIL",
    "Files exist",
    "Files don't exist: Possible cause: Representations are not valid and no " .
    "file/directory for box info is created.)"
);
if (!file_exists($location1)) {
    return;
}

for ($i = 0; $i < $fileCount1; $i++) {
    $xml = get_DOM($files1[$i], 'atomlist');
    $id = $adaptationSets[$indices[0] - 1]['Representation'][$i]['id'];

    if (!$xml) {
        return;
    }
    // For this naming there is no automation yet, since this implementation has an assumption on ids
    $location2 = $session->getAdaptationDir($mpdHandler->getSelectedPeriod(), $indices[1] - 1);
    $fileCount2 = 0;
    $files2 = DASHIF\rglob("$location2/*.xml");
    if ($files2) {
        $fileCount2 = count($files2);
    }
    $logger->test(
        "CMAF",
        "Presentation Checks",
        "Attempting to open switching sets for adaptationSet $indices[1]",
        file_exists($location2),
        "FAIL",
        "Files exist",
        "Files don't exist: Possible cause: Representations are not valid and no " .
        "file/directory for box info is created.)"
    );
    if (!file_exists($location2)) {
        return;
    }
    for ($j = 0; $j < $fileCount2; $j++) {
        $xml2 = get_DOM($files2[$j], 'atomlist');
        $id2 = $adaptationSets[$indices[1] - 1]['Representation'][$j]['id'];

        if (!$xml2) {
            return;
        }
        $moofBoxesCount1 = $xml->getElementsByTagName('moof')->length;
        $moofBoxesCount2 = $xml2->getElementsByTagName('moof')->length;

         //Check Tracks have same ISOBMFF defined duration.
        if ($i == 0 && $j == 0) { // As duration is checked between Sw Sets, checking only once is enough.
            $mehdBoxes1 = $xml->getElementsByTagName('mehd');
            $mehdBoxes2 = $xml2->getElementsByTagName('mehd');

            $equalDuration = true;

            if ($mehdBoxes1->length && $mehdBoxes2->length) {
                $mehdDuration1 = $mehdBoxes1->item(0)->getAttribute('fragmentDuration');
                $mehdDuration2 = $mehdBoxes2->item(0)->getAttribute('fragmentDuration');

                if ($mehdDuration1 != $mehdDuration2) {
                    $equalDuration = false;
                }
            } else {
                $tfdtLastBox1 = $xml->getElementsByTagName('tfdt')->item($moofBoxesCount1 - 1);
                $tfdtLastBox2 = $xml2->getElementsByTagName('tfdt')->item($moofBoxesCount2 - 1);

                $lastDecodeTime1 = $tfdtLastBox1->getAttribute('baseMediaDecodeTime');
                $lastDecodeTime2 = $tfdtLastBox2->getAttribute('baseMediaDecodeTime');

                $trunLastBox1 = $xml->getElementsByTagName('trun')->item($moofBoxesCount1 - 1);
                $trunLastBox2 = $xml2->getElementsByTagName('trun')->item($moofBoxesCount2 - 1);

                $cumulativeSampleDuration1 = $trunLastBox1->getAttribute('cummulatedSampleDuration');
                $cumulativeSampleDuration2 = $trunLastBox2->getAttribute('cummulatedSampleDuration');

                if ($lastDecodeTime1 + $cumulativeSampleDuration1 != $lastDecodeTime2 + $cumulativeSampleDuration2) {
                    $equalDuration = false;
                }
            }
            $logger->test(
                "CMAF",
                "7.3.4.4",
                "Aligned Switching Sets SHALL contain CMAF switching sets of equal duration",
                $equalDuration,
                "FAIL",
                "Matches between Set $indices[0] rep $id and Set $indices[1] rep $id2",
                "Differs between Set $indices[0] rep $id and Set $indices[1] rep $id2",
            );
        }

        $logger->test(
            "CMAF",
            "7.3.4.4",
            "Aligned Switching Sets SHALL contain the same number of CMAF Fragments in every CMAF Track",
            $moofBoxesCount1 == $moofBoxesCount2,
            "FAIL",
            "Matches between Set $indices[0] rep $id and Set $indices[1] rep $id2",
            "Differs between Set $indices[0] rep $id and Set $indices[1] rep $id2",
        );
        if ($moofBoxesCount1 != $moofBoxesCount2) {
            break;
        }

       //This check only if previous check is not failed.
        $trunBoxes1 = $xml->getElementsByTagName('trun');
        $tfdtBoxes1 = $xml->getElementsByTagName('tfdt');

        $trunBoxes2 = $xml2->getElementsByTagName('trun');
        $tfdtBoxes2 = $xml2->getElementsByTagName('tfdt');

        for ($y = 0; $y < $moofBoxesCount1; $y++) {
            $cumulativeSampleDuration1 = $trunBoxes1->item($y)->getAttribute('cummulatedSampleDuration');
            $decodeTime1 = $tfdtBoxes1->item($y)->getAttribute('baseMediaDecodeTime');

            //$sampleCount2=$trunBoxes2[$y]->getAttribute('sampleCount');
            $cumulativeSampleDuration2 = $trunBoxes2->item($y)->getAttribute('cummulatedSampleDuration');
            $decodeTime2 = $tfdtBoxes2->item($y)->getAttribute('baseMediaDecodeTime');

            $validDurationAndDecodeTime = $cumulativeSampleDuration1 == $cumulativeSampleDuration2 &&
                                          $decodeTime1 == $decodeTime2;

            $logger->test(
                "CMAF",
                "7.3.4.4",
                "Aligned Switching Sets SHALL contain CMAF Fragments in every CMAF Track with matching " .
                "baseMediaDecodeTime and duration",
                $validDurationAndDecodeTime,
                "FAIL",
                "Matches between Set $indices[0] rep $id and Set $indices[1] rep $id2",
                "Differs between Set $indices[0] rep $id and Set $indices[1] rep $id2",
            );
            if (!$validDurationAndDecodeTime) {
                break;
            }
        }
    }
}
