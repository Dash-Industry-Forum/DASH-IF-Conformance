<?php

namespace DASHIF;

class ModuleCTAWAVE extends ModuleInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->name = "CTA-WAVE";
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
        return CTAFlags();
    }

    public function hookBeforeAdaptationSet()
    {
        CTASelectionSet();
        $this->CTACheckPresentation();
    }

    public function hookPeriod()
    {
        return CTABaselineSpliceChecks();
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

    private function checkSequentialSwSetMediaProfile()
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

    private function getPresentationProfile($encryptedTrackFound, $cencSwSetFound, $cbcsSwSetFound)
    {
        return include 'impl/getPresentationProfile.php';
    }
}

$modules[] = new ModuleCTAWAVE();
