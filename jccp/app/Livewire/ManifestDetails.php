<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\On;
//
use App\Services\ModuleReporter;

class ManifestDetails extends Component
{
    /**
     * @var array<string> $sections;
     **/
    public array $sections;


    public function __construct()
    {
        $this->refresh();
    }

    #[On('mpd-selected')]
    public function refresh(): void
    {
        $this->sections = ['MPD', 'Segments'];
    }

    public function isOpenSection(string $spec): bool
    {
        return $this->sections[0] == $spec;
    }

    public function render(): View
    {
        return view('livewire.manifest-details');
    }
}
