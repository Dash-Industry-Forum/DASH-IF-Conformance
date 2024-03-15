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

    public function getProtectionScheme(): Boxes\ProtectionScheme|null
    {
        $res = null;
        if ($this->payload) {
            $sinfBoxes = $this->payload->getElementsByTagName('ProtectionSchemeInfoBox');
            if (count($sinfBoxes) == 1) {
                $sinfBox = $sinfBoxes->item(0);
                $res = new Boxes\ProtectionScheme();

                $originalFormatBox = $sinfBox->getElementsByTagName('OriginalFormatBox');
                if (count($originalFormatBox)) {
                    $res->originalFormat = $originalFormatBox->item(0)->getAttribute('data_format');
                }

                $schemeTypeBoxes = $sinfBox->getElementsByTagName('SchemeTypeBox');
                if (count($schemeTypeBoxes)) {
                    $schemeTypeBox = $schemeTypeBoxes->item(0);
                    $res->scheme->schemeType = $schemeTypeBox->getAttribute('scheme_type');
                    $res->scheme->schemeVersion = $schemeTypeBox->getAttribute('scheme_version');
                }

                $trackEncryptionBoxes = $sinfBox->getElementsByTagName('TrackEncryptionBox');
                if (count($trackEncryptionBoxes)) {
                    $trackEncryptionBox = $trackEncryptionBoxes->item(0);
                    $res->encryption->isEncrypted = (int)$trackEncryptionBox->getAttribute('isEncrypted');
                    $res->encryption->ivSize = (int)$trackEncryptionBox->getAttribute('constant_IV_size');
                    $res->encryption->iv = $trackEncryptionBox->getAttribute('constant_IV');
                    $res->encryption->kid = $trackEncryptionBox->getAttribute('KID');
                    $res->encryption->cryptByteBlock = (int)$trackEncryptionBox->getAttribute('crypt_byte_block');
                    $res->encryption->skipByteBlock = (int)$trackEncryptionBox->getAttribute('skip_byte_block');
                }
            }
        }
        return $res;
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

    public function getSampleAuxiliaryInformation(): Boxes\SampleAuxiliaryInformation|null
    {
        $res = null;
        if ($this->payload) {
            $saioBoxes = $this->payload->getElementsByTagName('SampleAuxiliaryInfoOffsetBox');
            if (count($saioBoxes)) {
                $res = new Boxes\SampleAuxiliaryInformation();
            }
        }
        return $res;
    }

    public function getKindBoxes(): array|null
    {
        $res = array();
        if ($this->payload) {
            $userDataBoxes = $this->payload->getElementsByTagName('UserDataBox');
            foreach ($userDataBoxes as $udtaBox) {
                $kindBoxes = $udtaBox->getElementsByTagName('KindBox');
                foreach ($kindBoxes as $kindBox) {
                    $box = new Boxes\KINDBox();
                    $box->schemeURI = $kindBox->getAttribute('schemeURI');
                    $box->value = $kindBox->getAttribute('value');
                    $res[] = $box;
                }
            }
        }
        return $res;
    }

    public function getPsshBoxes(): array|null
    {
        $res = array();
        if ($this->payload) {
            $psshBoxes = $this->payload->getElementsByTagName('ProtectionSystemHeaderBox');
            foreach ($psshBoxes as $psshBox) {
                $box = new Boxes\ProtectionSystem();
                $box->systemId = $psshBox->getAttribute('SystemID');
                foreach ($psshBox->getElementsByTagName('PSSHKey') as $psshKey) {
                    $box->keys[] = $psshKey->getAttribute('KID');
                }
                foreach ($psshBox->getElementsByTagName('PSSHData') as $psshData) {
                    $box->data[] = $psshKey->getAttribute('value');
                }
                $res[] = $box;
            }
        }
        return $res;
    }

    public function getSencBoxes(): array|null
    {
        $res = array();
        if ($this->payload) {
            $sencBoxes = $this->payload->getElementsByTagName('SampleEncryptionBox');
            foreach ($sencBoxes as $sencBox) {
                $box = new Boxes\SampleEncryption();
                $box->sampleCount = $sencBox->getAttribute('sampleCount');
                foreach ($sencBox->getElementsByTagName('SampleEncryptionEntry') as $sencEntry) {
                    $box->ivSizes[] = $sencEntry->getAttribute('IV_size');
                }
                $res[] = $box;
            }
        }
        return $res;
    }

    public function getEmsgBoxes(): array|null
    {
        $res = array();
        if ($this->payload) {
            $emsgBoxes = $this->payload->getElementsByTagName('EventMessageBox');
            foreach ($emsgBoxes as $emsgBox) {
                $box = new Boxes\EventMessage();
                $box->presentationTime = $emsgBox->getAttribute('presentation_time');
                $box->timeScale = $emsgBox->getAttribute('timescale');
                $box->eventDuration = $emsgBox->getAttribute('event_duration');

                $box->schemeIdUri = $emsgBox->getAttribute('scheme_id_uri');
                $box->value = $emsgBox->getAttribute('value');
                $box->messageData = $emsgBox->getAttribute('message_data');
                $res[] = $box;
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
