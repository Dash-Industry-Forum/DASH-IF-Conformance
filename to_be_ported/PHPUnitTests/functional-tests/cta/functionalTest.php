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
include 'Utils/FileOperations.php';  //#Filesystem and XML checking functions. No Direct Executable Code.
//#Global variables. Direct evaluation of post/session vars to define conditionals,
//#conditional extra includes for module initialization
include 'Utils/GlobalVariables.php';
include 'Utils/segment_validation.php'; //#Segment validation functions. No Direct Executable Code.
include 'Utils/MPDUtility.php';
include 'Utils/MPDHandler.php';
include 'Utils/functions.php';
include 'Utils/Featurelist.php';

include 'DASH/module.php';
include 'CMAF/module.php';
include 'CTAWAVE/module.php';
include 'HbbTV_DVB/module.php';
include 'DASH/LowLatency/module.php';
include 'DASH/IOP/module.php';

require_once 'Utils/ValidatorWrapper.php';

$argumentParser->parseAll();

include 'DASH/processMPD.php';
include 'DASH/SchematronIssuesAnalyzer.php';
include 'DASH/cross_validation.php';
include 'DASH/Representation.php';
include 'HLS/HLSProcessing.php';


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
        $enabledModules = ["MPEG-DASH Common", "CTA-WAVE", "CMAF"];
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

        process_MPD(true, false, false);//MPD and Segments
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
        $blacklist = [];
        $content = file_get_contents(
            "functional-tests/cta/wave.json"
        );
        $dbJson = json_decode($content);
        $streamsToTest = array();
        foreach ($dbJson as $item) {
            foreach ($item as $subitem) {
                if ($limit && $i >= $limit) {
                    break;
                }
                if (!in_array($subitem->mpdPath, $blacklist) && $i >= $startnumber) {
                    $streamsToTest[] = array($subitem->mpdPath);
                }
                $i++;
            }
        }
        return $streamsToTest;
    }
}
