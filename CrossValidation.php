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

function loadLeafInfoFile($fileName, $PresTimeOffset){
    global $session_dir;
    $info = array();

    $leafInfoFile = open_file($session_dir . '/' . $fileName, 'rt');
    if (!$leafInfoFile)
        return;

    fscanf($leafInfoFile, "%lu\n", $accessUnitDurationNonIndexedTrack);
    fscanf($leafInfoFile, "%u\n", $info['numTracks']);

    $info['leafInfo'] = array();
    $info['numLeafs'] = array();
    $info['trackTypeInfo'] = array();

    for ($i = 0; $i < $info['numTracks']; $i++)
        fscanf($leafInfoFile, "%lu %lu\n", $info['trackTypeInfo'][$i]['track_ID'], $info['trackTypeInfo'][$i]['componentSubType']);

    for ($i = 0; $i < $info['numTracks']; $i++){
        fscanf($leafInfoFile, "%u\n", $info['numLeafs'][$i]);
        $info['leafInfo'][$i] = array();

        for ($j = 0; $j < $info['numLeafs'][$i]; $j++){
            fscanf($leafInfoFile, "%d %f %f\n", $info['leafInfo'][$i][$j]['firstInSegment'], $info['leafInfo'][$i][$j]['earliestPresentationTime'], $info['leafInfo'][$i][$j]['lastPresentationTime']);
            $info['leafInfo'][$i][$j]['earliestPresentationTime'] = $info['leafInfo'][$i][$j]['earliestPresentationTime'] - $PresTimeOffset;
            $info['leafInfo'][$i][$j]['lastPresentationTime'] = $info['leafInfo'][$i][$j]['lastPresentationTime'] - $PresTimeOffset;
        }
    }

    close_file($leafInfoFile);
    return $info;
}

function checkAlignment($leafInfoA, $leafInfoB, $opfile, $segmentAlignment, $subsegmentAlignment, $bitstreamSwitching){
    if ($leafInfoA['numTracks'] != $leafInfoB['numTracks']){
        fprintf($opfile, "Error: Number of tracks logged %d for representation with id \"%s\" not equal to the number of indexed tracks %d for representation id \"%s\"\n", $leafInfoA['numTracks'], $leafInfoA['id'], $leafInfoB['numTracks'], $leafInfoB['id']);
        if ($bitstreamSwitching == "true")
            fprintf($opfile, "Bitstream switching not possible, validation failed for bitstreamSwitching\n");
        return;
    }

    if ($bitstreamSwitching == "true"){
        for ($i = 0; $i < $leafInfoA['numTracks']; $i++){
            $correspondingTrackFound = false;

            for ($j = 0; $j < $leafInfoB['numTracks']; $j++){
                if ($leafInfoA['trackTypeInfo'][$i]['track_ID'] == $leafInfoB['trackTypeInfo'][$j]['track_ID'] && $leafInfoA['trackTypeInfo'][$i]['componentSubType'] == $leafInfoB['trackTypeInfo'][$j]['componentSubType']){
                    $correspondingTrackFound = true;
                    break;
                }
            }

            if (!$correspondingTrackFound)
                fprintf($opfile, "Error: No corresponding track found in representation id \"%s\" for representation id \"%s\" track ID \"%s\" with type \"%s\", bitstream switching is not possible: ISO/IEC 23009-1:2012(E), 7.3.3.2: The track IDs for the same media content component are identical for each Representation in each Adaptation Set \n", $leafInfoB['id'], $leafInfoA['id'], $leafInfoA['trackTypeInfo'][$i]['track_ID'], $leafInfoA['trackTypeInfo'][$i]['componentSubType']);
        }
    }

    if ($segmentAlignment != "true" && $subsegmentAlignment != "true")
        return;

    for ($i = 0; $i < $leafInfoA['numTracks']; $i++){
        if ($leafInfoA['numLeafs'][$i] != $leafInfoB['numLeafs'][$i]){
            fprintf($opfile, "Error: Number of leafs %d for track %d for representation id \"%s\" not equal to the number of leafs %d for track %d for representation id \"%s\"\n", $leafInfoA['numLeafs'][$i], $i + 1, $leafInfoA['id'], $leafInfoB['numLeafs'][$i], $i + 1, $leafInfoB['id']);
            continue;
        }

        for ($j = 0; $j < ($leafInfoA['numLeafs'][$i] - 1); $j++){
            if ($subsegmentAlignment == "true" || ($leafInfoA['leafInfo'][$i][$j + 1]['firstInSegment'] > 0)){
                if ($leafInfoA['leafInfo'][$i][$j + 1]['earliestPresentationTime'] <= $leafInfoB['leafInfo'][$i][$j]['lastPresentationTime']){
                    if ($leafInfoA['leafInfo'][$i][$j + 1]['firstInSegment'] > 0)
                        fprintf($opfile, "Error: Overlapping segment: EPT of leaf %f for leaf number %d for representation id \"%s\" is <= the latest presentation time %f corresponding leaf for representation id \"%s\"\n", $leafInfoA['leafInfo'][$i][$j + 1]['earliestPresentationTime'], $j + 2, $leafInfoA['id'], $leafInfoB['leafInfo'][$i][$j]['lastPresentationTime'], $leafInfoB['id']);
                    else
                        fprintf($opfile, "Error: Overlapping sub-segment: EPT of leaf %f for leaf number %d for representation id \"%s\" is <= the latest presentation time %f corresponding leaf for representation id \"%s\"\n", $leafInfoA['leafInfo'][$i][$j + 1]['earliestPresentationTime'], $j + 2, $leafInfoA['id'], $leafInfoB['leafInfo'][$i][$j]['lastPresentationTime'], $leafInfoB['id']);
                }

                if ($leafInfoB['leafInfo'][$i][$j + 1]['earliestPresentationTime'] <= $leafInfoA['leafInfo'][$i][$j]['lastPresentationTime']) {
                    if ($leafInfoB['leafInfo'][$i][$j + 1]['firstInSegment'] > 0)
                        fprintf($opfile, "Error: Overlapping segment: EPT of leaf %f for leaf number %d for representation id \"%s\" is <= the latest presentation time %f corresponding leaf for representation id \"%s\"\n", $leafInfoB['leafInfo'][$i][$j + 1]['earliestPresentationTime'], $j + 2, $leafInfoB['id'], $leafInfoA['leafInfo'][$i][$j]['lastPresentationTime'], $leafInfoA['id']);
                    else
                        fprintf($opfile, "Error: Overlapping sub-segment: EPT of leaf %f for leaf number %d for representation id \"%s\" is <= the latest presentation time %f corresponding leaf for representation id \"%s\"\n", $leafInfoB['leafInfo'][$i][$j + 1]['earliestPresentationTime'], $j + 2, $leafInfoB['id'], $leafInfoA['leafInfo'][$i][$j]['lastPresentationTime'], $leafInfoA['id']);
                }
            }
        }
    }
}

