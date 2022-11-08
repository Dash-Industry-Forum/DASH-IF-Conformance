<?php

global $hbbtv_conformance, $dvb_conformance, $mpd_features, $current_period, $session;

$this->contentProtectionReport();
$adaptations = $mpd_features['Period'][$current_period]['AdaptationSet'];
for ($adaptationIndex = 0; $adaptationIndex < sizeof($adaptations); $adaptationIndex++) {
    $loc = $session->getAdaptationDir($current_period, $adaptationIndex);
    $fileCount = 0;
    $files = DASHIF\rglob("$loc/*.xml");
    if ($files) {
        $fileCount = count($files);
    }

    ## Cross Validation Checks
    for ($index1 = 0; $index1 < $fileCount; $index1++) {
        $xmlDom1 = get_DOM($files[$index1], 'atomlist');

        for ($index2 = $index1 + 1; $index2 < $fileCount; $index2++) {
            $xmlDom2 = get_DOM($files[$index2], 'atomlist');

            if ($xmlDom1 && $xmlDom2) {
                if ($hbbtv_conformance) {
                    $this->crossValidationHbbTVRepresentations(
                        $xmlDom1,
                        $xmlDom2,
                        $adaptationIndex,
                        $index1,
                        $index2
                    );
                }
                if ($dvb_conformance) {
                    $this->crossValidationDVBRepresentations(
                        $xmlDom1,
                        $xmlDom2,
                        $adaptationIndex,
                        $index1,
                        $index2
                    );
                }
            }
        }
    }
    $this->initializationSegmentCommonCheck($files);
    if ($dvb_conformance) {
        $this->DVBPeriodContinousAdapatationSetsCheck();
    }

    $this->addOrRemoveImages('REMOVE');
}
