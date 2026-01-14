<?php

namespace App\Modules\WaveHLSInterop\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Reporter\TestCase;
use App\Services\Validators\Boxes\DescriptionType;
use App\Interfaces\ModuleComponents\SegmentComponent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class KeyRotation extends SegmentComponent
{
    private TestCase $sgbpCase;
    private TestCase $psshCase;

    public function __construct()
    {
        parent::__construct(
            self::class,
            new ReporterContext(
            "Segments",
            "CTA-5005-A",
            "Final",
            []
        ));

        $this->sgbpCase = $this->reporter->add(
            section: '4.6.2 - Rotation of Encryption Keys',
            test: "CMAF Segments [.. with differing encryption keys] SHALL provide an 'sbgp' box [..]",
            skipReason: "No 'seig' boxes found"
        );

        $this->psshCase = $this->reporter->add(
            section: '4.6.2 - Rotation of Encryption Keys',
            test: "CMAF Segments [.. with differing encryption keys] SHALL provide a 'pssh' box [..]",
            skipReason: "No 'seig' boxes found"
        );
    }

    //Public validation functions
    public function validateSegment(Representation $representation, Segment $segment, int $segmentIndex): void
    {
        $boxAccess = $segment->boxAccess();

        $seigBoxes = $boxAccess->seig();
        $sampleGroups = $boxAccess->sgbp();

        if (empty($seigBoxes)) {
            return;
        }

        $this->sgbpCase->pathAdd(
            result: count($seigBoxes) == count($sampleGroups),
            severity: "FAIL",
            path: $representation->path() . "-$segmentIndex",
            pass_message: "Equal amount of 'seig' and 'sbgp' boxes",
            fail_message: "Not equal amount of 'seig' and 'sbgp' boxes",
        );

        $psshBoxes = $boxAccess->pssh();

        $psshSystems = [];

        foreach ($psshBoxes as $psshBox) {
            if (!array_key_exists($psshBox->systemId, $psshSystems)) {
                $psshSystems[$psshBox->systemId] = 0;
            }
            $psshSystems[$psshBox->systemId]++;
        }

        $anyDuplicate = false;
        $missingReplacements = array();

        foreach ($psshSystems as $system => $count) {
            if ($count == 1) {
                $missingReplacements[] = $system;
            } else {
                $anyDuplicate = true;
            }
        }



        $this->psshCase->pathAdd(
            result: !$anyDuplicate || empty($missingReplacements),
            severity: "FAIL",
            path: $representation->path() . "-$segmentIndex",
            pass_message: "All 'pssh' either unique, or all replacements found",
            fail_message: "Expected replacement 'pssh' boxes for " .
                          implode(',', $missingReplacements)
        );
    }

    //Private helper functions
}
