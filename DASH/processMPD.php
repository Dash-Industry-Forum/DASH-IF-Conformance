<?php

/* This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

function process_MPD()
{
    global $session_dir, $mpd_dom, $mpd_features, $mpd_validation_only,
            $current_period, $profiles;

    global $modules;

    $mpd_dom = mpd_load();
    if (!$mpd_dom) {
        ///\RefactorTodo Add global error message!
        //die("Error: Failed loading XML file\n");
        return;
    }

    foreach ($modules as $module) {
        if ($module->isEnabled()) {
            $module->hookBeforeMPD();
        }
    }

    ## Get MPD features into an array
    $mpd_features = MPD_features($mpd_dom);
    $profiles = derive_profiles();

    //------------------------------------------------------------------------//
    ## Perform MPD Validation
    ## Write to MPD report
    ## If only MPD validation is requested or inferred, exit
    ## If any error is found in the MPD validation process, exit
    ## If no error is found, then proceed with segment validation below
    $valid_mpd = validate_MPD();

    foreach ($modules as $module) {
        if ($module->isEnabled()) {
            $module->hookMPD();
        }
    }

    if (!$valid_mpd[0] || $mpd_validation_only) {
        return;
    }

    //------------------------------------------------------------------------//
    ## Perform Segment Validation for each representation in each adaptation set within the current period
    check_before_segment_validation();
    if ($mpd_features['type'] !== 'dynamic') {
        $current_period = 0;
    }
    while ($current_period < sizeof($mpd_features['Period'])) {
        $period_info = current_period();
        $urls = process_base_url();
        $segment_urls = derive_segment_URLs($urls, $period_info);

        $period_dir_name = "Period" . $current_period;
        $curr_period_dir = $session_dir . '/' . $period_dir_name;
        create_folder_in_session($curr_period_dir);


        $period = $mpd_features['Period'][$current_period];
        processAdaptationSetOfCurrentPeriod($period, $curr_period_dir, $ResultXML, $segment_urls);

        if ($mpd_features['type'] === 'dynamic') {
            break;
        }

        $current_period++;
    }
    if ($current_period >= 1) {
        foreach ($modules as $module) {
            if ($module->isEnabled()) {
                $module->hookPeriod();
            }
        }
    }
}

function processAdaptationSetOfCurrentPeriod($period, $curr_period_dir, $ResultXML, $segment_urls)
{
    global  $mpd_features, $current_period, $current_adaptation_set, $adaptation_set_template,$current_representation,$reprsentation_template,$session_dir,
           $progress_report, $reprsentation_error_log_template, $additional_flags,
           $dashif_conformance, $iop_function_name, $iop_when_to_call,                                      // DASH-IF IOP data
           $cmaf_conformance, $cmaf_function_name, $cmaf_when_to_call,                                      // CMAF data
           $hbbtv_conformance, $dvb_conformance, $hbbtv_dvb_function_name, $hbbtv_dvb_when_to_call,         // HbbTV-DVB data
           $ctawave_conformance, $ctawave_function_name, $ctawave_when_to_call,                             // CTA WAVE data
           $low_latency_dashif_conformance, $low_latency_function_name, $low_latency_when_to_call;          // Low Latency DASH-IF data

    global $modules;

    $adaptation_sets = $period['AdaptationSet'];
    while ($current_adaptation_set < sizeof($adaptation_sets)) {
        $adaptation_set = $adaptation_sets[$current_adaptation_set];
        $representations = $adaptation_set['Representation'];

        $adapt_dir_name = str_replace('$AS$', $current_adaptation_set, $adaptation_set_template);
        $curr_adapt_dir = $curr_period_dir . '/' . $adapt_dir_name . '/';
        create_folder_in_session($curr_adapt_dir);


        while ($current_representation < sizeof($representations)) {
            $representation = $representations[$current_representation];
            $segment_url = $segment_urls[$current_adaptation_set][$current_representation];


            $rep_dir_name = str_replace(array('$AS$', '$R$'), array($current_adaptation_set, $current_representation), $reprsentation_template);
            $curr_rep_dir = $curr_period_dir . '/' . $rep_dir_name . '/';
            create_folder_in_session($curr_rep_dir);


            $additional_flags = '';
            foreach ($modules as $module) {
                if ($module->isEnabled()) {
                    $module->hookBeforeRepresentation();
                }
            }

            $return_seg_val = validate_segment($curr_adapt_dir, $curr_rep_dir, $period, $adaptation_set, $representation, $segment_url, $rep_dir_name, $is_subtitle_rep);
            ValidateDolby($adaptation_set, $representation);

            foreach ($modules as $module) {
                if ($module->isEnabled()) {
                    $module->hookRepresentation();
                }
            }

            $current_representation++;
        }

        ## Representations in current Adaptation Set finished
        crossRepresentationProcess();

        foreach ($modules as $module) {
            if ($module->isEnabled()) {
                $module->hookBeforeAdaptationSet();
            }
        }

        $current_representation = 0;
        $current_adaptation_set++;
    }

    ## Adaptation Sets in current Period finished
    foreach ($modules as $module) {
        if ($module->isEnabled()) {
            $module->hookAdaptationSet();
        }
    }
    /*
    if ($dashif_conformance) {
        $iop_function_name($iop_when_to_call[2]);
    }
    if ($cmaf_conformance) {
        $return_arr = $cmaf_function_name($cmaf_when_to_call[3]);
        foreach ($return_arr as $return_item) {
            $file_error[] = $return_item;
        }
    }
    if ($hbbtv_conformance || $dvb_conformance) {
        $return_arr = $hbbtv_dvb_function_name($hbbtv_dvb_when_to_call[5]);
    }
    if ($ctawave_conformance) {
        $return_arr = $ctawave_function_name($ctawave_when_to_call[1]);
    }
    if ($low_latency_dashif_conformance) {
        $return_arr = $low_latency_function_name($low_latency_when_to_call[1]);
    }*/

    //err_file_op(2);
    $current_adaptation_set = 0;
}

function check_before_segment_validation()
{
    global $session_dir, $mpd_features, $mpd_dom, $progress_report;


    $supplemental = $mpd_dom->getElementsByTagName('SupplementalProperty');
    if ($supplemental->length > 0) {
        $supplementalScheme = $supplemental->item(0)->getAttribute('schemeIdUri');
        if (($supplementalScheme === 'urn:mpeg:dash:chaining:2016') || ($supplementalScheme === 'urn:mpeg:dash:fallback:2016')) {
            $MPDChainingURL = $supplemental->item(0)->getAttribute('value');
        }
    }

    if ($mpd_dom->getElementsByTagName('SegmentList')->length !== 0) {
        session_close();
        exit;
    }
}
