<?php

global $session, $mpdHandler;

$infoFileInfoAdaptationSet = array();
$representations = $adaptationSet['Representation'];

foreach ($representations as $representationId => $representation) {
    $repDir = $session->getRepresentationDir($mpdHandler->getSelectedPeriod(), $adaptationSetId, $representationId);
    if (!($representationInformationFile = fopen("$repDir/assemblerInfo.txt", 'r'))) {
        return;
    }

    $infoFileInfo = array(
      'isSegmentStart' => array(),
      'PresStart' => array(),
      'PresEnd' => array(),
      'NextPresStart' => array()
    );

    while (($line = fgets($representationInformationFile)) !== false) {
        $lineInfo = explode(' ', $line);
        if (sizeof($lineInfo) < 3) {
            continue;
        }

        $infoFileInfo['isSegmentStart'][] = $lineInfo[0];
        $infoFileInfo['PresStart'][] = $lineInfo[1];
        $infoFileInfo['PresEnd'][] = $lineInfo[2];
        $infoFileInfo['NextPresStart'][] = (sizeof($lineInfo) > 3) ? explode("\n", $lineInfo[3])[0] : PHP_INT_MAX;
    }
    fclose($representationInformationFile);

    $infoFileInfoAdaptationSet[$representationId] = $infoFileInfo;
}

return $infoFileInfoAdaptationSet;
