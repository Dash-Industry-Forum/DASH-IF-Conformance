<?php

namespace App\Modules\DVB\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\AdaptationSet;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes\DescriptionType;
use App\Interfaces\Module;
use App\Services\SegmentManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ContentProtection
{
    //Private subreporters
    private SubReporter $v141Reporter;

    private TestCase $genericIdentifierCase;
    private TestCase $psshCase;
    private TestCase $systemInformationCase;
    private TestCase $cencCase;

    /**
     * @var array<string> $psshMissingFromMPD
     **/
    private array $psshMissingFromMPD = [];
    /**
     * @var array<string> $mpdSystems
     **/
    private array $mpdSystems = [];

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->v141Reporter = &$reporter->context(new ReporterContext(
            "CrossValidation",
            "Legacy",
            "DVB",
            []
        ));

        $this->genericIdentifierCase = $this->v141Reporter->add(
            section: 'DRM',
            test: "Content Protection SHALL contain a ContentProtectionDescriptor",
            skipReason: 'No protected stream found'
        );
        $this->psshCase = $this->v141Reporter->add(
            section: 'DRM',
            test: "PSSH Boxes SHOULD exist in either the MPD or the Initialization Segment",
            skipReason: 'No protected stream found'
        );
        $this->systemInformationCase = $this->v141Reporter->add(
            section: 'DRM',
            test: "MPD DRM System information",
            skipReason: 'No protected stream found'
        );
        $this->cencCase = $this->v141Reporter->add(
            section: 'DRM',
            test: "The scheme SHOULD be set to 'cenc'",
            skipReason: "No protected stream or no 'schm' box found"
        );
    }

    //Public validation functions
    public function validateContentProtection(AdaptationSet $adaptationSet): void
    {
        $contentProtectionNodes = $adaptationSet->getDOMElements('ContentProtection');
        if (count($contentProtectionNodes) == 0) {
            return;
        }
        $this->validateGenericIdentifier($adaptationSet, $contentProtectionNodes);

        $this->determineMissingPSSH($adaptationSet, $contentProtectionNodes);
        $this->determineMPDSystems($adaptationSet, $contentProtectionNodes);

        $segmentManager = app(SegmentManager::class);
        foreach ($adaptationSet->allRepresentations() as $representation) {
            $segmentList = $segmentManager->representationSegments($representation);
            if (count($segmentList)) {
                $this->validateInitializationSegment($representation, $segmentList[0]);
            }
        }
    }

    //Private helper functions
    private function validateInitializationSegment(Representation $representation, Segment $segment): void
    {
        $psshSystems = [];
        $psshBoxes = $segment->getPSSHBoxes();
        if (count($psshBoxes) == 0) {
            $this->psshCase->pathAdd(
                result: count($this->psshMissingFromMPD) == 0,
                severity: "FAIL",
                path: $representation->path() . "-init",
                pass_message: "No pssh boxes found",
                fail_message: "No pssh boxes found"
            );
            return;
        }

        $systemIds = [];
        foreach ($psshBoxes as $psshBox) {
            $systemId = "urn:uuid:" . $psshBox->systemId;
            if (array_key_exists($systemId, $this->drmSystemList)) {
                $systemIds[] = $this->drmSystemList[$systemId];
            } else {
                $systemIds[] = "Unkown: $systemId";
            }
        }

        $this->systemInformationCase->pathAdd(
            result: true,
            severity: "INFO",
            path: $representation->path() . "-init",
            pass_message: "Found DRM systems: " . implode(',', $systemIds),
            fail_message: ""
        );

        $psshOnlyInSegment = array_intersect($this->psshMissingFromMPD, $systemIds);

        $this->psshCase->pathAdd(
            result: count($this->psshMissingFromMPD) == 0 ||
                    count($this->psshMissingFromMPD) == count($psshOnlyInSegment),
            severity: "FAIL",
            path: $representation->path() . "-init",
            pass_message: "No missing pssh boxes",
            fail_message: "Missing at least one pssh box in segment"
        );

        $schmBox = $segment->getProtectionScheme();
        if ($schmBox) {
            $schemeType = $schmBox->scheme->schemeType;
            $this->cencCase->pathAdd(
                path: $representation->path() . "-init",
                result: $schemeType == 'cenc',
                severity: "WARN",
                pass_message: "Scheme set to 'cenc'",
                fail_message: "Scheme set to '" . $schemeType . "'"
            );
        }

        // TODO: Re-implement 'unique track KID' << blame this commit for previous implementation
        // TODO: Re-implement 'key rotation' << blame this commit for previous implementation
    }



    /**
     * @param \DomNodeList<\DomElement> $contentProtectionNodes
     **/
    private function validateGenericIdentifier(AdaptationSet $adaptationSet, \DomNodeList $contentProtectionNodes): void
    {
        $genericIdentifier = '';

        foreach ($contentProtectionNodes as $contentProtection) {
            $schemeIdUri = $contentProtection->getAttribute('schemeIdUri');

            if ($this->isGenericIdentifier($schemeIdUri)) {
                $genericIdentifier = $schemeIdUri;
            }
        }

        $this->genericIdentifierCase->pathAdd(
            result: $genericIdentifier != '',
            severity: "FAIL",
            path: $adaptationSet->path(),
            pass_message: "Generic identifier set to '$genericIdentifier'",
            fail_message: "No generic identifier found",
        );
    }

    /**
     * @param \DomNodeList<\DomElement> $contentProtectionNodes
     **/
    private function determineMissingPSSH(AdaptationSet $adaptationSet, \DomNodeList $contentProtectionNodes): void
    {
        foreach ($contentProtectionNodes as $contentProtection) {
            $schemeIdUri = $contentProtection->getAttribute('schemeIdUri');
            $cencPSSH = $contentProtection->getAttribute('cenc:pssh');

            if ($this->isGenericIdentifier($schemeIdUri)) {
                continue;
            }

            if ($cencPSSH == '') {
                $this->psshMissingFromMPD[] = $this->normalizeSystemId($schemeIdUri);
            }
        }

        $this->psshCase->pathAdd(
            result: count($this->psshMissingFromMPD) == 0,
            severity: "INFO",
            path: $adaptationSet->path(),
            pass_message: "No missing PSSH in MPD",
            fail_message: "Missing PSSH in MPD: " . implode(',', $this->psshMissingFromMPD)
        );
    }

    /**
     * @param \DomNodeList<\DomElement> $contentProtectionNodes
     **/
    private function determineMPDSystems(AdaptationSet $adaptationSet, \DomNodeList $contentProtectionNodes): void
    {
        foreach ($contentProtectionNodes as $contentProtection) {
            $schemeIdUri = $contentProtection->getAttribute('schemeIdUri');
            $cencPSSH = $contentProtection->getAttribute('cenc:pssh');

            $systemId = $this->normalizeSystemId($schemeIdUri);
            if (array_key_exists($systemId, $this->drmSystemList)) {
                $this->mpdSystems[] = $this->drmSystemList[$systemId];
            } else {
                $this->mpdSystems[] = "Unkown: $systemId";
            }
        }

        $this->systemInformationCase->pathAdd(
            result: true,
            severity: "INFO",
            path: $adaptationSet->path(),
            pass_message: "Found DRM Systems in MPD: " . implode(',', $this->mpdSystems),
            fail_message: ""
        );
    }

    private function isGenericIdentifier(string $identifier): bool
    {
        return $identifier == 'urn:mpeg:dash:mp4protection:2011' ||
               $identifier == 'urn:mpeg:dash:13818:1:CA_descriptor:2011';
    }

    private function normalizeSystemId(string $systemId): string
    {
        //Remove all dashes from the string
        return str_replace('-', '', $systemId);
    }


    /**
     * @var array<string,string> $drmSystemList
     **/
    private array $drmSystemList = [
        'urn:mpeg:dash:mp4protection:2011' => 'Generic Identifier 1',
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
        'urn:uuid:279fe473512c48feade8d176fee6b40f' => 'Arris Titanium'
    ];
}
