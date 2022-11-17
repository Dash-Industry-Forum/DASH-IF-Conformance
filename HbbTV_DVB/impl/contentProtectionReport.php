<?php

global $mpdHandler, $logger, $session;

$UUIDToDRMSystem = array ('urn:mpeg:dash:mp4protection:2011' => 'Generic Identifier 1',
                         'urn:mpeg:dash:13818:1:CA_descriptor:2011' => 'Generic Identifier 2',
                         'urn:uuid:5E629AF538DA4063897797FFBD9902D4' => 'Marlin Adaptive Streaming Specification',
                         'urn:uuid:adb41c242dbf4a6d958b4457c0d27b95' => 'Nagra MediaAccess PRM 3.0',
                         'urn:uuid:A68129D3575B4F1A9CBA3223846CF7C3' => 'Cisco/NDS VideoGuard Everywhere DRM',
                         'urn:uuid:9a04f07998404286ab92e65be0885f95' => 'Microsoft PlayReady',
                         'urn:uuid:9a27dd82fde247258cbc4234aa06ec09' => 'Verimatrix ViewRight Web',
                         'urn:uuid:F239E769EFA348509C16A903C6932EFB' => 'Adobe Primetime',
                         'urn:uuid:1f83e1e86ee94f0dba2f5ec4e3ed1a66' => 'SecureMedia',
                         'urn:uuid:644FE7B5260F4FAD949A0762FFB054B4' => 'CMLA',
                         'urn:uuid:6a99532d869f59229a91113ab7b1e2f3' => 'MobiTV',
                         'urn:uuid:35BF197B530E42D78B651B4BF415070F' => 'DivX ',
                         'urn:uuid:B4413586C58CFFB094A5D4896C1AF6C3' => 'Viaccess-Orca',
                         'urn:uuid:edef8ba979d64acea3c827dcd51d21ed' => 'Widevine',
                         'urn:uuid:80a6be7e14484c379e70d5aebe04c8d2' => 'Irdeto',
                         'urn:uuid:dcf4e3e362f158187ba60a6fe33ff3dd' => 'DigiCAP SmartXess',
                         'urn:uuid:45d481cb8fe049c0ada9ab2d2455b2f2' => 'CoreTrust',
                         'urn:uuid:616C7469636173742D50726F74656374' => 'Alticast altiProtect',
                         'urn:uuid:992c46e6c4374899b6a050fa91ad0e39' => 'SecureMedia SteelKnot',
                         'urn:uuid:1077efecc0b24d02ace33c1e52e2fb4b' => 'W3C',
                         'urn:uuid:e2719d58a985b3c9781ab030af78d30e' => 'Clear Key',
                         'urn:uuid:94CE86FB07FF4F43ADB893D2FA968CA2' => 'Apple FairPlay Streaming',
                         'urn:uuid:279fe473512c48feade8d176fee6b40f' => 'Arris Titanium');

