<?php

namespace App\Modules\DVB\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Period;
use App\Services\Manifest\AdaptationSet;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\SegmentManager;
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
    private TestCase $ept1Case;
    private TestCase $ept2Case;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->v141Reporter = &$reporter->context(new ReporterContext(
            "Segments",
            "DVB",
            "v1.4.1",
            []
        ));

        $this->associationCase = $this->v141Reporter->add(
            section: 'Section 10.5.2.3',
            test: "Continous adaptation sets shall be signalled accordingly",
            skipReason: 'No continuous adaptations signalled'
        );
        $this->ept1Case = $this->v141Reporter->add(
            section: 'Section 10.5.2.3',
            test: "All Representations in the Adaptation Set in the first Period shall share the same value EPT1",
            skipReason: 'No continuous adaptations signalled'
        );
        $this->ept2Case = $this->v141Reporter->add(
            section: 'Section 10.5.2.3',
            test: "All Representations in the Adaptation Set in a subsequent Period shall share the same value EPT2",
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

                foreach ($firstPeriod->allAdaptationSets() as $firstAdaptationSet) {
                    if ($firstAdaptationSet->getAttribute('id') != $secondAdaptationSet->getAttribute('id')) {
                        continue;
                    }
                    $this->associationCase->pathAdd(
                        result: true,
                        severity: "INFO",
                        path: $firstAdaptationSet->path() . " + " . $secondAdaptationSet->path(),
                        pass_message: "Found continuity",
                        fail_message: ""
                    );

                    $this->validateEPT($firstAdaptationSet, $this->ept1Case);
                    $this->validateEPT($secondAdaptationSet, $this->ept2Case);
                }

                //TODO Implement associativity checks from 10.5.2.2 - implementation at this commit was broken
            }
        }
    }

    //Private helper functions
    private function validateEPT(AdaptationSet $adaptationSet, TestCase &$case): void
    {
        $segmentManager = app(SegmentManager::class);

        $earliestPresentationTimes = [];
        foreach ($adaptationSet->allRepresentations() as $representation) {
            $segments = $segmentManager->getSegments(
                $representation->periodIndex,
                $representation->adaptationSetIndex,
                $representation->representationIndex
            );
            if (!count($segments)) {
                $earliestPresentationTimes[] = null;
                continue;
            }

            $earliestPresentationTimes[] = $segments[0]->getEPT();
        }


        $hasUniqueEPT = count(array_unique($earliestPresentationTimes)) == 1;
        $case->pathAdd(
            path: $adaptationSet->path(),
            result: $hasUniqueEPT,
            severity: "FAIL",
            pass_message: "All EPT are equal",
            fail_message: "At least one representation has a differing EPT"
        );
        if (!$hasUniqueEPT) {
            $case->pathAdd(
                path: $adaptationSet->path(),
                result: true,
                severity: "INFO",
                pass_message: "EPT found: " . implode(', ', $earliestPresentationTimes),
                fail_message: ""
            );
        }
    }
}
