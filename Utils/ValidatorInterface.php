<?php

namespace DASHIF;

require_once __DIR__ . '/RepresentationInterface.php';

class ValidatorInterface
{
    public $name;
    public $detected;

    public $validRepresentations;

    public function __construct()
    {
        $this->name = "INTERFACE_UNINITIALIZED";
        $this->enabled = false;
        $this->validRepresentations = array();
    }

    public function enableFeature($featureName)
    {
    }

    public function run($period, $adaptation, $representation)
    {
    }

    public function getRepresentation($period, $adaptation, $representation){
      foreach ($this->validRepresentations as $r){
        if ($r->periodNumber == $period && $r->adaptationNumber == $adaptation && $r->representationNumber == $representation){
          return $r;
        }
      }
      return null;
    }
}

