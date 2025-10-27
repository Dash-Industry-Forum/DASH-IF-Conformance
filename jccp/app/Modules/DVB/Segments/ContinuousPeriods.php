<?php

namespace App\Modules\DVB\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Period;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes\DescriptionType;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ContinuousPeriods
{
    //Private subreporters
    private SubReporter $v141Reporter;

    private TestCase $associationCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->v141Reporter = &$reporter->context(new ReporterContext(
            "Segments",
            "A DVB",
            "v1.4.1",
            []
        ));

        $this->associationCase = $this->v141Reporter->add(
            section: 'Section 10.5.2.3',
            test: "Continous adaptation sets shall be signalled accordingly",
            skipReason: 'No continuous adaptations signalled'
        );
    }

    //Public validation functions
    public function validateContinuity(Period $firstPeriod, Period $secondPeriod): void
    {
        $this->associationCase->pathAdd(
            result: true,
            severity: "INFO",
            path: $firstPeriod->path() . " + " . $secondPeriod->path(),
            pass_message: "Multi check run",
            fail_message: ""
        );
        foreach ($secondPeriod->allAdaptationSets() as $secondAdaptationSet) {
            $supplementalProperties = $secondAdaptationSet->getDOMElements("SupplementalProperty");
            foreach ($supplementalProperties as $supplementalProperty) {
                $schemeIdUri = $supplementalProperty->getAttribute('schemeIdUri');
                $value = $supplementalProperty->getAttribute('value');

                if ($schemeIdUri != 'urn:dvb:dash:period_continuity:2014') {
                    continue;
                }

                if ($value != $firstPeriod->getAttribute('id')) {
                    continue;
                }
                $this->associationCase->pathAdd(
                    result: true,
                    severity: "INFO",
                    path: $firstPeriod->path() . " + " . $secondAdaptationSet->path(),
                    pass_message: "Found continuity",
                    fail_message: ""
                );

                //TODO Implement associativity checks from 10.5.2.2 - implementation at this commit was broken
            }
        }
    }

    //Private helper functions
}
