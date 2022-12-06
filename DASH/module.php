<?php

namespace DASHIF;

class ModuleDASH extends ModuleInterface
{
    public $useLatestXSD;

    public function __construct()
    {
        parent::__construct();
        $this->name = "MPEG-DASH Common";
        $this->enabled = true;
        $this->useLatestXSD = false;
    }

    protected function addCLIArguments()
    {
        global $argumentParser;
        $argumentParser->addOption("dash", "d", "dash", "Enable DASH-IF checking");
        $argumentParser->addOption("latest_xsd", "", "latest_xsd", "Use the latest xsd");
    }

    public function handleArguments()
    {
        global $argumentParser;
        if ($argumentParser->getOption("dash")) {
            $this->enabled = true;
        }
        if ($argumentParser->getOption("latest_xsd")) {
            $this->useLatestXSD = true;
        }
    }
}

$modules[] = new ModuleDASH();
