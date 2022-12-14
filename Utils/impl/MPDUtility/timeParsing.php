<?php

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
return ($Y[0] * 365 * 24 * 60 * 60) +
       ($Mo[0] * 30 * 24 * 60 * 60) +
       ($W[0] * 7 * 24 * 60 * 60) +
       ($D[0] * 24 * 60 * 60) +
       ($H[0] * 60 * 60) +
       ($M[0] * 60) +
       $S[0]; // calculate durations in seconds
