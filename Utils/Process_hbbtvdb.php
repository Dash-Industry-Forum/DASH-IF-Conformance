#!/usr/bin/php7.4
<?php
$db = file_get_contents("/home/dsi/conformance_output/hbbtv/hbbtv.json");
$db_json = json_decode($db);
foreach ($db_json as $item) {
    $folder = $item;
    $file_name = substr($folder, strrpos($folder, '_') + 1);
    $command = 'php Process_cli_disk.php -H http://localhost/hbbtv/sources/' .$folder.'/'.$file_name.'.mpd';
    shell_exec($command);
}
?>