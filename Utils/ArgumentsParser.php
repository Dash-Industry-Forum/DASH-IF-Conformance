<?php

namespace DASHIF;

class ArgumentsParser
{
    protected $parsedOptions;
    protected $allOptions;

    public function __construct()
    {
        $this->addOption("help", "h", "help", "Print this help text");
    }

    public function parseAll()
    {
        $restidx = null;
        $this->parsedOptions = getopt($this->getShortOpts(), $this->getLongOpts(), $restidx);


        if ($this->getOption("help")) {
            exit($this->help());
        }

        global $modules;
        foreach ($modules as $module) {
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

    public function help()
    {
        global $argv;
        $helptext = $argv[0] . " [options] URL\n";
        foreach ($this->allOptions as $option) {
            $helptext .= "  ";
            $helptext .= "-" . $option->short[0] . ", ";
            $helptext .= "--" . $option->long;
            $helptext .= "\t\t" . $option->desc;
            $helptext .= "\n";
        }

        return $helptext;
    }
}
