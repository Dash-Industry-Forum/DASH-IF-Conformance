<?php

global $session, $MediaProfDatabase, $logger, $mpdHandler;

$periodCount = sizeof($MediaProfDatabase);
$adaptationCount = sizeof($MediaProfDatabase[0]);
for ($i = 0; $i < ($periodCount - 1); $i++) {
    for ($adapt = 0; $adapt < $adaptationCount; $adapt++) {
        if ($mpdHandler->getContentType($i, $adapt) != "video") {
            continue;
        }
        if ($mpdHandler->getContentType($i + 1, $adapt) != "video") {
            continue;
        }
        $framerate_p1 = $mpdHandler->getFrameRate($i, $adapt, 0);
        $framerate_p2 = $mpdHandler->getFrameRate($i + 1, $adapt, 0);


        $remainder = ($framerate_p1 > $framerate_p2 ?
          ($framerate_p1 % $framerate_p2) :
          ($framerate_p2 % $framerate_p1));


        $logger->test(
            "WAVE Content Spec 2018Ed",
            "Section 7.2.2",
            "Frame rate Should be the same between Sequential Sw Sets at the Splice point",
            $remainder == 0,
            "WARN",
            "Correct for Sw set $adapt between presentations $i and " . ($i + 1),
            "Invalid for Sw set $adapt between presentations $i and " . ($i + 1),
        );
    }
}
