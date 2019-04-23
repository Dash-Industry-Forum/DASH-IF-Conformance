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

ini_set('memory_limit', '-1');
error_reporting(E_ERROR | E_PARSE);

include 'Session.php';
include 'Load.php';
include 'FileOperations.php';
include 'VisitorCounter.php';
include 'GlobalVariables.php';
include 'PrettyPrint.php';
include 'SegmentDownload.php';
include 'SegmentAssemble.php';
include 'SegmentValidation.php';
include '../DASH/MPDProcessing.php';
include '../DASH/MPDFeatures.php';
include '../DASH/MPDValidation.php';
include '../DASH/MPDInfo.php';
include '../DASH/SchematronIssuesAnalyzer.php';
include '../DASH/CrossValidation.php';
include '../DASH/Representation.php';
include '../DASH/SegmentURLs.php';
include '../HLS/HLSProcessing.php';
include '../Conformance-Frontend/Featurelist.php';

set_time_limit(0);
ini_set("log_errors", 1);
ini_set("error_log", "myphp-error.log");

$session_id = json_decode($_POST['sessionid']);
session_name($session_id);
session_start();
error_log("session_start:" . session_name());

session_create();
if(!$hls_manifest)
    process_MPD();
else
    processHLS();