<?php

namespace DASHIF;

include_once './HbbTV_DVB_Initialization.php';

class ModuleHbbTVDVB extends ModuleInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->name = "HbbTV_DVB";
    }

    protected function addCLIArguments()
    {
        global $argumentParser;
        $argumentParser->addOption("hbbtv", "H", "hbbtv", "Enable HBBTV checking");
    }

    public function handleArguments()
    {
        global $argumentParser;
        if ($argumentParser->getOption("hbbtv")) {
            $this->enabled = true;
        }
    }

    public function hookBeforeMPD()
    {
        parent::hookBeforeMPD();
        $this->moveScripts();
        include_once 'impl/beforeMPD.php';
    }

    public function hookMPD()
    {
        parent::hookMPD();
        include_once 'impl/profileSpecificMediaTypesReport.php';
        //return HbbTV_DVB_mpdvalidator();
    }

    public function hookBeforeRepresentation()
    {
        HbbTV_DVB_flags();
        return is_subtitle();
    }

    public function hookRepresentation()
    {
        return RepresentationValidation_HbbTV_DVB();
    }

    public function hookBeforeAdaptationSet()
    {
        return add_remove_images('REMOVE');
    }

    public function hookAdaptationSet()
    {
        return CrossValidation_HbbTV_DVB();
    }

    private function moveScripts()
    {
        global $session_dir, $bitrate_script, $segment_duration_script;

        copy(dirname(__FILE__) . "/$bitrate_script", "$session_dir/$bitrate_script");
        chmod("$session_dir/$bitrate_script", 0777);
        copy(dirname(__FILE__) . "/$segment_duration_script", "$session_dir/$segment_duration_script");
        chmod("$session_dir/$segment_duration_script", 0777);
    }
}

$modules[] = new ModuleHbbTVDVB();
