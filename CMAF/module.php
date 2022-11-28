<?php

namespace DASHIF;

class ModuleCMAF extends ModuleInterface
{
    private $CMAFMediaProfileAttributesVideo;
    private $CMAFMediaProfileAttributesAudio;
    private $CMAFMediaProfileAttributesSubtitle;
    private $careAboutFtyp;
    private $careAboutElst;
    private $careAboutMdhd;
    private $boxList;
    private $cfhdSwitchingSetFound;
    private $caadSwitchingSetFound;
    private $encryptedSwitchingSetFound;

    private $mediaTypes;
    private $mediaProfiles;

    public function __construct()
    {
        parent::__construct();
        $this->name = "CMAF";

        $this->cfhdSwitchingSetFound = 0;
        $this->caadSwitchingSetFound = 0;
        $this->encryptedSwitchingSetFound = 0;


        $this->CMAFMediaProfileAttributesVideo = array(
          "codec" => "",
          "profile" => "",
          "level" => "",
          "height" => "",
          "width" => "",
          "framerate" => "",
          "color_primaries" => "",
          "transfer_char" => "",
          "matrix_coeff" => "",
          "tier" => "",
          "brand" => ""
        );

        $this->CMAFMediaProfileAttributesAudio = array(
          "codec" => "",
          "profile" => "",
          "level" => "",
          "channels" => "",
          "sampleRate" => "",
          "brand" => ""
        );

        $this->CMAFMediaProfileAttributesSubtitle = array(
          "codec" => "",
          "mimeType" => "",
          "mimeSubtype" => "",
          "brand" => ""
        );

        // All the boxes and related attributes to be checked for CMAF Table 11
        $this->boxList = array(
          "ftyp" => array("majorbrand", "version", "compatible_brands"),
          "mvhd" => array("version", "flags", "timeScale", "duration", "nextTrackID"),
          "tkhd" => array("version", "flags", "trackID", "duration", "volume"),
          "elst" => array("version", "flags", "entryCount"),
          "mdhd" => array("version", "flags", "timescale", "duration", "language"),
          "hdlr" => array("version", "flags", "handler_type", "name"),
          "vmhd" => array("version", "flags"),
          "smhd" => array("version", "flags"),
          "dref" => array("version", "flags", "entryCount"),
          "vide_sampledescription" => array("sdType"),
          "soun_sampledescription" => array("sdType"),
          "hint_sampledescription" => array("sdType"),
          "sdsm_sampledescription" => array("sdType"),
          "odsm_sampledescription" => array("sdType"),
          "stts" => array("version", "flags", "entryCount"),
          "stsc" => array("version", "flags", "entryCount"),
          "stsz" => array("version", "flags", "sampleSize", "entryCount"),
          "stco" => array("version", "flags", "entryCount"),
          "sgpd" => array("version", "flags", "groupingType", "entryCount"),
          "mehd" => array("version", "flags", "fragmentDuration"),
          "trex" => array("version", "flags", "trackID", "sampleDescriptionIndex", "sampleDuration", "sampleSize", "sampleFlags"),
          "pssh" => array("version", "flags", "systemID", "dataSize"),
          "tenc" => array("version", "flags", "default_IsEncrypted", "default_IV_size", "default_KID"),
          "cprt" => array("version", "flags", "language", "notice"),
          "kind" => array("schemeURI", "value"),
          "elng" => array("extended_languages"),
          "sinf" => array(),
          "schi" => array("comment"),
          "schm" => array("scheme", "version", "location"),
          "frma" => array("original_format")
        );

        $this->careAboutFtyp = false;
        $this->careAboutElst = false;
        $this->careAboutMdhd = false;
    }

    protected function addCLIArguments()
    {
        global $argumentParser;
        $argumentParser->addOption("cmaf", "c", "cmaf", "Enable CMAF checking");
    }

    public function handleArguments()
    {
        global $argumentParser;
        if ($argumentParser->getOption("cmaf")) {
            $this->enabled = true;
        }
    }

    public function hookBeforeRepresentation()
    {
        parent::hookBeforeRepresentation();
        global $additional_flags;
        $additional_flags .= " -cmaf";
    }

    public function hookRepresentation()
    {
        parent::hookRepresentation();
        $this->checkCMAFTracks();
    }

    private function checkCMAFTracks()
    {
        include 'impl/checkCMAFTracks.php';
    }

    public function hookBeforeAdaptationSet()
    {
        parent::hookBeforeAdaptationSet();
        $this->checkSwitchingSets();
    }

    public function hookAdaptationSet()
    {
        parent::hookAdaptationSet();
        $this->checkCMAFPresentation();
        $this->checkSelectionSet();
        $this->checkAlignedSwitchingSets();
    }

    private function checkCMAFPresentation()
    {
        include 'impl/checkCMAFPresentation.php';
    }

    private function getSelectionSets()
    {
        return include 'impl/getSelectionSets.php';
    }

    private function caacMediaProfileConformance($xml)
    {
        return include 'impl/caacMediaProfileConformance.php';
    }

    private function cfhdMediaProfileConformance($xml)
    {
        return include 'impl/cfhdMediaProfileConformance.php';
    }

    private function checkSelectionSet()
    {
        include 'impl/checkSelectionSet.php';
    }

    private function checkAlignedSwitchingSets()
    {
        include 'impl/checkAlignedSwitchingSets.php';
    }

    private function checkCMAFMessages($representationDirectory)
    {
        return include 'impl/checkCMAFMessages.php';
    }

    private function getAudioTrackMediaProfile($mpParameters)
    {
        return include 'impl/getAudioTrackMediaProfile.php';
    }

    private function getSubtitleTrackMediaProfile($mpParameters)
    {
        return include 'impl/getSubtitleTrackMediaProfile.php';
    }

    private function getVideoTrackMediaProfile($mpParameters)
    {
        return include 'impl/getVideoTrackMediaProfile.php';
    }

    private function determineCMAFMediaProfiles($xml)
    {
        return include 'impl/determineCMAFMediaProfiles.php';
    }

    private function determineVideoProfileValidity(
        $mpParameters,
        $section,
        $tableSection,
        $validColorPrimaries,
        $validTransferCharacteristics,
        $validMatrixCoefficients,
        $maxHeight,
        $maxWidth,
        $maxFrameRate
    ) {
        return include 'impl/determineVideoProfileValidity.php';
    }

    private function createString()
    {
        return include 'impl/createString.php';
    }

    private function validateFileBrands($brand1, $brand2)
    {
        include 'impl/validateFileBrands.php';
    }

    private function getIds($node)
    {
        return include 'impl/getIds.php';
    }

    private function compare($xml1, $xml2, $id1, $id2, $currAdaptationDir, $index, $path)
    {
        include 'impl/compare.php';
    }

    private function getNALArray($hvcC, $type)
    {
        return include 'impl/getNalArray.php';
    }

    private function getNALUnit($nalArray)
    {
        return include 'impl/getNalUnit.php';
    }

    private function checkMediaProfiles($representation1, $representation2)
    {
        include 'impl/checkMediaProfiles.php';
    }

    private function checkHeaders($xml1, $xml2, $id1, $id2, $currentAdaptionDir, $index, $path)
    {
        include 'impl/checkHeaders.php';
    }

    private function compareHevc($xml1, $xml2, $id1, $id2)
    {
        include 'impl/compareHevc.php';
    }

    private function compareRest($xml1, $xml2, $id1, $id2)
    {
        include 'impl/compareRest.php';
    }

    private function checkSwitchingSets()
    {
        include 'impl/checkSwitchingSets.php';
    }
}

$modules[] = new ModuleCMAF();
