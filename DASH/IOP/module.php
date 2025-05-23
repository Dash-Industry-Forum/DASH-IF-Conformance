<?php

namespace DASHIF;

class ModuleDASHInteroperability extends ModuleInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->name = "DASH-IF IOP Conformance";
    }

    protected function addCLIArguments()
    {
        global $argumentParser;
        $argumentParser->addOption("iop", "i", "iop", "Enable DASH-IF interoperability checking");
    }

    public function handleArguments()
    {
        global $argumentParser;
        if ($argumentParser->getOption("iop")) {
            $this->enabled = true;
        }
    }
    public function detectFromManifest()
    {
        global $mpdHandler;
        $mpdProfiles = $mpdHandler->getDOM()->getAttribute('profiles');
        if (strpos($mpdProfiles, 'http://dashif.org/guidelines/dash') !== false) {
            $this->enabled = true;
            $this->detected = true;
        }
    }

    public function hookMPD()
    {
        parent::hookMPD();
        $this->validateMPD();
    }

    private function validateMPD()
    {
        include 'impl/validateMPD.php';
    }

    private function validateMPDCommon()
    {
        include 'impl/validateMPDCommon.php';
    }

    private function validateMPDOnDemand()
    {
        include 'impl/validateMPDOnDemand.php';
    }
    private function validateMPDLiveOnDemand()
    {
        include 'impl/validateMPDLiveOnDemand.php';
    }

    private function validateMPDMixedOnDemand()
    {
        include 'impl/validateMPDMixedOnDemand.php';
    }

    public function hookRepresentation()
    {
        parent::hookRepresentation();
        global $validatorWrapper, $mpdHandler;
        $thisRepresentation = [
            $mpdHandler->getSelectedPeriod(),
            $mpdHandler->getSelectedAdaptationSet(),
            $mpdHandler->getSelectedRepresentation()
        ];
        $validatorWrapper->analyzeSingle(
            $thisRepresentation,
            $this,
            'validateSegment'
        );
    }

    public function validateSegment($representation)
    {
        return include 'impl/validateSegment.php';
    }

    public function validateSegmentCommon($representation)
    {
        return include 'impl/validateSegmentCommon.php';
    }

    public function validateSegmentOnDemand($representation)
    {
        return include 'impl/validateSegmentOnDemand.php';
    }

    public function hookAdaptationSet()
    {
        parent::hookAdaptationSet();
        $this->validateCross();
    }

    private function validateCross()
    {
        include 'impl/validateCross.php';
    }

    private function validateCrossAvcHevc($adaptationSet, $adaptationSetId)
    {
        include 'impl/validateCrossAvcHevc.php';
    }
}

  $modules[] = new moduleDASHInteroperability();
