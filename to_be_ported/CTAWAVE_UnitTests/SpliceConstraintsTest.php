<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

declare(strict_types=1);

require_once __DIR__ . '/../CTAWAVE_BaselineSpliceChecks.php';
require_once __DIR__ . '/../../Utils/Load.php';

use PHPUnit\Framework\TestCase;

final class SpliceConstraintsTest extends TestCase
{
    public function testNoSameMediaProfile()
    {
        $MediaProfDatabase[0][0][0] = "HD";
        $MediaProfDatabase[1][0][0] = "HHD10";
        $this->assertStringContainsString("Sequential Switching Sets SHALL conform to the same CMAF Media Profile, voilated for Sw set", checkSequentialSwSetMProfile($MediaProfDatabase));
    }
    public function testSameMediaProfile()
    {
        $MediaProfDatabase[0][0][0] = "HD";
        $MediaProfDatabase[1][0][0] = "HD";
        $this->assertStringNotContainsString("Sequential Switching Sets SHALL conform to the same CMAF Media Profile, voilated for Sw set", checkSequentialSwSetMProfile($MediaProfDatabase));
    }

    public function testNoDiscontinuitySplice()
    {
        $MediaProfDatabase[0][0][0] = "HD";
        $MediaProfDatabase[1][0][0] = "HD";
        $session_dir = "./CTAWAVE/UnitTests/Splice_examples/Pass_case";
        $adaptation_set_template = 'Adapt$AS$';
        $reprsentation_template = 'Adapt$AS$rep$R$';
        $this->assertStringNotContainsString("Sequential Switching Sets can be discontinuous, and it is observed", checkDiscontinuousSplicePoints($session_dir, $MediaProfDatabase, $adaptation_set_template, $reprsentation_template));
    }
    public function testDiscontinuitySplice()
    {
        $MediaProfDatabase[0][0][0] = "HD";
        $MediaProfDatabase[1][0][0] = "HD";
        $session_dir = "./CTAWAVE/UnitTests/Splice_examples/Fail_case";
        $adaptation_set_template = 'Adapt$AS$';
        $reprsentation_template = 'Adapt$AS$rep$R$';
        $this->assertStringContainsString("Sequential Switching Sets can be discontinuous, and it is observed", checkDiscontinuousSplicePoints($session_dir, $MediaProfDatabase, $adaptation_set_template, $reprsentation_template));
    }

    public function testNoEncryptionChangeSplice()
    {
        $MediaProfDatabase[0][0][0] = "HD";
        $MediaProfDatabase[1][0][0] = "HD";
        $session_dir = "./CTAWAVE/UnitTests/Splice_examples/Pass_case";
        $adaptation_set_template = 'Adapt$AS$';
        $reprsentation_template = 'Adapt$AS$rep$R$';
        $this->assertStringNotContainsString("WAVE content SHALL contain one CENC Scheme per program', violated between", checkEncryptionChangeSplicePoint($session_dir, $MediaProfDatabase, $adaptation_set_template, $reprsentation_template));
    }
    public function testEncryptionChangeSplice()
    {
        $MediaProfDatabase[0][0][0] = "HD";
        $MediaProfDatabase[1][0][0] = "HD";
        $session_dir = "./CTAWAVE/UnitTests/Splice_examples/Fail_case";
        $adaptation_set_template = 'Adapt$AS$';
        $reprsentation_template = 'Adapt$AS$rep$R$';
        $this->assertStringContainsString("WAVE content SHALL contain one CENC Scheme per program', violated between", checkEncryptionChangeSplicePoint($session_dir, $MediaProfDatabase, $adaptation_set_template, $reprsentation_template));
    }

    public function testNoSampleEntryChangeSplice()
    {
        $MediaProfDatabase[0][0][0] = "HD";
        $MediaProfDatabase[1][0][0] = "HD";
        $session_dir = "./CTAWAVE/UnitTests/Splice_examples/Pass_case";
        $adaptation_set_template = 'Adapt$AS$';
        $reprsentation_template = 'Adapt$AS$rep$R$';
        $this->assertStringNotContainsString("Sequential Switching Sets Shall not change sample type at Splice points', but different sample types", checkSampleEntryChangeSplicePoint($session_dir, $MediaProfDatabase, $adaptation_set_template, $reprsentation_template));
    }
    public function testSampleEntryChangeSplice()
    {
        $MediaProfDatabase[0][0][0] = "HD";
        $MediaProfDatabase[1][0][0] = "HD";
        $session_dir = "./CTAWAVE/UnitTests/Splice_examples/Fail_case";
        $adaptation_set_template = 'Adapt$AS$';
        $reprsentation_template = 'Adapt$AS$rep$R$';
        $this->assertStringContainsString("Sequential Switching Sets Shall not change sample type at Splice points', but different sample types", checkSampleEntryChangeSplicePoint($session_dir, $MediaProfDatabase, $adaptation_set_template, $reprsentation_template));
    }

