<?php

namespace DASHIF;

require_once __DIR__ . '/../ValidatorInterface.php';

class ISOSegmentValidatorRepresentation extends RepresentationInterface
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getBoxNameTree(): Boxes\NameOnlyNode | null
    {
        $box = new Boxes\NameOnlyNode('');
        if ($this->payload) {
            $child = $this->payload->firstElementChild;
            while ($child != null) {
                $thisBox = new Boxes\NameOnlyNode('');
                $thisBox->fillChildrenRecursive($child);
                $box->children[] = $thisBox;
                $child = $child->nextElementSibling;
            }
        }
        return $box;
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

    public function getEmsgBoxes(): array|null
    {
        $res = array();
        if ($this->payload) {
            $emsgBoxes = $this->payload->getElementsByTagName('emsg');
            foreach ($emsgBoxes as $emsgBox) {
                $box = new Boxes\EventMessage();
                $box->presentationTime = $emsgBox->getAttribute('presentationTimeDelta');
                $box->timeScale = $emsgBox->getAttribute('timeScale');
                $box->eventDuration = $emsgBox->getAttribute('eventDuration');

                $box->id = $emsgBox->getAttribute('id');
                $res[] = $box;
            }
        }
        return $res;
    }

    public function getProtectionScheme(): Boxes\ProtectionScheme|null
    {
        $res = null;
        if ($this->payload) {
            $sinfBoxes = $this->payload->getElementsByTagName('sinf');
            if (count($sinfBoxes) == 1) {
                $sinfBox = $sinfBoxes->item(0);
                $res = new Boxes\ProtectionScheme();

                $originalFormatBox = $sinfBox->getElementsByTagName('frma');
                if (count($originalFormatBox)) {
                    $res->originalFormat = $originalFormatBox->item(0)->getAttribute('original_format');
                }

                $schemeTypeBoxes = $sinfBox->getElementsByTagName('schm');
                if (count($schemeTypeBoxes)) {
                    $schemeTypeBox = $schemeTypeBoxes->item(0);
                    $res->scheme->schemeType = $schemeTypeBox->getAttribute('scheme');
                    $res->scheme->schemeVersion = $schemeTypeBox->getAttribute('version');
                }

                $trackEncryptionBoxes = $sinfBox->getElementsByTagName('tenc');
                if (count($trackEncryptionBoxes)) {
                    $trackEncryptionBox = $trackEncryptionBoxes->item(0);
                    $res->encryption->isEncrypted = (int)$trackEncryptionBox->getAttribute('default_IsEncrypted');
                    $res->encryption->ivSize = (int)$trackEncryptionBox->getAttribute('default_IV_size');
                    $res->encryption->kid = $trackEncryptionBox->getAttribute('default_KID');
                    $GLOBALS['logger']->validatorMessage(
                        'Not all protection parameters can be extracted with this validator yet'
                    );
                    //- Constant IV
                    //- Crypt/skip blocks
                }
            }
        }
        return $res;
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

    public function getSampleDuration(): float|null
    {
        if (!$this->payload) {
            return null;
        }
        $trexBoxes = $this->payload->getElementsByTagName('trex');
        if (!count($trexBoxes)) {
            return null;
        }
        $duration = (float) $trexBoxes->item(0)->getAttribute('sampleDuration');
        //Assume ms if we can't find otherwise
        $timescale = 1000;
        $mdhdBoxes = $this->payload->getElementsByTagName('mdhd');
        if (count($mdhdBoxes)) {
            $timescale = $mdhdBoxes->item(0)->getAttribute('timescale');
        }


        return $duration / $timescale;
    }

    public function getFragmentDurations(): array|null
    {
        if (!$this->payload) {
            return null;
        }
        $sidxBoxes = $this->payload->getElementsByTagName('sidx');
        if (!count($sidxBoxes)) {
            return null;
        }
        $prevDuration = 0.0;
        $res = array();
        foreach ($sidxBoxes as $sidxBox) {
            $duration = (float)$sidxBox->getAttribute('cumulatedDuration');
            $res[] = $duration - $prevDuration;
            $prevDuration = $duration;
        }
        return $res;
    }

    public function getDAC4Boxes(): array|null
    {
        $res = array();
        if ($this->payload) {
            $dac4Nodes = $this->payload->getElementsByTagName('ac4_dsi_v1');
            foreach ($dac4Nodes as $node) {
                $box = new \DASHIF\Boxes\DAC4();
                $box->bitstream_version = $node->getAttribute('bitstream_version');
                $box->fs_index = $node->getAttribute('fs_index');
                $box->frame_rate_index = $node->getAttribute('frame_rate_index');
                $box->short_program_id = $node->getAttribute('short_program_id');
                $box->n_presentations = $node->getAttribute('n_presentations');
                $res[] = $box;
            }
        }
        return $res;
    }
    
    public function getAC4TOCBoxes(): array|null
    {
        $res = array();
        if ($this->payload) {
            $tocNodes = $this->payload->getElementsByTagName('ac4_toc');
            foreach ($tocNodes as $node) {
                $box = new \DASHIF\Boxes\AC4TOC();
                $box->bitstream_version = $node->getAttribute('bitstream_version');
                $box->fs_index = $node->getAttribute('fs_index');
                $box->frame_rate_index = $node->getAttribute('frame_rate_index');
                $box->short_program_id = $node->getAttribute('short_program_id');
                $box->n_presentations = $node->getAttribute('n_presentations');
                $res[] = $box;
            }
        }
        return $res;
    }
}
