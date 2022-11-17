<?php

namespace DASHIF;

class ModuleDASH extends ModuleInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->name = "MPEG-DASH Common";
        $this->enabled = true;
    }

    protected function addCLIArguments()
    {
        global $argumentParser;
        $argumentParser->addOption("dash", "d", "dash", "Enable DASH-IF checking");
    }

    public function handleArguments()
    {
        global $argumentParser;
        if ($argumentParser->getOption("dash")) {
            $this->enabled = true;
        }
    }
}

$modules[] = new ModuleDASH();
