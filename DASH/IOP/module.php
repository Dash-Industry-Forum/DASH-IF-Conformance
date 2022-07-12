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
        $this->validateSegment();
    }

    private function validateSegment()
    {
        include 'impl/validateSegment.php';
    }

    private function validateSegmentCommon($xml)
    {
        include 'impl/validateSegmentCommon.php';
    }

    private function validateSegmentOnDemand($xml)
    {
        include 'impl/validateSegmentOnDemand.php';
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
