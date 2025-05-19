<?php

namespace App\Livewire;

use Livewire\Component;

class SessionStats extends Component
{

    protected $listeners = [
        'select-mpd' => '$refresh'
    ];
    public function render()
    {
        return view('livewire.session-stats');
    }
}
