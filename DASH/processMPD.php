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

function process_MPD($parseSegments = false, $autoDetect = false, $detailedSegmentOutput = true)
{
    global $mpd_url;

    global $session;

    global $modules;

    global $logger;


    global $mpdHandler;

    $logger->parseSegments = $parseSegments;

    //------------------------------------------------------------------------//
    ## Perform MPD Validation
    ## Write to MPD report
    ## If only MPD validation is requested or inferred, stop
    ## If any error is found in the MPD validation process, stop
    ## If no error is found, then proceed with segment validation below
    $mpdHandler = new DASHIF\MPDHandler($mpd_url);

    if ($mpdHandler->getDOM() == null) {
        fwrite(STDERR, "Unable to parse mpd @ $mpd_url\n");
        return;
    }

    fwrite(STDERR, "Going to parse $mpd_url\n");


    if ($autoDetect) {
        foreach ($modules as &$module) {
            $module->detectFromManifest();
        }
    }


    foreach ($modules as $module) {
        if ($module->isEnabled()) {
            $module->hookBeforeMPD();
        }
    }


    foreach ($modules as $module) {
        if ($module->isEnabled()) {
            $module->hookMPD();
        }
    }

    echo "Internal\n";
    print_r($mpdHandler->internalSegmentUrls());
    echo "----\nFormer\n";
    print_r($mpdHandler->getSegmentUrls(0));



    if (!$parseSegments) {
        fwrite(STDERR, ($parseSegments ? "DO " : "DO NOT ") . "parse segments\n");
        return;
    }

    //------------------------------------------------------------------------//
    ## Perform Segment Validation for each representation in each adaptation set within the current period
    if ($mpdHandler->getDom()->getElementsByTagName('SegmentList')->length !== 0) {
        return;
    }
    if ($mpdHandler->getFeatures()['type'] !== 'dynamic') {
        $mpdHandler->selectPeriod(0);
    }
    while ($mpdHandler->getSelectedPeriod() < sizeof($mpdHandler->getFeatures()['Period'])) {
        processAdaptationSetOfCurrentPeriod($detailedSegmentOutput);

        if ($logger->getModuleVerdict("HEALTH") == "FAIL") {
            return;
        }

        if ($mpdHandler->getFeatures()['type'] === 'dynamic') {
            break;
        }

        $mpdHandler->selectNextPeriod();
    }
    if ($mpdHandler->getSelectedPeriod() >= 1) {
        foreach ($modules as $module) {
            if ($module->isEnabled()) {
                $module->hookPeriod();
            }
        }
    }
}

function processAdaptationSetOfCurrentPeriod($detailedSegmentOutput = true)
{
    global $additional_flags;

    global $session, $logger;

    global $modules, $mpdHandler;

    $period = $mpdHandler->getCurrentPeriodFeatures();
    $segment_urls = $mpdHandler->getSegmentUrls();

    global $logger;

    $adaptation_sets = $period['AdaptationSet'];
    $adaptationSetCount = $adaptation_sets == null ? 0 : sizeof($adaptation_sets);
    while ($mpdHandler->getSelectedAdaptationSet() < $adaptationSetCount) {
        if ($logger->getModuleVerdict("HEALTH") == "FAIL") {
            break;
        }
        $adaptation_set = $adaptation_sets[$mpdHandler->getSelectedAdaptationSet()];
        $representations = $adaptation_set['Representation'];

        $adaptationDirectory = $session->getSelectedAdaptationDir();


        while ($mpdHandler->getSelectedRepresentation() < sizeof($representations)) {
            if ($logger->getModuleVerdict("HEALTH") == "FAIL") {
                break;
            }
            $representation = $representations[$mpdHandler->getSelectedRepresentation()];
            $segment_url = $segment_urls[$mpdHandler->getSelectedAdaptationSet()]
            [$mpdHandler->getSelectedRepresentation()];

            $representationDirectory = $session->getSelectedRepresentationDir();


            $additional_flags = '';
            foreach ($modules as $module) {
                if ($module->isEnabled()) {
                    $module->hookBeforeRepresentation();
                }
            }

            validate_segment(
                $adaptationDirectory,
                $representationDirectory,
                $period,
                $adaptation_set,
                $representation,
                $segment_url,
                $is_subtitle_rep,
                $detailedSegmentOutput
            );
            $logger->write();
            if ($logger->getModuleVerdict("HEALTH") == "FAIL") {
                break;
            }

            foreach ($modules as $module) {
                if ($module->isEnabled()) {
                    $module->hookRepresentation();
                }
            }

            $mpdHandler->selectNextRepresentation();
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


        $mpdHandler->selectRepresentation(0);
        $mpdHandler->selectNextAdaptationSet();
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
    $mpdHandler->selectAdaptationSet(0);
}
