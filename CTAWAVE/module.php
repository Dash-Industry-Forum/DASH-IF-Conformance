<?php

namespace DASHIF;

class ModuleCTAWAVE extends ModuleInterface
{
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
        $argumentParser->addOption("ctawave", "w", "ctawave", "Enable CTAWAVE checking");
    }

    public function handleArguments()
    {
        global $argumentParser;
        if ($argumentParser->getOption("ctawave")) {
            $this->enabled = true;
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
        return include 'impl/checkCMFHDBaselineConstraints.php';
    }

    private function checkSequentialSwitchingSetAV()
    {
        return include 'impl/checkSequentialSwitchingSetAv.php';
    }

    private function CTABaselineSpliceChecks()
    {
        include 'impl/ctaBaselineSpliceChecks.php';
    }

    private function checkSequentialSwitchingSetMediaProfile()
    {
        return include 'impl/checkSequentialSwitchingSetMediaProfile.php';
    }

    private function checkDiscontinuousSplicePoints()
    {
        return include 'impl/checkDiscontinousSplicePoints.php';
    }

    private function checkEncryptionChangeSplicePoint()
    {
        return include 'impl/checkEncryptionChangeSplicePoint.php';
    }

    private function getEncrytionScheme($xml)
    {
        return include 'impl/getEncryptionScheme.php';
    }

    private function checkSampleEntryChangeSplicePoint()
    {
        return include 'impl/checkSampleEntryChangeSplicePoint.php';
    }

    private function getSdType($xml)
    {
        return include 'impl/getSdType.php';
    }

    private function checkDefaultKIDChangeSplicePoint()
    {
        return include 'impl/checkDefaultKIDChangeSplicePoint.php';
    }

    private function checkTrackIDChangeSplicePoint()
    {
        return include 'impl/checkTrackIDChangeSplicePoint.php';
    }

    private function checkTimeScaleChangeSplicePoint()
    {
        return include 'impl/checkTimeScaleChangeSplicePoint.php';
    }

    private function checkFragmentOverlapSplicePoint()
    {
        return include 'impl/checkFragmentOverlapSplicePoint.php';
    }

    private function checkPictureAspectRatioSplicePoint()
    {
        return include 'impl/checkPictureAspectRatioSlicePoint.php';
    }

    private function checkFrameRateSplicePoint()
    {
        return include 'impl/checkFrameRateSplicePoint.php';
    }

    private function getFrameRate($xml)
    {
        return include 'impl/getFrameRate.php';
    }

    private function checkAudioChannelSplicePoint()
    {
        return include 'impl/checkAudioChannelSplicePoint.php';
    }

    private function CTACheckPresentation()
    {
        include 'impl/CTACheckPresentation.php';
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
}

$modules[] = new ModuleCTAWAVE();
