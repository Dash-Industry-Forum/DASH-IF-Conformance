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

        $this->validateCross();
    }

    private function validateCross()
    {
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

    private function validate9X43(
        $period,
        $adaptationSet,
        $adaptationSetId,
        $infoFileAdaptation,
        $audioPresent,
        $adaptationGroupName
    ) {
        include 'impl/validate9X43.php';
    }
    private function validate9X44(
        $adaptationSet,
        $adaptationSetId,
        $isLowLatency,
        $segmentAccessInfo,
        $infoFileAdaptation,
        $logger
    ) {
        return include 'impl/validate9X44.php';
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

    private function validateDASHProfileCMAF(
        $adaptationSet,
        $adaptationSetId,
        $segmentAccessInfo,
        $infoFileAdaptation,
        $logger
    ) {
        return include 'impl/validateDASHProfileCMAF.php';
    }

    private function validateSegmentTemplate(
        $adaptationSetId,
        $representationId,
        $segmentAccessRepresentation,
        $infoFileAdaptation,
        $logger
    ) {
        include 'impl/validateSegmentTemplate.php';
    }

    private function checkSegment($adaptationSetId, $representationId, $segmentDurations)
    {
        return include 'impl/checkSegment.php';
    }

    private function readInfoFile($adaptationSet, $adaptationSetId)
    {
        return include 'impl/readInfoFile.php';
    }
    private function validateSelfInitializingSegment(
        $adaptationSetId,
        $representationId,
        $segmentAccessRepresentation,
        $infoFileAdaptation,
        $xml,
        $logger
    ) {
        include 'impl/validateSelfInitializingSegment.php';
    }

    private function validateSegmentTimeline(
        $adaptationSet,
        $adaptationSetId,
        $representation,
        $representationId,
        $segmentAccessRepresentation,
        $infoFileAdaptation,
        $logger
    ) {
        include 'impl/validateSegmentTimeline.php';
    }

    private function validateTimingsWithinRepresentation(
        $adaptationSet,
        $adaptationSetId,
        $representationId,
        $infoFileAdaptation,
        $logger
    ) {
        include 'impl/validateTimingsWithinRepresentation.php';
    }

    private function validateEmsg(
        $adaptationSet,
        $adaptationSetId,
        $representationId,
        $infoFileAdaptation
    ) {
        return include 'impl/validateEmsg.php';
    }
}

  $modules[] = new moduleDASHLowLatency();
