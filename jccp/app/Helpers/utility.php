<?php

namespace DASHIF\Utility;

if (!function_exists('syscall')) {
    function sysCall($command)
    {
        $result = '';
        if ($proc = popen("($command)2>&1", "r")) {
            while (!feof($proc)) {
                $result .= fgets($proc, 1000);
            }
            pclose($proc);
        }
        return $result;
    }
}


if (! function_exists('formSegmentAccess')) {
    function formSegmentAccess($highLevel, $lowLevel): array
    {
        foreach ($highLevel as $key => $highValue) {
            $lowValue = array_key_exists($key, $lowLevel) ? $lowLevel[$key] : array();
            foreach ($highValue as $k => $v) {
                if (!array_key_exists($k, $lowValue) || !$lowValue[$k]) {
                    $lowValue[$k] = $v;
                } elseif (gettype($lowValue[$k]) == 'array') {
                    //$v would also work, but this is more clear in meaning
                    $lowValue[$k] = formSegmentAccess($highValue[$k], $lowValue[$k]);
                }
            }
            $lowLevel[$key] = $lowValue;
        }
        return $lowLevel;
    }
}


if (!function_exists('mergeSegmentAccess')) {
    function mergeSegmentAccess($highLevel, $lowLevel): array
    {
        if (!$highLevel && !$lowLevel) {
            return array();
        }
        if (!$highLevel) {
            return $lowLevel;
        }
        if (!$lowLevel) {
            return $highLevel;
        }
        return formSegmentAccess($highLevel, $lowLevel);
    }
}


if (! function_exists('isAbsoluteURL')) {
    function isAbsoluteURL(string $url): bool
    {
        $parsedUrl = parse_url($url);
        return array_key_exists("scheme", $parsedUrl) && array_key_exists("host", $parsedUrl);
    }
}
