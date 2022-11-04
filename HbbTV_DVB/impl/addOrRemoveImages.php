<?php

global $string_info;

if ($request == 'ADD') {
    $index = strpos($string_info, '</body>');
    $string_info = substr($string_info, 0, $index) . $hbbtv_string_info . substr($string_info, $index);
} elseif ($request == 'REMOVE') {
    $startIndex = strpos($string_info, '<img');
    $endIndex = strpos($string_info, '>', $startIndex);

    while ($startIndex !== false) {
        $string_info = substr($string_info, 0, $startIndex) . substr($string_info, $endIndex + 1);
        $startIndex = strpos($string_info, '<img');
        $endIndex = strpos($string_info, '>', $startIndex);
    }
}
