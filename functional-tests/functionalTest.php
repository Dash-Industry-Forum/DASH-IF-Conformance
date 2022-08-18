<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

ini_set('memory_limit', '-1');
ini_set('display_errors', 'stderr');
error_reporting(E_ERROR | E_PARSE);

require_once 'Utils/Argument.php';
require_once 'Utils/ArgumentsParser.php';

global $argumentParser;
$argumentParser = new DASHIF\ArgumentsParser();

require 'Utils/moduleInterface.php';
include 'Utils/moduleLogger.php';


include 'Utils/Session.php';         //#Session Functions, No Direct Executable Code
//#Document loading functions, mostly xml. Some assertion options and error initialization
include 'Utils/Load.php';
include 'Utils/FileOperations.php';  //#Filesystem and XML checking functions. No Direct Executable Code.
include 'Utils/VisitorCounter.php';  //#Various Session-based functions. No Direct Executable Code.
//#Global variables. Direct evaluation of post/session vars to define conditionals,
//#conditional extra includes for module initialization
include 'Utils/GlobalVariables.php';
include 'Utils/PrettyPrint.php';     //#Pretty printing functions for terminal output. No Direct Executable Code.
include 'Utils/SegmentDownload.php'; //#Very large function for downloading data. No Direct Executable Code.
include 'Utils/SegmentValidation.php'; //#Segment validation functions. No Direct Executable Code.
//#Dolby validation functions. Attempt at use of objects. No Direct Executable Code.
include 'Utils/DolbySegmentValidation.php';

include 'Utils/MPDUtility.php';


include 'DASH/module.php';
include 'CMAF/module.php';
include 'CMAF/CTAWAVE/module.php';
include 'HbbTV_DVB/module.php';
include 'DASH/LowLatency/module.php';
include 'DASH/IOP/module.php';

$argumentParser->parseAll();

$mpd_url = "https://cmafref.akamaized.net/cmaf/live-ull/2006350/akambr/out.mpd";
$logger->setSource($mpd_url);


$mpd_validation_only = true;

//#Cross repo includes. These should be made optional at the very least.
include 'DASH/processMPD.php';
include 'DASH/MPDFeatures.php';
include 'DASH/MPDValidation.php';
include 'DASH/MPDInfo.php';
include 'DASH/SchematronIssuesAnalyzer.php';
include 'DASH/CrossValidation.php';
include 'DASH/Representation.php';
include 'DASH/SegmentURLs.php';
include 'HLS/HLSProcessing.php';
include 'Conformance-Frontend/Featurelist.php';
include 'Conformance-Frontend/TabulateResults.php';

$session_id = (array_key_exists('sessionid', $_POST) ? json_decode($_POST['sessionid']) : "test");
session_name($session_id);
session_start();
#error_log("session_start:" . session_name());

session_create();

final class FunctionalTests extends TestCase
{

    public function testSingleVectorViaCli(): void {
        update_visitor_counter();
        process_MPD();
        $output =  $GLOBALS['logger']->asJSON();
        $source = $GLOBALS['logger']->getSource();
        $this->assertNotNull(
            $output,
            "Output is not null"
        );
    }
}