<?php

if (! function_exists('curlOptions')) {
    function curlOptions(): mixed
    {
        return array(
            CURLOPT_FAILONERROR => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 500,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 0,
            CURLOPT_USERAGENT => "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)",
        );
    }
}

if (! function_exists('formSegmentAccess')) {
    function formSegmentAccess(mixed $highLevel, mixed $lowLevel): mixed
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
    function mergeSegmentAccess(mixed $highLevel, mixed $lowLevel): mixed
    {
        if (!$highLevel && !$lowLevel) {
            return null;
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


if (! function_exists('timeParsing')) {
    function timeParsing(string $var): int
    {
        $y = str_replace("P", "", $var);
        if (strpos($y, 'Y') !== false) { // Year
            $Y = explode("Y", $y);
            $y = substr($y, strpos($y, 'Y') + 1);
        } else {
            $Y[0] = 0;
        }

        if (strpos($y, 'M') !== false && strpos($y, 'M') < strpos($y, 'T')) { // Month
            $Mo = explode("M", $y);
            $y = substr($y, strpos($y, 'M') + 1);
        } else {
            $Mo[0] = 0;
        }

        if (strpos($y, 'W') !== false) { // Week
            $W = explode("W", $y);
            $y = substr($y, strpos($y, 'W') + 1);
        } else {
            $W[0] = 0;
        }

        if (strpos($y, 'D') !== false) { // Day
            $D = explode("D", $y);
            $y = substr($y, strpos($y, 'D') + 1);
        } else {
            $D[0] = 0;
        }

        $y = str_replace("T", "", $y);
        if (strpos($y, 'H') !== false) { // Hour
            $H = explode("H", $y);
            $y = substr($y, strpos($y, 'H') + 1);
        } else {
            $H[0] = 0;
        }

        if (strpos($y, 'M') !== false) { // Minute
            $M = explode("M", $y);
            $y = substr($y, strpos($y, 'M') + 1);
        } else {
            $M[0] = 0;
        }

        $S = explode("S", $y); // Second

///\todo This function does not take into account leap years. There has to be a better way for this?
        return (intval($Y[0]) * 365 * 24 * 60 * 60) +
        (intval($Mo[0]) * 30 * 24 * 60 * 60) +
        (intval($W[0]) * 7 * 24 * 60 * 60) +
        (intval($D[0]) * 24 * 60 * 60) +
        (intval($H[0]) * 60 * 60) +
        (intval($M[0]) * 60) +
        intval($S[0]); // calculate durations in seconds
    }
}
