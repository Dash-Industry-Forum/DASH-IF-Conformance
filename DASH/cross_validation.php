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

function loadLeafInfoFile($fileName, $PresTimeOffset)
{

    global $session;

    $info = array();

    $leafInfoFile = fopen($session->getDir() . '/' . $fileName, 'rt');
    if (!$leafInfoFile) {
        return;
    }

    fscanf($leafInfoFile, "%lu\n", $accessUnitDurationNonIndexedTrack);
    fscanf($leafInfoFile, "%u\n", $info['numTracks']);

    $info['leafInfo'] = array();
    $info['numLeafs'] = array();
    $info['trackTypeInfo'] = array();

    for ($i = 0; $i < $info['numTracks']; $i++) {
        fscanf(
            $leafInfoFile,
            "%lu %lu\n",
            $info['trackTypeInfo'][$i]['track_ID'],
            $info['trackTypeInfo'][$i]['componentSubType']
        );
    }

    for ($i = 0; $i < $info['numTracks']; $i++) {
        fscanf($leafInfoFile, "%u\n", $info['numLeafs'][$i]);
        $info['leafInfo'][$i] = array();

        for ($j = 0; $j < $info['numLeafs'][$i]; $j++) {
            fscanf(
                $leafInfoFile,
                "%d %f %f\n",
                $info['leafInfo'][$i][$j]['firstInSegment'],
                $info['leafInfo'][$i][$j]['earliestPresentationTime'],
                $info['leafInfo'][$i][$j]['lastPresentationTime']
            );
            $info['leafInfo'][$i][$j]['earliestPresentationTime'] -= $PresTimeOffset;
            $info['leafInfo'][$i][$j]['lastPresentationTime'] -= $PresTimeOffset;
        }
    }

    fclose($leafInfoFile);
    return $info;
}

function checkAlignment($leafInfoA, $leafInfoB, $segmentAlignment, $subsegmentAlignment, $bitstreamSwitching)
{
    global $logger;

    $equalTrackCount = $logger->test(
        "CrossValidation",
        "",
        "The number of tracks between representations should be equal",
        $leafInfoA['numTracks'] == $leafInfoB['numTracks'],
        "FAIL",
        "Track count equal for tracks " . $leafInfoA['id'] . " and " . $leafInfoB['id'],
        "Track count not equal for tracks " . $leafInfoA['id'] . " and " . $leafInfoB['id'] .
        ($bitstreamSwitching ? ", bitstream switching not possible" : "")
    );

    if (!$equalTrackCount) {
        return;
    }

    $trackTypeInfoA = $leafInfoA['trackTypeInfo'];
    $trackTypeInfoB = $leafInfoB['trackTypeInfo'];

    if ($bitstreamSwitching) {
        for ($i = 0; $i < $leafInfoA['numTracks']; $i++) {
            $correspondingTrackFound = false;

            for ($j = 0; $j < $leafInfoB['numTracks']; $j++) {
                if (
                    $trackTypeInfoA[$i]['track_ID'] == $trackTypeInfoB[$j]['track_ID'] &&
                    $trackTypeInfoA[$i]['componentSubType'] == $trackTypeInfoB[$j]['componentSubType']
                ) {
                    $correspondingTrackFound = true;
                    break;
                }
            }


            $logger->test(
                "ISO/IEC 23009-1:2012(E)",
                "Section 7.3.3.2",
                "The track IDs for the same media content component are identical for each Representation " .
                "in each Adaptation Set",
                $correspondingTrackFound,
                "FAIL",
                "Corresponding track found for representation id " . $leafInfoA['id'] . " track " .
                $trackTypeInfoA[$i]['track_ID'] . " with type " . $trackTypeInfoA[$i]['componentSubType'],
                "No corresponding track found for representation id " . $leafInfoA['id'] . " track " .
                $trackTypeInfoA[$i]['track_ID'] . " with type " . $trackTypeInfoA[$i]['componentSubType']
            );
        }
    }

    if (!$segmentAlignment && !$subsegmentAlignment) {
        return;
    }

    $leafIdA = $leafInfoA['id'];
    $leafIdB = $leafInfoB['id'];

    for ($i = 0; $i < $leafInfoA['numTracks']; $i++) {
        $equalLeafCount = $logger->test(
            "CrossValidation",
            "",
            "The number of leafs between representations should be equal",
            $leafInfoA['numLeafs'] == $leafInfoB['numLeafs'],
            "FAIL",
            "Leaf count equal for tracks $leafIdA and $leafIdB",
            "Leaf count not equal for tracks $leafIdA and $leafIdB"
        );

        if (!$equalLeafCount) {
            continue;
        }

        $leafAInfo = $leafInfoA['leafInfo'];
        $leafBInfo = $leafInfoB['leafInfo'];

        for ($j = 0; $j < ($leafInfoA['numLeafs'][$i] - 1); $j++) {
            if ($subsegmentAlignment || ($leafAInfo[$i][$j + 1]['firstInSegment'] > 0)) {
                $overlapLeafA = $leafAInfo[$i][$j + 1]['earliestPresentationTime'] <=
                                $leafBInfo[$i][$j]['lastPresentationTime'];
                $isSegmentLeafA = $leafAInfo[$i][$j + 1]['firstInSegment'] > 0;

                $logger->test(
                    "Cross validation",
                    "",
                    "Leafs should not overlap",
                    !$overlapLeafA,
                    "FAIL",
                    "EPT for Leaf number " . ($j + 2) . " in representation $leafIdA is > the latest presentation " .
                    "time for corresponding leaf in representation $leafIdB",
                    "Overlapping " . ($isSegmentLeafA ? "segment" : "subsegment") . ": EPT for Leaf number " .
                    ($j + 2) . " in representation $leafIdA is <= the latest presentation time for corresponding " .
                    "leaf in representation i$leafIdB"
                );


                $overlapLeafB = $leafBInfo[$i][$j + 1]['earliestPresentationTime'] <=
                                $leafAInfo[$i][$j]['lastPresentationTime'];
                $isSegmentLeafB = $leafBInfo[$i][$j + 1]['firstInSegment'] > 0;

                $logger->test(
                    "Cross validation",
                    "",
                    "Leafs should not overlap",
                    !$overlapLeafB,
                    "FAIL",
                    "EPT for Leaf number " . ($j + 2) . " in representation $leafIdB is > the latest presentation " .
                    "time for corresponding leaf in representation $leafIdA",
                    "Overlapping " . ($isSegmentLeafB ? "segment" : "subsegment") . ": EPT for Leaf number " .
                    ($j + 2) . " in representation $leafIdB is <= the latest presentation time for corresponding " .
                    "leaf in representation $leafIdA"
                );
            }
        }
    }
}

