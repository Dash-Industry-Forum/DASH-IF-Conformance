<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
declare(strict_types=1);
require_once '../CTAWAVE_Programs.php';
require_once '../CTAWAVE_PresentationProfile.php';
include(dirname(__FILE__)."/../../../Utils/Load.php");

use PHPUnit\Framework\TestCase;

final class ProgramsTest extends TestCase
{
    public function testNoSequentialVideoSwSet()
    {
        $MediaProfDatabase[0][0][0]="HD";
        $MediaProfDatabase[1][0][0]="HD";
        $session_dir="Splice_examples/Fail_case";
        $adaptation_set_template='Adapt$AS$';
        $reprsentation_template = 'Adapt$AS$rep$R$';
        $this->assertContains("contained in Sequential Sw Sets', overlap/gap in presenation time (non-sequential) is observed for Sw set",
                checkSequentialSwSetAV($session_dir,$MediaProfDatabase, $adaptation_set_template,$reprsentation_template));
    }
    
    public function testSequentialVideoSwSet()
    {
        $MediaProfDatabase[0][0][0]="HD";
        $MediaProfDatabase[1][0][0]="HD";
        $session_dir="Splice_examples/Pass_case";
        $adaptation_set_template='Adapt$AS$';
        $reprsentation_template = 'Adapt$AS$rep$R$';
        $this->assertNotContains("contained in Sequential Sw Sets', overlap/gap in presenation time (non-sequential) is observed for Sw set",
                checkSequentialSwSetAV($session_dir,$MediaProfDatabase, $adaptation_set_template,$reprsentation_template));
    }
    
    public function testCMFHDBaselineFailWAVEBaseline()
    {
        $MediaProfDatabase[0][0][0]="HD";
        $MediaProfDatabase[1][0][0]="HD";
        $session_dir="Splice_examples/Fail_case";
        $adaptation_set_template='Adapt$AS$';
        $reprsentation_template = 'Adapt$AS$rep$R$';
        $spliceConstraitsLog="SampleErrorLog";
        $this->assertContains("contain splices conforming to WAVE Baseline Splice profile (section 7.2)', but violation observed in WAVE Baseline",
                checkCMFHDBaselineConstraints($MediaProfDatabase, $session_dir,$adaptation_set_template,$spliceConstraitsLog));
    }
    
    public function testCMFHDBaselineFailCMFHDPresentation()
    {
        $MediaProfDatabase[0][0][0]="HD";
        $MediaProfDatabase[1][0][0]="HD";
        $session_dir="Splice_examples/Fail_case";
        $adaptation_set_template='Adapt$AS$';
        $reprsentation_template = 'Adapt$AS$rep$R$';
        $spliceConstraitsLog="SampleErrorLog";
        $this->assertContains("more CMAF Presentations conforming to CMAF CMFHD profile', violated as not all CMAF presentations conforms to CMFHD",
                checkCMFHDBaselineConstraints($MediaProfDatabase, $session_dir,$adaptation_set_template,$spliceConstraitsLog));
    }
    public function testCMFHDBaselinePass()
    {
        $MediaProfDatabase[0][0][0]="HD";
        $MediaProfDatabase[1][0][0]="HD";
        $session_dir="Splice_examples/CMFHDBaseline";
        $adaptation_set_template='Adapt$AS$';
        $reprsentation_template = 'Adapt$AS$rep$R$';
        $spliceConstraitsLog="SampleNoErrorLog";
        $this->assertNotContains("more CMAF Presentations conforming to CMAF CMFHD profile', violated as not all CMAF presentations conforms to CMFHD",
                checkCMFHDBaselineConstraints($MediaProfDatabase, $session_dir,$adaptation_set_template,$spliceConstraitsLog));
        $this->assertNotContains("contain splices conforming to WAVE Baseline Splice profile (section 7.2)', but violation observed in WAVE Baseline",
                checkCMFHDBaselineConstraints($MediaProfDatabase, $session_dir,$adaptation_set_template,$spliceConstraitsLog));
    }
    
}