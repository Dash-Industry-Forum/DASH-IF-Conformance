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

