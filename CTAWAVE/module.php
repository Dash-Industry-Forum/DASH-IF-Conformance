<?php

namespace DASHIF;

class ModuleCTAWAVE extends ModuleInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->name = "CTA-WAVE";
    }

    protected function addCLIArguments()
    {
        global $argumentParser;
        $argumentParser->addOption("ctawave", "w", "ctawave", "Enable CTAWAVE checking");
    }

    public function handleArguments()
    {
        global $argumentParser;
        if ($argumentParser->getOption("ctawave")) {
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