$adaptationIndex = 0;
$keyRotationUsed = false;
foreach ($mpdHandler->getDom()->getElementsByTagName('AdaptationSet') as $adaptationSetNode) {
    $adaptationId = $adaptationIndex + 1;
    $adaptationReport = fopen(
        $session->getAdaptationDir($mpdHandler->getSelectedPeriod(), $adaptationIndex) . '/hbbDvbCross.txt',
        'a+b'
    );
    if ($adaptationReport === false) {
        $adaptationIndex++; //move to check the next adapt set
        continue;
    }

    $MPDSystemIDs = array();
    $missingPSSHs = array(); //holds the uuid-s of the DRM-s which are missing the pssh in the mpd
    $representationKIDs = array(); // for the summary of reps and their KID
    $representationIndex = 0;
    $MPDKIDFlag = false;
    $genericIdentifier = "";
    $contentProtectionFlag = false;
    foreach ($adaptationSetNode->getElementsByTagName('ContentProtection') as $contentProtectionNode) {
        // only if there is a content protection instance the below will be executed
        $contentProtectionFlag = true;
        if (
            ($contentProtectionNode->getAttribute('schemeIdUri') == "urn:mpeg:dash:mp4protection:2011") ||
            ($contentProtectionNode->getAttribute('schemeIdUri') == 'urn:mpeg:dash:13818:1:CA_descriptor:2011')
        ) {
            $genericIdentifier = $contentProtectionNode->getAttribute('schemeIdUri');
        }

        if ((!$MPDKIDFlag) && ($genericIdentifier != "")) { // if a KID was not found in the init seg check in the mpd
            $MPDKID = $contentProtectionNode->getAttribute('cenc:default_KID');
            if ($MPDKID != '') {
                $MPDKID = str_replace('-', '', $MPDKID);
                $MPDKIDFlag = true; //there is a cenc:default_KID, so there must be a pssh in a mpd or init seg
            }
        }

        $cencPSSH = $contentProtectionNode->getAttribute('cenc:pssh');
        if (
            $cencPSSH == '' &&
            $contentProtectionNode->getAttribute('schemeIdUri') != "urn:mpeg:dash:mp4protection:2011" &&
            $contentProtectionNode->getAttribute('schemeIdUri') != 'urn:mpeg:dash:13818:1:CA_descriptor:2011'
        ) {
            $uuid = $contentProtectionNode->getAttribute('schemeIdUri');
            $uuid = str_replace('-', '', $uuid);
            $missingPSSHs[] = $uuid; //the drm uuid which is missing a pssh in the mpd
        }

        //excluding generic identifiers which are usually in the first instance of Content Protection
        if (
            $contentProtectionNode->getAttribute('schemeIdUri') != "urn:mpeg:dash:mp4protection:2011" &&
            $contentProtectionNode->getAttribute('schemeIdUri') != 'urn:mpeg:dash:13818:1:CA_descriptor:2011'
        ) {
            $MPDSystemID = $contentProtectionNode->getAttribute('schemeIdUri');
            $MPDSystemID = str_replace('-', '', $MPDSystemID);

            if (array_key_exists($MPDSystemID, $UUIDToDRMSystem)) {
                $MPDSystemIDs[$MPDSystemID] = $UUIDToDRMSystem[$MPDSystemID];
            } else {
                $MPDSystemIDs[$MPDSystemID] = 'unknown'; //if no matches are found in the mapping array
            }
        }
    }

    /*For an encrypted Adaptation Set, ContentProtection Descriptors shall always be
    present in the AdaptationSet element, and apply to all contained Representations.
    A ContentProtection Descriptor for the mp4 Protection Scheme with the
    @schemeIdUri value of "urn:mpeg:dash:mp4protection:2011" and
    @value=’cenc’ shall be present in the AdaptationSet element if the contained
    Representations are encrypted.*/

    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "Section 'DRM'",
        "Content Protection Usage",
        $contentProtectionFlag,
        "PASS",
        "Content Protection used in Adapatation set $adaptationId",
        "Content Protection not used in Adapatation set $adaptationId"
    );

    if (!$contentProtectionFlag) {
        fclose($adaptationReport);
        $adaptationIndex++; //move to check the next adapt set
        continue;
    }

    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "Section 'DRM'",
        "Content Protection SHALL contain a ContentProtectionDescriptor",
        $genericIdentifier != "",
        "FAIL",
        "ContentProtection Descriptor for the mp4 Protection Scheme with the @schemeIdUri " .
        "value of 'urn:mpeg:dash:mp4protection:2011' and @value=’cenc’ found",
        "ContentProtection Descriptor for the mp4 Protection Scheme with the @schemeIdUri " .
        "value of 'urn:mpeg:dash:mp4protection:2011' and @value=’cenc’ not found"
    );
    if ($genericIdentifier == "") {
        fclose($adaptationReport);
        $adaptationIndex++; //move to check the next adapt set
        continue;
    }

    $MPDSystemValueString  = implode(', ', array_map(
        function ($v, $k) {
            return sprintf(" '%s' :: '%s'", $k, $v);
        },
        $MPDSystemIDs,
        array_keys($MPDSystemIDs)
    ));
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "Section 'DRM'",
        "Informative",
        true,
        "PASS",
        "DRM systems present in the MPD in Adaptation Set $adaptationId  are identified as follows: " .
        "$genericIdentifier :: " . $UUIDToDRMSystem[$genericIdentifier] . " $MPDSystemValueString",
        ""
    );

    $tencKIDs = array();
    foreach ($adaptationSetNode->getElementsByTagName('Representation') as $representationNode) {
        $duplicationFlag = false;
        $inconsistencyFlag = false;
        $missingPSSHFlag = false;
        $PSSHSystemIDs = array(); // to see the DRM uuid in mpd and pssh and compare them
        $tencKIDFlag = false;


        //first rep of the adapt set will have the same pssh as the rest
        ///\RefactorTodo This was definitely pointing to a wrong directory. Probably not intentional.
        $xmlFilePath = $session->getRepresentationDir($mpdHandler->getSelectedPeriod(), $adaptationIndex, $representationIndex) .
          '/atomInfo.xml';
        $abs = get_DOM($xmlFilePath, 'atomlist'); // load mpd from url

        if (!$abs) {
            $representationIndex++;
            continue;
        }

        /*There SHALL be identical values of default_KID in the Track Encryption Box
        (‘tenc’) of all Representation referenced by one Adaptation Set.*/
        $tencKID = '';
        if ($abs->getElementsByTagName('tenc')->length) {
            //default KID can be in the pssh in the init seg
            $tencKID = $abs->getElementsByTagName('tenc')->item(0)->getAttribute('default_KID');
        }
        //Can still be empty if not available in the init segment
        if ($tencKID != '') {
            $tencKIDFlag = true;
            $tencKIDs[] = $tencKID;
        }
        foreach ($abs->getElementsByTagName('pssh') as $psshNode) {
            $PSSHSystemID = 'urn:uuid:' . $psshNode->getAttribute('systemID');
            if (array_key_exists($PSSHSystemID, $UUIDToDRMSystem)) {
                $PSSHSystemIDs[$PSSHSystemID] = $UUIDToDRMSystem[$PSSHSystemID];
            } else {
                $PSSHSystemIDs[$PSSHSystemID] = 'unknown'; //if no matches are found in the mapping array
            }
        }

        if ($tencKIDFlag || $MPDKIDFlag) {
            // if a pssh is missing in the mpd then there must be in the init seg
            // all the nr of instances which are missing the pssh in the mpd must be in the init seg
            if (count($missingPSSHs) && count(array_intersect($missingPSSHs, $PSSHSystemIDs)) != count($missingPSSHs)) {
                //not all the missing pssh are in the init seg
                $missingPSSHFlag = true;
            }
        }

        if (!empty($PSSHSystemIDs)) { //comparing if there's a DRM in pssh since the MPD has at least a generic one
            //flag if in both and show inconsistencies
            //Store the uuid that are in pssh but not mpd
            $SystemIDDifferences = array_diff(array_keys($PSSHSystemIDs), array_keys($MPDSystemIDs));
            if (count(array_intersect(array_keys($PSSHSystemIDs), array_keys($MPDSystemIDs)))) {
                //there is at least one DRM with the same uuid in both
                $duplicationFlag = true;
            } else {
                //the pssh box has at least one DRM uuid which is not in the mpd while all the DRM uuid-s
                //must be in the ContentProtection instance of the MPD, so we have inconsistency
                $inconsistencyFlag = true;
            }
        }

        //add summary for encrypted rep and their kID. use rep id to identify them
        $representationID = $representationNode->getAttribute('id');
        $representationKIDs[$representationID] = $tencKID;

        //checking for key rotation:
        //if there is no pssh in any moof then no key rotation is used
        foreach ($abs->getElementsByTagName('moof') as $moof) {
            //if pssh does't exists and is an empty node
            if ($moof->getElementsByTagName('pssh')->length) {
                if ($moof->getElementsByTagName('sgpd')->length && $moof->getElementsByTagName('sbgp')->length) {
                    $keyRotationUsed = true;
                }
            }
        }

        $foundCenc = false;
        //Check the scheme_type field of the ‘schm’ box has the value ‘cenc’
        if ($abs->getElementsByTagName('schm')->length) {
            if ($abs->getElementsByTagName('schm')->item(0)->getAttribute('scheme') == "cenc") {
                $foundCenc = true;
            }
        }

        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "Section 'DRM'",
            "scheme_type field of the 'schm' box should have the value 'cenc'",
            $foundCenc,
            "PASS",
            "Value 'cenc' found",
            "Either no 'schm' box, or the value is not 'cenc'"
        );

        if (!empty($PSSHSystemIDs)) {
            $PSSHSystemValueString  = implode(', ', array_map(
                function ($v, $k) {
                    return sprintf(" '%s' :: '%s'", $k, $v);
                },
                $PSSHSystemIDs,
                array_keys($PSSHSystemIDs)
            ));

            $logger->test(
                "HbbTV-DVB DASH Validation Requirements",
                "Section 'DRM'",
                "Informative",
                true,
                "PASS",
                "DRM systems present in the PSSH in Adaptation Set $adaptationId, representation $representationID " .
                "are identified by: $PSSHSystemValueString",
                ""
            );
        }

        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "Section 'DRM'",
            "Informative",
            !$missingPSSHFlag,
            "WARN",
            "No missing PSSH Boxes" .
            "in adaptation $adaptationId, representation $representationID",
            "Missing PSSH Boxes, but there is a default_KID: $tencKID" .
            "in adaptation $adaptationId, representation $representationID"
        );

        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "Section 'DRM'",
            "Informative",
            !$duplicationFlag,
            "WARN",
            "No duplicate DRM between MPD and PSSH Boxes " .
            "in adaptation $adaptationId, representation $representationID",
            "Duplicate but consistent DRM between MPD and PSSH Boxes " .
            "in adaptation $adaptationId, representation $representationID"
        );

        $SystemIDDifferencesValueString  = implode(', ', array_map(
            function ($v, $k) {
                return sprintf(" SystemID: '%s' ", $v);
            },
            $SystemIDDifferences,
            array_keys($SystemIDDifferences)
        ));
        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "Section 'DRM'",
            "Informative",
            !$inconsistencyFlag,
            "WARN",
            "No inconsistent DRM between MPD and PSSH Boxes " .
            "in adaptation $adaptationId, representation $representationID",
            "Inconsistent DRM between MPD and PSSH Boxes " .
            "in adaptation $adaptationId, representation $representationID, " .
            "identified by $SystemIDDifferencesValueString"
        );

        $representationIndex++;
    }

    //summary of reps and the KID used
    if ($tencKIDFlag) {
        $representationKeyString  = implode(', ', array_map(
            function ($v, $k) {
                return sprintf(" '%s' ", $k);
            },
            $representationKIDs,
            array_keys($representationKIDs)
        ));

        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "Section 'DRM'",
            "Representations in an Adaptation Set SHALL all have the same 'default_KID'",
            count(array_unique($tencKIDs)) == 1,
            "FAIL",
            "'default_KID' $tencKID is used for representations $representationKeyString" .
            "in adaptation $adaptationId",
            "Found differing 'default_KID' values " .
            "in adaptation $adaptationId"
        );
    }

    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "Section 'DRM'",
        "Informative",
        $keyRotationUsed,
        "PASS",
        "Key rotation used for adaptation set $adaptationId",
        "Key rotation not used for adaptation set $adaptationId"
    );

    fclose($adaptationReport);
    $adaptationIndex++; //move to check the next adapt set
}
