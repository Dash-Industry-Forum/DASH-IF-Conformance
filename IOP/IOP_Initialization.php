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

include 'IOP_Handle.php';
include 'IOP_MPDValidation.php';
include 'IOP_SegmentValidation.php';
include 'IOP_CrossValidation.php';

global $mpd_xml_string;

$iop_function_name = 'IOP_Handle';
$iop_when_to_call = array('MPD', 'Representation', 'AdaptationSet');
$mpd_xml_string = '<mpdresult><xlink>No Result</xlink><schema>No Result</schema><schematron>No Result</schematron><dashif>No Result</dashif></mpdresult>';