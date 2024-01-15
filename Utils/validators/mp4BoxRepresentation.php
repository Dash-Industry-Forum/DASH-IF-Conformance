<?php

namespace DASHIF;

class MP4BoxRepresentation extends RepresentationInterface
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getTopLevelBoxNames()
    {
        $boxNames = array();
        if ($this->payload) {
            $topLevelBoxes = $this->payload->getElementsByTagName('IsoMediaFile');
            if ($topLevelBoxes->count()) {
                $child = $topLevelBoxes->item(0)->firstElementChild;
                while ($child != null) {
                    $boxNames[] = $child->getAttribute('Type');
                    $child = $child->nextElementSibling;
                }
            }
        }
        return $boxNames;
    }

    public function getHandlerType()
    {
        if (!$this->payload) {
            return null;
        }
        $handlerBoxes = $this->payload->getElementsByTagName('HandlerBox');
        if (count($handlerBoxes) == 0) {
            return null;
        }
        return $handlerBoxes->item(0)->getAttribute('hdlrType');
    }

    public function getSDType()
    {
        if (!$this->payload) {
            return null;
        }
        $handlerType = $this->getHandlerType();
        if ($handlerType == 'soun') {
            $sampleDescriptionBoxes = $this->payload->getElementsByTagName("MPEGAudioSampleDescriptionBox");
            if (count($sampleDescriptionBoxes) == 0) {
                return null;
            }
            return $sampleDescriptionBoxes->item(0)->getAttribute('Type');
        }
        if ($handlerType == 'vide') {
            $sampleDescriptionBoxes = $this->payload->getElementsByTagName("AVCSampleEntryBox");
            if (count($sampleDescriptionBoxes) == 0) {
                return null;
            }
            return $sampleDescriptionBoxes->item(0)->getAttribute('Type');
        }
        return null;
    }

    public function getSampleDescription(): Boxes\SampleDescription|null
    {
        $result = null;
        if ($this->payload) {
            $handlerType = $this->getHandlerType();
            switch ($handlerType) {
                case 'subt':
                    $subtBoxes = $this->payload->getElementsByTagName('XMLSubtitleSampleEntryBox');
                    if (count($subtBoxes)) {
                        $result = $this->parseSubtDescription($subtBoxes[0]);
                    }
                    break;
                case 'text':
                    $textBoxes = $this->payload->getElementsByTagName('WebVTTSampleEntryBox');
                    if (count($textBoxes)) {
                        $result = $this->parseSubtDescription($textBoxes[0]);
                    }
                    break;

                default:
                    break;
            }
        }

        return $result;
    }

    public function getSegmentDurations()
    {
        $res = array();
        if ($this->payload) {
            $sidxBoxes = $this->payload->getElementsByTagName('SegmentIndexBox');
            foreach ($sidxBoxes as $sidxBox) {
                $timescale = $sidxBox->getAttribute('timescale');
                $references = $sidxBox->getElementsByTagName('Reference');
                $thisDuration = 0;
                foreach ($references as $reference) {
                    $thisDuration += floatval($reference->getAttribute('duration'));
                }
                $res[] = ($thisDuration / $timescale);
            }
        }
        return $res;
    }

    private function parseSubtDescription($box)
    {
        $result = null;
        if ($box->tagName == "XMLSubtitleSampleEntryBox") {
            switch ($box->getAttribute('Type')) {
                case 'stpp':
                    $result = new Boxes\STPPBox();
                    $result->type = Boxes\DescriptionType::Subtitle;
                    $result->codingname = 'stpp';
                    $result->namespace = $box->getAttribute('namespace');
                    $result->schemaLocation = $box->getAttribute('schema_location');
                    $result->auxiliaryMimeTypes = $box->getAttribute('auxiliary_mime_types');
                    break;
                default:
                    break;
            }
        }
        if ($box->tagName == "WebVTTSampleEntryBox") {
            switch ($box->getAttribute('Type')) {
                case 'wvtt':
                    $result = new Boxes\SampleDescription();
                    $result->type = Boxes\DescriptionType::Text;
                    $result->codingname = 'wvtt';
                    break;
                default:
                    break;
            }
        }
        return $result;
    }


    public function getTrackId($boxName, $index)
    {
        if (!$this->payload) {
            return null;
        }
        $boxes = array();
        switch ($boxName) {
            case 'TKHD':
                $boxes = $this->payload->getElementsByTagName('TrackHeaderBox');
                break;
            case 'TFHD':
                $boxes = $this->payload->getElementsByTagName('TrackFragmentHeaderBox');
                break;
            default:
                return null;
        }
        if (count($boxes) <= $index) {
            return null;
        }
        return $boxes->item($index)->getAttribute('TrackID');
    }

    public function getWidth()
    {
        if (!$this->payload) {
            return null;
        }
        $handlerType = $this->getHandlerType();
        if ($handlerType != 'vide') {
            return null;
        }
        $sampleDescriptionBoxes = $this->payload->getElementsByTagName("AVCSampleEntryBox");
        if (count($sampleDescriptionBoxes) == 0) {
            return null;
        }
        return $sampleDescriptionBoxes->item(0)->getAttribute('Width');
    }
    public function getHeight()
    {
        if (!$this->payload) {
            return null;
        }
        $handlerType = $this->getHandlerType();
        if ($handlerType != 'vide') {
            return null;
        }
        $sampleDescriptionBoxes = $this->payload->getElementsByTagName("AVCSampleEntryBox");
        if (count($sampleDescriptionBoxes) == 0) {
            return null;
        }
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

    public function getRawBox($boxName, $index)
    {
        if (!$this->payload) {
            return null;
        }
        $boxes = array();
        switch ($boxName) {
            case 'STSD':
                $boxes = $this->payload->getElementsByTagName('SampleDescriptionBox');
                break;
            default:
                return null;
        }
        if (count($boxes) <= $index) {
            return null;
        }
        return $boxes->item($index);
    }
}
