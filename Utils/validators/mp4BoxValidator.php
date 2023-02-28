<?php

namespace DASHIF;

class MP4BoxRepresentation extends RepresentationInterface
{

    public function __construct()
    {
        parent::__construct();
    }

    public function getHandlerType(){
      if (!$this->payload){return null;}
      $handlerBoxes = $this->payload->getElementsByTagName('HandlerBox');
      if (count($handlerBoxes) == 0){return null;}
      return $handlerBoxes->item(0)->getAttribute('hdlrType');
    }

    public function getSDType(){
      if (!$this->payload){return null;}
      $handlerType = $this->getHandlerType();
      if ($handlerType == 'soun'){
        $sampleDescriptionBoxes = $this->payload->getElementsByTagName("MPEGAudioSampleDescriptionBox");
        if (count($sampleDescriptionBoxes) == 0){return null;}
        return $sampleDescriptionBoxes->item(0)->getAttribute('Type');
      }
      if ($handlerType == 'vide'){
        $sampleDescriptionBoxes = $this->payload->getElementsByTagName("AVCSampleEntryBox");
        if (count($sampleDescriptionBoxes) == 0){return null;}
        return $sampleDescriptionBoxes->item(0)->getAttribute('Type');
      }
      return null;
    }

    public function getTrackId($boxName, $index){
      if (!$this->payload){return null;}
      $boxes = array();
      switch ($boxName){
        case 'TKHD':
          $boxes = $this->payload->getElementsByTagName('TrackHeaderBox');
          break;
        case 'TFHD':
          $boxes = $this->payload->getElementsByTagName('TrackFragmentHeaderBox');
          break;
        default:
          return null;
      }
      if (count($boxes) <= $index){return null;}
      return $boxes->item($index)->getAttribute('TrackID');
    }

    public function getWidth(){
      if (!$this->payload){return null;}
      $handlerType = $this->getHandlerType();
      if ($handlerType != 'vide'){return null;}
      $sampleDescriptionBoxes = $this->payload->getElementsByTagName("AVCSampleEntryBox");
      if (count($sampleDescriptionBoxes) == 0){return null;}
      return $sampleDescriptionBoxes->item(0)->getAttribute('Width');
    }
    public function getHeight(){
      if (!$this->payload){return null;}
      $handlerType = $this->getHandlerType();
      if ($handlerType != 'vide'){return null;}
      $sampleDescriptionBoxes = $this->payload->getElementsByTagName("AVCSampleEntryBox");
      if (count($sampleDescriptionBoxes) == 0){return null;}
      return $sampleDescriptionBoxes->item(0)->getAttribute('Height');
    }

    /*
    //THESE FUNCTIONS ARE NOT YET IMPLEMENTED FOR MP4BOX VALIDATOR
    public function getDefaultKID(){
      return null;
    }

    public function hasBox($boxName){
      if (!$this->payload){return false;}
      $boxes = array();
      switch ($boxName){
        case 'TENC':
          $boxes = $this->payload->getElementsByTagName('<TENC BOX NAME HERE>');
          break;
        default:
          return null;
      }
      if (count($boxes) == 0){return false;}
      return true;
    }
     */

    public function getRawBox($boxName, $index){
      if (!$this->payload){return null;}
      $boxes = array();
      switch ($boxName){
        case 'STSD':
          $boxes = $this->payload->getElementsByTagName('SampleDescriptionBox');
          break;
        default:
          return null;
      }
      if (count($boxes) <= $index){return null;}
      return $boxes->item($index);
    }

}

class MP4BoxValidator extends ValidatorInterface
{
    private $path;
    private $output;

    public function __construct()
    {
        parent::__construct();
        $this->name = "MP4BoxValidator";
        $this->enabled = (exec("MP4Box -h") !== false);
        $this->output = array();
    }

    public function run($period, $adaptation, $representation)
    {
        global $session, $mpdHandler;

        exec("MP4Box -dxml " . $session->getSelectedRepresentationDir() . "/assembled.mp4 2>&1", $this->output);

        $thisRep = new MP4BoxRepresentation();
        $thisRep->source = $this->name;
        $thisRep->periodNumber = $mpdHandler->getSelectedPeriod();
        $thisRep->adaptationNumber = $mpdHandler->getSelectedAdaptationSet();
        $thisRep->representationNumber = $mpdHandler->getSelectedRepresentation();
        $thisRep->payload = Utility\parseDom($session->getSelectedRepresentationDir() . "/assembled_dump.xml", "ISOBaseMediaFileTrace");


        $this->validRepresentations[] = $thisRep;

        $this->handleOutput();
    }

    private function handleOutput()
    {
        global $logger, $session, $mpdHandler;
        $representationDirectory = $session->getSelectedRepresentationDir();

        $currentModule = $logger->getCurrentModule();
        $currentHook = $logger->getCurrentHook();
        $logger->setModule("SEGMENT_VALIDATION");
        $logger->setHook("MP4BoxValidator");

        $contentArray = $this->output;

        $testName = "std error output for Period " . $mpdHandler->getSelectedPeriod() . 
                    ", adaptation " . $mpdHandler->getSelectedAdaptationSet() . 
                    ", representation " . $mpdHandler->getSelectedRepresentation();

        if (!count($contentArray)){ 
            $logger->test(
                "Segment Validation",
                "Segment Validation",
                $testName,
                true,
                "PASS",
                "Segment validation did not produce any output",
                $content
            );
        } else {
            foreach ($contentArray as $i => $msg) {
                $severity = "PASS";
                //Catch both warn and warning
                if (stripos($msg, "ignoring") !== false) {
//                    $severity = "WARN";
                }
                //Catch errors
                if (stripos($msg, "aborting") !== false) {
//                    $severity = "FAIL";
                }

                $logger->test(
                    "Segment Validation",
                    "Segment Validation",
                    $testName,
                    $severity == "PASS",
                    $severity,
                    $msg,
                    $msg
                );
            }
        }


    // Restore module information since health checks are over
        $logger->setModule($currentModule);
        $logger->setHook($currentHook);
    }
}

global $validators;
$validators[] = new MP4BoxValidator();
