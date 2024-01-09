<?php

namespace DASHIF;

//See the validators subfolder for example implementations
class RepresentationInterface {
  public $source;
  public $periodNumber;
  public $adaptationNumber;
  public $representationNumber;
  public $payload;

  //These public values get filled after construction by the sample implementations
    public function __construct()
    {
      $this->source = null;
      $this->periodNumber = null;
      $this->adaptationNumber = null;
      $this->representationNumber = null;
      $this->payload = null;
    }

  //This enables a more uniform & 'DRY' way to print log messages for the output json.
    public function getPrintable(){ 
      return "[$this->source: " .
        "Period $this->periodNumber, " .
        "Adaptation $this->adaptationNumber, " .
        "Representation $this->representationNumber]";
    }

    //Fields that only have one occurence in the file can be handled with simple functions.
    public function getHandlerType(){
      return null;
    }

    public function getSDType(){
      return null;
    }

    public function getWidth(){
      return null;
    }
    public function getHeight(){
      return null;
    }

    public function getDefaultKID(){
      return null;
    }

    //Fields that can be found in multiple locations take a boxname as well as a box index.
    //Set $index to 0 for singular boxes.
    //For the sample implementations, only the used boxes are handled.
    public function getTrackId($boxName, $index){
      return null;
    }

    public function getRawBox($boxName, $index){
      return null;
    }

    //Example function to check whether a box exists at all.
    public function hasBox($boxName){
      return false;
    }

}

