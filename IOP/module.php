<?php
  class moduleDASHIF_IOP extends moduleInterface {
    function __construct() {
      parent::__construct();
      $this->name = "DASH-IF IOP Conformance";

      ///\warn Remove global here
      global $dashif_conformance;
      if ($dashif_conformance){
        $this->enabled = true;
      }
    }

    public function hookMPD(){
      return IOP_ValidateMPD();
    }

    public function hookRepresentation(){
      return IOP_ValidateSegment();
    }

    public function hookAdaptationSet(){
      return IOP_ValidateCross();
    }
  } 

  $modules[] = new moduleDASHIF_IOP();
?>
