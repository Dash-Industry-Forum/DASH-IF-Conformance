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

require './moduleInterface.php';
include './moduleLogger.php';

include 'Session.php';         //#Session Functions, No Direct Executable Code
//#Document loading functions, mostly xml. Some assertion options and error initialization
include 'Load.php';
include 'FileOperations.php';  //#Filesystem and XML checking functions. No Direct Executable Code.
include 'VisitorCounter.php';  //#Various Session-based functions. No Direct Executable Code.
//#Global variables. Direct evaluation of post/session vars to define conditionals,
//#conditional extra includes for module initialization
include 'GlobalVariables.php';
include 'PrettyPrint.php';     //#Pretty printing functions for terminal output. No Direct Executable Code.
include 'segmentDownload.php'; //#Very large function for downloading data. No Direct Executable Code.
include 'segmentValidation.php'; //#Segment validation functions. No Direct Executable Code.
//#Dolby validation functions. Attempt at use of objects. No Direct Executable Code.
include 'DolbySegmentValidation.php';


include 'MPDUtility.php';


include '../DASH/module.php';
include '../CMAF/module.php';
include '../CTAWAVE/module.php';
include '../HbbTV_DVB/module.php';
include '../DASH/LowLatency/module.php';
include '../DASH/IOP/module.php';

$argumentParser->parseAll();

$mpd_url = $argumentParser->getPositionalArgument("url");
$logger->setSource($mpd_url);


$mpd_validation_only = false;




//#Cross repo includes. These should be made optional at the very least.
include '../DASH/processMPD.php';
include '../DASH/MPDFeatures.php';
include '../DASH/MPDValidation.php';
include '../DASH/MPDInfo.php';
include '../DASH/SchematronIssuesAnalyzer.php';
include '../DASH/crossValidation.php';
include '../DASH/Representation.php';
include '../DASH/SegmentURLs.php';
include '../HLS/HLSProcessing.php';
include '../Conformance-Frontend/Featurelist.php';
include '../Conformance-Frontend/TabulateResults.php';


set_time_limit(0);
ini_set("log_errors", 1);
ini_set("error_log", "myphp-error.log");

$session_id = (array_key_exists('sessionid', $_POST) ? json_decode($_POST['sessionid']) : "test");
#session_name($session_id);
#session_start();
#error_log("session_start:" . session_name());

#session_create();

update_visitor_counter();


if (!$hls_manifest) {
    process_MPD();
} else {
    processHLS();
}

  echo($logger->asJSON() . "\n");
?>
