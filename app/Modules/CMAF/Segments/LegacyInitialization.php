<?php

namespace App\Modules\CMAF\Segments;

use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Interfaces\ModuleComponents\InitSegmentComponent;

class LegacyInitialization extends InitSegmentComponent
{
    private TestCase $hevcColourCase;
    private TestCase $decryptionCase;

    public function __construct()
    {
        parent::__construct(
            self::class,
            new ReporterContext(
                "Segments",
                "LEGACY",
                "CMAF",
                []
            )
        );

        $this->hevcColourCase = $this->reporter->add(
            section: 'Section 7.3.2.4',
            test: "The HEVCSampleEntry SHALL contain a 'colr' box with type 'nclx'",
            skipReason: "No 'hev1' track found"
        );
        $this->decryptionCase = $this->reporter->add(
            section: 'Section 7.3.2.4',
            test: "Each encrypted Fragment SHALL be independently decryptable",
            skipReason: "No encrypted track found"
        );
    }

    //Public validation functions
    public function validateInitSegment(Representation $representation, Segment $segment): void
    {

        $sdType = $segment->getSampleDescriptor();

        if ($sdType == 'hvc1' || $sdType == 'hev1') {
            $this->validateHEVCColrBox($representation, $segment);
        }

        if ($representation->hasProfile("http://dashif.org/guidelines/dash264")) {
            $this->validateDecryption($representation, $segment);
        }
    }



    //Private helper functions
    private function validateDecryption(Representation $representation, Segment $segment): void
    {
        $contentProtection = $representation->getDomElements('ContentProtection');
        if (!count($contentProtection)) {
            $contentProtection = $representation->getAdaptationSet()->getDomElements('ContentProtection');
        }

        if (!count($contentProtection)) {
            return;
        }

        $boxAccess = $segment->boxAccess();
        $sencBoxes = $boxAccess->senc();
        $moofBoxes = $boxAccess->moof();

        //TODO: Check whether this is still correct
        $this->decryptionCase->pathAdd(
            path: $representation->path() . "-init",
            result: count($moofBoxes) == count($sencBoxes),
            severity: "FAIL",
            pass_message: "Found decryption Configuration",
            fail_message: "Unable to find decryption Configuration",
        );
    }

    private function validateHEVCColrBox(Representation $representation, Segment $segment): void
    {
        if ($segment->getSampleDescriptor() != "hev1") {
            return;
        }

        //TODO: Only if no VUI parameters present flag?
        //TODO: Add check for 'pasp' box

        $colrBoxes = $segment->boxAccess()->colr();
        $this->hevcColourCase->pathAdd(
            path: $representation->path() . "-init",
            result: count($colrBoxes) != 0 && $colrBoxes[0]->colourType == 'nclx',
            severity: "FAIL",
            pass_message: "Correct 'colr' box found",
            fail_message: "No 'colr' box or wrong colourType found",
        );
    }
}
