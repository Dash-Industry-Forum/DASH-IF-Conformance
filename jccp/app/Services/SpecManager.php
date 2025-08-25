<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Modules\DVB\MPD as DVBManifest;
use App\Modules\HbbTV\MPD as HbbTVManifest;
use App\Interfaces\Module;

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
                'run' => false
            ];
        }
    }

    private function registerMPDSpecs(): void
    {
        $this->manifestSpecs[] = new DVBManifest();
        $this->manifestSpecs[] = new HbbTVManifest();
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
        foreach ($this->manifestSpecs as $manifestSpec) {
            if ($this->moduleStates[$manifestSpec->name]['enabled']) {
                $manifestSpec->validateMPD();
            }
        }
    }

    public function stateJSON(): string
    {
        return \json_encode($this->moduleStates, JSON_PRETTY_PRINT);
    }
}
