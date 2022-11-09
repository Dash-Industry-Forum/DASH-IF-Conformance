<?php
/* This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);
require_once __DIR__.'/../CTAWAVE_SelectionSet.php';
require_once __DIR__.'/../../Utils/Load.php';

use PHPUnit\Framework\TestCase;

final class SelectionSetTest extends TestCase
{
    //Test that no WAVE Tracks are found in the SwSet of the SelSet.
    //Two non WAVE tracks are present in the SwSet of this SelSet.
    public function testNoWAVETracksInSelSet()
    { 
        $adapts_count=1;
        $session_dir="./CTAWAVE/UnitTests/Selection_set_examples/SelSetVideoNoWaveTracks";
        $adaptation_set_template='Adapt$AS$';
        $outfile=fopen("out.txt","w");
        $current_period=0;
        $this->assertStringContainsString("no Tracks found conforming to WAVE", CTACheckSelectionSet($adapts_count,$session_dir,$adaptation_set_template,$outfile,$current_period));
    }
    //Test that no WAVE SwSet found in the SelSet. SwSet contains one WAVE track and one non-WAVE track.
    public function testNoWAVESwSetInSelSet()
    { 
        $adapts_count=1;
        $session_dir="./CTAWAVE/UnitTests/Selection_set_examples/SelSetVideoNoWaveSwSet";
        $adaptation_set_template='Adapt$AS$';
        $outfile=fopen("out.txt","w");
        $current_period=0;
        $this->assertStringContainsString("no Switching Set found conforming to WAVE", CTACheckSelectionSet($adapts_count,$session_dir,$adaptation_set_template,$outfile,$current_period));
    }
    //

    //Test that a WAVE SwSet is found in the SelSet. The SwSet contains two Wave tracks. 
    public function testWAVESwSetInSelSet()
    { 
        $adapts_count=1;
        $session_dir="./CTAWAVE/UnitTests/Selection_set_examples/SelSetVideoWaveSwSet";
        $adaptation_set_template='Adapt$AS$';
        $outfile=fopen("out.txt","w");
        $current_period=0;
        $this->assertStringNotContainsString("no Switching Set found conforming to WAVE", CTACheckSelectionSet($adapts_count,$session_dir,$adaptation_set_template,$outfile,$current_period));
    }
    
    public function testSingleInitSwSet()
    {
        $adapts_count=1;
        $session_dir="./CTAWAVE/UnitTests/Selection_set_examples/SwSetSingleInit";
        $adaptation_set_template='Adapt$AS$';
        $this->assertStringContainsString("reinitialization not req on Track switches', and found CMAF common header", CTACheckSingleInitSwSet($adapts_count,$session_dir,$adaptation_set_template));
    }
    public function testNoSingleInitSwSet()
    {
        $adapts_count=1;
        $session_dir="./CTAWAVE/UnitTests/Selection_set_examples/SelSetVideoWaveSwSet";
        $adaptation_set_template='Adapt$AS$';
        $this->assertStringNotContainsString("reinitialization not req on Track switches', and found CMAF common header", CTACheckSingleInitSwSet($adapts_count,$session_dir,$adaptation_set_template));
    }
}
