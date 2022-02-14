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

/**
 * Retrieve time from an NTP server
 *
 * @param    string   $host   The NTP server to retrieve the time from
 * @return   int      The current unix timestamp
 * @author   Aidan Lister <aidan@php.net>
 * @link     http://aidanlister.com/2010/02/retrieve-time-from-an-ntp-server/
 */
function ntp_time($host) {
  
  // Create a socket and connect to NTP server
  $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
  socket_connect($sock, $host, 123);
  
  // Send request
  $msg = "\010" . str_repeat("\0", 47);
  socket_send($sock, $msg, strlen($msg), 0);
  
  // Receive response and close socket
  socket_recv($sock, $recv, 48, MSG_WAITALL);
  socket_close($sock);

  // Interpret response
  $data = unpack('N12', $recv);
  $timestamp = sprintf('%u', $data[9]);
  
  // NTP is number of seconds since 0000 UT on 1 January 1900
  // Unix time is seconds since 0000 UT on 1 January 1970
  $timestamp -= 2208988800;
  
  return $timestamp;
}


$host = $_REQUEST['host'];
$timestamp = ntp_time($host);
 
//$time = date('F j, Y, g:i a', $timestamp);
echo $timestamp;
?>