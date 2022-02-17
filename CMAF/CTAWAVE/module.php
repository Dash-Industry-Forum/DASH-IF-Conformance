<?php

namespace DASHIF;

class ModuleCTAWAVE extends ModuleInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->name = "CTA-WAVE";

      ///\warn Remove global here
        global $ctawave_conformance;
        if ($ctawave_conformance) {
            $this->enabled = true;
        }
    }

    public function hookBeforeRepresentation()
    {
        return CTAFlags();
    }

    public function hookBeforeAdaptationSet()
    {
        CTASelectionSet();
        return CTAPresentation();
    }

    public function hookPeriod()
    {
        return CTABaselineSpliceChecks();
    }
}

$modules[] = new ModuleCTAWAVE();
