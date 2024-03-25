<?php

global $logger;

$spec = "CTA-5005-A";
$section = "4.6.2 - Rotation of Encryption Keys";
$baseExplanation = "Each CMAF Segment whose encryption key differs from the default_KID declared in the original " .
  "CMAF Header Track Encryption Box (‘tenc’) box SHALL provide: ";
$sgpdExplanation = $baseExplanation . "A Sample Group Description Box (‘sgpd’) containing a " .
  "CencSampleEncryptionInformationGroupEntry (‘seig’) sample group description structure that describes the new " .
  "encryption key information.";
$sbgpExplanation = $baseExplanation . "A Sample To Group Box (‘sbgp’) that maps the samples of the fragment to " .
  "the CencSampleEncryptionInformationGroupEntry (‘seig’) containing the new encryption key information.";
$psshExplanation = $baseExplanation . "If also present in the original CMAF Header, Protection System Specific " .
  "Header Boxes (‘pssh’) containing the new encryption key information for each expected DRM system.";

$assumptionSpec = "DASH-IF Conformance internal assumptions";
$assumptionSection = "Conformance internal assumption validations";
$assumptionExplanationSgbp = "Count of `sgbp` is equal to count of `sgpd`";
$assumptionExplanationPSSH = "Count of `pssh` is a valid multiple of either `spgd` count or `spgd` count + 1";

$seigDescriptionGroups = $representation->getSeigDescriptionGroups();
$seigDescriptionCount = count($seigDescriptionGroups);
$sampleGroups = $representation->getSampleGroups();
$sampleGroupCount = count($sampleGroups);

$psshBoxes = $representation->getPsshBoxes();
$psshCount = count($psshBoxes);

if ($seigDescriptionCount == 0) {
  //No sgpd found, assuming no key rotation
    return;
}

$firstKid = '';
$hasKeyChanges = false;
foreach ($seigDescriptionGroups as $seigDescription) {
    if ($firstKid == '') {
        $firstKid = $seigDescription->kid;
    }
    if ($seigDescription->kid != $firstKid) {
        $hasKeyChanges = true;
    }
}
if (!$hasKeyChanges) {
  //No key rotation found, assuming everything else is okay
    return;
}


//NOTE: Revisit when we have segment-level analysis
//  Some assumptions hold for this code:
//    - `sgpd` is assumed to signal a possible change in encryption key
//    - `sbgp` is assumed to hold an equal count
//    - `pssh` is assumed to either have (a multiple of) an equal count or one extra for each system in the header box.
//      - If the count is equal no validation will be done
//      - If an extra `pssh` box is found validation will be performed on all but the first.



$logger->test(
    $internalAssumption,
    $assumptionSection,
    $assumptionExplanationSgbp,
    $seigDescriptionCount == $sampleGroupCount,
    "WARN",
    "Internal check valid for " . $representation->getPrintable(),
    "Internal check invalid for " . $representation->getPrintable() .
      ", messages regarding $spec - $section might be invalid"
);



foreach ($sampleGroups as $sampleGroup) {
    foreach ($sampleGroup->groupDescriptionIndices as $idx) {
        $logger->test(
            $spec,
            $section,
            $sbgpExplanation,
            $idx != 0,
            "FAIL",
            "`sgbp` entry maps to an entry in the `sgpd` for " . $representation->getPrintable(),
            "`sgbp` does notmap to an yentry in the `sgpd` for " . $representation->getPrintable(),
        );
    }
}

$checkPssh = false;
$validPssh = false;
if ($psshCount % $seigDescriptionCount == 0) {
    $validPssh = true;
}
if ($psshCount % ($seigDescriptionCount + 1) == 0) {
    $validPssh = true;
    $checkPssh = true;
}


$logger->test(
    $internalAssumption,
    $assumptionSection,
    $assumptionExplanationPSSH,
    $validPssh,
    "WARN",
    "Internal check valid for " . $representation->getPrintable(),
    "Internal check invalid for " . $representation->getPrintable() .
      ", messages regarding $spec - $section might be invalid"
);

if (!$checkPssh) {
  //we are done
    return;
}


$psshBoxesGrouped = array();

for ($i = 0; $i < $psshCount; $i += $seigDescriptionCount) {
    $psshGroup = array();
    for ($j = 0; $j < $seigDescriptionCount; $j++) {
        $psshBoxesToCheck[] = $psshBoxes[$i + $j];
    }
    $psshBoxesGrouped[] = $psshGroup();
}

$first = true;
foreach ($psshBoxesGrouped as $psshGroup) {
    if ($first) {
        $first = false;
        continue;
    }

    foreach ($psshBoxesGrouped[0] as $expectedPssh) {
        $foundExpected = false;
        foreach ($psshGroup as $actualPssh) {
            if ($actualPssh->systemId == $expectedPssh->systemId) {
                $foundExpected = true;
                break;
            }
        }
        $logger->test(
            $spec,
            $section,
            $psshExplanation,
            $validPssh,
            "FAIL",
            "Expected systemid " . $expectedPssh->systemId . " found in group for " . $representation->getPrintable(),
            "Expected systemid " . $expectedPssh->systemId . " not found in group for " .
              $representation->getPrintable(),
        );
    }
}
