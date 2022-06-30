<?php

namespace DASHIF;

class ModuleCMAF extends ModuleInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->name = "CMAF";

        $this->cfhdSwitchingSetFound = 0;
        $this->caadSwitchingSetFound = 0;
        $this->encryptedSwitchingSetFound = 0;
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

    private function checkPresentation()
    {
        include 'impl/checkPresentation.php';
    }

    private function checkCMAFPresentation()
    {
        include 'impl/checkCMAFPresentation.php';
    }

    private function getSelectionSets()
    {
        return include 'impl/getSelectionSets.php';
    }

    private function caacMediaProfileConformance($xml)
    {
        return include 'impl/caacMediaProfileConformance.php';
    }

    private function cfhdMediaProfileConformance($xml)
    {
        return include 'impl/cfhdMediaProfileConformance.php';
    }

    private function checkSelectionSet()
    {
        include 'impl/checkSelectionSet.php';
    }

    private function checkAlignedSwitchingSets()
    {
        include 'impl/checkAlignedSwitchingSets.php';
    }
}

$modules[] = new ModuleCMAF();
