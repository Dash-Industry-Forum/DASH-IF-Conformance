<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
declare(strict_types=1);

require_once __DIR__ . '/../CTAWAVE_Programs.php';
require_once __DIR__ . '/../CTAWAVE_PresentationProfile.php';
require_once __DIR__ . '/../CTAWAVE_SelectionSet.php';
require_once __DIR__ . '/../../Utils/Load.php';

use PHPUnit\Framework\TestCase;

final class ProgramsTest extends TestCase
{
    public function testNoSequentialVideoSwSet()
    {
        $MediaProfDatabase[0][0][0] = "HD";
        $MediaProfDatabase[1][0][0] = "HD";
        $session_dir = "./CTAWAVE/UnitTests/Splice_examples/Fail_case";
        $adaptation_set_template = 'Adapt$AS$';
        $representation_template = 'Adapt$AS$rep$R$';
        $this->assertStringContainsString(
            "contained in Sequential Sw Sets', overlap/gap in presenation time (non-sequential) is observed for Sw set",
            checkSequentialSwSetAV($session_dir, $MediaProfDatabase, $adaptation_set_template, $representation_template)
        );
    }

    public function testSequentialVideoSwSet()
    {
        $MediaProfDatabase[0][0][0] = "HD";
        $MediaProfDatabase[1][0][0] = "HD";
        $session_dir = "./CTAWAVE/UnitTests/Splice_examples/Pass_case";
        $adaptation_set_template = 'Adapt$AS$';
        $representation_template = 'Adapt$AS$rep$R$';
        $this->assertStringNotContainsString(
            "contained in Sequential Sw Sets', overlap/gap in presenation time (non-sequential) is observed for Sw set",
            checkSequentialSwSetAV($session_dir, $MediaProfDatabase, $adaptation_set_template, $representation_template)
        );
    }

    public function testCMFHDBaselineFailWAVEBaseline()
    {
        $MediaProfDatabase[0][0][0] = "HD";
        $MediaProfDatabase[1][0][0] = "HD";
        $session_dir = "./CTAWAVE/UnitTests/Splice_examples/Fail_case";
        $adaptation_set_template = 'Adapt$AS$';
        $representation_template = 'Adapt$AS$rep$R$';
        $spliceConstraitsLog = "SampleErrorLog";
        $this->assertStringContainsString(
            "contain splices conforming to WAVE Baseline Splice profile (section 7.2)', but violation observed in WAVE Baseline",
            checkCMFHDBaselineConstraints($MediaProfDatabase, $session_dir, $adaptation_set_template, $spliceConstraitsLog)
        );
    }

    public function testCMFHDBaselineFailCMFHDPresentation()
    {
        $MediaProfDatabase[0][0][0] = "HD";
        $MediaProfDatabase[1][0][0] = "HD";
        $session_dir = "./CTAWAVE/UnitTests/Splice_examples/Fail_case";
        $adaptation_set_template = 'Adapt$AS$';
        $representation_template = 'Adapt$AS$rep$R$';
        $spliceConstraitsLog = "SampleErrorLog";
        $this->assertStringContainsString(
            "more CMAF Presentations conforming to CMAF CMFHD profile', violated as not all CMAF presentations conforms to CMFHD",
            checkCMFHDBaselineConstraints($MediaProfDatabase, $session_dir, $adaptation_set_template, $spliceConstraitsLog)
        );
    }

    public function testCMFHDBaselinePass()
    {
        $MediaProfDatabase[0][0][0] = "HD";
        $MediaProfDatabase[1][0][0] = "HD";
        $session_dir = "./CTAWAVE/UnitTests/Splice_examples/CMFHDBaseline";
        $adaptation_set_template = 'Adapt$AS$';
        $representation_template = 'Adapt$AS$rep$R$';
        $spliceConstraitsLog = "SampleNoErrorLog";
        $this->assertStringNotContainsString(
            "more CMAF Presentations conforming to CMAF CMFHD profile', violated as not all CMAF presentations conforms to CMFHD",
            checkCMFHDBaselineConstraints($MediaProfDatabase, $session_dir, $adaptation_set_template, $spliceConstraitsLog)
        );
        $this->assertStringNotContainsString(
            "contain splices conforming to WAVE Baseline Splice profile (section 7.2)', but violation observed in WAVE Baseline",
            checkCMFHDBaselineConstraints($MediaProfDatabase, $session_dir, $adaptation_set_template, $spliceConstraitsLog)
        );
    }
}
