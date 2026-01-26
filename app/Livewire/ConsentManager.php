<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\View\View;

class ConsentManager extends Component
{
    public function render(): View
    {
        return view('livewire.consent-manager');
    }

    public function accept(): void
    {
        session()->put('process-consent', true);
        $this->dispatch('consent-changed');
    }

    public function revoke(): void
    {
        session()->forget('process-consent');
        $this->dispatch('consent-changed');
    }
}
