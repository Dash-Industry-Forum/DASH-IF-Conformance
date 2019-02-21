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

include 'CMAFHandle.php';
include 'CMAFTracksValidation.php';
include 'CMAFSwitchingSetsValidation.php';
include 'CMAFPresentationValidation.php';

$cmaf_function_name = 'CMAF_handle';
$cmaf_when_to_call = array('BeforeRepresentation', 'Representation', 'AdaptationSet', 'All');

$cmaf_mediaProfiles = array();
$infofile_template = 'infofile$Number$.txt';
$compinfo_file = 'Adapt$AS$_compInfo';
$comparison_folder = 'comparisonResults/';
$presentation_infofile = 'Presentation_infofile';
$selectionset_infofile = 'SelectionSet_infofile';
$alignedswitching_infofile= 'AlignedSwitchingSet_infofile';