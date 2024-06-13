<?php

namespace DASHIF;

class ModuleHbbTVDVB extends ModuleInterface
{
    public $DVBVersion;
    private $periodCount;
    private $hohSubtitleLanguages;
    private $videoBandwidth;
    private $audioBandwidth;
    private $subtitleBandwidth;
    private $associativity;
    private $hasJPGraph;
    private $hasJPBarGraph;

    public function __construct()
    {
        parent::__construct();
        $this->name = "HbbTV_DVB";

        $this->periodCount = 0;
        $this->hohSubtitleLanguages = array();
        $this->videoBandwidth = array();
        $this->audioBandwidth = array();
        $this->subtitleBandwidth = array();
        $this->associativity = array();

        $this->HbbTvEnabled = false;
        $this->DVBEnabled = false;

        $this->hasJPGraph = include_once __DIR__ . '/../external/jpgraph/src/jpgraph.php';
        $this->hasJPBarGraph = include_once __DIR__ . '/../external/jpgraph/src/jpgraph_bar.php';
    }
    public function isEnabled()
    {
        return $this->HbbTvEnabled || $this->DVBEnabled;
    }
    public function isDVBEnabled()
    {
        return $this->DVBEnabled;
    }
    public function detectFromManifest()
    {
        global $mpdHandler;
        $mpdProfiles = $mpdHandler->getDOM()->getAttribute('profiles');
        if (strpos($mpdProfiles, 'urn:hbbtv:dash:profile:isoff-live:2012') !== false) {
            $this->HbbTvEnabled = true;
            $this->detected = true;
        }
        if (
            strpos($mpdProfiles, 'urn:dvb:dash:profile:dvb-dash:2014') !== false ||
            strpos($mpdProfiles, 'urn:dvb:dash:profile:dvb-dash:isoff-ext-live:2014') !== false ||
            strpos($mpdProfiles, 'urn:dvb:dash:profile:dvb-dash:isoff-ext-on-demand:2014') !== false
        ) {
            $this->DVBEnabled = true;
            $this->detected = true;
            if (!$this->DVBVersion) {
                $this->DVBVersion = "2018";
            }
        }
    }

    public function setEnabled($newVal)
    {
        $this->HbbTvEnabled = $newVal;
        $this->DVBEnabled = $newVal;
    }

    protected function addCLIArguments()
    {
        global $argumentParser;
        $argumentParser->addOption("hbbtv", "H", "hbbtv", "Enable HBBTV checking");
        $argumentParser->addOption("dvb", "D", "dvb", "Enable DVB checking (2018 xsd)");
        $argumentParser->addOption("dvb2019", "", "dvb_2019", "Enable DVB checking (2019 xsd)");
    }

    public function handleArguments()
    {
        global $argumentParser;
        if ($argumentParser->getOption("hbbtv")) {
            $this->enabled = true;
            $this->HbbTvEnabled = true;
        }
        if ($argumentParser->getOption("dvb")) {
            $this->enabled = true;
            $this->DVBEnabled = true;
            $this->DVBVersion = "2018";
        }
        if ($argumentParser->getOption("dvb2019")) {
            $this->enabled = true;
            $this->DVBEnabled = true;
            $this->DVBVersion = "2019";
        }
    }

    public function hookLiveMpd($mpd, $nextMpd)
    {
        parent::hookLiveMpd($mpd, $nextMpd);
        $this->mpdUpdateConstraints($mpd, $nextMpd);
    }

    private function mpdUpdateConstraints($mpd, $nextMpd)
    {
        include 'impl/mpdUpdateConstraints.php';
    }

    private function mpdUpdateConstraintsWithinPeriod(
        $mpd,
        $nextMpd,
        $periodIndex,
        $nextPeriodIndex
    ) {
        include 'impl/mpdUpdateConstraintsWithinPeriod.php';
    }
    private function mpdUpdateConstraintsWithinAdaptationSet(
        $mpd,
        $nextMpd,
        $periodIndex,
        $nextPeriodIndex,
        $adaptationIndex,
        $nextAdaptationIndex
    ) {
        include 'impl/mpdUpdateConstraintsWithinAdaptationSet.php';
    }


    public function hookBeforeMPD()
    {
        parent::hookBeforeMPD();
        include_once 'impl/beforeMPD.php';
    }

    public function hookMPD()
    {
        parent::hookMPD();
        $this->profileSpecificMediaTypesReport();
        $this->crossProfileCheck();

        if ($this->DVBEnabled) {
            $this->dvbMPDValidator();
            $this->dvbMpdAnchorCheck();
        }

        if ($this->HbbTvEnabled) {
            $this->hbbMPDValidator();
        }
    }

    private function profileSpecificMediaTypesReport()
    {
        include 'impl/profileSpecificMediaTypesReport.php';
    }

    private function crossProfileCheck()
    {
        include 'impl/crossProfileCheck.php';
    }

    private function dvbMPDValidator()
    {
        include 'impl/dvbMPDValidator.php';
    }

    private function dvbEventChecks($eventStream)
    {
        include 'impl/dvbEventChecks.php';
    }

    private function dvbVideoChecks($adaptation, $representations, $i, $videoComponentFound)
    {
        include 'impl/dvbVideoChecks.php';
    }

    private function dvbAudioChecks($adaptation, $representations, $i, $audioComponentFound)
    {
        include 'impl/dvbAudioChecks.php';
    }

    private function mpdTimingInfo()
    {
        return include 'impl/mpdTimingInfo.php';
    }

    private function dvbSubtitleChecks($adaptation, $representations, $i)
    {
        include 'impl/dvbSubtitleChecks.php';
    }

