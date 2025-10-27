<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Modules\Schematron;
use App\Modules\DVB\MPD as DVBManifest;
use App\Modules\DVB\Segments as DVBSegments;
use App\Modules\HbbTV\MPD as HbbTVManifest;
use App\Modules\HbbTV\Segments as HbbTVSegments;
use App\Modules\Wave\Segments as WaveHLSInteropSegments;
use App\Interfaces\Module;
use App\Services\Manifest\Period;
use App\Services\SegmentManager;
use App\Services\MPDCache;

class SpecManager
{
    /**
     * @var array<Module> $manifestSpecs
     **/
    private array $manifestSpecs = [];

    /**
     * @var array<string, array<string,boolean>> $moduleStates
     **/
    private array $moduleStates = [];

    public function __construct()
    {
        $this->registerMPDSpecs();

        //Always enable schematron
        $cachePath = cache_path(['spec', 'Global Module']);
        Cache::put($cachePath, 'enabled');

        foreach ($this->manifestSpecs as $manifestSpec) {
            $this->moduleStates[$manifestSpec->name] = [
                'enabled' => Cache::has(cache_path(['spec', $manifestSpec->name])),
                'dependency' => false,
                'run' => false,
                'runSegments' => false
            ];
        }
    }

    private function registerMPDSpecs(): void
    {
        $this->manifestSpecs[] = new Schematron();
        $this->manifestSpecs[] = new DVBManifest();
        $this->manifestSpecs[] = new HbbTVManifest();
        $this->manifestSpecs[] = new DVBSegments();
        $this->manifestSpecs[] = new HbbTVSegments();
        $this->manifestSpecs[] = new WaveHLSInteropSegments();
    }

    public function toggle(string $moduleName): void
    {
        if ($moduleName == "Global Module") {
            return;
        }
        $cachePath = cache_path(['spec', $moduleName]);
        if (Cache::has($cachePath)) {
            Cache::forget($cachePath);
        } else {
            Cache::put($cachePath, 'enabled');
        }
        $this->moduleStates[$moduleName]['enabled'] = Cache::has($cachePath);
        $this->validate();
        $this->validateSegments();
    }

    public function activateDependency(string $moduleName): void
    {
        $this->moduleStates[$moduleName]['dependency']  = true;
    }

    public function validate(): void
    {
        $runAtLeastOne = false;
        do {
            $runAtLeastOne = false;
            foreach ($this->manifestSpecs as $manifestSpec) {
                $state = &$this->moduleStates[$manifestSpec->name];
                if ($state['run']) {
                    continue;
                }
                if (!$state['enabled'] && !$state['dependency']) {
                    continue;
                }
                $manifestSpec->validateMPD();
                $state['run'] = true;
                $runAtLeastOne = true;
            }
        } while ($runAtLeastOne);
    }

    public function validateSegments(): void
    {
        $runAtLeastOne = false;
        do {
            $runAtLeastOne = false;
            foreach ($this->manifestSpecs as $manifestSpec) {
                $state = &$this->moduleStates[$manifestSpec->name];
                if ($state['runSegments']) {
                    continue;
                }
                if (!$state['enabled'] && !$state['dependency']) {
                    continue;
                }
                $this->validateAllRepresentations($manifestSpec);
                $state['runSegments'] = true;
                $runAtLeastOne = true;
            }
        } while ($runAtLeastOne);
    }

    private function validateAllRepresentations(Module $module): void
    {
        $mpdCache = app(MPDCache::class);
        $segmentManager = app(SegmentManager::class);
        foreach ($mpdCache->allPeriods() as $period) {
            foreach ($period->allAdaptationSets() as $adaptationSet) {
                $module->validateCrossAdaptationSet($adaptationSet);
                foreach ($adaptationSet->allRepresentations() as $representation) {
                    $segments = $segmentManager->representationSegments($representation);
                    $module->validateSegments($representation, $segments);
                }
            }
        }
        $this->validateMultiPeriod($module, $mpdCache->allPeriods());
    }

    /**
     * @param array<Period> $periods
     **/
    private function validateMultiPeriod(Module $module, array $periods): void
    {
        $periodCount = count($periods);
        for ($first = 0; $first < $periodCount; $first++) {
            for ($second = $first + 1; $second < $periodCount; $second++) {
                $module->validateMultiPeriod($periods[$first], $periods[$second]);
            }
        }
    }

    /**
     * @return array<string>
     **/
    public function specNames(): array
    {
        return array_map(fn($spec): string => $spec->name, $this->manifestSpecs);
    }

    public function specState(string $spec): string
    {
        $state = $this->moduleStates[$spec];
        if ($state) {
            if ($state['enabled']) {
                return "Enabled";
            }
            if ($state['dependency']) {
                return "Dependency";
            }
        }
        return "Disabled";
    }

    public function stateJSON(): string
    {
        return \json_encode($this->moduleStates, JSON_PRETTY_PRINT);
    }
}
