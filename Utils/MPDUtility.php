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

function isAbsoluteURL($URL)
{
    $parsedUrl = parse_url($URL);
    return $parsedUrl['scheme'] && $parsedUrl['host'];
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
    global $mpdHandler;
    return profileListContainsProfile($mpdHandler->getDom()->getAttribute('profiles'), $profile);
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
    global $mpdHandler;
    return profileListContainsAtLeastOne($mpdHandler->getDom()->getAttribute('profiles'), $profile);
}

function mediaTypes()
{
   return include 'impl/MPDUtility/mediaTypes.php';
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

   function timeParsing($var)
    {
        return include 'impl/MPDUtility/timeParsing.php';
    }


function checkYearMonth($str)
{
    $y = str_replace("P", "", $str);

    if (strpos($y, 'Y') !== false) { // Year
        $Y = explode("Y", $y);

        $y = substr($y, strpos($y, 'Y') + 1);
    } else {
        $Y[0] = 0;
    }

    if (strpos($y, 'M') !== false && strpos($y, 'M') < strpos($y, 'T')) { // Month
        $Mo = explode("M", $y);
        $y = substr($y, strpos($y, 'M') + 1);
    }

    $duration = ($Y[0] * 365 * 24 * 60 * 60) +
                ($Mo[0] * 30 * 24 * 60 * 60);

    return ($duration > 0);
}

function mergeSegmentAccess($highLevel, $lowLevel)
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

function formSegmentAccess($highLevel, $lowLevel)
{
    foreach ($highLevel as $key => $highValue) {
        $lowValue = $lowLevel[$key];
        foreach ($highValue as $k => $v) {
            if (!$lowValue[$k]) {
                $lowValue[$k] = $v;
            } elseif (gettype($lowValue[$k]) == 'array') {
              //$v would also work, but this is more clear in meaning
                $lowValue[$k] = formSegmentAccess($highValue[$k], $lowValue[$k]);
            }
        }
    }
}
