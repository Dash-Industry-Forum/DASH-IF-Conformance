<?php

namespace DASHIF;

class ArgumentsParser
{
    protected $parsedOptions;
    protected $allOptions;
    protected $extraArguments;

    public function __construct()
    {
        $this->addOption("help", "h", "help", "Print this help text");
    }

    public function parseAll()
    {
        global $modules;

        $restidx = null;
        $this->parsedOptions = getopt($this->getShortOpts(), $this->getLongOpts(), $restidx);

        global $argv;
        $this->extraArguments = array_slice($argv, $restidx);


        if ($this->getOption("help")) {
            exit($this->help());
        }

        foreach ($modules as &$module) {
            $module->handleArguments();
        }
    }

    public function getOption($name)
    {
        foreach ($this->allOptions as $option) {
            if ($option->label == $name) {
                if (
                    array_key_exists($option->short[0], $this->parsedOptions) ||
                    array_key_exists($option->long, $this->parsedOptions)
                ) {
                    return true;
                }
            }
        }
        if ($_REQUEST[$name]) {
            return true;
        }
        return false;
    }

    private function getShortOpts()
    {
        $res = "";
        foreach ($this->allOptions as $option) {
            $res .= $option->short;
        }
        return $res;
    }

    private function getLongOpts()
    {
        $res = array();
        foreach ($this->allOptions as $option) {
            $res[] = $option->long;
        }
        return $res;
    }

    public function addOption($label, $short, $long, $desc)
    {
        $this->allOptions[] = new Argument($label, $short, $long, $desc);
    }

    public function getPositionalArgument($argname)
    {
        if ($argname == "url" && $_REQUEST["url"]) {
            return urldecode($_REQUEST["url"]);
        }
        switch ($argname) {
            case 'url':
                return $this->extraArguments[0];
        }
        return null;
    }

    public function helpAPI()
    {
        $helpObject = array(
          description => "API Help for the DASH IF Conformance checker. " .
                         "All options can be passed in as either GET or POST parameters",
        options => array(
          url => "An url-encoded url to a DASH Manifest"
        )
        );
        foreach ($this->allOptions as $option) {
            $helpObject['options'][$option->long] = $option->desc;
        }

        return \json_encode($helpObject);
    }

    public function help()
    {

        if (http_response_code() !== false) {
            return $this->helpAPI();
        }

        global $argv;
        $helptext = "Usage: " . $argv[0] . " [options] URL\n";
        foreach ($this->allOptions as $option) {
            $helptext .= "  ";
            if ($option->short != "") {
                $helptext .= "-" . $option->short[0] . ", ";
            } else {
                $helptext .= "    ";
            }
            $helptext .= "--" . $option->long;
            $helptext .= "\t\t" . $option->desc;
            $helptext .= "\n";
        }

        return $helptext;
    }
}
