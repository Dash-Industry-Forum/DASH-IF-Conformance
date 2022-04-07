<?php

namespace DASHIF\Utility;

function profileListContainsProfile($list, $profile)
{
    $profiles = explode(',', $list);
    return in_array($profile, $profiles);
}

function in_array_at_least_one($options, $array)
{
    foreach ($options as $option) {
        if (in_array($option, $array)) {
            return true;
        }
    }
    return false;
}

function inString($option, $string)
{
    return strpos($string, $option) !== false;
}

function inStringAtLeastOne($options, $string)
{
    foreach ($options as $option) {
        if (inString($option, $string)) {
            return true;
        }
    }
    return false;
}

function mpdContainsProfile($profile)
{
    global $mpd_dom;
    return profileListContainsProfile($mpd_dom->getAttribute('profiles'), $profile);
}

function mpdProfilesContainsAll($profiles)
{
    foreach ($profiles as $profile) {
        if (!mpdContainsProfile($profile)) {
            return false;
        }
    }
    return true;
}

function mpdProfilesContainsNone($profiles)
{
    foreach ($profiles as $profile) {
        if (mpdContainsProfile($profile)) {
            return false;
        }
    }
    return true;
}

function profileListContainsAtLeastOne($list, $profiles)
{
    foreach ($profiles as $profile) {
        if (profileListContainsProfile($list, $profile)) {
            return true;
        }
    }
    return false;
}
function mpdProfilesContainsAtLeastOne($profiles)
{
    global $mpd_dom;
    return profileListContainsAtLeastOne($mpd_dom->getAttribute('profiles'), $profile);
}

function media_types()
{
    global $mpd_dom;
    $media_types = array();

    $adapts = $mpd_dom->getElementsByTagName('AdaptationSet');
    $reps = $mpd_dom->getElementsByTagName('Representation');
    $subreps = $mpd_dom->getElementsByTagName('SubRepresentation');

    if ($adapts->length != 0) {
        for ($i = 0; $i < $adapts->length; $i++) {
            $adapt = $adapts->item($i);
            $adapt_contentType = $adapt->getAttribute('contentType');
            $adapt_mimeType = $adapt->getAttribute('mimeType');

            if ($adapt_contentType == 'video' || strpos($adapt_mimeType, 'video') !== false) {
                $media_types[] = 'video';
            }
            if ($adapt_contentType == 'audio' || strpos($adapt_mimeType, 'audio') !== false) {
                $media_types[] = 'audio';
            }
            if ($adapt_contentType == 'text' || strpos($adapt_mimeType, 'application') !== false) {
                $media_types[] = 'subtitle';
            }

            $contentcomps = $adapt->getElementsByTagName('ContentComponent');
            foreach ($contentcomps as $contentcomp) {
                $contentcomp_contentType = $contentcomp->getAttribute('contentType');

                if ($contentcomp_contentType == 'video') {
                    $media_types[] = 'video';
                }
                if ($contentcomp_contentType == 'audio') {
                    $media_types[] = 'audio';
                }
                if ($contentcomp_contentType == 'text') {
                    $media_types[] = 'subtitle';
                }
            }
        }
    }

    if ($reps->length != 0) {
        for ($i = 0; $i < $reps->length; $i++) {
            $rep = $reps->item($i);
            $rep_mimeType = $rep->getAttribute('mimeType');

            if (strpos($rep_mimeType, 'video') !== false) {
                $media_types[] = 'video';
            }
            if (strpos($rep_mimeType, 'audio') !== false) {
                $media_types[] = 'audio';
            }
            if (strpos($rep_mimeType, 'application') !== false) {
                $media_types[] = 'subtitle';
            }
        }
    }

    if ($subreps->length != 0) {
        for ($i = 0; $i < $subreps->length; $i++) {
            $subrep = $subreps->item($i);
            $subrep_mimeType = $subrep->getAttribute('mimeType');

            if (strpos($subrep_mimeType, 'video') !== false) {
                $media_types[] = 'video';
            }
            if (strpos($subrep_mimeType, 'audio') !== false) {
                $media_types[] = 'audio';
            }
            if (strpos($subrep_mimeType, 'application') !== false) {
                $media_types[] = 'subtitle';
            }
        }
    }

    return array_unique($media_types);
}

function recursive_generate($node, &$domDocument, &$domElement, $profile)
{
    foreach ($node->childNodes as $child) {
        if ($child->nodeType == XML_ELEMENT_NODE) {
            if (
                $child->getAttribute('profiles') == '' ||
                strpos($child->getAttribute('profiles'), $profile) !== false
            ) {
                $domchild = $domDocument->createElement($child->nodeName);
                $domchild = $child->cloneNode();

                $domchild = recursive_generate($child, $domDocument, $domchild, $profile);
                $domElement->appendChild($domchild);
            }
        }
    }

    return $domElement;
}

//Helper function to find next sibling. Php function doesn't do what we want
function nextElementSibling($node)
{
    while ($node && ($node = $node->nextSibling)) {
        if ($node instanceof DOMElement) {
            break;
        }
    }
    return $node;
}

function compute_timerange($timeRange)
{
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
                    $val0_valid = (($val0_converted === $val_vals[0]) || ( '0' . $val0_converted) === $val_vals[0]) &&
                      ($val_vals[0] <= PHP_INT_MAX) && ($val_vals[0] >= ~PHP_INT_MAX);

                    $val1_converted = (string) (int) $val_vals[1];
                    $val1_valid = (($val1_converted === $val_vals[1]) || (( '0' . $val1_converted === $val_vals[1])) &&
                      ($val_vals[1] <= PHP_INT_MAX) && ($val_vals[1] >= ~PHP_INT_MAX);

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
                        "Uses W3C Media Fragment format with \"npt\" but fraciton notation is used"
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
            ($val_vals[1] <= PHP_INT_MAX) && ($val_vals[1] >= ~PHP_INT_MAX);

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
}
