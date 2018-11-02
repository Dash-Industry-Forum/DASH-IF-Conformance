<?php
declare(strict_types=1);
require_once '../CTAWAVE_SelectionSet.php';
include(dirname(__FILE__)."/../../Utils/Load.php");

use PHPUnit\Framework\TestCase;

final class SelectionSetTest extends TestCase
{
    //Test that no WAVE Tracks are found in the SwSet of the SelSet.
    //Two non WAVE tracks are present in the SwSet of this SelSet.
    public function testNoWAVETracksInSelSet()
    { 
        $adapts_count=1;
        $session_dir="Selection_set_examples/SelSetVideoNoWaveTracks";
        $adaptation_set_template='Adapt$AS$';
        $outfile=fopen("out.txt","w");
        $this->assertContains("no Tracks found conforming to WAVE", CTACheckSelectionSet($adapts_count,$session_dir,$adaptation_set_template,$outfile));
    }
    //Test that no WAVE SwSet found in the SelSet. SwSet contains one WAVE track and one non-WAVE track.
    public function testNoWAVESwSetInSelSet()
    { 
        $adapts_count=1;
        $session_dir="Selection_set_examples/SelSetVideoNoWaveSwSet";
        $adaptation_set_template='Adapt$AS$';
        $outfile=fopen("out.txt","w");
        $this->assertContains("no Switching Set found conforming to WAVE", CTACheckSelectionSet($adapts_count,$session_dir,$adaptation_set_template,$outfile));
    }
    //

    //Test that a WAVE SwSet is found in the SelSet. The SwSet contains two Wave tracks. 
    public function testWAVESwSetInSelSet()
    { 
        $adapts_count=1;
        $session_dir="Selection_set_examples/SelSetVideoWaveSwSet";
        $adaptation_set_template='Adapt$AS$';
        $outfile=fopen("out.txt","w");
        $this->assertNotContains("no Switching Set found conforming to WAVE", CTACheckSelectionSet($adapts_count,$session_dir,$adaptation_set_template,$outfile));
    }
}
