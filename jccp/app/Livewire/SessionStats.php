<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\View\View;

class SessionStats extends Component
{
    public function resetSession(): mixed
    {
        session()->invalidate();
        return redirect('/');
    }

    /**
     * This is a laravel-specific type, so we ignore it
     * @phpstan-ignore missingType.property
     **/
    protected $listeners = [
        'select-mpd' => '$refresh'
    ];
    public function render(): View
    {
        return view('livewire.session-stats');
    }
}
