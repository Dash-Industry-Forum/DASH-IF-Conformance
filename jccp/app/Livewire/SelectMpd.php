<?php

namespace App\Livewire;

use App\Services\MPDHandler;
use App\Services\MPDCache;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\View\View;

class SelectMpd extends Component
{
    public string $mpd = '';
    public string $error = '';

    public function process(): void
    {
        if ($this->mpd == ''){
            $this->error = "Please provide a url";
        }else{
            $this->error = '';
            session()->put('mpd', $this->mpd);
            $this->dispatch('mpd-selected');
        }
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

    public function mpdError(): ?string {
        if ($this->error){
            return $this->error;
        }
        if (!session()->get('mpd')){
            return null;
        }
        $mpdCache = app(MPDCache::class);
        $mpdCache->getMPD();
        return $mpdCache->error;
    }



    public function render(): View
    {
        if ($this->mpd == '') {
            $this->mpd = session()->get('mpd') ?? '';
        }
        return view('livewire.select-mpd');
    }
}
