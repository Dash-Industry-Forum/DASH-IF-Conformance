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

include 'CTAWAVE_Handle.php';
include 'CTAWAVE_SelectionSet.php';
include 'CTAWAVE_PresentationProfile.php';

$ctawave_function_name = 'CTAWAVE_Handle';
$ctawave_when_to_call = array( 'Tracks','AdaptationSet');
$CTAselectionset_infofile = 'SelectionSet_infofile_ctawave';
$CTApresentation_infofile = 'Presentation_infofile_ctawave';

function CTAFlags(){
    global $additional_flags;
    $additional_flags .= ' -ctawave';
}