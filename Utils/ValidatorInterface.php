<?php

namespace DASHIF;

require_once __DIR__ . '/RepresentationInterface.php';

enum ValidatorFlags
{
    case PreservesOrder;
}

//See the validators subfolder for example implementations
class ValidatorInterface
{
    public $name;
    public $detected;

    public $validRepresentations;

    protected $flags;

    //Implementations should set a proper name, and set the enabled flag according to whether it can be run or not.
    public function __construct()
    {
        $this->name = "INTERFACE_UNINITIALIZED";
        $this->enabled = false;
        $this->validRepresentations = array();
        $this->flags = array();
    }

    //If there are features that need to be specifically enabled, this function should handle it.
    public function enableFeature($featureName)
    {
    }

    //Run the validator for a specific configuration
    //Also should add to the validRepresentation array.
    public function run($period, $adaptation, $representation)
    {
    }

    public function hasValidatorFlag($flag)
    {
        return in_array($flag, $this->flags);
    }

    //Return a representation object if the configuration exists.
    public function getRepresentation($period, $adaptation, $representation)
    {
        foreach ($this->validRepresentations as $r) {
            if (
                $r->periodNumber == $period &&
                $r->adaptationNumber == $adaptation &&
                $r->representationNumber == $representation
            ) {
                return $r;
            }
        }
        return null;
    }
}
