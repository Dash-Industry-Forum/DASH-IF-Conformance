<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Modules\DVB\MPD as DVBManifest;
use App\Modules\DVB\Segments as DVBSegments;
use App\Modules\HbbTV\MPD as HbbTVManifest;
use App\Modules\Wave\Segments as WaveHLSInteropSegments;
use App\Interfaces\Module;
use App\Services\SegmentManager;
use App\Services\MPDCache;

class SpecManager
{
    /**
     * @var array<Module> $manifestSpecs
     **/
    private array $manifestSpecs = [];

    /**
     * @var array<string, array<mixed>> $moduleStates
     **/
    private array $moduleStates = [];

    public function __construct()
    {
        $this->registerMPDSpecs();

        foreach ($this->manifestSpecs as $manifestSpec) {
            $this->moduleStates[$manifestSpec->name] = [
                'enabled' => false,
                'dependency' => false,
                'run' => false,
                'runSegments' => false
            ];
        }
    }

    private function registerMPDSpecs(): void
    {
        $this->manifestSpecs[] = new DVBManifest();
        $this->manifestSpecs[] = new HbbTVManifest();
        $this->manifestSpecs[] = new DVBSegments();
        $this->manifestSpecs[] = new WaveHLSInteropSegments();
    }

    public function enable(string $moduleName): void
    {
        foreach ($this->moduleStates as $name => &$moduleState) {
            if ($name == $moduleName) {
                $moduleState['enabled'] = true;
            }
        }
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
                foreach ($adaptationSet->allRepresentations() as $representation) {
                    $segments = $segmentManager->representationSegments($representation);
                    $module->validateSegments($representation, $segments);
                }
            }
        }
    }

    /**
     * @return array<string>
     **/
    public function specNames(): array {
        return [];
    }

    public function stateJSON(): string
    {
        return \json_encode($this->moduleStates, JSON_PRETTY_PRINT);
    }
}
