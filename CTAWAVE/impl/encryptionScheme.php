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
$section = "4.3.2 - Constraints on CMAF Authoring for Manifest Interoperability";
$schemeExplanation = "The common encryption `cbcs` scheme SHALL be used for encryption.";
$ivExplanation = "Constant 16-byte Initialization Vectors SHALL be used.";
$patternExplanation = "When not stated by a codec media profile, the encrypt:skip pattern SHALL be ";
$patternExplanationVideo = $patternExplanation . "1:9 for video copmonents";
$patternExplanationAudio = $patternExplanation . "10:0 for audio copmonents";
$auxiliaryInformationMessage = 'Sample auxiliary information, if present, SHALL be addressed by a CMAF " .
  "conforming SampleAuxiliaryInformationOffsetsBox (‘saio’).';

$logger->test(
    $spec,
    $section,
    $schemeExplanation,
    $schemeInfo->schemeType == "cbcs",
    "FAIL",
    "`cbcs` encryption scheme found " . $representation->getPrintable(),
    "`$schemeInfo->schemeType` encryption scheme found for " . $representation->getPrintable()
);

$logger->test(
    $spec,
    $section,
    $ivExplanation,
    $encryptionInfo->ivSize == 16 && $encryptionInfo->iv != '',
    "FAIL",
    "A constant 16-byte IV found for " . $representation->getPrintable(),
    "No valid constant IV found for " . $representation->getPrintable()
);

$encryptPatternValid = false;
$patternMessage = '';

if ($representation->getHandlerType() == 'vide') {
    $encryptPatternValid = $encryptionInfo->cryptByteBlock > 0 &&
    ($encryptionInfo->cryptByteBlock * 9 == $encryptionInfo->skipByteBlock);
    $patternMessage = $patternExplanationVideo;
}

if ($representation->getHandlerType() == 'soun') {
    $encryptPatternValid = $encryptionInfo->cryptByteBlock > 0 && $encryptionInfo->skipByteBlock == 0;

    $patternMessage = $patternExplanationAudio;
}

if ($patternMessage != '') {
    $logger->test(
        $spec,
        $section,
        $patternMessage,
        $encryptPatternValid,
        "FAIL",
        "Valid pattern detected for " . $representation->getPrintable(),
        "Invalid pattern detected for " . $representation->getPrintable()
    );
}

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
