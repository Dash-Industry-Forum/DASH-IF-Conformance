<?php

namespace DASHIF;

require_once __DIR__ . '/../Utils/moduleInterface.php';

class ModuleCTAWAVE extends ModuleInterface
{
    private $mediaProfileAttributesAudio;
    private $mediaProfileAttributesVideo;
    private $mediaProfileAttributesSubtitle;
    private $presentationProfile;

    public function __construct()
    {
        parent::__construct();
        $this->name = "CTA-WAVE";

        $this->mediaProfileAttributesVideo = array(
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
        "brand" => "");

        $this->mediaProfileAttributesAudio = array(
        "codec" => "",
        "profile" => "",
        "level" => "",
        "channels" => "",
        "sampleRate" => "",
        "brand" => "");

        $this->mediaProfileAttributesSubtitle = array(
        "codec" => "",
        "mimeType" => "",
        "mimeSubtype" => "",
        "brand" => "");
    }

    protected function addCLIArguments()
    {
        global $argumentParser;
        if ($argumentParser) {
            $argumentParser->addOption("ctawave", "w", "ctawave", "Enable CTAWAVE checking");
        }
    }

    public function handleArguments()
    {
        global $argumentParser;
        if ($argumentParser->getOption("ctawave")) {
            $this->enabled = true;
        }
    }

    public function detectFromManifest()
    {
        global $mpdHandler;
        $mpdProfiles = $mpdHandler->getDOM()->getAttribute('profiles');
        if (strpos($mpdProfiles, 'urn:cta:wave:test-content-media-profile') !== false) {
            $this->enabled = true;
            $this->detected = true;
        }
    }

    public function hookBeforeRepresentation()
    {
        parent::hookBeforeRepresentation();
        global $additional_flags;
        $additional_flags .= ' -ctawave';
    }

    public function hookBeforeAdaptationSet()
    {
        parent::hookBeforeAdaptationSet();
    }

    public function hookPeriod()
    {
        parent::hookPeriod();
$this->checkSequentialSwitchingSetMediaProfile();
$this->checkDiscontinuousSplicePoints();
$this->checkEncryptionChangeSplicePoint();


$this->checkSequentialSwitchingSetAV();


    }

    private function waveProgramChecks()
    {
        include 'impl/waveProgramChecks.php';
    }


    private function checkSequentialSwitchingSetAV()
    {
        include 'impl/checkSequentialSwitchingSetAv.php';
    }

    private function checkSequentialSwitchingSetMediaProfile()
    {
        include 'impl/checkSequentialSwitchingSetMediaProfile.php';
    }

    private function checkDiscontinuousSplicePoints()
    {
        include 'impl/checkDiscontinousSplicePoints.php';
    }

    private function checkEncryptionChangeSplicePoint()
    {
        include 'impl/checkEncryptionChangeSplicePoint.php';
    }


    private function checkFragmentOverlapSplicePoint()
    {
        include 'impl/checkFragmentOverlapSplicePoint.php';
    }


    private function fourCCEquivalent($mediaProfile)
    {
        return include 'impl/fourCCEquivalent.php';
    }

    private function checkAndGetConformingSubtitleProfile(
        $mediaProfileParameters,
        $representationIndex,
        $adaptationIndex
    ) {
        return include 'impl/checkAndGetConformingSubtitleProfile.php';
    }

    private function getMediaProfile($xml, $hdlrType, $representationIndex, $adaptationIndex)
    {
        return include 'impl/getMediaProfile.php';
    }
}

$modules[] = new ModuleCTAWAVE();
