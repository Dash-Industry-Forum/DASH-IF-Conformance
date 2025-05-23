<?php

namespace DASHIF;

require_once __DIR__ . '/boxes/boxes.php';

//See the validators subfolder for example implementations
class RepresentationInterface
{
    public $source;
    public $periodNumber;
    public $adaptationNumber;
    public $representationNumber;
    public $payload;
    public $fileSizes;

  //These public values get filled after construction by the sample implementations
    public function __construct()
    {
        $this->source = null;
        $this->periodNumber = null;
        $this->adaptationNumber = null;
        $this->representationNumber = null;
        $this->payload = null;

        $this->fileSizes = isset($GLOBALS['sizearray']) ? $GLOBALS['sizearray'] : [];
    }

  //This enables a more uniform & 'DRY' way to print log messages for the output json.
    public function getPrintable()
    {
        return "[$this->source: " .
        "Period $this->periodNumber, " .
        "Adaptation $this->adaptationNumber, " .
        "Representation $this->representationNumber]";
    }

    //Fields that only have one occurence in the file can be handled with simple functions.
    public function getHandlerType()
    {
        return null;
    }

    public function getSDType()
    {
        return null;
    }

    public function getWidth()
    {
        return null;
    }
    public function getHeight()
    {
        return null;
    }

    public function getDefaultKID()
    {
        return null;
    }

    public function getTopLevelBoxNames()
    {
        return null;
    }

    public function getBoxNameTree(): Boxes\NameOnlyNode|null
    {
        return null;
    }

    public function getSegmentSizes()
    {
        return $this->fileSizes;
    }

    public function getSegmentDurations()
    {
        return null;
    }

    public function getSampleDescription(): Boxes\SampleDescription|null
    {
        return null;
    }

    public function getProtectionScheme(): Boxes\ProtectionScheme|null
    {
        return null;
    }

    public function getSampleAuxiliaryInformation(): Boxes\SampleAuxiliaryInformation|null
    {
        return null;
    }

    public function getKindBoxes(): array|null
    {
        return null;
    }

    public function getPsshBoxes(): array|null
    {
        return null;
    }

    public function getSencBoxes(): array|null
    {
        return null;
    }

    public function getEmsgBoxes(): array|null
    {
        return null;
    }

    public function getSampleDuration(): float|null
    {
        return null;
    }

    public function getFragmentDurations(): array|null
    {
        return null;
    }

    public function getSeigDescriptionGroups(): array | null
    {
        return null;
    }
    public function getSampleGroups(): array | null
    {
        return null;
    }

    //Fields that can be found in multiple locations take a boxname as well as a box index.
    //Set $index to 0 for singular boxes.
    //For the sample implementations, only the used boxes are handled.
    public function getTrackId($boxName, $index)
    {
        return null;
    }

    public function getRawBox($boxName, $index)
    {
        return null;
    }


    //Example function to check whether a box exists at all.
    public function hasBox($boxName)
    {
        return false;
    }

    public function getAVCCBoxes(): array|null
    {
        return null;
    }

    public function getHVCCBoxes(): array|null
    {
        return null;
    }

    public function getELSTBoxes(): array|null
    {
        return null;
    }

    public function getTRUNBoxes(): array|null
    {
        return null;
    }

    public function getTFDTBoxes(): array|null
    {
        return null;
    }
        
    public function getSIDXBoxes(): array|null
    {
        return null;
    }

}
