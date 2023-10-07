<?php

global $logger;
$first = 1;
foreach ($timeRange as $timestamp) {
    $t = 0;

    if ($timestamp == '') {
        if (!$first) {
            $t = PHP_INT_MAX;
        }
    } elseif ($timestamp == 'now') {
        $t = time();
    } elseif (strpos($timestamp, ':') !== false) {
        $vals = explode(':', $timestamp);

        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "DVB DASH Specifics",
            "Value for key \"t\" should be in {npt-ss, npt-mmss, npt-hhmmss}",
            sizeof($vals) <= 3,
            "WARN",
            "Valid value",
            "Invalid value found: $timestamp"
        );

        for ($v = sizeof($vals) - 1,$p = 0; $v > 0; $v--,$p++) {
            $val = $vals[$v];

            if (strpos($val, '.')) {
                $val_vals = explode('.', $val);
                $t += ($val_vals[0] * pow(60, $p) + ($val_vals[1] / 10) * pow(60, $p));

                $val0_converted = (string) (int) $val_vals[0];
                $val0_valid = (($val0_converted === $val_vals[0]) || (( '0' . $val0_converted) === $val_vals[0]) &&
                  ($val_vals[0] <= PHP_INT_MAX) && ($val_vals[0] >= ~PHP_INT_MAX));

                $val1_converted = (string) (int) $val_vals[1];
                $val1_valid = (($val1_converted === $val_vals[1]) || (( '0' . $val1_converted) === $val_vals[1]) &&
                  ($val_vals[1] <= PHP_INT_MAX) && ($val_vals[1] >= ~PHP_INT_MAX));

                $logger->test(
                    "HbbTV-DVB DASH Validation Requirements",
                    "DVB DASH Specifics",
                    "Value for key \"t\" should be an integer",
                    $val0_valid && $val1_valid,
                    "WARN",
                    "Valid value",
                    "Invalid value found: $timestamp"
                );

                $logger->test(
                    "HbbTV-DVB DASH Validation Requirements",
                    "DVB DASH Specifics",
                    "Requirements for value of key \"t\"",
                    $p == 0,
                    "WARN",
                    "Valid value",
                    "Uses W3C Media Fragment format with \"npt\" but fraction notation is used"
                );

                $logger->test(
                    "HbbTV-DVB DASH Validation Requirements",
                    "DVB DASH Specifics",
                    "Requirements for value of key \"t\"",
                    sizeof($vals) >= 3 && $v == (sizeof($vals) - 1) && $val_vals[0] >= 0 && $val_vals[0] <= 59,
                    "WARN",
                    "Valid value",
                    "Uses W3C Media Fragment format with \"npt\" but range for minutes and/or seconds is not " .
                    "in the range of [0,59]"
                );
            } else {
                $t += ($val * pow(60, $p));

                $logger->test(
                    "HbbTV-DVB DASH Validation Requirements",
                    "DVB DASH Specifics",
                    "Requirements for value of key \"t\"",
                    sizeof($vals) >= 3 && $v == (sizeof($vals) - 1) && $val_vals[0] >= 0 && $val_vals[0] <= 59,
                    "WARN",
                    "Valid value",
                    "Uses W3C Media Fragment format with \"npt\" but range for minutes and/or seconds is not " .
                    "in the range of [0,59]"
                );
            }
        }
    } elseif (strpos($timestamp, '.') !== false) { // Fractional time
        $vals = explode('.', $timestamp);
        $t += $vals[0] + $vals[1] / 10;

        $val0_converted = (string) (int) $val_vals[0];
        $val0_valid = (($val0_converted === $val_vals[0]) || ( '0' . $val0_converted) === $val_vals[0]) &&
        ($val_vals[0] <= PHP_INT_MAX) && ($val_vals[0] >= ~PHP_INT_MAX);

        $val1_converted = (string) (int) $val_vals[1];
        $val1_valid = (($val1_converted === $val_vals[1]) || (( '0' . $val1_converted === $val_vals[1])) &&
        ($val_vals[1] <= PHP_INT_MAX) && ($val_vals[1] >= ~PHP_INT_MAX));

        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "DVB DASH Specifics",
            "Value for key \"t\" should be an integer",
            $val0_valid && $val1_valid,
            "WARN",
            "Valid value",
            "Invalid value found: $timestamp"
        );
    } else {
        $t += $timestamp;
        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "DVB DASH Specifics",
            "Value for key \"t\" should be an integer",
            ((string) (int) $timestamp === $timestamp) && ($timestamp <= PHP_INT_MAX) &&
            ($timestamp >= ~PHP_INT_MAX),
            "WARN",
            "Valid value",
            "Invalid value found: $timestamp not an integer"
        );
    }

    if ($first) {
        $first = 0;
        $t_start = $t;
    } else {
        $t_end = $t;
    }
}

if (sizeof($time_range) == 1) {
    $t_end = PHP_INT_MAX;
}

$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB DASH Specifics",
    "Value for key \"t\" should be an integer",
    $t_end >= $t_start,
    "WARN",
    "Valid value",
    "Invalid value found: start time $t_start is larger than end $t_end"
);

return [$t_start, $t_end];
