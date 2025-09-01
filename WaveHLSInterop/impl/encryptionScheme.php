<?php

global $logger;

$protectionInformation = $representation->getProtectionScheme();

if ($protectionInformation == null) {
  //Assume this track is not protected
    return;
}
if (!$protectionInformation->encryption->isEncrypted) {
  //This track is not protected
    return;
}

$encryptionInfo = $protectionInformation->encryption;
$schemeInfo = $protectionInformation->scheme;


$spec = "CTA-5005-A";
$section = "4.3.2 - Encrypted Media Presentations";
$auxiliaryInformationMessage = 'Sample auxiliary information, if present, SHALL be addressed by a CMAF " .
  "conforming SampleAuxiliaryInformationOffsetsBox (‘saio’).';
$singleIvMessage = 'Any individual CMAF Segment SHALL have a single encryption key and Initialization Vector.';


$auxiliaryInformation = $representation->getSampleAuxiliaryInformation();

$logger->test(
    $spec,
    $section,
    $auxiliaryInformationMessage,
    $auxiliaryInformation != null,
    "PASS",
    "`saio` box found, assuming it is the only auxiliary information for " . $representation->getPrintable(),
    "`saio` box not found, assuming no auxiliary information is available for " . $representation->getPrintable()
);

$psshInformation = $representation->getPsshBoxes();
$sencInformation = $representation->getSencBoxes();

if ($psshInformation == null || $sencInformation == null || count($psshInformation) != count($sencInformation)) {
    $logger->test(
        $spec,
        $section,
        $singleIvMessage,
        false,
        "PASS",
        "",
        "Unable to read segment encryption data for " . $representation->getPrintable()
    );
    return;
}

//NOTE: Revisit once per-segment analysis is implemented
//  For now we can't detect segment boundaries, so this code checks the requirement on fragment level.
//  Also, since multiple KID are allowed to refer to the same actual key, this requirement may be too strict

$fragmentIndex = 0;
foreach ($psshInformation as $pssh) {
    $logger->test(
        $spec,
        $section,
        $singleIvMessage,
        count($pssh->keys) == 1,
        "FAIL",
        "Found a single key in pssh for fragment $fragmentIndex for " . $representation->getPrintable(),
        "Found " . count($pssh->keys) . " keys in pssh for fragment $fragmentIndex for " .
          $representation->getPrintable()
    );
    $fragmentIndex++;
}

$fragmentIndex = 0;
foreach ($sencInformation as $senc) {
    $ivSize = 0;
    foreach ($senc->ivSizes as $size) {
        $ivSize += $size;
    }

    $logger->test(
        $spec,
        $section,
        $singleIvMessage,
        $ivSize == 0,
        "FAIL",
        "None of the samples in fragment $fragmentIndex contains an individual IV for " .
          $representation->getPrintable(),
        "At least one of the samples in fragment $fragmentIndex contains an individual IV for " .
          $representation->getPrintable()
    );
    $fragmentIndex++;
}
