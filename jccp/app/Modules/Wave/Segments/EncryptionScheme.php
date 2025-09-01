<?php

namespace App\Modules\Wave\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class EncryptionScheme
{
    //Private subreporters
    private SubReporter $waveReporter;

    private string $section = '4.3.2 - Encrypted Media Presentations';

    private string $schemeExplanation = "The common encryption `cbcs` scheme SHALL be used for encryption";
    private string $ivExplanation = "Constant 16-byte Initialization Vectors SHALL be used";
    private string $alternativeSchemeExplanation = "For every `cbcs` encrypted component, [.. an alternative using " .
        "the 'cenc' scheme MAY be produced]";
    private string $alternativeIvExplanation = "Constant 8-byte Initialization Vectors SHALL be used for 'cenc' " .
        "encrypted material";


    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->waveReporter = &$reporter->context(new ReporterContext(
            "Segments",
            "CTA-5005-A",
            "Final",
            []
        ));
    }

    //Public validation functions
    public function validateEncryptionScheme(Representation $representation, Segment $segment): void
    {
        $protectionScheme = $segment->getProtectionScheme();

        if (!$protectionScheme) {
            $this->waveReporter->test(
                section: $this->section,
                test: $this->schemeExplanation,
                result: true,
                severity: "INFO",
                pass_message: $representation->path() . " - No available protectionScheme, we are " .
                              "going to assume it is not encrypted",
                fail_message: "",
            );
            return;
        }

        if (!$protectionScheme->encryption->isEncrypted) {
            $this->waveReporter->test(
                section: $this->section,
                test: $this->schemeExplanation,
                result: true,
                severity: "INFO",
                pass_message: $representation->path() . " - Explicitly marked as not encrypted",
                fail_message: "",
            );
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

            $this->waveReporter->test(
                section: $this->section,
                test: "Sample auxiliary information, if present, SHALL be addressed by [.. a 'saio' box]",
                result: $auxiliaryInformation != null,
                severity: "INFO",
                pass_message: $representation->path() . " - 'saio' box found",
                fail_message: $representation->path() . " - 'saio' box not found",
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
            $this->waveReporter->test(
                section: $this->section,
                test: "[..] video components SHALL be encrypted with a 1:9 pattern",
                result: $encryptionInfo->cryptByteBlock > 0 &&
                        $encryptionInfo->cryptByteBlock * 9 == $encryptionInfo->skipByteBlock,
                severity: "FAIL",
                pass_message: $representation->path() . " - Correct pattern detected",
                fail_message: $representation->path() . " - Incorrect pattern detected: " .
                              $encryptionInfo->cryptByteBlock . ":" . $encryptionInfo->skipByteBlock
            );
        }
        if ($handlerType == "soun") {
            $this->waveReporter->test(
                section: $this->section,
                test: "[..] audio components SHALL be encrypted with a 10:0 pattern",
                result: $encryptionInfo->cryptByteBlock > 0 &&
                        $encryptionInfo->skipByteBlock == 0,
                severity: "FAIL",
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
            $this->waveReporter->test(
                section: $this->section,
                test: $this->schemeExplanation,
                result: false,
                severity: "FAIL",
                pass_message: "",
                fail_message: $representation->path() . " - Invalid '" . $protectionScheme->scheme->schemeType . "' " .
                              "encryption scheme found",
            );
        }
    }

    private function validateCBCS(
        Representation $representation,
        Segment $segment,
        Boxes\SINFBox $protectionScheme
    ): void {
        $this->waveReporter->test(
            section: $this->section,
            test: $this->schemeExplanation,
            result: true,
            severity: "PASS",
            pass_message: $representation->path() . " - 'cbcs' encryption scheme found",
            fail_message: "",
        );

        $this->waveReporter->test(
            section: $this->section,
            test: $this->ivExplanation,
            result: $protectionScheme->encryption->ivSize == 16 && $protectionScheme->encryption->iv,
            severity: "FAIL",
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
        $this->waveReporter->test(
            section: $this->section,
            test: $this->alternativeSchemeExplanation,
            result: true,
            severity: "PASS",
            pass_message: $representation->path() . " - 'cenc' encryption scheme found",
            fail_message: "",
        );

        $this->waveReporter->test(
            section: $this->section,
            test: $this->alternativeIvExplanation,
            result: $protectionScheme->encryption->ivSize == 8 && $protectionScheme->encryption->iv,
            severity: "FAIL",
            pass_message: $representation->path() . " - Constant 8-byte IV found",
            fail_message: $representation->path() . " - Constant 8-byte IV not found",
        );
    }
}
