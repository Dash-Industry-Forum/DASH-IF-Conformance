<?php

namespace App\Services\Validators;

use Illuminate\Support\Facades\Log;

class MP4BoxRepresentation
{
    public ?\DOMElement $payload;
    public function __construct(string $filePath)
    {
        // TODO: Re-inherit from 'generic' parent
        $contents = file_get_contents($filePath);

        $doc = new \DOMDocument();
        $doc->loadXML($contents);


        $rootNodes = $doc->getElementsByTagName('ISOBaseMediaFileTrace');
        if ($rootNodes->length == 0) {
            Log::error("Unexpected content in xml");
            return;
        }


        $this->payload = $rootNodes->item(0);
    }

    public function getBoxNameTree(): ?Boxes\NameOnlyNode
    {
        $box = new Boxes\NameOnlyNode('');
        if ($this->payload) {
            $topLevelBoxes = $this->payload->getElementsByTagName('IsoMediaFile');
            for ($i = 0; $i < $topLevelBoxes->count(); $i++) {
                $child = $topLevelBoxes->item($i)->firstElementChild;
                while ($child != null) {
                    $thisBox = new Boxes\NameOnlyNode('');
                    $thisBox->fillChildrenRecursive($child);
                    $box->children[] = $thisBox;
                    $child = $child->nextElementSibling;
                }
            }
        }
        return $box;
    }

    /**
     * @return array<string>
     **/
    public function getTopLevelBoxNames(): array
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

    public function getHandlerType(): ?string
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

