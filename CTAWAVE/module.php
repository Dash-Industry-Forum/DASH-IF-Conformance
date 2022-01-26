<?php
  class moduleCTAWAVE extends moduleInterface {
    function __construct() {
      parent::__construct();
      $this->name = "CTA-WAVE";

      ///\warn Remove global here
      global $ctawave_conformance;
      if ($ctawave_conformance){
        $this->enabled = true;
      }
    }

    function hookBeforeRepresentation(){
      return CTAFlags();
    }

    function hookBeforeAdaptationSet(){
      CTASelectionSet();
      return CTAPresentation();
    }

    function hookPeriod(){
      return CTABaselineSpliceChecks();
    }

  } 

  $modules[] = new moduleCTAWAVE();
?>
