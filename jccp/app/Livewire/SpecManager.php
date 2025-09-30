<?php

namespace App\Livewire;

use Livewire\Component;

class SpecManager extends Component
{
    public function render()
    {
        return view('livewire.spec-manager');
    }

    public function specManagerState(): string
    {
        return app(\App\Services\SpecManager::class)->stateJSON();
    }
}
