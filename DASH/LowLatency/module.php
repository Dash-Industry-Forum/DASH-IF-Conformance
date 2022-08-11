<?php

namespace DASHIF;

class ModuleDASHLowLatency extends ModuleInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->name = "DASH-IF Low Latency";
        $this->maxSegmentDurations = array();
        $this->firstOption = array();
        $this->secondOption = array();
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


    public function hookMPD()
    {
        parent::hookMPD();


        $this->validateProfiles();
        $this->validateServiceDescription();
        $this->validateUTCTiming();
        $this->validateLeapSecondInformation();


        global $session_dir, $mpd_xml_report;
        $mpd_xml = simplexml_load_file($session_dir . '/' . $mpd_xml_report);
        $mpd_xml->dashif_ll = 'true';//NOTE this will be deprecated anyway
        $mpd_xml->asXml($session_dir . '/' . $mpd_xml_report);

        return 'true';
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
        $infoFileAdaptation
    ) {
        return include 'impl/validate9X44.php';
    }

    private function validate9X45(
        $adaptationSet,
        $adaptationSetId,
        $isLowLatency,
        $segmentAccessInfo,
        $infoFileAdaptation
    ) {
        return include 'impl/validate9X45.php';
    }

    private function validate9X45Extended($adaptation_set, $adaptationSetId)
    {
        return include 'impl/validate9X45Extended.php';
    }

    private function validateDASHProfileCMAF(
        $adaptationSet,
        $adaptationSetId,
        $segmentAccessInfo,
        $infoFileAdaptation
    ) {
        return include 'impl/validateDASHProfileCMAF.php';
    }

    private function validateSegmentTemplate(
        $adaptationSetId,
        $representationId,
        $segmentAccessRepresentation,
        $infoFileAdaptation
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
        $xml
    ) {
        include 'impl/validateSelfInitializingSegment.php';
    }

    private function validateSegmentTimeline(
        $adaptationSet,
        $adaptationSetId,
        $representation,
        $representationId,
        $segmentAccessRepresentation,
        $infoFileAdaptation
    ) {
        include 'impl/validateSegmentTimeline.php';
    }

    private function validateTimingsWithinRepresentation(
        $adaptationSet,
        $adaptationSetId,
        $representationId,
        $infoFileAdaptation
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


    private function validateProfiles()
    {
        include 'impl/validateProfiles.php';
    }
    private function validateServiceDescription()
    {
        include 'impl/validateServiceDescription.php';
    }
    private function validateUTCTiming()
    {
        include 'impl/validateUTCTiming.php';
    }
    private function validateLeapSecondInformation()
    {
        include 'impl/validateLeapSecondInformation.php';
    }
}

  $modules[] = new moduleDASHLowLatency();
