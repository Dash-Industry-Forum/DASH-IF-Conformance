<?php

namespace App\Livewire;

use App\Services\MPDHandler;
use Livewire\Component;
use Illuminate\View\View;
use Livewire\Attributes\On;

class SelectMpd extends Component
{
    public string $mpd = '';

    public function process(): void
    {
        session()->put('mpd', $this->mpd);
        $this->dispatch('mpd-selected');
    }

    public function clearSession(): mixed
    {
        $processConsent = session()->get('process-consent');
        session()->invalidate();
        if ($processConsent) {
            session()->put('process-consent', $processConsent);
        }
        return redirect('/');
    }

    #[On('consent-changed')]
    public function refresh(): mixed
    {
        return $this->clearSession();
    }



    public function render(): View
    {
        if ($this->mpd == '') {
            $this->mpd = session()->get('mpd') ?? '';
        }
        return view('livewire.select-mpd');
    }
}
