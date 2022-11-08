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

function delete_schema($dash_schema_location)
{
    global $session_id;
    if ($dash_schema_location === "schemas/DASH-MPD_CustomSchema_$session_id.xsd") {
        shell_exec("sudo rm $dash_schema_location");
    }
}

function extract_relevant_text($result)
{
    $needle = 'Start XLink resolving';
    $temp_result = str_replace('[java]', "", $result);

    return substr($temp_result, strpos($temp_result, $needle));
}

