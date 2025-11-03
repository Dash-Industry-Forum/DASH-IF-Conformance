<?php

global $mpdHandler, $logger;

$adapt = $mpdHandler->getFeatures()['Period'][$mpdHandler->getSelectedPeriod()]['AdaptationSet'][$adaptationIndex];
$representation1 = $adaptation['Representation'][$xmlIndex1];
$representation2 = $adaptation['Representation'][$xmlIndex2];

$equalRepresentationCount = (sizeof($representation1) == sizeof($representation2));

if (!$equalRepresentationCount) {
    return;
}

// Frame rate
$frameRateGroup1 = array('25', '25/1', '50', '50/1');
$frameRateGroup2 = array('30000/1001', '60000/1001');
$frameRateGroup3 = array('30', '30/1', '60', '60/1');
$frameRateGroup4 = array('24', '24/1', '48', '48/1');
$frameRateGroup5 = array('24000/1001');
$frameRate1 = $representation1['frameRate'];
$frameRate2 = $representation2['frameRate'];

$frameRatesInSameGroup = true;
if ($frameRate1 != '' && $frameRate2 != '') {
    if (in_array($frameRate1, $frameRateGroup1) != in_array($frameRate2, $frameRateGroup1)) {
        $frameRatesInSameGroup = false;
    }
    if (in_array($frameRate1, $frameRateGroup2) != in_array($frameRate2, $frameRateGroup2)) {
        $frameRatesInSameGroup = false;
    }
    if (in_array($frameRate1, $frameRateGroup3) != in_array($frameRate2, $frameRateGroup3)) {
        $frameRatesInSameGroup = false;
    }
    if (in_array($frameRate1, $frameRateGroup4) != in_array($frameRate2, $frameRateGroup4)) {
        $frameRatesInSameGroup = false;
    }
    if (in_array($frameRate1, $frameRateGroup5) != in_array($frameRate2, $frameRateGroup5)) {
        $frameRatesInSameGroup = false;
    }
}
$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB: Section 10.4",
    "Players SHALL support seamless switching between audio Representations which differ only in " .
    "frame rate, bit rate, profile and/or level, and resolution",
    $frameRatesInSameGroup,
    "PASS",
    "Adaptation $adaptationIndex: framerates are in the same group for " .
    "representations $xmlIndex1 and $xmlIndex2",
    "Adaptation $adaptationIndex: framerates are not in the same group for " .
    "representations $xmlIndex1 and $xmlIndex2"
);

// Resolution
$width1 = $representation1['width'];
$height1 = $representation1['height'];
$width2 = $representation2['width'];
$height2 = $representation2['height'];

if ($width1 == '' || $height1 == '' || $width2 == '' || $height2 == '') {
    return;
}

$validAspectRatio = true;
if ($adaptation['pictureAspectRatio'] != '') {
    $pictureAspectRatio = $adaptation['pictureAspectRatio'];
    if ($width1 != $width2 || $height1 != $height2) {
        $pictureAspectRatioArray = explode(':', $pictureAspectRatio);
        $adaptationAspectRatio = (float)$pictureAspectRatioArray[0] / (float)$pictureAspectRatioArray[1];

        $pictureAspectRatio1 = $width1 / $height1;
        $pictureAspectRatio2 = $width2 / $height2;

        if ($pictureAspectRatio1 != $pictureAspectRatio2 || $pictureAspectRatio1 != $adaptationAspectRatio) {
            $validAspectRatio = false;
        }
    }
} else {
    $contentComponents = $adaptation['ContentComponent'];
    foreach ($contentComponents as $component) {
        $pictureAspectRatios[] = $component['pictureAspectRatio'];
    }

    $uniqueAspectRatio = (count(array_unique($pictureAspectRatios)) == 1 && !in_array('', $pictureAspectRatios));

    if (!$uniqueAspectRatio) {
        $validAspectRatio = false;
    } else {
        if ($width1 != $width2 || $height1 != $height2) {
            $pictureAspectRatio = $pictureAspectRatios[0];
            $pictureAspectRatioArray = explode(':', $pictureAspectRatio);
            $adaptationAspectRatio = (float)$pictureAspectRatioArray[0] / (float)$pictureAspectRatioArray[1];

            $pictureAspectRatio1 = $width1 / $height1;
            $pictureAspectRatio2 = $width2 / $height2;
            if ($pictureAspectRatio1 != $pictureAspectRatio2 || $pictureAspectRatio1 != $adaptationAspectRatio) {
                $validAspectRatio = false;
            }
        }
    }
}
$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB: Section 10.4",
    "Players SHALL support seamless switching between video Representations which can differ in resolution, " .
    "maintaining the same picture aspect ratio",
    $validAspectRatio,
    "PASS",
    "Adaptation $adaptationIndex: aspect ratio is the same for " .
    "representations $xmlIndex1 and $xmlIndex2",
    "Adaptation $adaptationIndex: aspect ratio is different  for " .
    "representations $xmlIndex1 and $xmlIndex2"
);
