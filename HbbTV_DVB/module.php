<?php

namespace DASHIF;

class ModuleHbbTVDVB extends ModuleInterface
{
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
    }

    protected function addCLIArguments()
    {
        global $argumentParser;
        $argumentParser->addOption("hbbtv", "H", "hbbtv", "Enable HBBTV checking");
        $argumentParser->addOption("dvb", "D", "dvb", "Enable DVB checking");
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
        }
    }

    public function hookBeforeMPD()
    {
        parent::hookBeforeMPD();
        $this->moveScripts();
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

    private function dvbSubtitleChecks($adaptation, $representations, $i)
    {
        include 'impl/dvbSubtitleChecks.php';
    }

    private function dvbMpdAnchorCheck()
    {
        include 'impl/dvbMpdAnchorCheck.php';
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
        HbbTV_DVB_flags();
        return is_subtitle();
    }

    public function hookRepresentation()
    {
        return RepresentationValidation_HbbTV_DVB();
    }

    public function hookBeforeAdaptationSet()
    {
        return add_remove_images('REMOVE');
    }

    public function hookAdaptationSet()
    {
        return CrossValidation_HbbTV_DVB();
    }

    private function moveScripts()
    {
      /*
        global $session_dir, $bitrate_script, $segment_duration_script;

        copy(dirname(__FILE__) . "/$bitrate_script", "$session_dir/$bitrate_script");
        chmod("$session_dir/$bitrate_script", 0777);
        copy(dirname(__FILE__) . "/$segment_duration_script", "$session_dir/$segment_duration_script");
        chmod("$session_dir/$segment_duration_script", 0777);
       */
    }
}

$modules[] = new ModuleHbbTVDVB();
