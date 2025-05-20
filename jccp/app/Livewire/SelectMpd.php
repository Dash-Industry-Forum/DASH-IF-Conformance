<?php

namespace App\Livewire;
use App\Services\MPDHandler;

use Livewire\Component;

class SelectMpd extends Component
{
    public $mpd = '';

    public function process() {
        session()->put('mpd', $this->mpd);
        $this->dispatch('select-mpd');

    }

    public function render()
    {
        if ($this->mpd == ''){
            $this->mpd = session()->get('mpd');
        }
        return view('livewire.select-mpd');
    }
}
