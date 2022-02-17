<?php

namespace DASHIF;

  include_once 'IOP_Initialization.php';

class ModuleDASHInteroperability extends ModuleInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->name = "DASH-IF IOP Conformance";

      ///\warn Remove global here
        global $dashif_conformance;
        if ($dashif_conformance) {
            $this->enabled = true;
        }
    }

    /**
     *  \brief Checks whether 'DASH_IOP' is found in the arguments, and enables this module accordingly
     */
    public function conditionalEnable($args)
    {
        $this->enabled = false;
        foreach ($args as $arg) {
            if ($arg == "DASH_IOP") {
                $this->enabled = true;
            }
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
