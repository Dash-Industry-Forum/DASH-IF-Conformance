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

include 'LowLatency_Handle.php';
include 'LowLatency_MPDValidation.php';
include 'LowLatency_RepresentationValidation.php';
include 'LowLatency_CrossValidation.php';

global $mpd_xml_string;

$low_latency_function_name = 'LL_DASHIF_Handle';
$low_latency_when_to_call = array('MPD', 'AdaptationSet');
$low_latency_cross_validation_file = 'LowLatencyCrossValidation_compInfo';
$mpd_xml_string = xml_string_update($mpd_xml_string, '<dashif_ll>No Result</dashif_ll>', '<');
