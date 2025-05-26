<?php

namespace App\Livewire;

use Livewire\Component;

class SessionStats extends Component
{
    public function resetSession()
    {
        session()->invalidate();
        return redirect('/');
    }

    protected $listeners = [
        'select-mpd' => '$refresh'
    ];
    public function render()
    {
        return view('livewire.session-stats');
    }
}
