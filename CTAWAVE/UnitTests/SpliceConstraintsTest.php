<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

declare(strict_types=1);
require_once '../BaselineSpliceChecks.php';
include(dirname(__FILE__)."/../../Utils/Load.php");

use PHPUnit\Framework\TestCase;

final class SpliceConstraintsTest extends TestCase
{
    public function testNoSameMediaProfile()
    {
        $MediaProfDatabase[0][0][0]="HD";
        $MediaProfDatabase[1][0][0]="HHD10";
        $this->assertContains("Sequential Switching Sets SHALL conform to the same CMAF Media Profile, voilated for Sw set", checkSequentialSwSetMProfile($MediaProfDatabase));
    }
    public function testSameMediaProfile()
    {
        $MediaProfDatabase[0][0][0]="HD";
        $MediaProfDatabase[1][0][0]="HD";
        $this->assertNotContains("Sequential Switching Sets SHALL conform to the same CMAF Media Profile, voilated for Sw set", checkSequentialSwSetMProfile($MediaProfDatabase));
    }
    
    public function testNoDiscontinuitySplice()
    {
        $MediaProfDatabase[0][0][0]="HD";
        $MediaProfDatabase[1][0][0]="HD";
        $session_dir="";
        $this->assertContains("Sequential Switching Sets can be discontinuous, and it is observed", checkDiscontinuousSplicePoints($session_dir,$MediaProfDatabase));
    }
    public function testDiscontinuitySplice()
    {
        $MediaProfDatabase[0][0][0]="HD";
        $MediaProfDatabase[1][0][0]="HD";
        $session_dir="";
        $this->assertNotContains("Sequential Switching Sets can be discontinuous, and it is observed", checkDiscontinuousSplicePoints($session_dir,$MediaProfDatabase));
    }
    
    public function testNoEncryptionChangeSplice()
    {
        $MediaProfDatabase[0][0][0]="HD";
        $MediaProfDatabase[1][0][0]="HD";
        $session_dir="";
        $this->assertContains("Sequential Switching Sets can be discontinuous, and it is observed", checkDiscontinuousSplicePoints($session_dir,$MediaProfDatabase));
    }
    public function testEncryptionChangeSplice()
    {
        $MediaProfDatabase[0][0][0]="HD";
        $MediaProfDatabase[1][0][0]="HD";
        $session_dir="";
        $this->assertNotContains("Sequential Switching Sets can be discontinuous, and it is observed", checkDiscontinuousSplicePoints($session_dir,$MediaProfDatabase));
    }
    
    public function testNoSampleEntryChangeSplice()
    {
        $MediaProfDatabase[0][0][0]="HD";
        $MediaProfDatabase[1][0][0]="HD";
        $session_dir="";
        $this->assertContains("Sequential Switching Sets can be discontinuous, and it is observed", checkDiscontinuousSplicePoints($session_dir,$MediaProfDatabase));
    }
    public function testSampleEntryChangeSplice()
    {
        $MediaProfDatabase[0][0][0]="HD";
        $MediaProfDatabase[1][0][0]="HD";
        $session_dir="";
        $this->assertNotContains("Sequential Switching Sets can be discontinuous, and it is observed", checkDiscontinuousSplicePoints($session_dir,$MediaProfDatabase));
    }
    
    public function testNoDefaultKIDChangeSplice()
    {
        $MediaProfDatabase[0][0][0]="HD";
        $MediaProfDatabase[1][0][0]="HD";
        $session_dir="";
        $this->assertContains("Sequential Switching Sets can be discontinuous, and it is observed", checkDiscontinuousSplicePoints($session_dir,$MediaProfDatabase));
    }
    public function testDefaultKIDChangeSplice()
    {
        $MediaProfDatabase[0][0][0]="HD";
        $MediaProfDatabase[1][0][0]="HD";
        $session_dir="";
        $this->assertNotContains("Sequential Switching Sets can be discontinuous, and it is observed", checkDiscontinuousSplicePoints($session_dir,$MediaProfDatabase));
    }
    
