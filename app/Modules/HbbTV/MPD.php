<?php

namespace App\Modules\HbbTV;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\ModuleLogger;
use App\Services\MPDCache;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Interfaces\Module;
use App\Services\Manifest\Representation;
use App\Services\SpecManager;

//Module checks

class MPD extends Module
{
    private SubReporter $legacyreporter;

    private TestCase $codecCase;

    public function __construct()
    {
        parent::__construct("HbbTV MPD");
    }

    public function enableDependencies(SpecManager $manager): void {
        $manager->activateDependency("DVB MPD");
    }

    public function validateMPD(): void
    {
        parent::validateMPD();

        $reporter = app(ModuleReporter::class);
        $this->legacyreporter = $reporter->context(new ReporterContext(
            "MPD",
            "LEGACY",
            "HbbTV",
            []
        ));


        $this->legacyreporter->dependencyAdd(
            section: "Unknown",
            test: "Inherit DVB legacy checks",
            dependentModule: "DVB MPD",
            dependentSpec: "LEGACY - DVB",
            dependentSection: "Unknown"
        )->add(
            result: true,
            severity: "DEPENDENCY",
            pass_message: "Inherit legacy DVB checks",
            fail_message: ""
        );

        $this->codecCase = $this->legacyreporter->add(
            section: 'Codec Information',
            test: 'The codec should be supported by the specification',
            skipReason: "No representation found",
        );

        $mpdCache = app(MPDCache::class);
        foreach ($mpdCache->allPeriods() as $period) {
            foreach ($period->allAdaptationSets() as $adaptationSet) {
                foreach ($adaptationSet->allRepresentations() as $representation) {
                    $this->validateCodecs($representation);
                }
            }
        }
    }

    //Private helper functions
    //

    private function validateCodecs(Representation $representation): void
    {
        $validCodecs = [
            'avc',
            'mp4a', 'ec-3',
        ];
        $codecs = $representation->getTransientAttribute("codecs");

        foreach (explode(',', $codecs) as $codec) {
            $isValidCodec = false;
            foreach ($validCodecs as $validCodec) {
                if (str_starts_with($codec, $validCodec)) {
                    $isValidCodec = true;
                    break;
                }
            }
            $this->codecCase->pathAdd(
                path: $representation->path(),
                result: $isValidCodec,
                severity: "WARN",
                pass_message: "Valid codec",
                fail_message: "Invalid codec - $codec"
            );
        }
    }
}
