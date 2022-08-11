#!/usr/bin/php7.4
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

ini_set('memory_limit', '-1');
ini_set('display_errors', 'stderr');
error_reporting(E_ERROR | E_PARSE);

require_once 'Argument.php';
require_once 'ArgumentsParser.php';

$argumentParser = new DASHIF\ArgumentsParser();

include __DIR__ . '/sessionHandler.php';
require __DIR__ . '/moduleInterface.php';
include __DIR__ . '/moduleLogger.php';

include __DIR__ . '/Session.php';         //#Session Functions, No Direct Executable Code
//#Document loading functions, mostly xml. Some assertion options and error initialization
include __DIR__ . '/Load.php';
include __DIR__ . '/FileOperations.php';  //#Filesystem and XML checking functions. No Direct Executable Code.
include __DIR__ . '/VisitorCounter.php';  //#Various Session-based functions. No Direct Executable Code.
//#Global variables. Direct evaluation of post/session vars to define conditionals,
//#conditional extra includes for module initialization
include __DIR__ . '/GlobalVariables.php';
include __DIR__ . '/PrettyPrint.php';     //#Pretty printing functions for terminal output. No Direct Executable Code.
include __DIR__ . '/segmentDownload.php'; //#Very large function for downloading data. No Direct Executable Code.
include __DIR__ . '/segmentValidation.php'; //#Segment validation functions. No Direct Executable Code.
//#Dolby validation functions. Attempt at use of objects. No Direct Executable Code.
include __DIR__ . '/DolbySegmentValidation.php';


include __DIR__ . '/MPDUtility.php';


include __DIR__ . '/../DASH/module.php';
include __DIR__ . '/../CMAF/module.php';
include __DIR__ . '/../CTAWAVE/module.php';
include __DIR__ . '/../HbbTV_DVB/module.php';
include __DIR__ . '/../DASH/LowLatency/module.php';
include __DIR__ . '/../DASH/IOP/module.php';

$argumentParser->parseAll();

include __DIR__ . '/../DASH/processMPD.php';
include __DIR__ . '/../DASH/MPDFeatures.php';
include __DIR__ . '/../DASH/validateMPD.php';
include __DIR__ . '/../DASH/MPDInfo.php';
include __DIR__ . '/../DASH/SchematronIssuesAnalyzer.php';
include __DIR__ . '/../DASH/crossValidation.php';
include __DIR__ . '/../DASH/Representation.php';
include __DIR__ . '/../DASH/SegmentURLs.php';
include __DIR__ . '/../HLS/HLSProcessing.php';
include __DIR__ . '/../Conformance-Frontend/Featurelist.php';
include __DIR__ . '/../Conformance-Frontend/TabulateResults.php';


set_time_limit(0);
ini_set("log_errors", 1);
ini_set("error_log", "myphp-error.log");


$streamsToTest = array(
  array(
    "id" => "Sintel_001",
    "url" => "http://localhost:8000/3a/fraunhofer/aac-lc_stereo_without_video/Sintel/sintel_audio_only_aaclc_stereo_sidx.mpd",
    "modules" => array("MPEG-DASH Common", "CTA-WAVE")
  ),
  array(
    "url" => "http://localhost:8000/3a/fraunhofer/aac-lc_stereo_without_video/ElephantsDream/elephants_dream_audio_only_aaclc_stereo_sidx.mpd",
    "modules" => array("!CMAF", "CTA-WAVE")
  ),
  "http://localhost:8000/3a/fraunhofer/heaac_stereo_without_video/Sintel/sintel_audio_only_heaac_stereo_sidx.mpd",
  "http://localhost:8000/3a/fraunhofer/heaac_stereo_without_video/ElephantsDream/elephants_dream_audio_only_heaac_stereo_sidx.mpd",
  "http://localhost:8000/3a/fraunhofer/heaacv2_stereo_without_video/Sintel/sintel_audio_only_heaacv2_stereo_sidx.mpd",
  "http://localhost:8000/3a/fraunhofer/heaacv2_stereo_without_video/ElephantsDream/elephants_dream_audio_only_heaacv2_stereo_sidx.mpd"
);

$commandLineEnabledModules = [];
foreach ($modules as &$module) {
    if ($module->isEnabled()) {
      $commandLineEnabledModules[] = $module->name;
    }
}

foreach ($streamsToTest as $idx => $stream) {
    
    $mpd_url = $stream;
    $enabledModules = [];
    $id = null;

    if (is_array($mpd_url)){
      $enabledModules = $mpd_url["modules"];
      $id = $mpd_url["id"];
      $mpd_url = $mpd_url["url"];
    }

    $logger->reset($id);
    $logger->setSource($mpd_url);

    fwrite(STDERR, "Going to parse stream $mpd_url\n");
    fwrite(STDERR, "Enabled modules: ");
    foreach ($modules as &$module) {
      $inCommon = in_array($module->name, $commandLineEnabledModules);
      $inSpecific = in_array($module->name, $enabledModules);
      $negateInCommon = in_array("!$module->name", $enabledModules);

      $module->setEnabled(($inCommon && !$negateInCommon) || $inSpecific);
        if ($module->isEnabled()) {
            fwrite(STDERR, "$module->name, ");
        }
    }
    fwrite(STDERR, "\n");

    //process_MPD(true);//MPD and Segments <==== This currently always leads to FAIL, as session files are moved around
    process_MPD(false);//MPD Only
}
?>
