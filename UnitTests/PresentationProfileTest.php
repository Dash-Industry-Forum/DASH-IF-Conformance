<?php
declare(strict_types=1);
require_once '../CTAWAVE_SelectionSet.php';
include(dirname(__FILE__)."/../../Utils/Load.php");

use PHPUnit\Framework\TestCase;

final class PresentationProfileTest extends TestCase
{
  
    public function testCMAFPresentationProfile()
    { 
        $adapts_count=1;
        //The given directory contains a SwSet of video with two tracks conforming to AVC-HD Media profile
        // and hence expected to conform to CMFHD presentation profile.
        $session_dir="Presention_examples/CMFHD_video/";
        $adaptation_set_template='Adapt$AS$';
        $outfile=fopen("out.txt","w");
        $this->assertSame("CMFHD", checkPresentation($adapts_count,$session_dir,$adaptation_set_template,$outfile));
    }

    public function testNoCMAFPresentationProfile()
    { 
        $adapts_count=1;
        //The given directory contains a SwSet of video with two tracks not conforming to any Media profile
        // and hence expected not to conform to CMFHD or any other presentation profiles.
        $session_dir="Presention_examples/noCMFHD_video/";
        $adaptation_set_template='Adapt$AS$';
        $outfile=fopen("out.txt","w");
        //CMFHD presentation profile is not expected, hence check notSame assertion.
        $this->assertNotSame("CMFHD", checkPresentation($adapts_count,$session_dir,$adaptation_set_template,$outfile));
    }



}
