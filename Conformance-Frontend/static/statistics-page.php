<?php

function entry($diff,$limit,$ip,&$table) {
  if ($diff < $limit) {
    $table[$ip] = isset($table[$ip]) ? $table[$ip]+1 : 1;
  }
}

// hold accesses in the last week, month, or quarter, keyed by IP address
$ip_in_week = array();
$ip_in_month = array();
$ip_in_quarter = array();

$now = date_timestamp_get(date_create());

// parse the access log and get all lines referring to /Utils/Process_cli.php
$output = `sudo /var/www/html/Conformance-Frontend/src/get-access.sh`;

// now go through the log line by line, storing IP vs date
foreach(preg_split("/((\r?\n)|(\r\n?))/", $output) as $line) {
  $ip = preg_replace("/^([^ ]*)\s.*$/", "$1", $line);
  $date = preg_replace("/^.*(\[[^ ]+ [^ ]+\]).*$/","$1", $line); // [16/Jun/2023:10:22:36 +0000]
  $timestamp = DateTime::createFromFormat(
    '[d/M/Y:H:i:s O]',
    $date,
    new DateTimeZone('EST')
  );

  if ($timestamp !== false) {
    $before = $now - $timestamp->getTimestamp();

    define("SEC_IN_DAY",24*60*60);
    entry($before,7*SEC_IN_DAY,$ip,$ip_in_week);
    entry($before,31*SEC_IN_DAY,$ip,$ip_in_month);
    entry($before,(31+31+30)*SEC_IN_DAY,$ip,$ip_in_quarter);
  }
}

echo "<table><tr>accesses<th></th><th>per week</th><th>per month</th><th>per quarter</th></tr>";
echo "<tr><th>unique</th><td>",count($ip_in_week),"</td><td>",count($ip_in_month),"</td><td>",count($ip_in_quarter),"</td>";
echo "<tr><th>total</th><td>",array_sum($ip_in_week),"</td><td>",array_sum($ip_in_month),"</td><td>",array_sum($ip_in_quarter),"</td></table>";

?>
