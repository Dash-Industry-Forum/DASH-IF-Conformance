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
        if ($argumentParser){
          $argumentParser->addOption("hbbtv", "H", "hbbtv", "Enable HBBTV checking");
          $argumentParser->addOption("dvb", "D", "dvb", "Enable DVB checking (2018 xsd)");
          $argumentParser->addOption("dvb2019", "", "dvb_2019", "Enable DVB checking (2019 xsd)");
        }
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

    public function mpdUpdateConstraints($mpd, $nextMpd)
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


    private function mpdTimingInfo()
    {
        return include 'impl/mpdTimingInfo.php';
    }

    public function hookBeforeRepresentation()
    {
        parent::hookBeforeRepresentation();
        $this->flags();
    }

    public function hookRepresentation()
    {
        parent::hookRepresentation();
        return $this->representationValidation();
    }

    private function representationValidation()
    {
        return include 'impl/representationValidation.php';
    }

    private function flags()
    {
        include 'impl/flags.php';
    }

    private function commonDVBValidation($xmlRepresentation, $mediaTypes)
    {
        include 'impl/commonDVBValidation.php';
    }

    private function segmentTimingCommon($xmlRepresentation)
    {
        include 'impl/segmentTimingCommon.php';
    }

    private function segmentDurationChecks()
    {
        return include 'impl/segmentDurationChecks.php';
    }
}

$modules[] = new ModuleHbbTVDVB();
