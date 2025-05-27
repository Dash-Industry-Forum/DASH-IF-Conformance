<?php

namespace App\Livewire;

use App\Services\MPDHandler;
use Livewire\Component;
use Illuminate\View\View;

class SelectMpd extends Component
{
    public string $mpd = '';

    public function process(): void
    {
        session()->put('mpd', $this->mpd);
        $this->dispatch('select-mpd');
    }

    public function render(): View
    {
        if ($this->mpd == '') {
            $this->mpd = session()->get('mpd');
        }
        return view('livewire.select-mpd');
    }
}
