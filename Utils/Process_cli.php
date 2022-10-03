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

//unlink("../Conformance-Frontend/temp/mpdreport.txt");

ini_set('memory_limit', '-1');
ini_set('display_errors', 'stderr');
error_reporting(E_ERROR | E_PARSE);

require_once 'Argument.php';
require_once 'ArgumentsParser.php';

$argumentParser = new DASHIF\ArgumentsParser();

include __DIR__ . '/sessionHandler.php';
require __DIR__ . '/moduleInterface.php';
include __DIR__ . '/moduleLogger.php';
include __DIR__ . '/MPDHandler.php';

include __DIR__ . '/Session.php';         //#Session Functions, No Direct Executable Code
//#Document loading functions, mostly xml. Some assertion options and error initialization
include __DIR__ . '/Load.php';
include __DIR__ . '/FileOperations.php';  //#Filesystem and XML checking functions. No Direct Executable Code.
include __DIR__ . '/VisitorCounter.php';  //#Various Session-based functions. No Direct Executable Code.
//#Global variables. Direct evaluation of post/session vars to define conditionals,
//#conditional extra includes for module initialization
include __DIR__ . '/GlobalVariables.php';
include __DIR__ . '/PrettyPrint.php';     //#Pretty printing functions for terminal output. No Direct Executable Code.
include __DIR__ . '/segment_download.php'; //#Very large function for downloading data. No Direct Executable Code.
include __DIR__ . '/segment_validation.php'; //#Segment validation functions. No Direct Executable Code.

include __DIR__ . '/MPDUtility.php';


include __DIR__ . '/../DASH/module.php';
include __DIR__ . '/../CMAF/module.php';
include __DIR__ . '/../CTAWAVE/module.php';
include __DIR__ . '/../HbbTV_DVB/module.php';
include __DIR__ . '/../DASH/LowLatency/module.php';
include __DIR__ . '/../DASH/IOP/module.php';
include __DIR__ . '/../Dolby/module.php';



$argumentParser->addOption("segments", "s", "segments", "Enable segment validation");

$argumentParser->parseAll();

$mpd_url = $argumentParser->getPositionalArgument("url");
$logger->setSource($mpd_url);


//#Cross repo includes. These should be made optional at the very least.
include __DIR__ . '/../DASH/processMPD.php';
include __DIR__ . '/../DASH/MPDFeatures.php';
include __DIR__ . '/../DASH/validateMPD.php';
include __DIR__ . '/../DASH/MPDInfo.php';
include __DIR__ . '/../DASH/SchematronIssuesAnalyzer.php';
include __DIR__ . '/../DASH/cross_validation.php';
include __DIR__ . '/../DASH/Representation.php';
include __DIR__ . '/../DASH/SegmentURLs.php';
include __DIR__ . '/../HLS/HLSProcessing.php';
include __DIR__ . '/../Conformance-Frontend/Featurelist.php';
include __DIR__ . '/../Conformance-Frontend/TabulateResults.php';


set_time_limit(0);
ini_set("log_errors", 1);
ini_set("error_log", "myphp-error.log");

//$session_id = (array_key_exists('sessionid', $_POST) ? json_decode($_POST['sessionid']) : "test");
#session_name($session_id);
#session_start();
#error_log("session_start:" . session_name());

#session_create();

//update_visitor_counter();

$parseSegments = $argumentParser->getOption("segments");

if (!$hls_manifest) {
    process_MPD($parseSegments);
} else {
    processHLS();
}

  echo($logger->asJSON() . "\n");
?>
