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
        $this->CTACheckSelectionSet();
        $this->CTACheckSingleInitSwitchingSet();
        $this->CTACheckPresentation();
    }

    public function hookPeriod()
    {
        parent::hookPeriod();
        return $this->CTABaselineSpliceChecks();
    }

    private function waveProgramChecks()
    {
        include 'impl/waveProgramChecks.php';
    }

    private function checkCMFHDBaselineConstraints()
    {
        include 'impl/checkCMFHDBaselineConstraints.php';
    }

    private function checkSequentialSwitchingSetAV()
    {
        include 'impl/checkSequentialSwitchingSetAv.php';
    }

    private function CTABaselineSpliceChecks()
    {
        include 'impl/ctaBaselineSpliceChecks.php';
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

    private function getEncrytionScheme($xml)
    {
        return include 'impl/getEncryptionScheme.php';
    }

    private function checkSampleEntryChangeSplicePoint()
    {
        include 'impl/checkSampleEntryChangeSplicePoint.php';
    }

    private function getSdType($xml)
    {
        return include 'impl/getSdType.php';
    }

    private function checkDefaultKIDChangeSplicePoint()
    {
        include 'impl/checkDefaultKIDChangeSplicePoint.php';
    }

    private function checkTrackIDChangeSplicePoint()
    {
        include 'impl/checkTrackIDChangeSplicePoint.php';
    }

    private function checkTimeScaleChangeSplicePoint()
    {
        include 'impl/checkTimeScaleChangeSplicePoint.php';
    }

    private function checkFragmentOverlapSplicePoint()
    {
        include 'impl/checkFragmentOverlapSplicePoint.php';
    }

    private function checkPictureAspectRatioSplicePoint()
    {
        include 'impl/checkPictureAspectRatioSlicePoint.php';
    }

    private function checkFrameRateSplicePoint()
    {
        include 'impl/checkFrameRateSplicePoint.php';
    }

    private function getFrameRate($xml)
    {
        return include 'impl/getFrameRate.php';
    }

    private function checkAudioChannelSplicePoint()
    {
        return include 'impl/checkAudioChannelSplicePoint.php';
    }

    private function CTACheckPresentation($adaptationCount = null, $periodIndex = null)
    {
        return include 'impl/CTACheckPresentation.php';
    }
    private function CTACheckSelectionSet()
    {
        include 'impl/CTACheckSelectionSet.php';
    }

    private function getPresentationProfile($encryptedTrackFound, $cencSwSetFound, $cbcsSwSetFound)
    {
        return include 'impl/getPresentationProfile.php';
    }

    private function CTACheckSingleInitSwitchingSet()
    {
        include 'impl/CTACheckSingleInitSwitchingSet.php';
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

    private function checkAndGetConformingAudioProfile(
        $mediaProfileParameters,
        $representationIndex,
        $adaptationIndex
    ) {
        return include 'impl/checkAndGetConformingAudioProfile.php';
    }

    private function checkAndGetConformingVideoProfile(
        $mediaProfileParameters,
        $representationIndex,
        $adaptationIndex
    ) {
        return include 'impl/checkAndGetConformingVideoProfile.php';
    }

    private function getMediaProfile($xml, $hdlrType, $representationIndex, $adaptationIndex)
    {
        return include 'impl/getMediaProfile.php';
    }

    public function textComponentConstraints($representation)
    {
        return include 'impl/textComponentConstraints.php';
    }

    public function addressableMediaObject($representation)
    {
        return include 'impl/addressableMediaObject.php';
    }

    public function bitrate($representation)
    {
        return include 'impl/bitrate.php';
    }

    public function encryptionScheme($representation)
    {
        return include 'impl/encryptionScheme.php';
    }

    public function trackRoles($representation)
    {
        return include 'impl/trackRoles.php';
    }

    public function timedEventData($representation)
    {
        return include 'impl/timedEventData.php';
    }

    public function splicingPoints($representation)
    {
        return include 'impl/splicingPoints.php';
    }
}

$modules[] = new ModuleCTAWAVE();