    public function getSDType(): ?string
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
            if (count($sampleDescriptionBoxes) > 0) {
                return $sampleDescriptionBoxes->item(0)->getAttribute('Type');
            }
            $sampleDescriptionBoxes = $this->payload->getElementsByTagName("HEVCSampleEntryBox");
            if (count($sampleDescriptionBoxes) > 0) {
                return $sampleDescriptionBoxes->item(0)->getAttribute('Type');
            }
        }
        return null;
    }

    public function getSampleDescription(): ?Boxes\SampleDescription
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

    public function getProtectionScheme(): ?Boxes\SINFBox
    {
        $res = null;
        if ($this->payload) {
            $sinfBoxes = $this->payload->getElementsByTagName('ProtectionSchemeInfoBox');
            if (count($sinfBoxes) == 1) {
                $sinfBox = $sinfBoxes->item(0);
                $res = new Boxes\SINFBox();

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
                    $res->encryption->isEncrypted = (int)$trackEncryptionBox->getAttribute('isEncrypted') > 0;
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


    /**
     * @return array<float>
     **/
    public function getSegmentSizes(): array
    {
        //TODO: Implement!
        return [];
    }

    /**
     * @return array<float>
     **/
    public function getSegmentDurations(): array
    {
        $res = array();
        if ($this->payload) {
            $sidxBoxes = $this->payload->getElementsByTagName('SegmentIndexBox');
            foreach ($sidxBoxes as $sidxBox) {
                $timescale = intval($sidxBox->getAttribute('timescale'));
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

    public function getSampleAuxiliaryInformation(): ?Boxes\SampleAuxiliaryInformation
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

    /**
     * @return array<Boxes\KINDBox>
     **/
    public function getKindBoxes(): ?array
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

    /**
     * @return array<Boxes\PSSHBox>
     **/
    public function getPsshBoxes(): array
    {
        $res = array();
        if ($this->payload) {
            $psshBoxes = $this->payload->getElementsByTagName('ProtectionSystemHeaderBox');
            foreach ($psshBoxes as $psshBox) {
                $box = new Boxes\PSSHBox();
                $box->systemId = $psshBox->getAttribute('SystemID');
                foreach ($psshBox->getElementsByTagName('PSSHKey') as $psshKey) {
                    $box->keys[] = $psshKey->getAttribute('KID');
                }
                foreach ($psshBox->getElementsByTagName('PSSHData') as $psshData) {
                    $box->data[] = $psshData->getAttribute('value');
                }
                $res[] = $box;
            }
        }
        return $res;
    }

    /**
     * @return array<Boxes\SENCBox>
     **/
    public function getSencBoxes(): ?array
    {
        $res = array();
        if ($this->payload) {
            $sencBoxes = $this->payload->getElementsByTagName('SampleEncryptionBox');
            foreach ($sencBoxes as $sencBox) {
                $box = new Boxes\SENCBox();
                $box->sampleCount = intval($sencBox->getAttribute('sampleCount'));
                foreach ($sencBox->getElementsByTagName('SampleEncryptionEntry') as $sencEntry) {
                    $box->ivSizes[] = intval($sencEntry->getAttribute('IV_size'));
                }
                $res[] = $box;
            }
        }
        return $res;
    }

    /**
     * @return array<Boxes\EventMessage>
     **/
    public function getEmsgBoxes(): ?array
    {
        $res = array();
        if ($this->payload) {
            $emsgBoxes = $this->payload->getElementsByTagName('EventMessageBox');
            foreach ($emsgBoxes as $emsgBox) {
                $box = new Boxes\EventMessage();
                $box->presentationTime = intval($emsgBox->getAttribute('presentation_time'));
                $box->timeScale = intval($emsgBox->getAttribute('timescale'));
                $box->eventDuration = intval($emsgBox->getAttribute('event_duration'));

                $box->schemeIdUri = $emsgBox->getAttribute('scheme_id_uri');
                $box->value = $emsgBox->getAttribute('value');
                $box->messageData = $emsgBox->getAttribute('message_data');
                $res[] = $box;
            }
        }
        return $res;
    }

    private function parseSubtDescription(\DOMElement $box): mixed
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
                    $result->codec = 'wvtt';
                    break;
                default:
                    break;
            }
        }
        return $result;
    }


    public function getTrackId(string $boxName, int $index): ?int
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
        return intval($boxes->item($index)->getAttribute('TrackID'));
    }

    public function getWidth(): ?int
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
        return intval($sampleDescriptionBoxes->item(0)->getAttribute('Width'));
    }
    public function getHeight(): ?int
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
        return intval($sampleDescriptionBoxes->item(0)->getAttribute('Height'));
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

    public function getRawBox(string $boxName, int $index): mixed
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

    public function getSampleDuration(): float|null
    {
        if (!$this->payload) {
            return null;
        }
        $trexBoxes = $this->payload->getElementsByTagName('TrackExtendsBox');
        if (!count($trexBoxes)) {
            return null;
        }
        $duration = (float)$trexBoxes->item(0)->getAttribute('SampleDuration');
        //Assume ms if we can't find otherwise
        $timescale = 1000;
        $mdhdBoxes = $this->payload->getElementsByTagName('MediaHeaderBox');
        if (count($mdhdBoxes)) {
            $timescale = $mdhdBoxes->item(0)->getAttribute('TimeScale');
        }

        return $duration / $timescale;
    }

    /**
     * @return array<float>
     **/
    public function getFragmentDurations(): ?array
    {
        if (!$this->payload) {
            return null;
        }
        $sidxBoxes = $this->payload->getElementsByTagName('SegmentIndexBox');
        if (!count($sidxBoxes)) {
            return null;
        }
        $prevDuration = 0.0;
        $res = array();
        foreach ($sidxBoxes as $sidxBox) {
            $references = $sidxBox->getElementsByTagName('Reference');
            $duration = 0.0;
            foreach ($references as $reference) {
                //Take the maximum
                $duration = (float)$reference->getAttribute('duration');
            }
            $duration /= intval($sidxBox->getAttribute('timescale'));
            $res[] = $duration - $prevDuration;
            $prevDuration = $duration;
        }
        return $res;
    }

    /**
     * @return array<Boxes\SampleGroupDescription>
     **/
    public function getSeigDescriptionGroups(): ?array
    {
        if (!$this->payload) {
            return null;
        }
        $sgpdBoxes = $this->payload->getElementsByTagName('SampleGroupDescriptionBox');
        if (!count($sgpdBoxes)) {
            return null;
        }
        $res = array();
        foreach ($sgpdBoxes as $sgpdBox) {
            $description = new Boxes\SampleGroupDescription();
            $description->groupingType = $sgpdBox->getAttribute('grouping_type');
            foreach ($sgpdBox->getElementsByTagName('CENCSampleEncryptionGroupEntry') as $seigEntry) {
                $entry = new Boxes\SEIGDescription();
                $entry->isEncrypted = intval($seigEntry->getAttribute('IsEncrypted')) > 0;
                $entry->ivSize = intval($seigEntry->getAttribute('IV_size'));
                $entry->kid = $seigEntry->getAttribute('KID');
                $entry->constantIvSize = intval($seigEntry->getAttribute('constant_IV_size'));
                $entry->constantIv = $seigEntry->getAttribute('constant_IV');
                $description->entries[] = $entry;
            }

            $res[] = $description;
        }

        return $res;
    }

    /**
     * @return array<Boxes\SampleGroup>
     **/
    public function getSampleGroups(): ?array
    {
        if (!$this->payload) {
            return null;
        }
        $sbgpBoxes = $this->payload->getElementsByTagName('SampleGroupBox');
        if (!count($sbgpBoxes)) {
            return null;
        }
        $res = array();
        foreach ($sbgpBoxes as $sbgpBox) {
            $sampleGroup = new Boxes\SampleGroup();
            foreach ($sbgpBox->getElementsByTagName('SampleGroupBoxEntry') as $sgbpEntry) {
                $sampleGroup->sampleCounts[] = $sgbpEntry->getAttribute('sample_count');
                $sampleGroup->groupDescriptionIndices[] = $sgbpEntry->getAttribute('group_description_index');
            }
            $res[] = $sampleGroup;
        }
        return $res;
    }
}