    public function testNoDefaultKIDChangeSplice()
    {
        $MediaProfDatabase[0][0][0] = "HD";
        $MediaProfDatabase[1][0][0] = "HD";
        $session_dir = "./CTAWAVE/UnitTests/Splice_examples/Pass_case";
        $adaptation_set_template = 'Adapt$AS$';
        $reprsentation_template = 'Adapt$AS$rep$R$';
        $this->assertStringNotContainsString("Default KID can change at Splice points', change is observed for ", checkDefaultKIDChangeSplicePoint($session_dir, $MediaProfDatabase, $adaptation_set_template, $reprsentation_template));
    }
    public function testDefaultKIDChangeSplice()
    {
        $MediaProfDatabase[0][0][0] = "HD";
        $MediaProfDatabase[1][0][0] = "HD";
        $session_dir = "./CTAWAVE/UnitTests/Splice_examples/Fail_case";
        $adaptation_set_template = 'Adapt$AS$';
        $reprsentation_template = 'Adapt$AS$rep$R$';
        $this->assertStringContainsString("Default KID can change at Splice points', change is observed for ", checkDefaultKIDChangeSplicePoint($session_dir, $MediaProfDatabase, $adaptation_set_template, $reprsentation_template));
    }

    public function testNoTrackIDChangeSplice()
    {
        $MediaProfDatabase[0][0][0] = "HD";
        $MediaProfDatabase[1][0][0] = "HD";
        $session_dir = "./CTAWAVE/UnitTests/Splice_examples/Pass_case";
        $adaptation_set_template = 'Adapt$AS$';
        $reprsentation_template = 'Adapt$AS$rep$R$';
        $this->assertStringNotContainsString("Track_ID can change at Splice points', change is observed ", checkTrackIDChangeSplicePoint($session_dir, $MediaProfDatabase, $adaptation_set_template, $reprsentation_template));
    }
    public function testTrackIDChangeSplice()
    {
        $MediaProfDatabase[0][0][0] = "HD";
        $MediaProfDatabase[1][0][0] = "HD";
        $session_dir = "./CTAWAVE/UnitTests/Splice_examples/Fail_case";
        $adaptation_set_template = 'Adapt$AS$';
        $reprsentation_template = 'Adapt$AS$rep$R$';
        $this->assertStringContainsString("Track_ID can change at Splice points', change is observed ", checkTrackIDChangeSplicePoint($session_dir, $MediaProfDatabase, $adaptation_set_template, $reprsentation_template));
    }

    public function testNoTimescaleChangeSplice()
    {
        $MediaProfDatabase[0][0][0] = "HD";
        $MediaProfDatabase[1][0][0] = "HD";
        $session_dir = "./CTAWAVE/UnitTests/Splice_examples/Pass_case";
        $adaptation_set_template = 'Adapt$AS$';
        $reprsentation_template = 'Adapt$AS$rep$R$';
        $this->assertStringNotContainsString("Timescale can change at Splice points', change is observed", checkTimeScaleChangeSplicePoint($session_dir, $MediaProfDatabase, $adaptation_set_template, $reprsentation_template));
    }
    public function testTimescaleChangeSplice()
    {
        $MediaProfDatabase[0][0][0] = "HD";
        $MediaProfDatabase[1][0][0] = "HD";
        $session_dir = "./CTAWAVE/UnitTests/Splice_examples/Fail_case";
        $adaptation_set_template = 'Adapt$AS$';
        $reprsentation_template = 'Adapt$AS$rep$R$';
        $this->assertStringContainsString("Timescale can change at Splice points', change is observed", checkTimeScaleChangeSplicePoint($session_dir, $MediaProfDatabase, $adaptation_set_template, $reprsentation_template));
    }

