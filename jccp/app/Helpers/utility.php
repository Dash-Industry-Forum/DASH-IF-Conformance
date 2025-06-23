<?php

if (!function_exists('cli_or_session')) {
    function cli_or_session(): string
    {
        if (session('artisan', false)) {
            return 'CLI';
        }
        return session()->id();
    }
}

if (!function_exists('session_dir')) {
    function session_dir(): string
    {
        $sessionDir = "/tmp/" . cli_or_session() . "/";
        if (!file_exists($sessionDir)) {
            mkdir($sessionDir, 0777, true);
        }
        return $sessionDir;
    }
}

if (!function_exists('invalidate_mpd_cache')) {
    function invalidate_mpd_cache(): void
    {
        Cache::forget(cache_path(['validator','output']));
        Cache::forget(cache_path(['resolvedmpd']));
        Cache::forget(cache_path(['mpd','url']));
        Cache::forget(cache_path(['mpd','contents']));
    }

}

if (!function_exists('cache_path')) {

    /**
     * @param array<string> $path;
     **/
    function cache_path(array $path): string
    {
        return cli_or_session() . '::' . implode("::", $path);
    }
}

if (!function_exists('period_cache_path')) {

    function period_cache_path(int $periodIndex): string
    {
        return cache_path(['mpd','period',strval($periodIndex)]);
    }
}

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
                    continue;
                }
                if (gettype($lowValue[$k]) == 'array') {
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
    function timeParsing(string $input): int
    {
        $remainder = str_replace("P", "", $input);
        if (strpos($remainder, 'Y') !== false) { // Year
            $year = intval(explode("Y", $remainder)[0]);
            $remainder = substr($remainder, strpos($remainder, 'Y') + 1);
        } else {
            $year = 0;
        }

        if (strpos($remainder, 'M') !== false && strpos($remainder, 'M') < strpos($remainder, 'T')) { // Month
            $month = intval(explode("M", $remainder)[0]);
            $remainder = substr($remainder, strpos($remainder, 'M') + 1);
        } else {
            $month = 0;
        }

        if (strpos($remainder, 'W') !== false) { // Week
            $week = intval(explode("W", $remainder)[0]);
            $remainder = substr($remainder, strpos($remainder, 'W') + 1);
        } else {
            $week = 0;
        }

        if (strpos($remainder, 'D') !== false) { // Day
            $day = intval(explode("D", $remainder)[0]);
            $remainder = substr($remainder, strpos($remainder, 'D') + 1);
        } else {
            $day = 0;
        }

        $remainder = str_replace("T", "", $remainder);
        if (strpos($remainder, 'H') !== false) { // Hour
            $hours = intval(explode("H", $remainder)[0]);
            $remainder = substr($remainder, strpos($remainder, 'H') + 1);
        } else {
            $hours = 0;
        }

        if (strpos($remainder, 'M') !== false) { // Minute
            $minutes = intval(explode("M", $remainder)[0]);
            $remainder = substr($remainder, strpos($remainder, 'M') + 1);
        } else {
            $minutes = 0;
        }

        $seconds = intval(explode("S", $remainder)[0]);

        ///\todo This function does not take into account leap years. There has to be a better way for this?
        return ($year * 365 * 24 * 60 * 60) +
        ($month * 30 * 24 * 60 * 60) +
        ($week * 7 * 24 * 60 * 60) +
        ($day * 24 * 60 * 60) +
        ($hours * 60 * 60) +
        ($minutes * 60) +
        $seconds;
    }
}
