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
require_once __DIR__.'/../CTAWAVE_PresentationProfile.php';
require_once __DIR__.'/../../Utils/Load.php';

use PHPUnit\Framework\TestCase;

final class PresentationProfileTest extends TestCase
{
  
    public function testCMAFPresentationProfile()
    { 
        $adapts_count=1;
        //The given directory contains a SwSet of video with two tracks conforming to AVC-HD Media profile
        // and hence expected to conform to CMFHD presentation profile.
        $session_dir="./CTAWAVE/UnitTests/Presention_examples/CMFHD_video/";
        $adaptation_set_template='Adapt$AS$';
        $outfile=fopen("out.txt","w");
        $current_period=0;
        $this->assertSame("CMFHD", CTACheckPresentation($adapts_count,$session_dir,$adaptation_set_template,$outfile,$current_period));
    }

    public function testNoCMAFPresentationProfile()
    { 
        $adapts_count=1;
        //The given directory contains a SwSet of video with two tracks not conforming to any Media profile
        // and hence expected not to conform to CMFHD or any other presentation profiles.
        $session_dir="Presention_examples/noCMFHD_video/";
        $adaptation_set_template='Adapt$AS$';
        $outfile=fopen("out.txt","w");
        $current_period=0;
        //CMFHD presentation profile is not expected, hence check notSame assertion.
        $this->assertNotSame("CMFHD", CTACheckPresentation($adapts_count,$session_dir,$adaptation_set_template,$outfile,$current_period));
    }



}
