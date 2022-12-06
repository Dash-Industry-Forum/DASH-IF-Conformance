<?php

global $MediaProfDatabase, $session;

$periodCount = sizeof($MediaProfDatabase);
$adaptationCount = sizeof($MediaProfDatabase[0]);
for ($i = 0; $i < ($periodCount - 1); $i++) {
    for ($adaptation = 0; $adaptation < $adaptationCount; $adaptation++) {
        $dir1 = $session->getRepresentationDir($i, $adapt, 0);
        $xml1 = DASHIF\Utility\parseDOM($dir1 . '/atomInfo.xml', 'atomlist');
        if ($xml1) {
            $hdlrBox1 = $xml1->getElementsByTagName('hdlr')->item(0);
            $hdlrType1 = $hdlrBox1->getAttribute("handler_type");
            $trunBoxes1 = $xml1->getElementsByTagName('trun');
            $mdhdBox1 = $xml1->getElementsByTagName("mdhd")->item(0);
            $timeScale1 = $mdhdBox1->getAttribute("timescale");
            $earliestCompositionTime1 = $trunBoxes1->item(0)->getAttribute('earliestCompositionTime');
            $elstEntries1 = $xml1->getElementsByTagName('elstEntry');
            $mediaTime1 = 0;
            if ($elstEntries1->length > 0) {
                $mediaTime1 = $elstEntries1->item(0)->getAttribute('mediaTime');
            }
            $sumSampleDur = 0;
            for ($j = 0; $j < $trunBoxes1->length; $j++) {
                $sumSampleDur += $trunBoxes1->item($j)->getAttribute("cummulatedSampleDuration");
            }
        }
        $dir2 = $session->getRepresentationDir($i + 1, $adapt, 0);
        $xml2 = DASHIF\Utility\parseDOM($dir2 . '/atomInfo.xml', 'atomlist');
        if ($xml2) {
            $hdlrBox2 = $xml2->getElementsByTagName('hdlr')->item(0);
            $hdlrType2 = $hdlrBox2->getAttribute("handler_type");
            $mdhdBox2 = $xml2->getElementsByTagName("mdhd")->item(0);
            $timeScale2 = $mdhdBox2->getAttribute("timescale");
            $sidx = $xml2->getElementsByTagName('sidx');
            if ($sidx->length > 0) {
                $presentationTime2 = $sidx->item(0)->getAttribute("earliestPresentationTime");
            } else {
                $trunBoxes2 = $xml2->getElementsByTagName('trun')->item(0);
                $earliestCompositionTime2 = $trunBoxes2->getAttribute('earliestCompositionTime');
                $elstEntries2 = $xml2->getElementsByTagName('elstEntry');
                $mediaTime2 = 0;
                if ($elstEntries2->length > 0) {
                    $mediaTime2 = $elstEntries2->item(0)->getAttribute('mediaTime');
                }
                $presentationTime2 = $earliestCompositionTime2 + $mediaTime2;
            }
        }
        if ($hdlrType1 == $hdlrType2 && ($hdlrType1 == "vide" || $hdlrType1 == "soun")) {
                $logger->test(
                    "WAVE Content Spec 2018Ed",
                    "Section 6.1",
                    "For a WAVE Program with more than one CMAF Presentation, all audio and video Shall be contained in Sequential Sw Sets",
                    $remainder == 0,
          (($earliestCompositionTime1 + $mediaTime1 + $sumSampleDur) / $timeScale1) ==
            ($presentationTime2 / $timeScale2),
                    "FAIL",
                    "No overlap/gap found for Sw set $adaptation between presentations $i and " . ($i+1),
                    "Overlap/gap found for Sw set $adaptation between presentations $i and " . ($i+1)
                );
        }
    }
}
