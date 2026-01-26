<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../module.php';
require_once __DIR__ . '/../../Utils/moduleLogger.php';

$mpdLead = '<?xml version="1.0" encoding="UTF-8"?>
<MPD type="dynamic" minimumUpdatePeriod="PT2.0S" availabilityStartTime="2024-07-05T06:50:32" timeShiftBufferDepth="PT1M23.560S" suggestedPresentationDelay="PT5.0S" minBufferTime="PT2.0S" publishTime="2024-07-05T06:52:35" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xsi:schemaLocation="urn:mpeg:DASH:schema:MPD:2011 http://standards.iso.org/ittf/PubliclyAvailableStandards/MPEG-DASH_schema_files/DASH-MPD.xsd" profiles="urn:mpeg:dash:profile:isoff-live:2011" xmlns="urn:mpeg:dash:schema:mpd:2011" >';
$mpdClose = '</MPD>';

$periodNoId = '<Period start="PT0.0S">';
$periodWithId = '<Period id="1" start="PT0.0S">';
$periodClose = '</Period>';


$videoAdaptationSet = '<AdaptationSet group="1" mimeType="video/mp4" width="800" height="600" frameRate="25" segmentAlignment="true" id="0" startWithSAP="1" subsegmentAlignment="true" subsegmentStartsWithSAP="1">
<SegmentTemplate timescale="1000" media="$RepresentationID$/chunk_$Time$.m4s" initialization="$RepresentationID$/init.m4s"><SegmentTimeline>
<S t="60023" d="10000" />
<S d="10000" />
<S d="10000" />
<S d="10000" />
<S d="10000" />
<S d="10000" />
</SegmentTimeline></SegmentTemplate>
<Representation id="0" bandwidth="169920" codecs="avc1.42c01e" /></AdaptationSet>';

$audioAdaptationSet = '<AdaptationSet group="2" mimeType="audio/mp4" segmentAlignment="true" id="1" startWithSAP="1" subsegmentAlignment="true" subsegmentStartsWithSAP="1">
<SegmentTemplate timescale="1000" media="$RepresentationID$/chunk_$Time$.m4s" initialization="$RepresentationID$/init.m4s"><SegmentTimeline>
<S t="60023" d="10000" />
<S d="10000" />
<S d="10000" />
<S d="10000" />
<S d="10000" />
<S d="10000" />
</SegmentTimeline></SegmentTemplate>
<Representation id="1" bandwidth="72312" codecs="mp4a.40.2" audioSamplingRate="44100"> <AudioChannelConfiguration schemeIdUri="urn:mpeg:dash:23003:3:audio_channel_configuration:2011" value="1" /></Representation>
</AdaptationSet>';


$mpdOnly = "${mpdLead}${mpdClose}";

$noPeriodId = "${mpdLead}${periodNoId}${videoAdaptationSet}${audioAdaptationSet}${periodClose}${mpdClose}";
$withPeriodId = "${mpdLead}${periodWithId}${videoAdaptationSet}${audioAdaptationSet}${periodClose}${mpdClose}";


class LiveUpdateTestModule extends DASHIF\ModuleHbbTVDVB
{
    public function __construct()
    {
        parent::__construct();
    }
}

final class HbbTVLiveUpdateTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['logger'] = new DASHIF\ModuleLogger();
        $this->module = new LiveUpdateTestModule();
    }



    public function caseProvider(){
      global $mpdOnly;
      global $noPeriodId, $withPeriodId;
      return [
        [null, null, 'FAIL'],
        [$mpdOnly, $mpdOnly, 'PASS'],
        [$noPeriodId, $noPeriodId, 'FAIL'],
        [$withPeriodId, $withPeriodId, 'PASS']
      ];
    }


    /**
     * @dataProvider caseProvider
     * @large
     */
    public function testMPD($mpdContent1, $mpdContent2, $expectedResult): void
    {
        $mpd1 = new DASHIF\MPDHandler(null);
        $mpd2 = new DASHIF\MPDHandler(null);
        $mpd1->refresh($mpdContent1);
        $mpd2->refresh($mpdContent2);
        $this->module->mpdUpdateConstraints($mpd1, $mpd2);
        $this->assertEquals($expectedResult, $GLOBALS['logger']->asArray()['verdict']);
    }
}
