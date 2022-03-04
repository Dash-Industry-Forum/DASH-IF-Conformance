<?php

namespace DASHIF;

  include_once 'IOP_Initialization.php';

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
        return IOP_ValidateMPD();
    }

    public function hookRepresentation()
    {
        return IOP_ValidateSegment();
    }

    public function hookAdaptationSet()
    {
        return IOP_ValidateCross();
    }
}

  $modules[] = new moduleDASHInteroperability();
