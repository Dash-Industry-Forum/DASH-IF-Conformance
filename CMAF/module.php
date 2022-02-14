<?php
  class moduleCMAF extends moduleInterface {
    function __construct() {
      parent::__construct();
      $this->name = "CMAF";

      ///\warn Remove global here
      global $cmaf_conformance;
      if ($cmaf_conformance){
        $this->enabled = true;
      }
    }

    function hookBeforeRepresentation(){
      return CMAFFlags();
    }

    function hookRepresentation(){
      return checkCMAFTracks();
    }

    function hookBeforeAdaptationSet(){
      return checkSwitchingSets();
    }

    function hookAdaptationSet(){
      return checkPresentation();
    }
  } 

  $modules[] = new moduleCMAF();
?>
