<?php

namespace DASHIF;

require_once __DIR__ . '/../ValidatorInterface.php';

class ISOSegmentValidatorRepresentation extends RepresentationInterface
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getHandlerType()
    {
        if (!$this->payload) {
            return null;
        }
        $handlerBoxes = $this->payload->getElementsByTagName('hdlr');
        if (count($handlerBoxes) == 0) {
            return null;
        }
        return $handlerBoxes->item(0)->getAttribute('handler_type');
    }

    public function getSDType()
    {
        if (!$this->payload) {
            return null;
        }
        $handlerType = $this->getHandlerType();
        $sampleDescriptionBoxes = $this->payload->getElementsByTagName($handlerType . "_sampledescription");
        if (count($sampleDescriptionBoxes) == 0) {
            return null;
        }
        return $sampleDescriptionBoxes->item(0)->getAttribute('sdType');
    }

    public function getSampleDescription(): Boxes\SampleDescription|null
    {

        $result = null;
        if ($this->payload) {
            $handlerType = $this->getHandlerType();
            switch ($handlerType) {
                case 'subt':
                    $subtBoxes = $this->payload->getElementsByTagName('subt_sampledescription');
                    if (count($subtBoxes)) {
                        $result = $this->parseSubtDescription($subtBoxes[0]);
                    }
                    break;
                case 'text':
                    $textBoxes = $this->payload->getElementsByTagName('text_sampledescription');
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
            $sidxBoxes = $this->payload->getElementsByTagName('sidx');
            foreach ($sidxBoxes as $sidxBox) {
                $res[] = floatval($sidxBox->getAttribute('cumulatedDuration'));
            }
        }
        return $res;
    }

    private function parseSubtDescription($box)
    {
        $result = null;
        if ($box->tagName == "subt_sampledescription") {
            switch ($box->getAttribute('sdType')) {
                case 'stpp':
                    $stpp = $box->getElementsByTagName('stpp');
                    if (count($stpp)) {
                        $result = new Boxes\STPPBox();
                        $result->type = Boxes\DescriptionType::Subtitle;
                        $result->codingname = 'stpp';
                        $result->namespace = $stpp->item(0)->getAttribute('namespace');
                        $result->schemaLocation = $stpp->item(0)->getAttribute('schema_location');
                        $result->auxiliaryMimeTypes = $stpp->item(0)->getAttribute('auxiliary_mime_types');
                    }
                    break;
                default:
                    break;
            }
        }
        if ($box->tagName == "text_sampledescription") {
            switch ($box->getAttribute('sdType')) {
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
                $boxes = $this->payload->getElementsByTagName('tkhd');
                break;
            case 'TFHD':
                $boxes = $this->payload->getElementsByTagName('tfhd');
                break;
            default:
                return null;
        }
        if (count($boxes) <= $index) {
            return null;
        }
        return $boxes->item($index)->getAttribute('trackID');
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
        $sampleDescriptionBoxes = $this->payload->getElementsByTagName($handlerType . "_sampledescription");
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
        $sampleDescriptionBoxes = $this->payload->getElementsByTagName($handlerType . "_sampledescription");
        if (count($sampleDescriptionBoxes) == 0) {
            return null;
        }
        return $sampleDescriptionBoxes->item(0)->getAttribute('Height');
    }

    public function getDefaultKID()
    {
        if (!$this->payload) {
            return null;
        }
        $tencBoxes = $this->payload->getElementsByTagName("tenc");
        if (count($tencBoxes) == 0) {
            return null;
        }
        return $tencBoxes->item(0)->getAttribute('default_KID');
    }

    public function hasBox($boxName)
    {
        if (!$this->payload) {
            return false;
        }
        $boxes = array();
        switch ($boxName) {
            case 'TENC':
                $boxes = $this->payload->getElementsByTagName('tenc');
                break;
            default:
                return null;
        }
        if (count($boxes) == 0) {
            return false;
        }
        return true;
    }

    public function getRawBox($boxName, $index)
    {
        if (!$this->payload) {
            return null;
        }
        $boxes = array();
        switch ($boxName) {
            case 'STSD':
                $boxes = $this->payload->getElementsByTagName('stsd');
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
