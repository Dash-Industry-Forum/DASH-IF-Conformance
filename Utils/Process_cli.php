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


if (!defined('STDIN')) {
    define('STDIN', fopen('php://stdin', 'rb'));
}
if (!defined('STDOUT')) {
    define('STDOUT', fopen('php://stdout', 'wb'));
}
if (!defined('STDERR')) {
    define('STDERR', fopen('php://stderr', 'wb'));
}

ini_set('memory_limit', '-1');
ini_set('display_errors', 'stderr');
error_reporting(E_ERROR | E_PARSE);

require_once 'Argument.php';
require_once 'ArgumentsParser.php';

$argumentParser = new DASHIF\ArgumentsParser();

include __DIR__ . '/functions.php';
include __DIR__ . '/sessionHandler.php';
require __DIR__ . '/moduleInterface.php';
include __DIR__ . '/moduleLogger.php';

include __DIR__ . '/FileOperations.php';  //#Filesystem and XML checking functions. No Direct Executable Code.
//#Global variables. Direct evaluation of post/session vars to define conditionals,
//#conditional extra includes for module initialization
include __DIR__ . '/GlobalVariables.php';
include __DIR__ . '/segment_validation.php'; //#Segment validation functions. No Direct Executable Code.

include __DIR__ . '/MPDUtility.php';
include __DIR__ . '/MPDHandler.php';


include __DIR__ . '/../DASH/module.php';
include __DIR__ . '/../CMAF/module.php';
include __DIR__ . '/../CTAWAVE/module.php';
include __DIR__ . '/../WaveHLSInterop/module.php';
include __DIR__ . '/../HbbTV_DVB/module.php';
include __DIR__ . '/../DASH/LowLatency/module.php';
include __DIR__ . '/../DASH/IOP/module.php';
include __DIR__ . '/../Dolby/module.php';

require_once __DIR__ . '/ValidatorWrapper.php';

$argumentParser->addOption("segments", "s", "segments", "Enable segment validation");
$argumentParser->addOption(
    "disable_detailed_segment_output",
    "",
    "disable_detailed_segment_output",
    "Disable detailed segment validation output"
);
$argumentParser->addOption("compact", "C", "compact", "Make JSON output compact");
$argumentParser->addOption("silent", "S", "silent", "Do not output JSON to stdout");
$argumentParser->addOption("autodetect", "A", "autodetect", "Try to automatically detect profiles");
$argumentParser->addOption("unlimited", "U", "unlimited", "Unlimit the amount of segments downloaded (default is 5 per representation");

$argumentParser->parseAll();

$mpd_url = $argumentParser->getPositionalArgument("url");
$logger->setSource($mpd_url);


//#Cross repo includes. These should be made optional at the very least.
include __DIR__ . '/../DASH/processMPD.php';
include __DIR__ . '/../DASH/SchematronIssuesAnalyzer.php';
include __DIR__ . '/../DASH/cross_validation.php';
include __DIR__ . '/../DASH/Representation.php';
include __DIR__ . '/../HLS/HLSProcessing.php';
include __DIR__ . '/Featurelist.php';


set_time_limit(0);
ini_set("log_errors", 1);
ini_set("error_log", "myphp-error.log");

//$session_id = (array_key_exists('sessionid', $_POST) ? json_decode($_POST['sessionid']) : "test");
#session_name($session_id);
#session_start();
#error_log("session_start:" . session_name());

#session_create();


$parseSegments = $argumentParser->getOption("segments");
$compactOutput = $argumentParser->getOption("compact");
$autoDetect = $argumentParser->getOption("autodetect");
$detailedSegmentOutput = !$argumentParser->getOption("disable_detailed_segment_output");

global $limit;
$limit = 5;
if ($argumentParser->getOption("unlimited")) {
    $limit = 0;
}

if (substr($mpd_url, -5) == ".m3u8") {
    processHLS();
} else {
    process_MPD($parseSegments, $autoDetect, $detailedSegmentOutput);
}

if (!$argumentParser->getOption("silent")) {
    echo($logger->asJSON($compactOutput) . "\n");
}


global $session;
$session->clearDirectory();
