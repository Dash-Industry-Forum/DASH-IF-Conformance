<?php

namespace DASHIF;

class ModuleCMAF extends ModuleInterface
{
    private $mediaTypes;
    private $mediaProfiles;

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

    public function detectFromManifest()
    {
        global $mpdHandler;
        $mpdProfiles = $mpdHandler->getDOM()->getAttribute('profiles');
        if (strpos($mpdProfiles, 'urn:mpeg:dash:profile:cmaf:2019') !== false) {
            $this->enabled = true;
            $this->detected = true;
        }
    }


    public function hookBeforeRepresentation()
    {
        parent::hookBeforeRepresentation();
        global $additional_flags;
        $additional_flags .= " -cmaf";
    }

    public function hookBeforeAdaptationSet()
    {
        parent::hookBeforeAdaptationSet();
        $this->checkSwitchingSets();
    }

    private function compareRest($xml1, $xml2, $id1, $id2)
    {
        include 'impl/compareRest.php';
    }

    private function checkSwitchingSets()
    {
        include 'impl/checkSwitchingSets.php';
    }
}

$modules[] = new ModuleCMAF();