function crossRepresentationProcess(){
    global $mpd_features, $current_period, $session_dir, $adaptation_set_error_log_template, $reprsentation_info_log_template, $string_info;

    $adaptation_sets = $mpd_features['Period'][$current_period]['AdaptationSet'];
    for ($i = 0; $i < sizeof($adaptation_sets); $i++){
        $timeoffset = 0;
        $timescale = 1;
        $adaptation_set = $adaptation_sets[$i];

        $file_path = str_replace('$AS$', $i, $adaptation_set_error_log_template) . '.txt';
        $opfile = open_file($session_dir . '/Period' .$current_period.'/' . $file_path, 'w');
        
        $segmentAlignment = ($adaptation_set['segmentAlignment']) ? $adaptation_set['segmentAlignment'] : 'false';
        $subsegmentAlignment = ($adaptation_set['subsegmentAlignment']) ? $adaptation_set['subsegmentAlignment'] : 'false';
        $bitstreamSwitching = ($adaptation_set['bitstreamSwitching']) ? $adaptation_set['bitstreamSwitching'] : 'false';

        if ($segmentAlignment == "true" || $subsegmentAlignment == "true" || $bitstreamSwitching == "true"){
            $leafInfo = array();

            $representations = $adaptation_set['Representation'];
            for ($j = 0; $j < sizeof($representations); $j++){
                $timeoffset = 0;
                $timescale = 1;
                $representation = $representations[$j];

                if (!empty($adaptation_set['SegmentTemplate']['timescale']))
                    $timescale = $adaptation_set['SegmentTemplate']['timescale'];

                if (!empty($adaptation_set['SegmentTemplate']['presentationTimeOffset']))
                    $timeoffset = $adaptation_set['SegmentTemplate']['presentationTimeOffset'];

                if (!empty($representation['SegmentTemplate']['timescale']))
                    $timescale = $representation['SegmentTemplate']['timescale'];

                if (!empty($representation['SegmentTemplate']['presentationTimeOffset']))
                    $timeoffset = $representation['SegmentTemplate']['presentationTimeOffset'];

                if (!empty($representation['presentationTimeOffset']))
                    $timeoffset = $representation['presentationTimeOffset'];

                $offsetmod = $timeoffset / $timescale;
                $leafInfo[$j] = loadLeafInfoFile('Period' . $current_period . '/' . str_replace(array('$AS$', '$R$'), array($i, $j), $reprsentation_info_log_template) . '.txt', $offsetmod);
                $leafInfo[$j]['id'] = $representation['id'];
            }

            for ($j = 0; $j < sizeof($representations)-1; $j++){
                for ($k = $j + 1; $k < sizeof($representations); $k++){
                    checkAlignment($leafInfo[$j], $leafInfo[$k], $opfile, $segmentAlignment, $subsegmentAlignment, $bitstreamSwitching);
                }
            }
        }

        close_file($opfile);
        $temp_string = str_replace('$Template$', explode('.', $file_path)[0], $string_info);
        file_put_contents($session_dir . '/Period' .$current_period.'/' . explode('.', $file_path)[0] . '.html', $temp_string);
        print_console($session_dir . '/Period' .$current_period.'/' . $file_path, "Period " . ($current_period+1) . " Adaptation Set " . ($i+1) . " DASH Cross Validation Results");
    }
}