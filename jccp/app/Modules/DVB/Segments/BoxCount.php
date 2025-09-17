<?php

namespace App\Modules\DVB\Segments;

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

class BoxCount
{
    //Private subreporters
    private SubReporter $v141Reporter;

    private string $section = 'Section 4.3';

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->v141Reporter = &$reporter->context(new ReporterContext(
            "Segments",
            "DVB",
            "v1.4.1",
            []
        ));
    }

    //Public validation functions
    public function validateBoxCount(Representation $representation, Segment $segment): void
    {

        $boxTree = $segment->getBoxNameTree();

        $moofBoxes = $boxTree->filterChildrenRecursive('moof');
        $validTraf = true;
        foreach ($moofBoxes as $moofBox) {
            $trafBoxes = $moofBox->filterChildrenRecursive('traf');
            if (count($trafBoxes) != 1) {
                $validTraf = false;
                break;
            }
        }
        $this->v141Reporter->test(
            section: $this->section,
            test: "The 'moof' box shall contain only one 'traf' box",
            result: $validTraf,
            severity: "FAIL",
            pass_message: $representation->path() . " Single 'traf' box in each 'moof'",
            fail_message: $representation->path() . " Multiple of no 'traf' boxes in at least one 'moof'"
        );

        $isOnDemand = $representation->hasProfile("urn:dvb:dash:profile:dvb-dash:isoff-ext-on-demand:2014");
        if ($isOnDemand) {
            $sidxBoxes = $boxTree->filterChildrenRecursive('sidx');
            $this->v141Reporter->test(
                section: $this->section,
                test: "[.. conforming to clause 4.2.6] The segment shall contain ony one single 'sidx' box",
                result: count($sidxBoxes) == 1,
                severity: "FAIL",
                pass_message: $representation->path() . " Single 'sidx' in segment",
                fail_message: $representation->path() . " " . count($sidxBoxes) . " 'sidx' boxes in segment"
            );
        }
    }

    //Private helper functions
}
