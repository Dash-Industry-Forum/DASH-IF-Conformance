<?php

global $logger;

$boxOrder = $representation->getTopLevelBoxNames();

$boxCount = count($boxOrder);

if (!$boxCount) {
    return;
}

$emsgIndices = array_keys($boxOrder, 'emsg');
$emsgCount = count($emsgIndices);

if (!$emsgCount) {
  //No emsg boxes found
    return;
}

$emsgBoxes = $representation->getEmsgBoxes();


$spec = "CTA-5005-A";
$section = "4.5.2 - Carriage of Timed Event Data";
$emsgExplanation = "All emsg boxes inserted into the CMAF Segment after the start of the first CMAF Chunk SHALL be " .
  "repeated before the first Chunk of the next Segment.";


//NOTE: Revisit once per-segment analysis is implemented
//  For now we can't detect segment boundaries, so what this code checks is the following:
//  - If an `emsg` box appears between a `moof` and an `mdat`, check that it repeats later on.
//  - If an `emsg` box appears after one as above, check that it repeats at the same time as the above.

$activeSegment = false;
$nextMoofIsSegment = false;

$expectRepeat = array();

//We start at -1 so we can increase the index once instead of for every return statment;
$emsgNum = -1;
for ($i = 0; $i < $boxCount; $i++) {
    if ($boxOrder[$i] == 'moof') {
        if ($nextMoofIsSegment) {
            foreach ($expectRepeat as $expectation) {
                $logger->test(
                    $spec,
                    $section,
                    $emsgExplanation,
                    false,
                    "FAIL",
                    "",
                    "Box for time $expectation->presentationTime did not get repeated for " .
                      $representation->getPrintable()
                );
            }
            $expectRepeat = array();
            $nextMoofIsSegment = false;
        }
        $activeSegment = true;
    }
    if ($boxOrder[$i] == 'mdat') {
        $activeSegment = false;
    }
    if ($boxOrder[$i] != 'emsg') {
        continue;
    }
    $emsgNum++;

    $thisEmsg = $emsgBoxes[$emsgNum];

    if ($activeSegment) {
        $expectRepeat[] = $thisEmsg;
        continue;
    }

    if (!count($expectRepeat)) {
        //We don't expect any repetitions, so this is fine
        $logger->test(
            $spec,
            $section,
            $emsgExplanation,
            true,
            "INFO",
            "Non-repeating box found for time $thisEmsg->presentationTime for " .
              $representation->getPrintable(),
            ""
        );
        continue;
    }

    $newExpect = array();
    $isRepeat = false;
    foreach ($expectRepeat as $expectation) {
        if ($expectation->equals($thisEmsg)) {
            $isRepeat = true;
            continue;
        }
        $newExpect[] = $expectation;
    }

    if ($isRepeat) {
        $nextMoofIsSegment = true;
        $expectRepeat = $newExpect;
        $logger->test(
            $spec,
            $section,
            $emsgExplanation,
            true,
            "INFO",
            "Found repetition for time $thisEmsg->presentationTime for " .
              $representation->getPrintable(),
            ""
        );
    } else {
        if (!$nextMoofIsSegment) {
            $expectRepeat[] = $thisEmsg;
        }
    }
}

//If there are still expectedRepetitions here, we can safely ignore this.
