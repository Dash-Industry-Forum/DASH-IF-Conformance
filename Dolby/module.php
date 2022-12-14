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
        $this->validateDolby();
    }

    private function validateDolby()
    {
        include 'impl/validateDolby.php';
    }

    private function compareTocWithDac4($atomInfo)
    {
        include 'impl/compareTocWithDac4.php';
    }

    private function getDac4($atomInfo)
    {
        return include 'impl/getDac4.php';
    }

    private function getToc($atomInfo)
    {
        return include 'impl/getToc.php';
    }
}

$modules[] = new ModuleDolby();
