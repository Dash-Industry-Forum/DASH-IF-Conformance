<?php

global $logger;

//Check all Tracks are of same media type.
$hdlrBox1 = $xml1->getElementsByTagName('hdlr')->item(0);
$handlerType1 = $hdlrBox1->getAttribute('handler_type');
$hdlrBox2 = $xml2->getElementsByTagName('hdlr')->item(0);
$handlerType2 = $hdlrBox2->getAttribute('handler_type');

$logger->test(
    "CMAF",
    "Section 7.3.4.1",
    "A CMAF Switching Set SHALL contain CMAF Tracks of only one media type",
    $handlerType1 == $handlerType2,
    "FAIL",
    "Media type identical between representation $id1 and $id2",
    "Media type not identical between representation $id1 and $id2"
);

//Check Tracks have same number of moofs.
$moofCount1 = $xml1->getElementsByTagName('moof')->length;
$moofCount2 = $xml2->getElementsByTagName('moof')->length;

$logger->test(
    "CMAF",
    "Section 7.3.4.1",
    "A CMAF Switching Set SHALL contain the same number of CMAF Fragments",
    $moofCount1 == $moofCount2,
    "FAIL",
    "Fragment count identical between representation $id1 and $id2",
    "Fragment count not identical between representation $id1 and $id2",
);

//Check all Tracks have same ISOBMFF defined duration.
if ($xml1->getElementsByTagName('mehd')->length && $xml2->getElementsByTagName('mehd')->length) {
    $mehdBox1 = $xml1->getElementsByTagName('mehd')->item(0);
    $mehdDuration1 = $mehdBox1->getAttribute('fragmentDuration');
    $mehdBox2 = $xml2->getElementsByTagName('mehd')->item(0);
    $mehdDuration2 = $mehdBox2->getAttribute('fragmentDuration');

    $logger->test(
        "CMAF",
        "Section 7.3.4.1",
        "A CMAF Switching Set SHALL have the same duration",
        $mehdDuration1 == $mehdDuration2,
        "FAIL",
        "Duration identical between representation $id1 and $id2",
        "Duration not identical between representation $id1 and $id2",
    );
} else { //added according to change in FDIS.
    $lastTFDT1 = $xml1->getElementsByTagName('tfdt')->item($moofCount1 - 1);
    $lastTFDT2 = $xml2->getElementsByTagName('tfdt')->item($moofCount2 - 1);

    $lastDecodeTime1 = $lastTFDT1->getAttribute('baseMediaDecodeTime');
    $lastDecodeTime2 = $lastTFDT2->getAttribute('baseMediaDecodeTime');

    $lastTrun1 = $xml1->getElementsByTagName('trun')->item($moofCount1 - 1);
    $lastTrun2 = $xml2->getElementsByTagName('trun')->item($moofCount2 - 1);

    $cumulativeSampleDuration1 = $lastTrun1->getAttribute('cummulatedSampleDuration');
    $cumulativeSampleDuration2 = $lastTrun2->getAttribute('cummulatedSampleDuration');

    $logger->test(
        "CMAF",
        "Section 7.3.4.1",
        "A CMAF Switching Set SHALL have the same duration",
        $lastDecodeTime1 + $cumulativeSampleDuration1 == $lastDecodeTime2 + $cumulativeSampleDuration2,
        "FAIL",
        "Duration identical between representation $id1 and $id2",
        "Duration not identical between representation $id1 and $id2",
    );
}

//Check base decode time of Tracks.
$tfdtBoxes1 = $xml1->getElementsByTagName('tfdt');
$tfdtBaseMediaDecodeTime1 = $tfdtBoxes1->item(0)->getAttribute('baseMediaDecodeTime');
$tfdtBoxes2 = $xml2->getElementsByTagName('tfdt');
$tfdtBaseMediaDecodeTime2 = $tfdtBoxes2->item(0)->getAttribute('baseMediaDecodeTime');

$logger->test(
    "CMAF",
    "Section 7.3.4.1",
    "A CMAF Switching Set SHALL have the same value of baseMediaDecodeTime in the 1st CMAF fragment's " .
    "tfdt box, measured from the same timeline origin",
    $tfdtBaseMediaDecodeTime1 == $tfdtBaseMediaDecodeTime2,
    "FAIL",
    "baseMediaDecodeTime identical between representation $id1 and $id2",
    "baseMediaDecodeTime not identical between representation $id1 and $id2",
);

//Check for Fragments with same decode time.
for ($y = 0; $y < $moofCount1; $y++) {
    $tfdtBaseMediaDecodeTime1 = $tfdtBoxes1->item($y)->getAttribute('baseMediaDecodeTime');
    $foundCorresponding = true;
    for ($z = 0; $z < $moofCount2; $z++) {
        $tfdtBaseMediaDecodeTime2 = $tfdtBoxes2->item($z)->getAttribute('baseMediaDecodeTime');
        if ($tfdtBaseMediaDecodeTime1 == $tfdtBaseMediaDecodeTime2) {
            break;
        }
        if ($z == $moofCount2 - 1) {
            $foundCorresponding = false;
        }
    }
    $logger->test(
        "CMAF",
        "Section 7.3.4.1",
        "For any CMAF Fragment in one CMAF Track in a CMAF Switching Set there SHALL be a CMAF Fragment " .
        "with same decode time in all other CMAF Tracks",
        $foundCorresponding,
        "FAIL",
        "Found representation $id1 fragment $y in representation $id2",
        "Unable to find representation $id1 fragment $y in representation $id2"
    );
}


//Check new presentation time check from FDIS on SwSet
$trunBox1 = $xml1->getElementsByTagName('trun')->item(0);
$trunBox2 = $xml2->getElementsByTagName('trun')->item(0);
$earliestCompositionTime1 = $trunBox1->getAttribute('earliestCompositionTime');
$earliestCompositionTime2 = $trunBox2->getAttribute('earliestCompositionTime');

if ($handlerType1 == 'vide') {
    $logger->test(
        "CMAF",
        "Section 7.3.4.1",
        "The presentation time of earliest media sample of the earliest CMAF fragment in each CMAF track " .
        "shall be equal",
        $earliestCompositionTime1 == $earliestCompositionTime2,
        "FAIL",
        "earliestCompositionTime identical between representation $id1 and $id2",
        "earliestCompositionTime not identical between representation $id1 and $id2"
    );
}
if ($handlerType1 == 'soun') {
    $elstBoxes1 = $xml1->getElementsByTagName('elstEntry');
    $elstBoxes2 = $xml2->getElementsByTagName('elstEntry');
    $mediaTime1 = 0;
    if ($elstBoxes1->length) {
        $mediaTime1 = $elstBoxes1->item(0)->getAttribute('mediaTime');
    }
    $mediaTime2 = 0;
    if ($elstBoxes2->length > 0) {
        $mediaTime2 = $elstBoxes2->item(0)->getAttribute('mediaTime');
    }
    $logger->test(
        "CMAF",
        "Section 7.3.4.1",
        "The presentation time of earliest media sample of the earliest CMAF fragment in each CMAF track " .
        "shall be equal",
        $earliestCompositionTime1 + $mediaTime1 == $earliestCompositionTime2 + $mediaTime2,
        "FAIL",
        "Presentation time identical between representation $id1 and $id2",
        "Presentation time not identical between representation $id1 and $id2"
    );
}
