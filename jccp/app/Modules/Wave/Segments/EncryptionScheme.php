<?php

namespace App\Modules\Wave\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Reporter\TestCase;
use App\Services\Validators\Boxes;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class EncryptionScheme
{
    //Private subreporters
    private SubReporter $waveReporter;

    private string $section = '4.3.2 - Encrypted Media Presentations';

    private TestCase $cbcsCase;
    private TestCase $cbcsIVCase;
    private TestCase $cencCase;
    private TestCase $cencIVCase;
    private TestCase $saioCase;
    private TestCase $videoPatternCase;
    private TestCase $audioPatternCase;


    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->waveReporter = &$reporter->context(new ReporterContext(
            "Segments",
            "CTA-5005-A",
            "Final",
            []
        ));

        $this->cbcsCase = $this->waveReporter->add(
            section: $this->section,
            test: "The common encryption `cbcs` scheme SHALL be used for encryption",
            skipReason: "No 'cbcs encrypted tracks found"
        );
        $this->cbcsIVCase = $this->waveReporter->add(
            section: $this->section,
            test: "Constant 16-byte Initialization Vectors SHALL be used",
            skipReason: "No 'cbcs encrypted tracks found"
        );
        $this->cencCase = $this->waveReporter->add(
            section: $this->section,
            test: "An alternative using the `cenc` scheme MAY be produced",
            skipReason: "No 'cenc' encrypted tracks found"
        );
        $this->cencIVCase = $this->waveReporter->add(
            section: $this->section,
            test: "Constant 8-byte Initialization Vectors SHALL be used for 'cenc' encrypted material",
            skipReason: "No 'cenc' encrypted tracks found"
        );
        $this->saioCase = $this->waveReporter->add(
            section: $this->section,
            test: "Sample auxiliary information, if present, SHALL be addressed by [.. a 'saio' box]",
            skipReason: "The stream is not encrypted"
        );
        $this->videoPatternCase = $this->waveReporter->add(
            section: $this->section,
            test: "Video components SHALL be encrypted with a 1:9 pattern",
            skipReason: "No encrypted video streams found"
        );
        $this->audioPatternCase = $this->waveReporter->add(
            section: $this->section,
            test: "Audio components SHALL be encrypted with a 10:0 pattern",
            skipReason: "No encrypted audio streams found"
        );
    }

    //Public validation functions
    public function validateEncryptionScheme(Representation $representation, Segment $segment): void
    {
        $protectionScheme = $segment->getProtectionScheme();

        if (!$protectionScheme || !$protectionScheme->encryption->isEncrypted) {
            return;
        }

        $this->validateSchemeType($representation, $segment, $protectionScheme);
        $this->validateEncryptionPattern($representation, $segment, $protectionScheme);
        $this->validateAuxiliaryInformation($representation, $segment, $protectionScheme);
    }

    //Private helper functions

    private function validateAuxiliaryInformation(
        Representation $representation,
        Segment $segment,
        Boxes\SINFBox $protectionScheme
    ): void {
        $auxiliaryInformation = $segment->getSampleAuxiliaryInformation();

        $this->saioCase->pathAdd(
            result: $auxiliaryInformation != null,
            severity: "INFO",
            path: $representation->path() . "-init",
            pass_message: "'saio' box found",
            fail_message: "'saio' box not found",
        );
    }


    private function validateEncryptionPattern(
        Representation $representation,
        Segment $segment,
        Boxes\SINFBox $protectionScheme
    ): void {
        //TODO: Implement "when not stated by a codec media profile"
        $encryptionInfo = $protectionScheme->encryption;

        $handlerType = $segment->getHandlerType();

        if ($handlerType == "vide") {
            $this->videoPatternCase->pathAdd(
                result: $encryptionInfo->cryptByteBlock > 0 &&
                        $encryptionInfo->cryptByteBlock * 9 == $encryptionInfo->skipByteBlock,
                severity: "FAIL",
                path: $representation->path() . "-init",
                pass_message: "Correct pattern detected",
                fail_message: "Incorrect pattern detected: " .
                              $encryptionInfo->cryptByteBlock . ":" . $encryptionInfo->skipByteBlock
            );
        }
        if ($handlerType == "soun") {
            $this->audioPatternCase->pathAdd(
                result: $encryptionInfo->cryptByteBlock > 0 &&
                        $encryptionInfo->skipByteBlock == 0,
                severity: "FAIL",
                path: $representation->path() . "-init",
                pass_message: $representation->path() . " - Correct pattern detected",
                fail_message: $representation->path() . " - Incorrect pattern detected: " .
                              $encryptionInfo->cryptByteBlock . ":" . $encryptionInfo->skipByteBlock
            );
        }
    }

    private function validateSchemeType(
        Representation $representation,
        Segment $segment,
        Boxes\SINFBox $protectionScheme
    ): void {

        if ($protectionScheme->scheme->schemeType == 'cbcs') {
            $this->validateCBCS($representation, $segment, $protectionScheme);
        } elseif ($protectionScheme->scheme->schemeType == 'cenc') {
            $this->validateCENC($representation, $segment, $protectionScheme);
        } else {
            $this->cbcsCase->pathAdd(
                result: false,
                severity: "FAIL",
                path: $representation->path() . "-init",
                pass_message: "",
                fail_message: "'" . $protectionScheme->scheme->schemeType . "' " .
                              "encryption scheme found",
            );
        }
    }

    private function validateCBCS(
        Representation $representation,
        Segment $segment,
        Boxes\SINFBox $protectionScheme
    ): void {
        $this->cbcsCase->pathAdd(
            result: true,
            severity: "PASS",
            path: $representation->path() . "-init",
            pass_message: "'cbcs' encryption scheme found",
            fail_message: "",
        );

        $this->cbcsIVCase->pathAdd(
            result: $protectionScheme->encryption->ivSize == 16 && $protectionScheme->encryption->iv,
            severity: "FAIL",
            path: $representation->path() . "-init",
            pass_message: $representation->path() . " - Constant 16-byte IV found",
            fail_message: $representation->path() . " - Constant 16-byte IV not found",
        );
    }

    private function validateCENC(
        Representation $representation,
        Segment $segment,
        Boxes\SINFBox $protectionScheme
    ): void {
        //TODO: Implement check that ensures 'cbcs' encrypted variant exists
        $this->cencCase->pathAdd(
            result: true,
            severity: "PASS",
            path: $representation->path() . "-init",
            pass_message: "'cenc' encryption scheme found",
            fail_message: "",
        );

        $this->cencIVCase->pathAdd(
            result: $protectionScheme->encryption->ivSize == 8 && $protectionScheme->encryption->iv,
            severity: "FAIL",
            path: $representation->path() . "-init",
            pass_message: $representation->path() . " - Constant 8-byte IV found",
            fail_message: $representation->path() . " - Constant 8-byte IV not found",
        );
    }
}
