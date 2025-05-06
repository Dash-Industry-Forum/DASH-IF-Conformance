<?php

namespace DASHIF;

class ModuleDolby extends ModuleInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->name = "Dolby";
    }

    protected function addCLIArguments()
    {
        global $argumentParser;
        $argumentParser->addOption("dolby", "o", "dolby", "Enable Dolby checking");
    }

    public function handleArguments()
    {
        global $argumentParser;
        if ($argumentParser->getOption("dolby")) {
            $this->enabled = true;
        }
    }

    public function hookRepresentation()
    {
        parent::hookRepresentation();

        global $validatorWrapper, $mpdHandler;

        $thisRepresentation = array(
            $mpdHandler->getSelectedPeriod(),
            $mpdHandler->getSelectedAdaptationSet(),
            $mpdHandler->getSelectedRepresentation()
        );

        $validatorWrapper->analyzeSingle(
            $thisRepresentation,
            $this,
            'validateDolby'
        );
    }

    public function validateDolby($representation)
    {
        return include 'impl/validateDolby.php';
    }

    public function compareTocWithDac4($representation)
    {
        return include 'impl/compareTocWithDac4.php';
    }
}

$modules[] = new ModuleDolby();
