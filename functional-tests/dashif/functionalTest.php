#!/usr/bin/php7.4
<?php

use PHPUnit\Framework\TestCase;

ini_set('memory_limit', '-1');
ini_set('display_errors', 'stderr');
error_reporting(E_ERROR | E_PARSE);

require_once 'Utils/Argument.php';
require_once 'Utils/ArgumentsParser.php';

global $argumentParser;
$argumentParser = new DASHIF\ArgumentsParser();

include 'Utils/sessionHandler.php';
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
include 'Utils/segment_download.php'; //#Very large function for downloading data. No Direct Executable Code.
include 'Utils/segment_validation.php'; //#Segment validation functions. No Direct Executable Code.
//#Dolby validation functions. Attempt at use of objects. No Direct Executable Code.
include 'Utils/DolbySegmentValidation.php';


include 'Utils/MPDUtility.php';


include 'DASH/module.php';
include 'CMAF/module.php';
include 'CTAWAVE/module.php';
include 'HbbTV_DVB/module.php';
include 'DASH/LowLatency/module.php';
include 'DASH/IOP/module.php';

$argumentParser->parseAll();

include 'DASH/processMPD.php';
include 'DASH/MPDFeatures.php';
include 'DASH/validateMPD.php';
include 'DASH/MPDInfo.php';
include 'DASH/SchematronIssuesAnalyzer.php';
include 'DASH/cross_validation.php';
include 'DASH/Representation.php';
include 'DASH/SegmentURLs.php';
include 'HLS/HLSProcessing.php';
include 'Conformance-Frontend/Featurelist.php';
include 'Conformance-Frontend/TabulateResults.php';


set_time_limit(0);
ini_set("log_errors", 1);
ini_set("error_log", "myphp-error.log");
ini_set("allow_url_fopen", 1);

final class functionalTest extends TestCase
{
    /**
     * @dataProvider streamProvider
     * @large
     */
    public function testThis($stream): void
    {
        $GLOBALS['mpd_url'] = $stream;
        $enabledModules = ["MPEG-DASH Common", "DASH-IF IOP Conformance", "CMAF"];
        $id = null;

        $GLOBALS['logger']->reset($id);
        $GLOBALS['logger']->setSource($GLOBALS['mpd_url']);

        foreach ($GLOBALS['modules'] as $module) {
            $enabled = in_array($module->name, $enabledModules);

            $module->setEnabled($enabled);
            if ($module->isEnabled()) {
                fwrite(STDERR, "$module->name, ");
            }
        }

        fwrite(STDERR, "Going to parse stream " . $GLOBALS['mpd_url'] . "\n");

        process_MPD(true);//MPD and Segments
        //process_MPD(false);//MPD Only
        //
        $this->assertSame(true, true);
    }

    /**
     * @codeCoverageIgnore
     */
    public function streamProvider()
    {
        $i = 0;
        $limit = 2000;
        $startnumber = 0;
        $blacklist = [
            "https://media.axprod.net/TestVectors/v7-MultiDRM-MultiKey/Manifest_1080p.mpd",
            "https://dash.akamaized.net/WAVE/vectors/avc_sets/14.985_29.97_59.94/t3/2022-01-17/stream.mpd",
            "https://livesim.dashif.org/livesim/periods_60/mpdcallback_30/testpic_2s/Manifest.mpd",
            "https://dash.akamaized.net/dash264/TestAdvertising/CMS/Axinom-CMS_HEVC_MultiRes_MultiRate_25fps.mpd",
            "https://dash.akamaized.net/dash264/TestCasesHD/1c/qualcomm/1/MultiRate.mpd",
            "https://dash.akamaized.net/dash264/TestCasesIOP33/adapatationSetSwitching/4/manifest.mpd",
            "https://dash.akamaized.net/dash264/TestCases/9c/qualcomm/1/MultiRate.mpd",
            "https://dash.akamaized.net/dash264/TestCasesHD/2c/qualcomm/1/MultiResMPEG2.mpd",
            "https://livesim.dashif.org/livesim/periods_20/testpic_2s/Manifest.mpd",
            "https://livesim.dashif.org/livesim/testpic_2s/Manifest.mpd#t=posix:now"
        ];
        $content = file_get_contents(
            "functional-tests/dashif/dashjs.json");
        $dbJson = json_decode($content);
        $streamsToTest = array();
        foreach ($dbJson->items as $item) {
            foreach ($item->submenu as $submenu) {
                if ($limit && $i >= $limit) {
                    break;
                }
                if (!in_array($submenu->url, $blacklist) && $i >= $startnumber && strpos($submenu->url, 'media.axprod.net') === false) {
                    $streamsToTest["$item->name::$submenu->name"] = array($submenu->url);
                }
                $i++;
            }
        }
        return $streamsToTest;
    }


}