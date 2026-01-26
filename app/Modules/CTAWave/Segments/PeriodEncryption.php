<?php

namespace App\Modules\CTAWave\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Period;
use App\Services\Manifest\AdaptationSet;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\SegmentManager;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Reporter\TestCase;
use App\Services\Validators\Boxes;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PeriodEncryption
{
    //Private subreporters
    private SubReporter $waveReporter;

    private TestCase $singleEncryptionProfileCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->waveReporter = &$reporter->context(new ReporterContext(
            "Segments",
            "LEGACY",
            "WAVE Content Spec 2018Ed",
            []
        ));

        $this->singleEncryptionProfileCase = $this->waveReporter->add(
            section: '7.2.2',
            test: "Wave content SHALL contain one CENC scheme per program",
            skipReason: "No periods found",
        );
    }

    //Public validation functions
    public function validatePeriodEncryption(Period $period): void
    {
        $protectionSchemes = [];
        foreach ($period->allAdaptationSets() as $adaptationSet) {
            $protection = $adaptationSet->getDOMElements('ContentProtection');
            if (count($protection)) {
                $protectionSchemes[] = $protection[0]->getAttribute('value');
            }
        }

        $protectionCount = count($protectionSchemes);
        if ($protectionCount) {
            $protectionCount = count(array_unique($protectionSchemes));
        }
        $validEncryption = $protectionCount < 2 || in_array('', array_unique($protectionSchemes));

        $this->singleEncryptionProfileCase->pathAdd(
            path: $period->path(),
            result: $validEncryption,
            severity: "FAIL",
            pass_message: "No encryption, or single encryption scheme used",
            fail_message: "Multiple encryption schemes used",
        );
    }
}
