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

$file_contents= file_get_contents("change_log.txt");
$verison_pos=strpos($file_contents, "Version");
$eol=strpos($file_contents,PHP_EOL);
$version=substr($file_contents, $verison_pos+8, $eol-($verison_pos+8));
echo $version;