    public function testNoTrackIDChangeSplice()
    {
        $MediaProfDatabase[0][0][0]="HD";
        $MediaProfDatabase[1][0][0]="HD";
        $session_dir="";
        $this->assertNotContains("Sequential Switching Sets can be discontinuous, and it is observed", checkDiscontinuousSplicePoints($session_dir,$MediaProfDatabase));
    }
    public function testTrackIDChangeSplice()
    {
        $MediaProfDatabase[0][0][0]="HD";
        $MediaProfDatabase[1][0][0]="HD";
        $session_dir="";
        $this->assertNotContains("Sequential Switching Sets can be discontinuous, and it is observed", checkDiscontinuousSplicePoints($session_dir,$MediaProfDatabase));
    }
    
    public function testNoTimescaleChangeSplice()
    {
        $MediaProfDatabase[0][0][0]="HD";
        $MediaProfDatabase[1][0][0]="HD";
        $session_dir="";
        $this->assertNotContains("Sequential Switching Sets can be discontinuous, and it is observed", checkDiscontinuousSplicePoints($session_dir,$MediaProfDatabase));
    }
    public function testTimescaleChangeSplice()
    {
        $MediaProfDatabase[0][0][0]="HD";
        $MediaProfDatabase[1][0][0]="HD";
        $session_dir="";
        $this->assertNotContains("Sequential Switching Sets can be discontinuous, and it is observed", checkDiscontinuousSplicePoints($session_dir,$MediaProfDatabase));
    }
    
    public function testNoPictureAspectRatioChangeSplice()
    {
        $MediaProfDatabase[0][0][0]="HD";
        $MediaProfDatabase[1][0][0]="HD";
        $session_dir="";
        $this->assertNotContains("Sequential Switching Sets can be discontinuous, and it is observed", checkDiscontinuousSplicePoints($session_dir,$MediaProfDatabase));
    }
    public function testPictureAspectRatioChangeSplice()
    {
        $MediaProfDatabase[0][0][0]="HD";
        $MediaProfDatabase[1][0][0]="HD";
        $session_dir="";
        $this->assertNotContains("Sequential Switching Sets can be discontinuous, and it is observed", checkDiscontinuousSplicePoints($session_dir,$MediaProfDatabase));
    }
    public function testNoFrameRateChangeSplice()
    {
        $MediaProfDatabase[0][0][0]="HD";
        $MediaProfDatabase[1][0][0]="HD";
        $session_dir="";
        $this->assertNotContains("Sequential Switching Sets can be discontinuous, and it is observed", checkDiscontinuousSplicePoints($session_dir,$MediaProfDatabase));
    }
    public function testFrameRateChangeSplice()
    {
        $MediaProfDatabase[0][0][0]="HD";
        $MediaProfDatabase[1][0][0]="HD";
        $session_dir="";
        $this->assertNotContains("Sequential Switching Sets can be discontinuous, and it is observed", checkDiscontinuousSplicePoints($session_dir,$MediaProfDatabase));
    }
   
      public function testNoAudioChannelChangeSplice()
    {
        $MediaProfDatabase[0][0][0]="HD";
        $MediaProfDatabase[1][0][0]="HD";
        $session_dir="";
        $this->assertNotContains("Sequential Switching Sets can be discontinuous, and it is observed", checkDiscontinuousSplicePoints($session_dir,$MediaProfDatabase));
    }
    public function testAudioChannelChangeSplice()
    {
        $MediaProfDatabase[0][0][0]="HD";
        $MediaProfDatabase[1][0][0]="HD";
        $session_dir="";
        $this->assertNotContains("Sequential Switching Sets can be discontinuous, and it is observed", checkDiscontinuousSplicePoints($session_dir,$MediaProfDatabase));
    }
}