function crossRepresentationProcess()
{
    global $mpdHandler, $session;

    $adaptation_set = $mpdHandler->getFeatures()['Period'][$mpdHandler->getSelectedPeriod()]
                                                ['AdaptationSet'][$mpdHandler->getSelectedAdaptationSet()];
    $timeoffset = 0;
    $timescale = 1;

    $adaptationDir = $session->getSelectedAdaptationDir();

    $segmentAlignment = false;
    if ($adaptation_set['segmentAlignment']) {
        $segmentAlignment = ($adaptation_set['segmentAlignment'] == "true");
    }
    $subsegmentAlignment = false;
    if ($adaptation_set['subsegmentAlignment']) {
        $subsegmentAlignment = ($adaptation_set['subsegmentAlignment'] == "true");
    }
    $bitstreamSwitching = false;
    if ($adaptation_set['bitstreamSwitching']) {
        $bitstreamSwitching = ($adaptation_set['bitstreamSwitching']  == "true");
    }

    if ($segmentAlignment || $subsegmentAlignment || $bitstreamSwitching) {
        $leafInfo = array();

        $representations = $adaptation_set['Representation'];
        for ($j = 0; $j < sizeof($representations); $j++) {
            $timeoffset = 0;
            $timescale = 1;
            $representation = $representations[$j];

            if (!empty($adaptation_set['SegmentTemplate']['timescale'])) {
                $timescale = $adaptation_set['SegmentTemplate']['timescale'];
            }

            if (!empty($adaptation_set['SegmentTemplate']['presentationTimeOffset'])) {
                $timeoffset = $adaptation_set['SegmentTemplate']['presentationTimeOffset'];
            }

            if (!empty($representation['SegmentTemplate']['timescale'])) {
                $timescale = $representation['SegmentTemplate']['timescale'];
            }

            if (!empty($representation['SegmentTemplate']['presentationTimeOffset'])) {
                $timeoffset = $representation['SegmentTemplate']['presentationTimeOffset'];
            }

            if (!empty($representation['presentationTimeOffset'])) {
                $timeoffset = $representation['presentationTimeOffset'];
            }

            $offsetmod = $timeoffset / $timescale;

            $representationDir = $session->getRepresentationDir(
                $mpdHandler->getSelectedPeriod(),
                $mpdHandler->getSelectedAdaptationSet(),
                $j
            );
            $leafInfo[$j] = loadLeafInfoFile($representationDir . "/infofile.txt", $offsetmod);
            $leafInfo[$j]['id'] = $representation['id'];
        }

        for ($j = 0; $j < sizeof($representations) - 1; $j++) {
            for ($k = $j + 1; $k < sizeof($representations); $k++) {
                checkAlignment(
                    $leafInfo[$j],
                    $leafInfo[$k],
                    $segmentAlignment,
                    $subsegmentAlignment,
                    $bitstreamSwitching
                );
            }
        }
    }
}
