<?php

namespace App\Livewire;

use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
//
use Livewire\Component;

class SpecManager extends Component
{
    public function render(): View
    {
        return view('livewire.spec-manager');
    }

    public function specManagerState(): string
    {
        return app(\App\Services\SpecManager::class)->stateJSON();
    }

    /**
     * @return array<string>
     **/
    public function specs(): array
    {
        return app(\App\Services\SpecManager::class)->specNames();
    }

    public function buttonClassForSpec(string $spec): string
    {
        $state = app(\App\Services\SpecManager::class)->specState($spec);
        if ($state == 'Enabled') {
            return 'btn-success';
        }
        if ($state == 'Dependency') {
            return 'btn-outline-success';
        }
        return 'btn-outline-dark';
    }

    public function enable(string $spec): void
    {
        app(\App\Services\SpecManager::class)->toggle($spec);
        $this->dispatch('spec-selection-changed');
    }
}
