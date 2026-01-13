<?php

namespace DASHIF;

require_once __DIR__ . '/../Utils/moduleInterface.php';

class ModuleCTAWAVE extends ModuleInterface
{

    public function __construct()
    {
        parent::__construct();
        $this->name = "CTA-WAVE";


    protected function addCLIArguments()
    {
        global $argumentParser;
        if ($argumentParser) {
            $argumentParser->addOption("ctawave", "w", "ctawave", "Enable CTAWAVE checking");
        }
    }

    public function handleArguments()
    {
        global $argumentParser;
        if ($argumentParser->getOption("ctawave")) {
            $this->enabled = true;
        }
    }

    public function detectFromManifest()
    {
        global $mpdHandler;
        $mpdProfiles = $mpdHandler->getDOM()->getAttribute('profiles');
        if (strpos($mpdProfiles, 'urn:cta:wave:test-content-media-profile') !== false) {
            $this->enabled = true;
            $this->detected = true;
        }
    }

    public function hookBeforeRepresentation()
    {
        parent::hookBeforeRepresentation();
        global $additional_flags;
        $additional_flags .= ' -ctawave';
    }

    public function hookBeforeAdaptationSet()
    {
        parent::hookBeforeAdaptationSet();
    }

    public function hookPeriod()
    {
        parent::hookPeriod();
    }

    private function checkFragmentOverlapSplicePoint()
    {
        include 'impl/checkFragmentOverlapSplicePoint.php';
    }


}

$modules[] = new ModuleCTAWAVE()m
