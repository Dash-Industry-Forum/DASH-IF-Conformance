<?php

global $hbbtv_conformance, $dvb_conformance, $session_dir, $mpd_features,
       $current_period, $adaptation_set_template, $hbbtv_dvb_crossvalidation_logfile,
       $string_info, $progress_xml, $progress_report;

//Todo: Fix
//content_protection_report();
$adaptations = $mpd_features['Period'][$current_period]['AdaptationSet'];
for ($adaptationIndex = 0; $adaptationIndex < sizeof($adaptations); $adaptationIndex++) {
    $adaptationDirectory = str_replace('$AS$', $adaptationIndex, $adaptation_set_template);
    $loc = $session_dir . '/Period' . $current_period . '/' . $adaptationDirectory . '/';
    $fileCount = 0;
    $files = glob($loc . "*.xml");
    if ($files) {
        $fileCount = count($files);
    }

    ## Cross Validation Checks
    for ($index1 = 0; $index1 < $fileCount; $index1++) {
        $xmlDom1 = get_DOM($files[$index1], 'atomlist');

        for ($index2 = $index1 + 1; $index2 < $fileCount; $index2++) {
            $xmlDom2 = get_DOM($files[$index2], 'atomlist');

            if ($xmlDom1 && $xmlDom2) {
              ///Todo: Fix
              /*
                if ($hbbtv_conformance) {
                    crossValidation_HbbTV_Representations(
                        $opfile,
                        $xmlDom1,
                        $xmlDom2,
                        $adaptationIndex,
                        $index1,
                        $index2
                    );
                }
                if ($dvb_conformance) {
                    crossValidation_DVB_Representations(
                        $opfile,
                        $xmlDom1,
                        $xmlDom2,
                        $adaptationIndex,
                        $index1,
                        $index2
                    );
                }
               */
            }
        }
    }
    ///Todo: Fix
    /*
    init_seg_commonCheck($files, $opfile);
    if ($dvb_conformance) {
        DVB_period_continuous_adaptation_sets_check($opfile);
    }
     */
    ##

    $this->addOrRemoveImages('REMOVE');
    tabulateResults($session_dir . '/Period' . $current_period . '/' . $log_file . '.txt', 'Cross');
}
