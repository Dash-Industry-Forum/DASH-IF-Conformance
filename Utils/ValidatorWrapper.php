<?php

namespace DASHIF;

require_once __DIR__ . '/ValidatorInterface.php';
require_once __DIR__ . '/validators/mp4BoxValidator.php';
require_once __DIR__ . '/validators/isoSegmentValidator.php';

//See the validators subfolder for example implementations
class ValidatorWrapper
{
  public function __construct($loadDefaults = true){
    $this->validators = array();
    if($loadDefaults){
      $this->loadDefaults();
    }
  }

  public function loadDefaults(){
    $this->addValidator(new MP4BoxValidator());
    $this->addValidator(new ISOSegmentValidator());
  }

  public function hasValidator($validatorName){
    foreach ($this->validators as $validator) {
      if ($validator->name == $validatorName){
        return true;
      }
    }
    return false;
  }

  public function addValidator($v){
    $this->validators[] = $v;
  }

  public function printEnabled(){
    foreach ($this->validators as $validator) {
      print "Validator $validator->name is enabled? $validator->enabled" . PHP_EOL;
    }
  }

  public function enableFeature($feature){
    foreach ($this->validators as &$validator) {
      if ($validator->enabled){
        $validator->enableFeature($feature);
      }
    }
  }

  public function run($period, $adaptation_set, $representation){
    foreach ($this->validators as &$validator) {
      if ($validator->enabled){
        $validator->run($period, $adaptation_set, $representation);
      }
    }
  }

  public function analyzeSingle($representationArray, $object, $functionName){
    if (count($representationArray) >= 3){
      foreach ($this->validators as &$validator) {
        if (!$validator->enabled){
          continue;
        }
        $rep = $validator->getRepresentation($representationArray[0], $representationArray[1], $representationArray[2]);
        if (!$rep){
          continue;
        }
        return call_user_func(array($object, $functionName), $rep);

      }
    }
    return null;
  }

}

$GLOBALS['validatorWrapper'] = new ValidatorWrapper();
