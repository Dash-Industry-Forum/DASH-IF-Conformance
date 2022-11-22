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

function process_MPD($parseSegments = false)
{
    global $mpd_dom, $mpd_features, $mpd_validation_only, $current_period, $profiles;

    global $session;

    global $modules;

    global $logger;

    $logger->parseSegments = $parseSegments;

    $mpd_dom = mpd_load();
    if (!$mpd_dom) {
        ///\RefactorTodo Add global error message!
        fwrite(STDERR, "Unable to load mpd dom\n");
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
    ## If only MPD validation is requested or inferred, stop
    ## If any error is found in the MPD validation process, stop
    ## If no error is found, then proceed with segment validation below
//    $valid_mpd = validate_MPD();



    foreach ($modules as $module) {
        if ($module->isEnabled()) {
            $module->hookMPD();
        }
    }

    if (!$parseSegments) {
        fwrite(STDERR, ($parseSegments ? "DO " : "DO NOT ") . "parse segments\n");
        return;
    }

    //------------------------------------------------------------------------//
    ## Perform Segment Validation for each representation in each adaptation set within the current period
    if (!checkBeforeSegmentValidation()) {
      return;
    }
    if ($mpd_features['type'] !== 'dynamic') {
        $current_period = 0;
    }
    while ($current_period < sizeof($mpd_features['Period'])) {
        $period_info = current_period();
        $urls = process_base_url();
        $segment_urls = derive_segment_URLs($urls, $period_info);

        $period = $mpd_features['Period'][$current_period];
        processAdaptationSetOfCurrentPeriod($period, $ResultXML, $segment_urls);

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

function processAdaptationSetOfCurrentPeriod($period, $ResultXML, $segment_urls)
{
    global  $current_adaptation_set, $adaptation_set_template,$current_representation,$reprsentation_template,
            $additional_flags, $current_period;

    global $session;

    global $modules;

    global $logger;

    $adaptation_sets = $period['AdaptationSet'];
    while ($current_adaptation_set < sizeof($adaptation_sets)) {
        if ($logger->getModuleVerdict("HEALTH") == "FAIL") {
            break;
        }
        $adaptation_set = $adaptation_sets[$current_adaptation_set];
        $representations = $adaptation_set['Representation'];

        $adaptationDirectory = $session->getAdaptationDir($current_period, $current_adaptation_set);


        while ($current_representation < sizeof($representations)) {
            if ($logger->getModuleVerdict("HEALTH") == "FAIL") {
                break;
            }
            $representation = $representations[$current_representation];
            $segment_url = $segment_urls[$current_adaptation_set][$current_representation];

            $representationDirectory = $session->getRepresentationDir($current_period, $current_adaptation_set, $current_representation);


            $additional_flags = '';
            foreach ($modules as $module) {
                if ($module->isEnabled()) {
                    $module->hookBeforeRepresentation();
                }
            }

            $logger->setModule("HEALTH");
            validate_segment($adaptationDirectory, $representationDirectory, $period, $adaptation_set, $representation, $segment_url, $is_subtitle_rep);
            $logger->write();
            if ($logger->getModuleVerdict("HEALTH") == "FAIL") {
                break;
            }

            foreach ($modules as $module) {
                if ($module->isEnabled()) {
                    $module->hookRepresentation();
                }
            }

            $current_representation++;
        }
        if ($logger->getModuleVerdict("HEALTH") == "FAIL") {
            break;
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

    if ($logger->getModuleVerdict("HEALTH") != "FAIL") {
    ## Adaptation Sets in current Period finished
        foreach ($modules as $module) {
            if ($module->isEnabled()) {
                $module->hookAdaptationSet();
            }
        }
    }
    //err_file_op(2);
    $current_adaptation_set = 0;
}

function checkBeforeSegmentValidation()
{
    global $mpd_dom;


    $supplemental = $mpd_dom->getElementsByTagName('SupplementalProperty');
    if ($supplemental->length > 0) {
        $supplementalScheme = $supplemental->item(0)->getAttribute('schemeIdUri');
        if (($supplementalScheme === 'urn:mpeg:dash:chaining:2016') || ($supplementalScheme === 'urn:mpeg:dash:fallback:2016')) {
            $MPDChainingURL = $supplemental->item(0)->getAttribute('value');
        }
    }

    if ($mpd_dom->getElementsByTagName('SegmentList')->length !== 0) {
        session_close();
        return false;
    }
    return true;
}