    private function dvbMpdAnchorCheck()
    {
        include 'impl/dvbMpdAnchorCheck.php';
    }

    private function computeTimerange($timeRange)
    {
        return include 'impl/MPDUtility/computeTimerange.php';
    }

    private function dvbContentProtection($adaptation, $representations, $i, $cenc)
    {
        include 'impl/dvbContentProtection.php';
    }

    private function streamBandwidthCheck()
    {
        include 'impl/streamBandwidthCheck.php';
    }

    private function fallbackOperationChecks($audioAdaptations)
    {
        include 'impl/fallbackOperationChecks.php';
    }

    private function tlsBitrateCheck()
    {
        include 'impl/tlsBitrateCheck.php';
    }

    private function checkDVBValidRelative()
    {
        include 'impl/checkDVBValidRelative.php';
    }

    private function dvbMetricReporting()
    {
        include 'impl/dvbMetricReporting.php';
    }

    private function checkAssetIdentifiers($assets1, $assets2)
    {
        return include 'impl/checkAssetIdentifiers.php';
    }

    private function dvbAssociatedAdaptationSetsCheck()
    {
        include 'impl/dvbAssociatedAdaptationSetsCheck.php';
    }

    private function checkAdaptationSetIds($periodId1, $periodId2)
    {
        include 'impl/checkAdaptationSetIds.php';
    }

    private function hbbMPDValidator()
    {
        include 'impl/hbbMPDValidator.php';
    }

    private function hbbVideoRepresentationChecks($adaptation, $adaptationNumber, $periodNumber)
    {
        include 'impl/hbbVideoRepresentationChecks.php';
    }

    private function hbbAudioRepresentationChecks($adaptation, $adaptationNumber, $periodNumber)
    {
        include 'impl/hbbAudioRepresentationChecks.php';
    }

    private function hbbAudioChannelCheck(
        $channelConfiguration,
        $codecs,
        $representationNumber,
        $adaptationNumber,
        $periodNumber
    ) {
        include 'impl/hbbAudioChannelCheck.php';
    }


    private function checkValidProbability($val)
    {
        if ($val == '') {
            return true;
        }
        if ((string) (int) $val !== $val) {
            return false;
        }
        if ($val > 1000) {
            return false;
        }
        if ($val < 1) {
            return false;
        }
        return true;
    }

    private function avcCodecValidForDVB($codec)
    {
        include 'impl/avcCodecValidForDVB.php';
    }

    public function hookBeforeRepresentation()
    {
        parent::hookBeforeRepresentation();
        $this->flags();
        return $this->isSubtitle();
    }

    public function hookRepresentation()
    {
        parent::hookRepresentation();
        return $this->representationValidation();
    }

    public function hookBeforeAdaptationSet()
    {
        parent::hookBeforeAdaptationSet();
        $this->addOrRemoveImages('REMOVE');
    }

    public function hookAdaptationSet()
    {
        parent::hookAdaptationSet();
        $this->crossValidation();
    }

    private function representationValidation()
    {
        return include 'impl/representationValidation.php';
    }

    private function addOrRemoveImages($request)
    {
        include 'impl/addOrRemoveImages.php';
    }

    private function flags()
    {
        include 'impl/flags.php';
    }

    private function isSubtitle()
    {
        return include 'impl/isSubtitle.php';
    }

    private function commonDVBValidation($xmlRepresentation, $mediaTypes)
    {
        include 'impl/commonDVBValidation.php';
    }

    private function commonHbbTVValidation($xmlRepresentation)
    {
        include 'impl/commonHbbTVValidation.php';
    }

    private function resolutionCheck($adaptation, $representation)
    {
        return include 'impl/resolutionCheck.php';
    }

    private function segmentTimingCommon($xmlRepresentation)
    {
        include 'impl/segmentTimingCommon.php';
    }

    private function bitrateReport($xmlRepresentation)
    {
        return include 'impl/bitrateReport.php';
    }

    private function segmentDurationChecks()
    {
        return include 'impl/segmentDurationChecks.php';
    }

    private function segmentToPeriodDurationCheck($xmlRepresentation)
    {
        return include 'impl/segmentToPeriodDurationCheck.php';
    }

    private function crossValidation()
    {
        include 'impl/crossValidation.php';
    }

    private function crossValidationDVB($xmlDom1, $xmlDom2, $adaptationIndex, $xmlIndex1, $xmldIndex2)
    {
        include 'impl/crossvalidationDVB.php';
    }

    private function crossValidationDVBAudio($xmlDom1, $xmlDom2, $adaptationIndex, $xmlIndex1, $xmldIndex2)
    {
        include 'impl/crossvalidationDVBAudio.php';
    }

    private function crossValidationDVBVideo($xmlDom1, $xmlDom2, $adaptationIndex, $xmlIndex1, $xmldIndex2)
    {
        include 'impl/crossvalidationDVBVideo.php';
    }

    private function crossValidationHbbTV($xmlDom1, $xmlDom2, $adaptationIndex, $xmlIndex1, $xmldIndex2)
    {
        include 'impl/crossvalidationHbbTV.php';
    }

    private function initializationSegmentCommonCheck($files)
    {
        include 'impl/inititializationSegmentCommonCheck.php';
    }

    private function contentProtectionReport()
    {
        include 'impl/contentProtectionReport.php';
    }

    private function dvbPeriodContinousAdaptationSetsCheck()
    {
        include 'impl/dvbPeriodContinousAdaptationSetsCheck.php';
    }

    private function segmentTimingInfo($xmlRepresentation)
    {
        return include 'impl/segmentTimingInfo.php';
    }
}

$modules[] = new ModuleHbbTVDVB();
