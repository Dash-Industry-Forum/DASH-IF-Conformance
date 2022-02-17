<?php

namespace DASHIF;

class ModuleCMAF extends ModuleInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->name = "CMAF";

      ///\warn Remove global here
        global $cmaf_conformance;
        if ($cmaf_conformance) {
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
