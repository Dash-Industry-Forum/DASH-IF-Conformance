<?php

namespace DASHIF;

include_once 'LowLatency_Initialization.php';


class ModuleDASHLowLatency extends ModuleInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->name = "DASH-IF Low Latency";
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
        return low_latency_validate_cross();
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
