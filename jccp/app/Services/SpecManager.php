<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Modules\DVB\MPD as DVBManifest;
use App\Interfaces\Module;

class SpecManager
{
    /**
     * @var array<Module> $manifestSpecs
     **/
    private array $manifestSpecs = [];

    /**
     * @var array<array<string, mixed>> $moduleStates
     **/
    private array $moduleStates = [];

    public function __construct()
    {
        $this->registerMPDSpecs();

        foreach ($this->manifestSpecs as $manifestSpec) {
            $this->moduleStates[] = [
                'name' => $manifestSpec->name,
                'enabled' => false,
                'dependent' => false
            ];
        }
    }

    private function registerMPDSpecs(): void {
        $this->manifestSpecs[] = new DVBManifest();
    }

    public function validate(): void
    {
        foreach ($this->manifestSpecs as $manifestSpec) {
            $manifestSpec->validateMPD();
        }
    }

    public function stateJSON(): string
    {
        return \json_encode($this->moduleStates, JSON_PRETTY_PRINT);
    }
}
