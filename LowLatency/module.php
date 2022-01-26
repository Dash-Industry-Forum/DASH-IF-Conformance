<?php
  class moduleDASHIF_LL extends moduleInterface {
    function __construct() {
      parent::__construct();
      $this->name = "DASH-IF Low Latency";

      ///\warn Remove global here
      global $low_latency_dashif_conformance;
      if ($low_latency_dashif_conformance){
        $this->enabled = true;
      }
    }

    public function hookMPD(){
      $this->message("HookMPD");
      return $this->validate_mpd();
    }

    public function hookAdaptationSet(){
      return low_latency_validate_cross();
    }

    private function validate_mpd(){
      global $session_dir, $mpd_log, $mpd_xml_report;
      
      $messages = '';

      $this->message("Opening MPD Report");
      
      $mpdreport = fopen($session_dir . '/' . $mpd_log . '.txt', 'a+b');
      if(!$mpdreport)
          return;

      $this->message("Starting validation");
      
      $messages .= validateProfiles();
      $this->message("Validated Profiles");
      $messages .= validateServiceDescription();
      $this->message("Validated Service Description");
      $messages .= validateUTCTiming();
      $this->message("Validated UTC Timing");
      $messages .= validateLeapSecondInformation();

      $this->message("Validated Leap Second Information");
      
      fwrite($mpdreport, $messages);
      fclose($mpdreport);

      $this->message("Written File");
      
      $returnValue = (strpos($messages, 'violated') != '') ? 'error' : ((strpos($messages, 'warning') != '') ? 'warning' : 'true');
      $mpd_xml = simplexml_load_file($session_dir . '/' . $mpd_xml_report);
      $mpd_xml->dashif_ll = $returnValue;
      $mpd_xml->asXml($session_dir . '/' . $mpd_xml_report);

      $this->message("Written XML");
      
      return $returnValue;
    }
  } 

  $modules[] = new moduleDASHIF_LL();
?>
