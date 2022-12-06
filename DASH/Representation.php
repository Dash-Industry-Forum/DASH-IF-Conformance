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

function construct_flags($period, $adaptation_set, $representation)
{
    global $session, $mpdHandler, $profiles;
    global $modules;



    ## @minimumBufferTime
    $timeSeconds = (string) DASHIF\Utility\timeParsing($mpdHandler->getFeatures()['minBufferTime']);
    $processArguments = ' -minbuffertime ' . $timeSeconds;

    ## @bandwidth
    $bandwidth = $representation['bandwidth'];
    $processArguments .= ' -bandwidth ' . $bandwidth;

    ## @width
    $width = ($adaptation_set['width'] == null) ? $representation['width'] : $adaptation_set['width'];
    $width = (!$width) ? 0 : $width;
    $processArguments .= ' -width ' . $width;

    ## @height
    $height = ($adaptation_set['height'] == null) ? $representation['height'] : $adaptation_set['height'];
    $height = (!$height) ? 0 : $height;
    $processArguments .= ' -height ' . $height;

    ## @sar
    $sar = ($adaptation_set['sar'] == null) ? $representation['sar'] : $adaptation_set['sar'];
    if ($sar != null) {
        $sar_x_y = explode(':', $sar);
        $processArguments .= ' -sarx ' . $sar_x_y[0] . ' -sary ' . $sar_x_y[1];
    }

    ## dynamic @type
    $dynamic = ($mpdHandler->getFeatures()['type'] == 'dynamic') ? ' -dynamic' : '';
    $processArguments .= $dynamic;

    ## @startWithSAP
    $startWithSAP = ($adaptation_set['startWithSAP'] == null) ?
      $representation['startWithSAP'] : $adaptation_set['startWithSAP'];
    if ($startWithSAP != null) {
        $processArguments .= ' -startwithsap ' . $startWithSAP;
    }

    ## @profiles
    $ondemand = array(
      'urn:mpeg:dash:profile:isoff-on-demand:2011',
      'urn:mpeg:dash:profile:isoff-ext-on-demand:2014',
      'http://dashif.org/guidelines/dash-if-ondemand',
      'urn:dvb:dash:profile:dvb-dash:isoff-ext-on-demand:2014'
    );
    $live = array(
      'urn:mpeg:dash:profile:isoff-live:2011',
      'urn:mpeg:dash:profile:isoff-ext-live:2014',
      'urn:dvb:dash:profile:dvb-dash:isoff-ext-live:2014'
    );
    $main = array('urn:mpeg:dash:profile:isoff-main:2011');
    $dash264 = array('http://dashif.org/guidelines/dash264');
    $dashif_ondemand = array('http://dashif.org/guidelines/dash-if-ondemand');
    $dashif_mixed_ondemand = array('http://dashif.org/guidelines/dash-if-mixed');

    $rep_profiles = explode(',', $profiles[$mpdHandler->getSelectedPeriod()]
                                          [$mpdHandler->getSelectedAdaptationSet()]
                                          [$mpdHandler->getSelectedRepresentation()]);
    foreach ($rep_profiles as $rep_profile) {
        if (strpos($rep_profile, ' ') !== false) {
            $rep_profile = str_replace(' ', '', $rep_profile);
        }

        if (in_array($rep_profile, $ondemand)) {
            $processArguments .= ' -isoondemand';
        }
        if (in_array($rep_profile, $live)) {
            $processArguments .= ' -isolive';
        }
        if (in_array($rep_profile, $main)) {
            $processArguments .= ' -isomain';
        }
        if (in_array($rep_profile, $dash264) !== false) {
            $processArguments .= ' -dash264base';
        }
        if (in_array($rep_profile, $dashif_ondemand) !== false) {
            $processArguments .= ' -dashifondemand';
        }
        if (in_array($rep_profile, $dashif_mixed_ondemand) !== false) {
            $processArguments .= ' -dashifmixed';
        }
        foreach ($modules as $module) {
            if ($module->name == "DASH-IF Common") {
                if ($module->isEnabled()) {
                    $processArguments .= ' -dash264base';
                }
                break;
            }
        }
    }

    ## ContentProtection
    $content_protection_len = 0;
    if ($adaptation_set['ContentProtection']) {
        $content_protection_len = sizeof($adaptation_set['ContentProtection']);
    } elseif ($representation['ContentProtection']) {
        $content_protection_len = sizeof($representation['ContentProtection']);
    }
    if ($content_protection_len > 0 && strpos($processArguments, 'dash264base') !== false) {
        $processArguments .= ' -dash264enc';
    }

    ## @codecs
    $codecs = ($adaptation_set['codecs'] == null) ? $representation['codecs'] : $adaptation_set['codecs'];
    if ($codecs == null) {
        $codecs = 0;
    }
    $processArguments .= ' -codecs ' . $codecs;

    ## @indexRange
    $indexRange = find_attribute(array($period, $adaptation_set, $representation), 'indexRange');
    if ($indexRange != null) {
        $processArguments .= ' -indexrange ' . $indexRange;
    }

    ## AudoChannelConfiguration
    $audioChannelConfiguration = (empty($adaptation_set['AudioChannelConfiguration'])) ?
      $representation['AudioChannelConfiguration'][0]['value'] :
      $adaptation_set['AudioChannelConfiguration'][0]['value'];
    $audioChannelConfiguration = ($audioChannelConfiguration == null) ? 0 : $audioChannelConfiguration;
    $processArguments .= ' -audiochvalue ' . $audioChannelConfiguration;

    ## RepresentationIndex
    $representationIndex = find_attribute(array($period, $adaptation_set, $representation), 'RepresentationIndex');
    if ($representationIndex != null) {
        $processArguments = $processArguments . " -repIndex";
    }

    ## ContentProtection
    $content_protections = $adaptation_set['ContentProtection'];
    if (!empty($content_protections)) {
        foreach ($content_protections as $content_protection) {
            if ($content_protection['cenc:default_KID']) {
                $default_KID = $content_protection['cenc:default_KID'];
            }

            $cencNS = "urn:mpeg:cenc:2013:pssh";
            $cenc_pssh = $content_protection[$cencNS];
            if (!empty($cenc_pssh)) {
                $psshBox[] = $cenc_pssh;
            }

            $ContentProtect_arr = array('default_KID' => $default_KID, 'psshBox' => $psshBox);
        }

        if ($default_KID !== null) {
            $processArguments = $processArguments . " -default_kid ";
            $processArguments = $processArguments . $default_KID;
        }

        $psshCount = sizeof($psshBox);
        if ($psshCount > 0) {
            $processArguments = $processArguments . " -pssh_count ";
            $processArguments = $processArguments . $psshCount;
            for ($i = 0; $i < $psshCount; $i++) {
                $psshBox = $psshBox[$i];
                $processArguments = $processArguments . " -psshbox ";
                $pssh_file_loc = $session->getDir() . "/psshBox" . $i . ".txt";
                $pssh_file = fopen($pssh_file_loc, "w");
                fwrite($pssh_file, $psshBox);
                fclose($pssh_file);
                $processArguments = $processArguments . $pssh_file_loc;
            }
        }
    }

    ## Inband Event Stream for LL
    foreach ($modules as $module) {
        if ($module->name == "DASH-IF Low Latency") {
            if ($module->isEnabled()) {
                $processArguments .= ' -dashifll';
            }
            break;
        }
    }

    return $processArguments;
}

function find_attribute($elements, $attribute)
{
    $return_val = null;
    foreach ($elements as $element) {
        if ($element['SegmentBase'] != null) {
            $temp_attribute = $element['SegmentBase'][0][$attribute];
        } elseif ($element['SegmentTemplate'] != null) {
            $segment_template = $element['SegmentTemplate'][0];
            $segment_base = $segment_template['SegmentBase'][0];
            if ($segment_base != null) {
                $temp_attribute = $segment_template['SegmentBase'][0][$attribute];
            }
        } elseif ($element['SegmentList'] != null) {
            $segment_list = $element['SegmentList'][0];
            $segment_base = $segment_list['SegmentBase'][0];
            if ($segment_base != null) {
                $temp_attribute = $segment_template['SegmentBase'][0][$attribute];
            }
        }

        if ($temp_attribute != null) {
            $return_val = $temp_attribute;
        }
    }

    return $return_val;
}
