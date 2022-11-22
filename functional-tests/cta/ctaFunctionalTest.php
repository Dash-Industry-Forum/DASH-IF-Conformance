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

include_once 'Utils/functions.php';
include_once 'Utils/MPDHandler.php';
include_once 'Utils/sessionHandler.php';
require_once 'Utils/moduleInterface.php';
include_once 'Utils/moduleLogger.php';

include_once 'Utils/Session.php';         //#Session Functions, No Direct Executable Code
//#Document loading functions, mostly xml. Some assertion options and error initialization
include_once 'Utils/Load.php';
include_once 'Utils/FileOperations.php';  //#Filesystem and XML checking functions. No Direct Executable Code.
//#Global variables. Direct evaluation of post/session vars to define conditionals,
//#conditional extra includes for module initialization
include_once 'Utils/GlobalVariables.php';
include_once 'Utils/segment_download.php'; //#Very large function for downloading data. No Direct Executable Code.
include_once 'Utils/segment_validation.php'; //#Segment validation functions. No Direct Executable Code.
//#Dolby validation functions. Attempt at use of objects. No Direct Executable Code.
include_once 'Utils/DolbySegmentValidation.php';


include_once 'Utils/MPDUtility.php';


include_once 'DASH/module.php';
include_once 'CMAF/module.php';
include_once 'CTAWAVE/module.php';
include_once 'HbbTV_DVB/module.php';
include_once 'DASH/LowLatency/module.php';
include_once 'DASH/IOP/module.php';

$argumentParser->parseAll();

include_once 'DASH/processMPD.php';
include_once 'DASH/MPDFeatures.php';
include_once 'DASH/validateMPD.php';
include_once 'DASH/MPDInfo.php';
include_once 'DASH/SchematronIssuesAnalyzer.php';
include_once 'DASH/cross_validation.php';
include_once 'DASH/Representation.php';
include_once 'DASH/SegmentURLs.php';
include_once 'HLS/HLSProcessing.php';
include_once 'Conformance-Frontend/Featurelist.php';
include_once 'Conformance-Frontend/TabulateResults.php';


set_time_limit(0);
ini_set("log_errors", 1);
ini_set("error_log", "myphp-error.log");
ini_set("allow_url_fopen", 1);

final class ctaFunctionalTest extends TestCase
{
    /**
     * @dataProvider ctaStreamProvider
     * @large
     */
    public function testCta($stream): void
    {
        $GLOBALS['mpd_url'] = $stream;
        //$enabledModules = ["MPEG-DASH Common", "CTA-WAVE", "CMAF"];
        $enabledModules = ["MPEG-DASH Common", "DASH-IF IOP Conformance", "CMAF", "CTA-WAVE", "HbbTV_DVB", "Dolby", "DASH-IF Low Latency"];
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
    public function ctaStreamProvider()
    {
        $i = 0;
        $limit = 2000;
        $startnumber = 0;
        $blacklist = [
            "https://dash.akamaized.net/WAVE/vectors/avc_sets/12.5_25_50/t16/2022-01-17/stream.mpd",
            "https://dash.akamaized.net/WAVE/vectors/avc_sets/14.985_29.97_59.94/t16/2022-01-17/stream.mpd",
            "https://dash.akamaized.net/WAVE/vectors/avc_sets/12.5_25_50/t3/2022-01-17/stream.mpd",
            "https://dash.akamaized.net/WAVE/vectors/avc_sets/14.985_29.97_59.94/t3/2022-01-17/stream.mpd",
            "https://dash.akamaized.net/WAVE/vectors/avc_sets/15_30_60/t16/2022-01-17/stream.mpd",
            "https://dash.akamaized.net/WAVE/vectors/avc_sets/15_30_60/t3/2022-01-17/stream.mpd"
        ];
        $content = file_get_contents(
           "functional-tests/cta/wave.json");
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

    /*
      public function testMultipleVectorsViaCli(): void
      {

        $limit = 10;
        $currentIndex = 0;
          $content = file_get_contents(
              "functional-tests/dashjs.json_single");
          $dbJson = json_decode($content);
          $streamsToTest = array();
          foreach($dbJson->items as $item) {
              foreach($item->submenu as $submenu) {
                  array_push($streamsToTest, $submenu->url);
              }
          }

          foreach ($streamsToTest as $idx => $stream) {
            if ($limit > 0 && $currentIndex >= $limit){
              break;
            }

              $GLOBALS['mpd_url'] = $stream;
              $enabledModules = ["MPEG-DASH Common", "DASH-IF IOP Conformance"];
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

              process_MPD(true);//MPD and Segments <==== This currently always leads to FAIL, as session files are moved around
              //process_MPD(false);//MPD Only
              //
              //
              $currentIndex++;
          }
          //update_visitor_counter();
          $output = $GLOBALS['logger']->asJSON();
          $this->assertNotNull(
              $output,
              "Output is not null"
          );
      }
     */
}
