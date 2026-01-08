<?php

namespace DASHIF;

class ModuleDASHLowLatency extends ModuleInterface
{
    private $maxSegmentDurations;
    private $firstOption;
    private $secondOption;
    private $utcTimingInfo;
    private $serviceDescriptionInfo;

    public function __construct()
    {
        parent::__construct();
        $this->name = "DASH-IF Low Latency";
        $this->maxSegmentDurations = array();
        $this->firstOption = array();
        $this->secondOption = array();
        $this->utcTimingInfo = array();
        $this->serviceDescriptionInfo = array();
    }

    protected function addCLIArguments()
    {
        global $argumentParser;
        $argumentParser->addOption("lowlatency", "l", "lowlatency", "Enable DASH-IF IOP Low Latency checking");
    }

    public function handleArguments()
    {
        global $argumentParser;
        if ($argumentParser->getOption("lowlatency")) {
            $this->enabled = true;
        }
    }

    public function detectFromManifest()
    {
        global $mpdHandler;
        $mpdProfiles = $mpdHandler->getDOM()->getAttribute('profiles');
        if (strpos($mpdProfiles, 'http://www.dashif.org/guidelines/low-latency-live-v5') !== false) {
            $this->enabled = true;
            $this->detected = true;
        }
    }


    public function hookMPD()
    {
        parent::hookMPD();
    }


    public function hookAdaptationSet()
    {
        parent::hookAdaptationSet();
        /*
    $maxSegmentDurations = array();
    $first_option = array();
    $second_option = array();
    $presentation_times = array();
    $decode_times = array();
         */

        $this->validateAdaptationSets();
    }

    private function validateAdaptationSets()
    {
        include 'impl/validateAdaptationSets.php';
    }

    private function validate9X42($adaptationSet, $adaptationSetId, $isLowLatency)
    {
        include 'impl/validate9X42.php';
    }

    private function validate9X45(
        $adaptationSet,
        $adaptationSetId,
        $isLowLatency,
        $segmentAccessInfo,
        $infoFileAdaptation,
        $logger
    ) {
        return include 'impl/validate9X45.php';
    }

    private function validate9X45Extended($adaptation_set, $adaptationSetId, $logger)
    {
        return include 'impl/validate9X45Extended.php';
    }

    private function readInfoFile($adaptationSet, $adaptationSetId)
    {
        return include 'impl/readInfoFile.php';
    }
}

  $modules[] = new moduleDASHLowLatency();
