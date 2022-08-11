<?php

global $session_dir, $MediaProfDatabase, $adaptation_set_template, $reprsentation_template;

$periodCount = sizeof($MediaProfDatabase);
$adaptationCount = sizeof($MediaProfDatabase[0]);
$errorMsg = "";
for ($i = 0; $i < ($periodCount - 1); $i++) {
    for ($adapt = 0; $adapt < $adaptationCount; $adapt++) {
        $adaptationDirectory = str_replace('$AS$', $adapt, $adaptation_set_template);
        $representationDirectory = str_replace(array('$AS$', '$R$'), array($adapt, 0), $reprsentation_template);
        $xml1 = get_DOM($session_dir . '/Period' . $i . '/' . $adaptationDirectory . '/' .
          $representationDirectory . '.xml', 'atomlist');
        if ($xml1) {
            $trun = $xml1->getElementsByTagName('trun')->item(0);
            $earlyCompTime_p1 = $trun->getAttribute('earliestCompositionTime');
            $xml_elst = $xml1->getElementsByTagName('elstEntry');
            $mediaTime_p1 = 0;
            if ($xml_elst->length > 0) {
                $mediaTime_p1 = $xml_elst->item(0)->getAttribute('mediaTime');
            }
            $trun = $xml1->getElementsByTagName('trun');
            $sumSampleDur = 0;
            for ($j = 0; $j < $trun->length; $j++) {
                $sumSampleDur += $trun->item($j)->getAttribute("cummulatedSampleDuration");
            }
        }
        $xml2 = get_DOM($session_dir . '/Period' . ($i + 1) . '/' . $adaptationDirectory . '/' .
          $representationDirectory . '.xml', 'atomlist');
        if ($xml2) {
            $sidx = $xml2->getElementsByTagName('sidx');
            if ($sidx->length > 0) {
                $presTime_p2 = $sidx->item(0)->getAttribute("earliestPresentationTime");
            } else {
                $trun = $xml2->getElementsByTagName('trun')->item(0);
                $earlyCompTime_p2 = $trun->getAttribute('earliestCompositionTime');
                $xml_elst = $xml2->getElementsByTagName('elstEntry');
                $mediaTime_p2 = 0;
                if ($xml_elst->length > 0) {
                    $mediaTime_p2 = $xml_elst->item(0)->getAttribute('mediaTime');
                }
                $presTime_p2 = $earlyCompTime_p2 + $mediaTime_p2;
            }
        }
        if (($earlyCompTime_p1 + $mediaTime_p1 + $sumSampleDur) > $presTime_p2) {
            $errorMsg = "###CTA WAVE check violated: WAVE Content Spec 2018Ed-Section 7.2.2: 'CMAF Fragments " .
            "Shall not overlap the same WAVE Program presentation time at the Splice point', overlap is observed " .
            "for Sw set " . $adapt . " between CMAF Presentations " . $i . " (" .
            ($earlyCompTime_p1 + $mediaTime_p1 + $sumSampleDur) . ") and  " . ($i + 1) . " (" . $presTime_p2 . ").";
        } elseif (($earlyCompTime_p1 + $mediaTime_p1 + $sumSampleDur) < $presTime_p2) {
            $errorMsg .= "###CTA WAVE check violated: WAVE Content Spec 2018Ed-Section 7.2.2: 'CMAF Fragments " .
            "Shall not have gaps in WAVE Program presentation time at the Splice point', gap is observed " .
            "for Sw set " . $adapt . " between CMAF Presentations " . $i . " (" .
            ($earlyCompTime_p1 + $mediaTime_p1 + $sumSampleDur) . ") and  " . ($i + 1) . " (" . $presTime_p2 . ").";
        }
    }
}

return $errorMsg;
