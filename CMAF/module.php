<?php

namespace DASHIF;

class ModuleCMAF extends ModuleInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->name = "CMAF";
    }

    protected function addCLIArguments()
    {
        global $argumentParser;
        $argumentParser->addOption("cmaf", "c", "cmaf", "Enable CMAF checking");
    }

    public function handleArguments()
    {
        global $argumentParser;
        if ($argumentParser->getOption("cmaf")) {
            $this->enabled = true;
        }
    }



    public function hookBeforeRepresentation()
    {
        return CMAFFlags();
    }

    public function hookRepresentation()
    {
        return checkCMAFTracks();
    }

    public function hookBeforeAdaptationSet()
    {
        return checkSwitchingSets();
    }

    public function hookAdaptationSet()
    {
        return checkPresentation();
    }
}

$modules[] = new ModuleCMAF();