    public function testNoPictureAspectRatioChangeSplice()
    {
        $MediaProfDatabase[0][0][0] = "HD";
        $MediaProfDatabase[1][0][0] = "HD";
        $session_dir = "./CTAWAVE/UnitTests/Splice_examples/Pass_case";
        $adaptation_set_template = 'Adapt$AS$';
        $reprsentation_template = 'Adapt$AS$rep$R$';
        $this->assertStringNotContainsString("Picture Aspect Ratio (PAR) Should be the same between Sequential Sw Sets at the Splice point', violated for ", checkPicAspectRatioSplicePoint($session_dir, $MediaProfDatabase, $adaptation_set_template, $reprsentation_template));
    }
    public function testPictureAspectRatioChangeSplice()
    {
        $MediaProfDatabase[0][0][0] = "HD";
        $MediaProfDatabase[1][0][0] = "HD";
        $session_dir = "./CTAWAVE/UnitTests/Splice_examples/Fail_case";
        $adaptation_set_template = 'Adapt$AS$';
        $reprsentation_template = 'Adapt$AS$rep$R$';
        $this->assertStringContainsString("Picture Aspect Ratio (PAR) Should be the same between Sequential Sw Sets at the Splice point', violated for ", checkPicAspectRatioSplicePoint($session_dir, $MediaProfDatabase, $adaptation_set_template, $reprsentation_template));
    }
    public function testNoFrameRateChangeSplice()
    {
        $MediaProfDatabase[0][0][0] = "HD";
        $MediaProfDatabase[1][0][0] = "HD";
        $session_dir = "./CTAWAVE/UnitTests/Splice_examples/Pass_case";
        $adaptation_set_template = 'Adapt$AS$';
        $reprsentation_template = 'Adapt$AS$rep$R$';
        $this->assertStringNotContainsString("Frame rate Should be the same family of multiples between Sequential Sw Sets at the Splice point', violated for", checkFrameRateSplicePoint($session_dir, $MediaProfDatabase, $adaptation_set_template, $reprsentation_template));
    }
    public function testFrameRateChangeSplice()
    {
        $MediaProfDatabase[0][0][0] = "HD";
        $MediaProfDatabase[1][0][0] = "HD";
        $session_dir = "./CTAWAVE/UnitTests/Splice_examples/Fail_case";
        $adaptation_set_template = 'Adapt$AS$';
        $reprsentation_template = 'Adapt$AS$rep$R$';
        $this->assertStringContainsString("Frame rate Should be the same family of multiples between Sequential Sw Sets at the Splice point', violated for", checkFrameRateSplicePoint($session_dir, $MediaProfDatabase, $adaptation_set_template, $reprsentation_template));
    }

    public function testNoAudioChannelChangeSplice()
    {
        $MediaProfDatabase[0][0][0] = "HD";
        $MediaProfDatabase[1][0][0] = "HD";
        $session_dir = "./CTAWAVE/UnitTests/Splice_examples/Audio/Pass_case";
        $adaptation_set_template = 'Adapt$AS$';
        $reprsentation_template = 'Adapt$AS$rep$R$';
        $this->assertStringNotContainsString("Audio channel configuration Should allow the same stereo or multichannel config between Sequential Sw Sets at the Splice point', violated for", checkAudioChannelSplicePoint($session_dir, $MediaProfDatabase, $adaptation_set_template, $reprsentation_template));
    }
    public function testAudioChannelChangeSplice()
    {
        $MediaProfDatabase[0][0][0] = "HD";
        $MediaProfDatabase[1][0][0] = "HD";
        $session_dir = "./CTAWAVE/UnitTests/Splice_examples/Audio/Fail_case";
        $adaptation_set_template = 'Adapt$AS$';
        $reprsentation_template = 'Adapt$AS$rep$R$';
        $this->assertStringContainsString("Audio channel configuration Should allow the same stereo or multichannel config between Sequential Sw Sets at the Splice point', violated for", checkAudioChannelSplicePoint($session_dir, $MediaProfDatabase, $adaptation_set_template, $reprsentation_template));
    }
    public function testNoFragOverlapSplice()
    {
        $MediaProfDatabase[0][0][0] = "HD";
        $MediaProfDatabase[1][0][0] = "HD";
        $session_dir = "./CTAWAVE/UnitTests/Splice_examples/Pass_case";
        $adaptation_set_template = 'Adapt$AS$';
        $reprsentation_template = 'Adapt$AS$rep$R$';
        $this->assertStringNotContainsString("not overlap the same WAVE Program presentation time at the Splice point', overlap is observed", checkFragrmentOverlapSplicePoint($session_dir, $MediaProfDatabase, $adaptation_set_template, $reprsentation_template));
    }
    public function testFragOverlapSplice()
    {
        $MediaProfDatabase[0][0][0] = "HD";
        $MediaProfDatabase[1][0][0] = "HD";
        $session_dir = "./CTAWAVE/UnitTests/Splice_examples/Fail_case";
        $adaptation_set_template = 'Adapt$AS$';
        $reprsentation_template = 'Adapt$AS$rep$R$';
        $this->assertStringContainsString("not overlap the same WAVE Program presentation time at the Splice point', overlap is observed", checkFragrmentOverlapSplicePoint($session_dir, $MediaProfDatabase, $adaptation_set_template, $reprsentation_template));
    }
}
