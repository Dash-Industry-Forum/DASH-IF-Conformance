<?php

namespace App\Modules\LowLatency\MPD;

use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Services\Manifest\AdaptationSet;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes\DescriptionType;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProducerReferenceTime
{
    //Private subreporters
    private SubReporter $legacyReporter;

    private TestCase $presentCase;
    private TestCase $uniqueIdCase;

    /**
     * @var array<string> $validReferenceIds
     **/
    private array $validReferenceIds = [];

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->legacyReporter = &$reporter->context(new ReporterContext(
            "MPD",
            "LEGACY",
            "Low Latency",
            []
        ));

        $this->presentCase = $this->legacyReporter->add(
            section: '9.X.4.3',
            test: 'At least one ProducerTimeReference SHALL be present',
            skipReason: "",
        );
        $this->uniqueIdCase = $this->legacyReporter->add(
            section: '9.X.4.3',
            test: 'Each ProducerTimeReference SHALL have a unique @id',
            skipReason: "",
        );
    }

    //Public validation functions
    public function validateProducerReferenceTime(): void
    {
        $mpdCache = app(MPDCache::class);
        foreach ($mpdCache->allPeriods() as $period) {
            foreach ($period->allAdaptationSets() as $adaptationSet) {
                $this->validateAdaptationSet($adaptationSet);
            }
        }
        $this->uniqueIdCase->add(
            result: count(array_unique($this->validReferenceIds)) == count($this->validReferenceIds) &&
                    !in_array('', $this->validReferenceIds),
            severity: "FAIL",
            pass_message: "All @ids on the otherwise valid references are set and unique",
            fail_message: "At least one duplicate or unset id on an otherwise valid reference",
        );
    }
    public function validateAdaptationSet(AdaptationSet $adaptationSet): void
    {

        $producerReferenceTimes = $adaptationSet->getDOMElements('ProducerReferenceTime');

        $atLeastOneValid = false;
        foreach ($producerReferenceTimes as $referenceIndex => $producerReferenceTime) {
            $isValid = true;
            $type = $producerReferenceTime->getAttribute('type');

            $isValid &= $this->presentCase->pathAdd(
                path: $adaptationSet->path() . "-Reference$referenceIndex",
                result: $type == 'encoder' || $type == 'captured',
                severity: "INFO",
                pass_message: "Valid @type found",
                fail_message: "Invalid @type found",
            );

            //TODO: Actually validate the UTCTiming with the MPD variant
            $isValid &= $this->presentCase->pathAdd(
                path: $adaptationSet->path() . "-Reference$referenceIndex",
                result: count($producerReferenceTime->getElementsByTagName('UTCTiming')) != 0,
                severity: "INFO",
                pass_message: "UTCTiming child element found (not yet validated)",
                fail_message: "UTCTiming child element not found",
            );

            $isValid &= $this->presentCase->pathAdd(
                path: $adaptationSet->path() . "-Reference$referenceIndex",
                result: $producerReferenceTime->getAttribute('wallClockTime') != '',
                severity: "INFO",
                pass_message: "@wallClockTime found",
                fail_message: "@wallClockTime not found",
            );
            $isValid &= $this->presentCase->pathAdd(
                path: $adaptationSet->path() . "-Reference$referenceIndex",
                result: $producerReferenceTime->getAttribute('presentationTime') != '',
                severity: "INFO",
                pass_message: "@presentationTime found",
                fail_message: "@presentationTime not found",
            );
            //TODO: Validate presentationTimeOffset
            //TODO: Validate optional @inband


            if ($isValid) {
                $atLeastOneValid = true;
                $this->validReferenceIds[] = $producerReferenceTime->getAttribute('id');
            }
        }

        $this->presentCase->add(
            result: $atLeastOneValid,
            severity: "FAIL",
            pass_message: "At least one valid ProducerReferenceTime found",
            fail_message: "No valid ProducerReferenceTime found",
        );
    }

    //Private helper functions
}
