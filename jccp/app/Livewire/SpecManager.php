<?php

namespace App\Livewire;

use Illuminate\View\View;
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
}
