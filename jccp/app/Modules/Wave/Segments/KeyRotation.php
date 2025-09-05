<?php

namespace App\Modules\Wave\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes\DescriptionType;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class KeyRotation
{
    //Private subreporters
    private SubReporter $waveReporter;

    private string $section = '4.6.2 - Rotation of Encryption Keys';

    private string $baseExplanation = "CMAF Segments [.. with differing encryption keys] SHALL provide";


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
    public function validateKeyRotation(Representation $representation, Segment $segment): void
    {

        $seigBoxes = $segment->getSeigDescriptionGroups();
        $sampleGroups = $segment->getSampleGroups();

        if (empty($seigBoxes)) {
            return;
        }

        $this->waveReporter->test(
            section: $this->section,
            test: $this->baseExplanation . " an 'sbgp' box [..]",
            result: count($seigBoxes) == count($sampleGroups),
            severity: "FAIL",
            pass_message: $representation->path() . " - Equal amount of 'seig' and 'sbgp' boxes",
            fail_message: $representation->path() . " - Not equal amount of 'seig' and 'sbgp' boxes",
        );

        $psshBoxes = $segment->getPSSHBoxes();

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



        $this->waveReporter->test(
            section: $this->section,
            test: $this->baseExplanation . " if present in the CMAF header: 'pssh' boxes [..]",
            result: !$anyDuplicate || empty($missingReplacements),
            severity: "FAIL",
            pass_message: $representation->path() . " - All 'pssh' either unique, or all replacements found",
            fail_message: $representation->path() . " - Expected replacement 'pssh' boxes for " .
                          implode(',', $missingReplacements)
        );
    }

    //Private helper functions
}
