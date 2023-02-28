<?php

global $mpdHandler, $session, $validators;

$this->contentProtectionReport();
$adaptations = $mpdHandler->getFeatures()['Period'][$mpdHandler->getSelectedPeriod()]['AdaptationSet'];
for ($adaptationIndex = 0; $adaptationIndex < sizeof($adaptations); $adaptationIndex++) {
    $loc = $session->getAdaptationDir($mpdHandler->getSelectedPeriod(), $adaptationIndex);
    $fileCount = 0;
    $files = DASHIF\rglob("$loc/*.xml");
    if ($files) {
        $fileCount = count($files);
    }

    ## Cross Validation Checks
    for ($index1 = 0; $index1 < $fileCount; $index1++) {
        $xmlDom1 = DASHIF\Utility\parseDOM($files[$index1], 'atomlist');

        for ($index2 = $index1 + 1; $index2 < $fileCount; $index2++) {
            $xmlDom2 = DASHIF\Utility\parseDOM($files[$index2], 'atomlist');

            if ($xmlDom1 && $xmlDom2) {
                if ($this->HbbTvEnabled) {
                    $this->crossValidationHbbTV(
                        $xmlDom1,
                        $xmlDom2,
                        $adaptationIndex,
                        $index1,
                        $index2
                    );
                }
                if ($this->DVBEnabled) {
                    $this->crossValidationDVB(
                        $xmlDom1,
                        $xmlDom2,
                        $adaptationIndex,
                        $index1,
                        $index2
                    );
                }
            }


            foreach ($validators as $v){
              $r1 = $v->getRepresentation($mpdHandler->getSelectedPeriod(), $adaptationIndex, $index1);
              $r2 = $v->getRepresentation($mpdHandler->getSelectedPeriod(), $adaptationIndex, $index2);

              if ($r1 && $r2){
                if ($this->DVBEnabled) {
                    $this->crossValidationDVBAdapter(
                        $r1,
                        $r2
                    );
                }
              }
            }

        }
    }
    $this->initializationSegmentCommonCheck($files);
    if ($this->DVBEnabled) {
        $this->dvbPeriodContinousAdaptationSetsCheck();
    }

    $this->addOrRemoveImages('REMOVE');
}
