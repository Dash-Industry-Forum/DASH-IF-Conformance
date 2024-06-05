<?php

global $logger, $mpdHandler;

$kindBoxes = $representation->getKindBoxes();

if ($kindBoxes == null) {
    return;
}

$mpdRoles = $mpdHandler->getRoles($representation->periodIndex, $representation->adaptationIndex);

$spec = "CTA-5005-A";
$section = "4.7.2 - Carriage of Track Role";
$roleExplanation = "Track roles SHALL be stored in one or more KindBox (‘kind’) within the " .
  "UserDataBox (‘udta’) of the TrackBox (‘trak’) in the CMAF Header.";
$dashRoleExplanation = "Track roles SHALL be represented by the DASH Role scheme ([DASH] 5.8.5.5) when possible.";

foreach ($kindBoxes as $kindBox) {
    $found = false;
    foreach ($mpdRoles as $mpdRole) {
        if (
            $mpdRole['schemeIdUri'] == $kindBox->schemeURI &&
            $mpdRole['value'] == $kindBox->value
        ) {
            $found = true;
            break;
        }
    }
    $logger->test(
        $spec,
        $section,
        $roleExplanation,
        $found,
        "WARN",
        "Role $kindBox->schemeURI :: $kindBox->value found in mpd for " . $representation->getPrintable(),
        "Role $kindBox->schemeURI :: $kindBox->value not found in mpd for " . $representation->getPrintable(),
    );
}

foreach ($mpdRoles as $mpdRole) {
    $found = false;
    foreach ($kindBoxes as $kindBox) {
        if (
            $mpdRole['schemeIdUri'] == $kindBox->schemeURI &&
            $mpdRole['value'] == $kindBox->value
        ) {
            $found = true;
            break;
        }
    }
    $logger->test(
        $spec,
        $section,
        $roleExplanation,
        $found,
        "WARN",
        "MPD Role " . $mpdRole['schemeIdUri'] . "::" . $mpdRole['value'] . " found in `udta` for " .
          $representation->getPrintable(),
        "MPD Role " . $mpdRole['schemeIdUri'] . "::" . $mpdRole['value'] . " not found in `udta` for " .
          $representation->getPrintable(),
    );
}

foreach ($kindBoxes as $kindBox) {
    $logger->test(
        $spec,
        $section,
        $dashRoleExplanation,
        $kindBox->schemeURI == "urn:mpeg:dash:role:2011",
        "WARN",
        "Role $kindBox->value has the correct namespace for " . $representation->getPrintable(),
        "Role $kindBox->value has an incorrect namespace ($kindBox->schemeURI) for " .
          $representation->getPrintable()
    );
}
